<?php

namespace Jankx\Extensions\NotificationSystem;

use Jankx\Extensions\AbstractExtension;
use Jankx\Extensions\NotificationSystem\Api\NotificationRestController;
use Jankx\Extensions\NotificationSystem\Channels\ChannelManager;
use Jankx\Extensions\NotificationSystem\Models\NotificationDatabaseInstaller;

class NotificationSystemExtension extends AbstractExtension
{
    protected static ?self $instance = null;

    public static function instance(): ?self
    {
        return self::$instance;
    }

    public function init(): void
    {
        self::$instance = $this;
    }

    public function register_hooks(): void
    {
        // Create DB table
        (new NotificationDatabaseInstaller())->register();

        // Boot channel manager (registers database + email channels + filter)
        add_action('init', [ChannelManager::class, 'instance'], 1);

        // REST API
        if (defined('REST_REQUEST') && REST_REQUEST) {
            (new NotificationRestController())->register();
        }

        // Admin menu
        if (is_admin()) {
            add_action('admin_menu', [$this, 'addAdminMenu']);
        }

        // Frontend: expose unread count via wp_localize_script
        add_action('wp_enqueue_scripts', [$this, 'enqueueFrontendAssets']);
    }

    /**
     * Expose a global JS object with notification data for frontend components.
     */
    public function enqueueFrontendAssets(): void
    {
        if (!is_user_logged_in()) {
            return;
        }

        wp_register_script(
            'jankx-notifications',
            false,
            [],
            '1.0.0',
            true
        );
        wp_enqueue_script('jankx-notifications');

        wp_localize_script('jankx-notifications', 'jankxNotifications', [
            'restUrl'    => rest_url('jankx/v1/notifications'),
            'nonce'      => wp_create_nonce('wp_rest'),
            'unreadCount' => NotificationService::unreadCount(get_current_user_id()),
        ]);
    }

    public function addAdminMenu(): void
    {
        add_submenu_page(
            'edit.php',
            __('Notifications', 'jankx'),
            __('Notifications', 'jankx'),
            'edit_posts',
            'jankx-notifications',
            [$this, 'renderAdminPage']
        );
    }

    public function renderAdminPage(): void
    {
        $notifications = NotificationService::getUserNotifications(get_current_user_id(), 1, 50);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Notifications', 'jankx'); ?></h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Type', 'jankx'); ?></th>
                        <th><?php esc_html_e('Title', 'jankx'); ?></th>
                        <th><?php esc_html_e('Message', 'jankx'); ?></th>
                        <th><?php esc_html_e('Date', 'jankx'); ?></th>
                        <th><?php esc_html_e('Status', 'jankx'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($notifications)): ?>
                        <tr><td colspan="5"><?php esc_html_e('No notifications.', 'jankx'); ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($notifications as $n): ?>
                            <tr style="<?php echo $n->isRead() ? '' : 'font-weight:600;'; ?>">
                                <td><?php echo esc_html($n->getType()); ?></td>
                                <td><?php echo esc_html($n->getTitle()); ?></td>
                                <td><?php echo esc_html(wp_trim_words($n->getMessage(), 20)); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format') . ' H:i', strtotime($n->getDateCreated()))); ?></td>
                                <td><?php echo $n->isRead()
                                    ? '<span style="color:#6b7280;">' . __('Read', 'jankx') . '</span>'
                                    : '<span style="color:#10b981;">' . __('Unread', 'jankx') . '</span>';
                                ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
