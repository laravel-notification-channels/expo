<?php declare(strict_types=1);

namespace NotificationChannels\Expo\Gateway;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
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
    private const string BASE_URL = 'https://exp.host/--/api/v2/push/send';

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
     */
    public function __construct(#[SensitiveParameter] ?string $accessToken = null)
    {
        $this->http = new Client([RequestOptions::HEADERS => $this->getDefaultHeaders($accessToken)]);
    }

    /**
     * Send the notifications to Expo's Push Service.
     */
    public function sendPushNotifications(ExpoEnvelope $envelope): ExpoResponse
    {
        [$headers, $body] = $this->compressUsingGzip($envelope->toJson());

        $response = $this->http->post(self::BASE_URL, [
            RequestOptions::BODY => $body,
            RequestOptions::HEADERS => $headers,
            RequestOptions::HTTP_ERRORS => false,
        ]);

        if ($response->getStatusCode() !== self::HTTP_OK) {
            return ExpoResponse::fatal((string) $response->getBody());
        }

        $tickets = $this->getPushTickets($response);
        $errors = $this->getPotentialErrors($envelope->recipients, $tickets);

        return count($errors) ? ExpoResponse::failed($errors) : ExpoResponse::ok();
    }

    /**
     * Compress the given payload if the size is greater than the threshold (1 KiB).
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
     * Get an array of potential errors responded by the service.
     *
     * @param $tokens array<int, ExpoPushToken>
     *
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
     */
    private function getPushTickets(ResponseInterface $response): array
    {
        /** @var array $body */
        $body = json_decode((string) $response->getBody(), true);

        return Arr::get($body, 'data', []);
    }

    /**
     * Create and return an ExpoError object representing a failed delivery.
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
