<?php

namespace Jankx\Extensions\NotificationSystem\Models;

class NotificationDatabaseInstaller
{
    public function register(): void
    {
        add_action('init', [$this, 'maybeCreateTable']);
    }

    public function maybeCreateTable(): void
    {
        global $wpdb;

        $table = $wpdb->prefix . 'jankx_notifications';

        if ($this->tableExists($table)) {
            if (get_option('jankx_notification_db_version') === false) {
                update_option('jankx_notification_db_version', '1.0.0');
            }
            return;
        }

        $this->createTable($table);
        update_option('jankx_notification_db_version', '1.0.0');
    }

    protected function tableExists(string $table): bool
    {
        global $wpdb;

        $result = $wpdb->get_var(
            $wpdb->prepare('SHOW TABLES LIKE %s', $table)
        );

        return $result === $table;
    }

    protected function createTable(string $table): void
    {
        global $wpdb;

        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            type varchar(50) NOT NULL DEFAULT 'system',
            title varchar(255) NOT NULL DEFAULT '',
            message text NOT NULL,
            data longtext NOT NULL,
            is_read tinyint(1) NOT NULL DEFAULT 0,
            read_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY type (type),
            KEY is_read (is_read),
            KEY created_at (created_at),
            KEY user_read (user_id, is_read)
        ) {$charsetCollate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
