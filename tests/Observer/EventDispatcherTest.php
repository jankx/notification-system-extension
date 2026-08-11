<?php

namespace Jankx\Extensions\NotificationSystem\Tests;

use Jankx\Extensions\NotificationSystem\Observer\EventDispatcher;
use Jankx\Extensions\NotificationSystem\Contracts\NotificationObserver;
use Jankx\Extensions\NotificationSystem\Contracts\NotificationDTO;

class SpyObserver implements NotificationObserver
{
    public array $events = [];

    public function onCreated(NotificationDTO $n): void
    {
        $this->events[] = 'created:' . $n->id;
    }

    public function onRead(NotificationDTO $n): void
    {
        $this->events[] = 'read:' . $n->id;
    }

    public function onDeleted(int $id): void
    {
        $this->events[] = 'deleted:' . $id;
    }
}

class EventDispatcherTest extends NotificationSystemTestCase
{
    private EventDispatcher $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dispatcher = new EventDispatcher();
    }

    public function test_subscribe_and_dispatch(): void
    {
        $spy = new SpyObserver();
        $this->dispatcher->subscribe($spy, ['created', 'read', 'deleted']);

        $dto = new NotificationDTO();
        $dto->id = 42;

        $this->dispatcher->dispatch('created', $dto);
        $this->dispatcher->dispatch('read', $dto);
        $this->dispatcher->dispatch('deleted', 42);

        $this->assertSame(['created:42', 'read:42', 'deleted:42'], $spy->events);
    }

    public function test_unsubscribe(): void
    {
        $spy = new SpyObserver();
        $this->dispatcher->subscribe($spy, ['created']);
        $this->dispatcher->unsubscribe($spy, ['created']);

        $dto = new NotificationDTO();
        $dto->id = 1;
        $this->dispatcher->dispatch('created', $dto);

        $this->assertEmpty($spy->events);
    }

    public function test_has_listeners(): void
    {
        $this->assertFalse($this->dispatcher->hasListeners('created'));

        $spy = new SpyObserver();
        $this->dispatcher->subscribe($spy, ['created']);
        $this->assertTrue($this->dispatcher->hasListeners('created'));
    }

    public function test_get_listeners(): void
    {
        $spy = new SpyObserver();
        $this->dispatcher->subscribe($spy, ['created']);
        $listeners = $this->dispatcher->getListeners('created');

        $this->assertCount(1, $listeners);
        $this->assertSame($spy, $listeners[0]);
    }

    public function test_dispatch_no_listeners_does_not_error(): void
    {
        $dto = new NotificationDTO();
        $dto->id = 1;

        // Should not throw
        $this->dispatcher->dispatch('created', $dto);
        $this->assertTrue(true);
    }

    public function test_subscribe_multiple_observers(): void
    {
        $spy1 = new SpyObserver();
        $spy2 = new SpyObserver();

        $this->dispatcher->subscribe($spy1, ['created']);
        $this->dispatcher->subscribe($spy2, ['created']);

        $dto = new NotificationDTO();
        $dto->id = 7;

        $this->dispatcher->dispatch('created', $dto);

        $this->assertSame(['created:7'], $spy1->events);
        $this->assertSame(['created:7'], $spy2->events);
    }
}
