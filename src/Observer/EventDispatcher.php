<?php

namespace Jankx\Extensions\NotificationSystem\Observer;

use Jankx\Extensions\NotificationSystem\Contracts\NotificationObserver;
use Jankx\Extensions\NotificationSystem\Contracts\NotificationDTO;

/**
 * Event dispatcher — implements the Observer pattern.
 *
 * Observers (channels) register themselves and are notified
 * when notification lifecycle events occur.
 *
 * Usage:
 *     $dispatcher = new EventDispatcher();
 *     $dispatcher->subscribe('created', $emailChannel);
 *     $dispatcher->dispatch('created', $dto);
 */
class EventDispatcher
{
    /** @var array<string, NotificationObserver[]> */
    protected array $listeners = [];

    /**
     * Subscribe an observer to one or more events.
     *
     * @param string|string[] $events
     */
    public function subscribe(NotificationObserver $observer, $events = ['created']): void
    {
        $events = (array) $events;
        foreach ($events as $event) {
            $this->listeners[$event][] = $observer;
        }
    }

    /**
     * Unsubscribe an observer from one or more events.
     *
     * @param string|string[] $events
     */
    public function unsubscribe(NotificationObserver $observer, $events = ['created']): void
    {
        $events = (array) $events;
        foreach ($events as $event) {
            $this->listeners[$event] = array_filter(
                $this->listeners[$event] ?? [],
                fn($o) => $o !== $observer
            );
        }
    }

    /**
     * Dispatch an event to all registered observers.
     */
    public function dispatch(string $event, mixed $data = null): void
    {
        $observers = $this->listeners[$event] ?? [];

        foreach ($observers as $observer) {
            match ($event) {
                'created' => $observer->onCreated($data),
                'read'    => $observer->onRead($data),
                'deleted' => $observer->onDeleted($data),
                default   => null,
            };
        }
    }

    /**
     * Get all listeners for a given event.
     *
     * @return NotificationObserver[]
     */
    public function getListeners(string $event): array
    {
        return $this->listeners[$event] ?? [];
    }

    /**
     * Check if any listeners are registered for an event.
     */
    public function hasListeners(string $event): bool
    {
        return !empty($this->listeners[$event]);
    }
}
