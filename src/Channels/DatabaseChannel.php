<?php

namespace Jankx\Extensions\NotificationSystem\Channels;

use Jankx\Extensions\NotificationSystem\Contracts\NotificationChannel;
use Jankx\Extensions\NotificationSystem\Contracts\NotificationDTO;
use Jankx\Extensions\NotificationSystem\Contracts\NotificationObserver;

/**
 * Database channel — the master notification store.
 *
 * Implements both NotificationChannel (Strategy) and NotificationObserver (Observer).
 * As the master store, it's always the first channel notified.
 */
class DatabaseChannel implements NotificationChannel, NotificationObserver
{
    public function getId(): string
    {
        return 'database';
    }

    public function getLabel(): string
    {
        return __('Database', 'jankx');
    }

    public function isAvailable(): bool
    {
        return true;
    }

    /**
     * Strategy: send — no-op because the row is already persisted by the factory.
     */
    public function send(NotificationDTO $notification): bool
    {
        return true;
    }

    public function sendBatch(array $notifications): array
    {
        return array_map(fn() => true, $notifications);
    }

    // ── Observer callbacks ────────────────────────────────

    public function onCreated(NotificationDTO $notification): void
    {
        // Master data already persisted — nothing to do.
    }

    public function onRead(NotificationDTO $notification): void
    {
        // Could update a denormalized counter, analytics, etc.
    }

    public function onDeleted(int $notificationId): void
    {
        // Cleanup if needed.
    }
}
