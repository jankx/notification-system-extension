<?php

namespace Jankx\Extensions\NotificationSystem\Channels;

use Jankx\Extensions\NotificationSystem\Contracts\NotificationChannel;
use Jankx\Extensions\NotificationSystem\Contracts\NotificationDTO;
use Jankx\Extensions\NotificationSystem\Contracts\NotificationObserver;
use Jankx\Extensions\NotificationSystem\Observer\EventDispatcher;

/**
 * Channel manager — Registry + Dispatcher.
 *
 * Design patterns:
 *   - **Registry** — channels are stored by ID, looked up by ID.
 *   - **Observer** — channels that implement NotificationObserver
 *     are automatically subscribed to the EventDispatcher.
 *   - **Strategy** — each channel is a swappable strategy for
 *     delivering notifications.
 *   - **Singleton** — one global instance via instance().
 */
class ChannelManager
{
    protected static ?self $instance = null;

    /** @var NotificationChannel[] id => channel */
    protected array $channels = [];

    protected EventDispatcher $dispatcher;
    protected bool $booted = false;

    public function __construct()
    {
        $this->dispatcher = new EventDispatcher();
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Boot: register built-in channels + allow extensions to register theirs.
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }
        $this->booted = true;

        // Register built-in channels (Strategy pattern)
        $this->register(new DatabaseChannel());
        $this->register(new EmailChannel());

        /**
         * Filter: register additional notification channels.
         *
         * Other extensions hook in here:
         *
         *   add_filter('jankx/notification/channels', function ($channels) {
         *       $channels['telegram'] = new TelegramChannel();
         *       return $channels;
         *   });
         *
         * @param NotificationChannel[] $channels
         */
        $registered = apply_filters('jankx/notification/channels', $this->channels);
        if (is_array($registered)) {
            foreach ($registered as $channel) {
                if ($channel instanceof NotificationChannel) {
                    $this->channels[$channel->getId()] = $channel;
                }
            }
        }

        // Auto-subscribe observer channels to lifecycle events
        $this->subscribeObservers();
    }

    // ── Registry ──────────────────────────────────────────

    public function register(NotificationChannel $channel): void
    {
        $this->channels[$channel->getId()] = $channel;
    }

    public function unregister(string $id): void
    {
        unset($this->channels[$id]);
    }

    public function get(string $id): ?NotificationChannel
    {
        return $this->channels[$id] ?? null;
    }

    /** @return NotificationChannel[] */
    public function all(): array
    {
        return $this->channels;
    }

    /** @return NotificationChannel[] */
    public function getAvailable(): array
    {
        return array_filter($this->channels, fn(NotificationChannel $ch) => $ch->isAvailable());
    }

    // ── Observer wiring ───────────────────────────────────

    protected function subscribeObservers(): void
    {
        foreach ($this->channels as $channel) {
            if ($channel instanceof NotificationObserver) {
                $this->dispatcher->subscribe($channel, ['created', 'read', 'deleted']);
            }
        }
    }

    // ── Dispatch (Strategy + Observer) ────────────────────

    /**
     * Dispatch via Strategy (explicit send) + notify observers.
     */
    public function dispatch(NotificationDTO $notification, ?array $channelIds = null): array
    {
        $targets = $channelIds
            ? array_intersect_key($this->channels, array_flip($channelIds))
            : $this->getAvailable();

        $results = [];
        foreach ($targets as $id => $channel) {
            /** Allow extensions to skip a channel per-notification */
            $skip = apply_filters('jankx/notification/skip_channel', false, $channel, $notification);
            if ($skip) {
                continue;
            }

            $results[$id] = $channel->send($notification);
        }

        // Also fire the Observer event so observer-only channels react
        $this->dispatcher->dispatch('created', $notification);

        return $results;
    }

    /**
     * Dispatch via Observer only (no Strategy send).
     * Useful when the factory already persisted the row
     * and you just want to notify observers.
     */
    public function notify(string $event, mixed $data): void
    {
        $this->dispatcher->dispatch($event, $data);
    }

    // ── Accessors ─────────────────────────────────────────

    public function getDispatcher(): EventDispatcher
    {
        return $this->dispatcher;
    }
}
