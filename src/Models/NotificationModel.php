<?php

namespace Jankx\Extensions\NotificationSystem\Models;

class NotificationModel
{
    protected static ?string $table = null;

    public static function table(): string
    {
        if (self::$table === null) {
            global $wpdb;
            self::$table = $wpdb->prefix . 'jankx_notifications';
        }
        return self::$table;
    }

    // ── CREATE ────────────────────────────────────────────

    public static function create(array $data): int
    {
        global $wpdb;

        $now = current_time('mysql');
        $defaults = [
            'user_id'    => 0,
            'type'       => 'system',
            'title'      => '',
            'message'    => '',
            'data'       => [],
            'is_read'    => 0,
            'read_at'    => null,
            'created_at' => $now,
        ];

        $data = array_merge($defaults, $data);

        if (is_array($data['data'])) {
            $data['data'] = wp_json_encode($data['data']);
        }

        $wpdb->insert(self::table(), $data);
        return (int) $wpdb->insert_id;
    }

    // ── READ ──────────────────────────────────────────────

    public static function findById(int $id): ?array
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM " . self::table() . " WHERE id = %d", $id),
            ARRAY_A
        );

        return $row ? self::decode($row) : null;
    }

    public static function query(array $args = []): array
    {
        global $wpdb;

        $table = self::table();
        $where = ['1=1'];
        $values = [];
        $orderBy = 'created_at DESC';
        $limit = '';

        if (!empty($args['user_id'])) {
            $where[] = 'user_id = %d';
            $values[] = (int) $args['user_id'];
        }

        if (!empty($args['type'])) {
            $where[] = 'type = %s';
            $values[] = $args['type'];
        }

        if (isset($args['is_read'])) {
            $where[] = 'is_read = %d';
            $values[] = (int) $args['is_read'];
        }

        if (!empty($args['date_from'])) {
            $where[] = 'created_at >= %s';
            $values[] = $args['date_from'];
        }

        if (!empty($args['date_to'])) {
            $where[] = 'created_at <= %s';
            $values[] = $args['date_to'];
        }

        if (!empty($args['orderby'])) {
            $allowed = ['id', 'type', 'created_at', 'is_read'];
            $col = in_array($args['orderby'], $allowed, true) ? $args['orderby'] : 'created_at';
            $dir = strtoupper($args['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
            $orderBy = "{$col} {$dir}";
        }

        if (!empty($args['per_page'])) {
            $perPage = (int) $args['per_page'];
            $offset = !empty($args['page']) ? ((int) $args['page'] - 1) * $perPage : 0;
            $limit = "LIMIT {$perPage} OFFSET {$offset}";
        }

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT * FROM {$table} WHERE {$whereClause} ORDER BY {$orderBy} {$limit}";

        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, ...$values);
        }

        $rows = $wpdb->get_results($sql, ARRAY_A);

        return array_map([self::class, 'decode'], $rows);
    }

    public static function count(array $args = []): int
    {
        global $wpdb;

        $table = self::table();
        $where = ['1=1'];
        $values = [];

        if (!empty($args['user_id'])) {
            $where[] = 'user_id = %d';
            $values[] = (int) $args['user_id'];
        }

        if (!empty($args['type'])) {
            $where[] = 'type = %s';
            $values[] = $args['type'];
        }

        if (isset($args['is_read'])) {
            $where[] = 'is_read = %d';
            $values[] = (int) $args['is_read'];
        }

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$whereClause}";

        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, ...$values);
        }

        return (int) $wpdb->get_var($sql);
    }

    public static function countUnread(int $userId): int
    {
        return self::count(['user_id' => $userId, 'is_read' => 0]);
    }

    // ── UPDATE ────────────────────────────────────────────

    public static function markAsRead(int $id): bool
    {
        global $wpdb;

        $result = $wpdb->update(
            self::table(),
            ['is_read' => 1, 'read_at' => current_time('mysql')],
            ['id' => $id],
            ['%d', '%s'],
            ['%d']
        );

        return $result !== false;
    }

    public static function markAllAsRead(int $userId): bool
    {
        global $wpdb;

        $result = $wpdb->update(
            self::table(),
            ['is_read' => 1, 'read_at' => current_time('mysql')],
            ['user_id' => $userId, 'is_read' => 0],
            ['%d', '%s'],
            ['%d', '%d']
        );

        return $result !== false;
    }

    // ── DELETE ────────────────────────────────────────────

    public static function delete(int $id): bool
    {
        global $wpdb;

        $result = $wpdb->delete(self::table(), ['id' => $id], ['%d']);
        return $result !== false;
    }

    // ── HELPERS ───────────────────────────────────────────

    protected static function decode(array $row): array
    {
        if (isset($row['data']) && is_string($row['data'])) {
            $decoded = json_decode($row['data'], true);
            $row['data'] = is_array($decoded) ? $decoded : [];
        }
        return $row;
    }
}
