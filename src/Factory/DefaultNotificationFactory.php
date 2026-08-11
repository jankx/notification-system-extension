<?php

namespace Jankx\Extensions\NotificationSystem\Factory;

use Jankx\Extensions\NotificationSystem\Contracts\NotificationDTO;
use Jankx\Extensions\NotificationSystem\Contracts\NotificationFactory;
use Jankx\Extensions\NotificationSystem\Models\NotificationModel;

/**
 * Default notification factory.
 *
 * Creates NotificationDTO from raw parameters or database rows.
 */
class DefaultNotificationFactory implements NotificationFactory
{
    public function create(
        int $userId,
        string $type,
        string $title,
        string $message = '',
        array $data = []
    ): ?NotificationDTO {
        $id = NotificationModel::create([
            'user_id' => $userId,
            'type'    => $type,
            'title'   => $title,
            'message' => $message,
            'data'    => $data,
        ]);

        if ($id <= 0) {
            return null;
        }

        $row = NotificationModel::findById($id);
        if (!$row) {
            return null;
        }

        return NotificationDTO::fromRow($row);
    }

    public function fromRow(array $row): NotificationDTO
    {
        return NotificationDTO::fromRow($row);
    }
}
