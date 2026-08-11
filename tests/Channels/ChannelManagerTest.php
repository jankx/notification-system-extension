<?php

namespace Jankx\Extensions\NotificationSystem\Tests;

use Jankx\Extensions\NotificationSystem\Channels\ChannelManager;
use Jankx\Extensions\NotificationSystem\Channels\DatabaseChannel;
use Jankx\Extensions\NotificationSystem\Channels\EmailChannel;
use Jankx\Extensions\NotificationSystem\Contracts\NotificationDTO;
use Jankx\Extensions\NotificationSystem\Contracts\NotificationChannel;

class ChannelManagerTest extends NotificationSystemTestCase
{
    public function test_singleton_instance(): void
    {
        $a = ChannelManager::instance();
        $b = ChannelManager::instance();
        $this->assertSame($a, $b);
    }

    public function test_boot_registers_builtin_channels(): void
    {
        $manager = new ChannelManager();
        $manager->boot();

        $this->assertNotNull($manager->get('database'));
        $this->assertNotNull($manager->get('email'));
        $this->assertInstanceOf(DatabaseChannel::class, $manager->get('database'));
        $this->assertInstanceOf(EmailChannel::class, $manager->get('email'));
    }

    public function test_register_custom_channel(): void
    {
        $manager = new ChannelManager();
        $manager->boot();

        $custom = new class implements NotificationChannel {
            public function getId(): string { return 'telegram'; }
            public function getLabel(): string { return 'Telegram'; }
            public function send(NotificationDTO $n): bool { return true; }
            public function sendBatch(array $n): array { return array_fill(0, count($n), true); }
            public function isAvailable(): bool { return true; }
        };

        $manager->register($custom);
        $this->assertSame($custom, $manager->get('telegram'));
    }

    public function test_unregister_channel(): void
    {
        $manager = new ChannelManager();
        $manager->boot();

        $manager->unregister('email');
        $this->assertNull($manager->get('email'));
    }

    public function test_get_available_filters_disabled(): void
    {
        $manager = new ChannelManager();
        $manager->boot();

        $available = $manager->getAvailable();
        $ids = array_keys($available);

        // Database is always available
        $this->assertContains('database', $ids);
    }

    public function test_dispatch_calls_send(): void
    {
        $manager = new ChannelManager();
        $manager->boot();

        $dto = new NotificationDTO();
        $dto->id = 1;
        $dto->userId = 1;
        $dto->type = 'test';
        $dto->title = 'Test';
        $dto->message = 'Hello';

        $results = $manager->dispatch($dto, ['database']);

        $this->assertArrayHasKey('database', $results);
        $this->assertTrue($results['database']);
    }
}
