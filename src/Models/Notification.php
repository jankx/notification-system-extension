<?php

namespace Jankx\Extensions\NotificationSystem\Models;

use Jankx\Extensions\NotificationSystem\Contracts\NotificationDTO;

/**
 * High-level Notification model.
 *
 * Wraps NotificationModel (raw DB) and provides domain methods +
 * a DTO bridge so channels stay decoupled from WP.
 */
class Notification
{
    protected ?array $data = null;
    protected int $id;

    public function __construct(int $id = 0)
    {
        $this->id = $id;
        if ($id > 0) {
            $this->data = NotificationModel::findById($id);
        }
    }

    public static function create(array $data): ?self
    {
        $id = NotificationModel::create($data);
        return $id > 0 ? new self($id) : null;
    }

    public static function query(array $args = []): array
    {
        $rows = NotificationModel::query($args);
        return array_map(fn(array $row) => new self($row['id']), $rows);
    }

    public static function countUnread(int $userId): int
    {
        return NotificationModel::countUnread($userId);
    }

    // ── Getters ───────────────────────────────────────────

    public function getId(): int
    {
        return $this->data['id'] ?? $this->id;
    }

    public function getUserId(): int
    {
        return (int) ($this->data['user_id'] ?? 0);
    }

    public function getType(): string
    {
        return $this->data['type'] ?? '';
    }

    public function getTitle(): string
    {
        return $this->data['title'] ?? '';
    }

    public function getMessage(): string
    {
        return $this->data['message'] ?? '';
    }

    public function getData(): array
    {
        return $this->data['data'] ?? [];
    }

    public function isRead(): bool
    {
        return !empty($this->data['is_read']);
    }

    public function getReadAt(): ?string
    {
        return $this->data['read_at'] ?? null;
    }

    public function getDateCreated(): string
    {
        return $this->data['created_at'] ?? '';
    }

    // ── Actions ───────────────────────────────────────────

    public function markAsRead(): bool
    {
        $result = NotificationModel::markAsRead($this->getId());
        if ($result) {
            $this->data['is_read'] = 1;
            $this->data['read_at'] = current_time('mysql');
        }
        return $result;
    }

    public static function markAllAsRead(int $userId): bool
    {
        return NotificationModel::markAllAsRead($userId);
    }

    public function delete(): bool
    {
        return NotificationModel::delete($this->getId());
    }

    // ── DTO bridge ────────────────────────────────────────

    public function toDTO(): NotificationDTO
    {
        return NotificationDTO::fromRow($this->data ?? ['id' => $this->id]);
    }

    public function toArray(): array
    {
        if (!$this->data) {
            return [];
        }

        return [
            'id'         => $this->getId(),
            'user_id'    => $this->getUserId(),
            'type'       => $this->getType(),
            'title'      => $this->getTitle(),
            'message'    => $this->getMessage(),
            'data'       => $this->getData(),
            'is_read'    => $this->isRead(),
            'read_at'    => $this->getReadAt(),
            'created_at' => $this->getDateCreated(),
        ];
    }
}
