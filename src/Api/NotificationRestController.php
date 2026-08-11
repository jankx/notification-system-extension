<?php

namespace Jankx\Extensions\NotificationSystem\Api;

use Jankx\Extensions\NotificationSystem\NotificationService;

class NotificationRestController
{
    protected string $namespace = 'jankx/v1';

    public function register(): void
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void
    {
        register_rest_route($this->namespace, '/notifications', [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'getNotifications'],
                'permission_callback' => [$this, 'permissionsCheck'],
                'args'                => [
                    'page'     => ['type' => 'integer', 'default' => 1, 'minimum' => 1],
                    'per_page' => ['type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 100],
                ],
            ],
        ]);

        register_rest_route($this->namespace, '/notifications/unread-count', [
            'methods'             => 'GET',
            'callback'            => [$this, 'getUnreadCount'],
            'permission_callback' => [$this, 'permissionsCheck'],
        ]);

        register_rest_route($this->namespace, '/notifications/(?P<id>\d+)/read', [
            'methods'             => 'POST',
            'callback'            => [$this, 'markAsRead'],
            'permission_callback' => [$this, 'permissionsCheck'],
        ]);

        register_rest_route($this->namespace, '/notifications/read-all', [
            'methods'             => 'POST',
            'callback'            => [$this, 'markAllAsRead'],
            'permission_callback' => [$this, 'permissionsCheck'],
        ]);

        register_rest_route($this->namespace, '/notifications/(?P<id>\d+)', [
            'methods'             => 'DELETE',
            'callback'            => [$this, 'deleteNotification'],
            'permission_callback' => [$this, 'permissionsCheck'],
        ]);
    }

    public function permissionsCheck(): bool
    {
        return is_user_logged_in();
    }

    public function getNotifications(\WP_REST_Request $request): \WP_REST_Response
    {
        $userId = get_current_user_id();
        $page = (int) $request->get_param('page');
        $perPage = (int) $request->get_param('per_page');

        $notifications = NotificationService::getUserNotifications($userId, $page, $perPage);
        $unreadCount = NotificationService::unreadCount($userId);

        $data = array_map(fn($n) => $n->toArray(), $notifications);

        $response = new \WP_REST_Response($data);
        $response->header('X-WP-TotalUnread', $unreadCount);

        return $response;
    }

    public function getUnreadCount(\WP_REST_Request $request): \WP_REST_Response
    {
        $count = NotificationService::unreadCount(get_current_user_id());
        return new \WP_REST_Response(['count' => $count]);
    }

    public function markAsRead(\WP_REST_Request $request): \WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $notification = new \Jankx\Extensions\NotificationSystem\Models\Notification($id);

        if (!$notification || $notification->getUserId() !== get_current_user_id()) {
            return new \WP_REST_Response(['message' => 'Not found'], 404);
        }

        $notification->markAsRead();
        return new \WP_REST_Response($notification->toArray());
    }

    public function markAllAsRead(\WP_REST_Request $request): \WP_REST_Response
    {
        NotificationService::markAllRead(get_current_user_id());
        return new \WP_REST_Response(['message' => 'All notifications marked as read']);
    }

    public function deleteNotification(\WP_REST_Request $request): \WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $notification = new \Jankx\Extensions\NotificationSystem\Models\Notification($id);

        if (!$notification || $notification->getUserId() !== get_current_user_id()) {
            return new \WP_REST_Response(['message' => 'Not found'], 404);
        }

        $notification->delete();
        return new \WP_REST_Response(null, 204);
    }
}
