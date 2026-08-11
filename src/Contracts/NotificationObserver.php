<?php

namespace Jankx\Extensions\NotificationSystem\Contracts;

/**
 * Observer interface — channels subscribe to notification events.
 *
 * Implements the Observer pattern so channels can react to
 * notification lifecycle events without tight coupling.
 */
interface NotificationObserver
{
    /**
     * Called when a notification has been created and persisted.
     */
    public function onCreated(NotificationDTO $notification): void;

    /**
     * Called when a notification has been read.
     */
    public function onRead(NotificationDTO $notification): void;

    /**
     * Called when a notification has been deleted.
     */
    public function onDeleted(int $notificationId): void;
}
