<?php

declare(strict_types=1);

namespace NotificationChannels\Expo\Gateway;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use NotificationChannels\Expo\Exceptions\CouldNotGetReceipts;
use NotificationChannels\Expo\ExpoError;
use NotificationChannels\Expo\ExpoErrorType;
use NotificationChannels\Expo\ExpoPushToken;
use Psr\Http\Message\ResponseInterface;
use SensitiveParameter;

/** @internal */
final readonly class ExpoGatewayUsingGuzzle implements ExpoGateway
{
    /**
     * Expo's Push API URL.
     */
    private const string SEND_URL = 'https://exp.host/--/api/v2/push/send';

    /**
     * Expo's Push Receipts API URL.
     */
    private const string RECEIPTS_URL = 'https://exp.host/--/api/v2/push/getReceipts';

    /**
     * OK status code.
     */
    private const int HTTP_OK = 200;

    /**
     * 1 KiB in bytes.
     */
    private const int KIBIBYTE = 1024;

    /**
     * The threshold (in KiB) determines whether a payload needs to be compressed.
     */
    private const int THRESHOLD = 1;

    /**
     * The Guzzle HTTP client instance.
     */
    private Client $http;

    /**
     * Create a new ExpoClient instance.
     *
     * @param  HandlerStack<mixed>|null  $handler
     */
    public function __construct(#[SensitiveParameter] ?string $accessToken = null, ?HandlerStack $handler = null)
    {
        $config = [RequestOptions::HEADERS => $this->getDefaultHeaders($accessToken)];

        if ($handler !== null) {
            $config['handler'] = $handler;
        }

        $this->http = new Client($config);
    }

    /**
     * Send the notifications to Expo's Push Service.
     */
    public function sendPushNotifications(ExpoEnvelope $envelope): ExpoResponse
    {
        [$headers, $body] = $this->compressUsingGzip($envelope->toJson());

        $response = $this->http->post(self::SEND_URL, [
            RequestOptions::BODY => $body,
            RequestOptions::HEADERS => $headers,
            RequestOptions::HTTP_ERRORS => false,
        ]);

        if ($response->getStatusCode() !== self::HTTP_OK) {
            return ExpoResponse::fatal((string) $response->getBody());
        }

        $tickets = $this->getPushTickets($response);
        $errors = $this->getPotentialErrors($envelope->recipients, $tickets);

        if (count($errors)) {
            return ExpoResponse::failed($errors);
        }

        return ExpoResponse::ok($this->collectTicketIds($tickets));
    }

    /**
     * Fetch delivery receipts for previously-sent tickets.
     *
     * @param  array<int, string>  $ticketIds
     * @return array<string, array{status: string, message?: string, details?: array<string, mixed>}>
     *
     * @throws CouldNotGetReceipts
     */
    public function getReceipts(array $ticketIds): array
    {
        if ($ticketIds === []) {
            return [];
        }

        $response = $this->http->post(self::RECEIPTS_URL, [
            RequestOptions::JSON => ['ids' => $ticketIds],
            RequestOptions::HTTP_ERRORS => false,
        ]);

        if ($response->getStatusCode() !== self::HTTP_OK) {
            throw CouldNotGetReceipts::becauseTheServiceRespondedWithAnError(
                (string) $response->getBody()
            );
        }

        /** @var array<string, mixed> $body */
        $body = json_decode((string) $response->getBody(), true);

        if (! is_array($body['data'] ?? null)) {
            return [];
        }

        /** @var array<string, array{status: string, message?: string, details?: array<string, mixed>}> */
        return $body['data'];
    }

    /**
     * Compress the given payload if the size is greater than the threshold (1 KiB).
     *
     * @return array{0: array<string, string>, 1: string}
     */
    private function compressUsingGzip(string $payload): array
    {
        if (! extension_loaded('zlib')) {
            return [[], $payload];
        }

        if (mb_strlen($payload) / self::KIBIBYTE <= self::THRESHOLD) {
            return [[], $payload];
        }

        $encoded = gzencode($payload, 6);

        if ($encoded === false) {
            return [[], $payload];
        }

        return [['Content-Encoding' => 'gzip'], $encoded];
    }

    /**
     * Get the default headers to be used by the HTTP client.
     *
     * @return array<string, string>
     */
    private function getDefaultHeaders(#[SensitiveParameter] ?string $accessToken): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Accept-Encoding' => 'gzip, deflate',
            'Content-Type' => 'application/json',
            'Host' => 'exp.host',
        ];

        if (is_string($accessToken)) {
            $headers['Authorization'] = "Bearer {$accessToken}";
        }

        return $headers;
    }

    /**
     * Collect successful push ticket IDs indexed by recipient position.
     *
     * @param  array<int, array<string, mixed>>  $tickets
     * @return array<int, string>
     */
    private function collectTicketIds(array $tickets): array
    {
        $ids = [];

        foreach ($tickets as $idx => $ticket) {
            if (($ticket['status'] ?? null) === 'ok' && is_string($id = $ticket['id'] ?? null)) {
                $ids[$idx] = $id;
            }
        }

        return $ids;
    }

    /**
     * Get an array of potential errors responded by the service.
     *
     * @param  array<int, ExpoPushToken>  $tokens
     * @param  array<int, array<string, mixed>>  $tickets
     * @return array<int, ExpoError>
     */
    private function getPotentialErrors(array $tokens, array $tickets): array
    {
        $errors = [];

        foreach ($tickets as $idx => $ticket) {
            if (Arr::get($ticket, 'status') === 'error') {
                $errors[] = $this->makeError($tokens[$idx], $ticket);
            }
        }

        return $errors;
    }

    /**
     * Get the array of push tickets responded by the service.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getPushTickets(ResponseInterface $response): array
    {
        /** @var array<string, mixed> $body */
        $body = json_decode((string) $response->getBody(), true);

        /** @var array<int, array<string, mixed>> */
        return Arr::get($body, 'data', []);
    }

    /**
     * Create and return an ExpoError object representing a failed delivery.
     *
     * @param  array<string, mixed>  $ticket
     */
    private function makeError(ExpoPushToken $token, array $ticket): ExpoError
    {
        /** @var string $type */
        $type = Arr::get($ticket, 'details.error');
        $type = ExpoErrorType::from($type);

        /** @var string $message */
        $message = Arr::get($ticket, 'message');

        return ExpoError::make($type, $token, $message);
    }
}
