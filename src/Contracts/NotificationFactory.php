<?php

namespace Jankx\Extensions\NotificationSystem\Contracts;

/**
 * Factory interface — creates Notification instances from raw data.
 *
 * Implements the Factory Method pattern so the creation logic
 * can be swapped or extended by other extensions.
 */
interface NotificationFactory
{
    /**
     * Create a notification from scratch.
     */
    public function create(
        int $userId,
        string $type,
        string $title,
        string $message = '',
        array $data = []
    ): ?NotificationDTO;

    /**
     * Create a notification from an existing database row.
     */
    public function fromRow(array $row): NotificationDTO;
}
