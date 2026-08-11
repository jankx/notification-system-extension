<?php

namespace Jankx\Extensions\NotificationSystem\Contracts;

/**
 * Plain data-transfer object that travels through channels.
 * Decoupled from the database model so channels never depend on WP internals.
 */
class NotificationDTO
{
    public int $id = 0;
    public int $userId = 0;
    public string $type = '';
    public string $title = '';
    public string $message = '';
    public array $data = [];
    public string $createdAt = '';

    public static function fromRow(array $row): self
    {
        $dto = new self();
        $dto->id = (int) ($row['id'] ?? 0);
        $dto->userId = (int) ($row['user_id'] ?? 0);
        $dto->type = $row['type'] ?? '';
        $dto->title = $row['title'] ?? '';
        $dto->message = $row['message'] ?? '';
        $dto->data = is_array($row['data'] ?? null) ? $row['data'] : [];
        $dto->createdAt = $row['created_at'] ?? '';

        return $dto;
    }

    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'user_id'    => $this->userId,
            'type'       => $this->type,
            'title'      => $this->title,
            'message'    => $this->message,
            'data'       => $this->data,
            'created_at' => $this->createdAt,
        ];
    }
}
