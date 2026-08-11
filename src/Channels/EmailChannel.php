<?php

namespace Jankx\Extensions\NotificationSystem\Channels;

use Jankx\Extensions\NotificationSystem\Contracts\NotificationChannel;
use Jankx\Extensions\NotificationSystem\Contracts\NotificationDTO;
use Jankx\Extensions\NotificationSystem\Contracts\NotificationObserver;

/**
 * Email channel — mirrors database notifications to the user's inbox.
 *
 * Implements NotificationObserver so it gets notified automatically
 * when new notifications are created (no explicit dispatch needed).
 */
class EmailChannel implements NotificationChannel, NotificationObserver
{
    protected string $templateDir;

    public function __construct(string $templateDir = '')
    {
        $this->templateDir = $templateDir ?: dirname(__DIR__, 2) . '/templates';
    }

    public function getId(): string
    {
        return 'email';
    }

    public function getLabel(): string
    {
        return __('Email', 'jankx');
    }

    public function isAvailable(): bool
    {
        return (bool) apply_filters('jankx/notification/email/enabled', get_option('jankx_notify_email_enabled', true));
    }

    // ── Strategy: send ────────────────────────────────────

    public function send(NotificationDTO $notification): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        $user = get_userdata($notification->userId);
        if (!$user || empty($user->user_email)) {
            return false;
        }

        $to = $user->user_email;
        $subject = $this->buildSubject($notification);
        $body = $this->buildBody($notification);
        $headers = ['Content-Type: text/html; charset=UTF-8'];

        /** @var array $headers */
        $headers = apply_filters('jankx/notification/email/headers', $headers, $notification);

        return wp_mail($to, $subject, $body, $headers);
    }

    public function sendBatch(array $notifications): array
    {
        return array_map(fn(NotificationDTO $n) => $this->send($n), $notifications);
    }

    // ── Observer callbacks ────────────────────────────────

    public function onCreated(NotificationDTO $notification): void
    {
        $this->send($notification);
    }

    public function onRead(NotificationDTO $notification): void
    {
        // Optional: send a "notification read" email digest
    }

    public function onDeleted(int $notificationId): void
    {
        // No-op for email
    }

    // ── Template rendering ────────────────────────────────

    protected function buildSubject(NotificationDTO $notification): string
    {
        $siteName = get_bloginfo('name');
        $default = sprintf('[%s] %s', $siteName, $notification->title);

        return apply_filters('jankx/notification/email/subject', $default, $notification);
    }

    protected function buildBody(NotificationDTO $notification): string
    {
        $vars = [
            'notification' => $notification,
            'user'         => get_userdata($notification->userId),
            'site_name'    => get_bloginfo('name'),
            'site_url'     => home_url('/'),
        ];

        $vars = apply_filters('jankx/notification/email/template_vars', $vars, $notification);

        ob_start();
        extract($vars, EXTR_SKIP);
        include $this->getTemplatePath();
        return ob_get_clean();
    }

    protected function getTemplatePath(): string
    {
        return apply_filters('jankx/notification/email/template', $this->templateDir . '/email-notification.php');
    }
}
