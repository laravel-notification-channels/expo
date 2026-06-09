# Changelog

All notable changes to `expo` will be documented in this file

## 2.3.0 - 2026-06-09

Backwards-compatible release adding deferred-receipt fetching and ticket-ID surfacing so consumers can persist ticket IDs and poll `/push/getReceipts` later.

- `ExpoResponse::ok()` now optionally carries the array of ticket IDs accepted by Expo; `ExpoResponse::tickets()` returns them (empty array on `failed`/`fatal`).
- `ExpoGateway` interface gains `getReceipts(array $ticketIds): array`. `ExpoGatewayUsingGuzzle` implements it against `https://exp.host/--/api/v2/push/getReceipts`.
- New `NotificationChannels\Expo\Exceptions\CouldNotGetReceipts` exception thrown on non-200 responses from the receipts endpoint. Separate from `CouldNotSendNotification` so consumers can distinguish "send failed" from "receipt poll failed."
- New `NotificationChannels\Expo\Events\ExpoNotificationSent` event dispatched on the success path, carrying `(notifiable, notification, tickets)` where `tickets` is a `token-string → ticket-UUID` map. App-specific metadata travels on the notification instance — consumers read it via `$event->notification->yourField` in their listeners.

No breaking changes — existing callers that don't listen for `ExpoNotificationSent` or call `getReceipts()` see identical behaviour.

## 2.0.0 - 2024-03-18

- Channel revamp

## 1.0.0 - 2021-05-29

- Initial release
