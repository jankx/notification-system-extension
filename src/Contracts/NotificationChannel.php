<?php

namespace Jankx\Extensions\NotificationSystem\Contracts;

/**
 * Notification channel interface.
 *
 * Every delivery channel (database, email, telegram, slack …) implements
 * this contract so the ChannelManager can dispatch notifications uniformly.
 */
interface NotificationChannel
{
    /**
     * Unique identifier for this channel (e.g. 'database', 'email', 'telegram').
     */
    public function getId(): string;

    /**
     * Human-readable label shown in admin settings.
     */
    public function getLabel(): string;

    /**
     * Send a single notification through this channel.
     *
     * @param NotificationDTO $notification
     * @return bool true on success
     */
    public function send(NotificationDTO $notification): bool;

    /**
     * Send a batch of notifications.
     *
     * @param NotificationDTO[] $notifications
     * @return array<int, bool> per-notification success flags
     */
    public function sendBatch(array $notifications): array;

    /**
     * Whether this channel is enabled / available right now.
     */
    public function isAvailable(): bool;
}
