<?php

namespace Jankx\Extensions\NotificationSystem;

use Jankx\Extensions\NotificationSystem\Channels\ChannelManager;
use Jankx\Extensions\NotificationSystem\Contracts\NotificationDTO;
use Jankx\Extensions\NotificationSystem\Contracts\NotificationFactory;
use Jankx\Extensions\NotificationSystem\Factory\DefaultNotificationFactory;
use Jankx\Extensions\NotificationSystem\Models\Notification;
use Jankx\Extensions\NotificationSystem\Models\NotificationModel;

/**
 * NotificationService — Mediator + Facade.
 *
 * Design patterns:
 *   - **Mediator** — coordinates between Factory, ChannelManager,
 *     and observers without any of them knowing about each other.
 *   - **Facade** — single entry point for all notification operations.
 *   - **Factory Method** — delegates creation to the registered factory.
 *
 * Usage:
 *
 *     // Simple send (uses default factory)
 *     NotificationService::send($userId, 'order.completed', 'Order shipped', '...');
 *
 *     // Register a custom factory
 *     NotificationService::setFactory(new MyFactory());
 *
 *     // Register additional channels
 *     add_filter('jankx/notification/channels', function ($channels) {
 *         $channels['telegram'] = new TelegramChannel();
 *         return $channels;
 *     });
 */
class NotificationService
{
    protected static ?NotificationFactory $factory = null;

    // ── Factory injection ─────────────────────────────────

    public static function setFactory(NotificationFactory $factory): void
    {
        self::$factory = $factory;
    }

    public static function getFactory(): NotificationFactory
    {
        if (self::$factory === null) {
            self::$factory = new DefaultNotificationFactory();
        }
        return self::$factory;
    }

    // ── Core operations (Mediator) ────────────────────────

    /**
     * Send a notification:
     *   1. Factory creates the DTO (and persists to master DB)
     *   2. ChannelManager dispatches to channels + notifies observers
     *   3. Action hook fires for any other listeners
     */
    public static function send(
        int $userId,
        string $type,
        string $title,
        string $message = '',
        array $data = [],
        ?array $channelIds = null
    ): ?Notification {
        // Step 1: Factory creates + persists
        $dto = self::getFactory()->create($userId, $type, $title, $message, $data);
        if (!$dto) {
            return null;
        }

        // Step 2: Dispatch via channel manager
        ChannelManager::instance()->dispatch($dto, $channelIds);

        /**
         * Action: fired after a notification is created and dispatched.
         *
         * @param NotificationDTO $dto
         */
        do_action('jankx/notification/sent', $dto);

        return new Notification($dto->id);
    }

    /**
     * Send to multiple users.
     */
    public static function sendToUsers(
        array $userIds,
        string $type,
        string $title,
        string $message = '',
        array $data = [],
        ?array $channelIds = null
    ): array {
        return array_map(
            fn(int $userId) => self::send($userId, $type, $title, $message, $data, $channelIds),
            array_map('intval', $userIds)
        );
    }

    /**
     * Broadcast to all active users.
     */
    public static function broadcast(
        string $type,
        string $title,
        string $message = '',
        array $data = [],
        ?array $channelIds = null
    ): array {
        global $wpdb;

        $userIds = $wpdb->get_col(
            "SELECT ID FROM {$wpdb->users} WHERE user_status = 0 ORDER BY ID"
        );

        return self::sendToUsers(array_map('intval', $userIds), $type, $title, $message, $data, $channelIds);
    }

    // ── Read operations ───────────────────────────────────

    public static function getUserNotifications(int $userId, int $page = 1, int $perPage = 20): array
    {
        return Notification::query([
            'user_id'  => $userId,
            'per_page' => $perPage,
            'page'     => $page,
        ]);
    }

    public static function unreadCount(int $userId): int
    {
        return Notification::countUnread($userId);
    }

    public static function markRead(int $notificationId): bool
    {
        $notification = new Notification($notificationId);
        $result = $notification->markAsRead();

        if ($result) {
            // Notify observers that a notification was read
            ChannelManager::instance()->notify('read', $notification->toDTO());
        }

        return $result;
    }

    public static function markAllRead(int $userId): bool
    {
        return Notification::markAllAsRead($userId);
    }

    public static function delete(int $notificationId): bool
    {
        $notification = new Notification($notificationId);
        $result = $notification->delete();

        if ($result) {
            ChannelManager::instance()->notify('deleted', $notificationId);
        }

        return $result;
    }
}
