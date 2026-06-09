<?php

declare(strict_types=1);

namespace NotificationChannels\Expo\Gateway;

/** @internal */
interface ExpoGateway
{
    /**
     * Send the notifications to Expo's Push Service.
     */
    public function sendPushNotifications(ExpoEnvelope $envelope): ExpoResponse;

    /**
     * Fetch delivery receipts for previously-sent tickets.
     *
     * @param  array<int, string>  $ticketIds  Up to 1000 ticket UUIDs per call (Expo's documented ceiling)
     * @return array<string, array{status: string, message?: string, details?: array<string, mixed>}>
     *                                                                                                keyed by ticket UUID
     */
    public function getReceipts(array $ticketIds): array;
}
