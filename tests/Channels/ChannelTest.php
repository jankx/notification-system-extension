<?php

namespace Jankx\Extensions\NotificationSystem\Tests;

use Jankx\Extensions\NotificationSystem\Channels\DatabaseChannel;
use Jankx\Extensions\NotificationSystem\Channels\EmailChannel;
use Jankx\Extensions\NotificationSystem\Contracts\NotificationDTO;

class DatabaseChannelTest extends NotificationSystemTestCase
{
    private DatabaseChannel $channel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->channel = new DatabaseChannel();
    }

    public function test_id(): void
    {
        $this->assertSame('database', $this->channel->getId());
    }

    public function test_label(): void
    {
        $this->assertSame('Database', $this->channel->getLabel());
    }

    public function test_is_always_available(): void
    {
        $this->assertTrue($this->channel->isAvailable());
    }

    public function test_send_returns_true(): void
    {
        $dto = new NotificationDTO();
        $dto->id = 1;
        $this->assertTrue($this->channel->send($dto));
    }
}

class EmailChannelTest extends NotificationSystemTestCase
{
    private EmailChannel $channel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->channel = new EmailChannel();
    }

    public function test_id(): void
    {
        $this->assertSame('email', $this->channel->getId());
    }

    public function test_label(): void
    {
        $this->assertSame('Email', $this->channel->getLabel());
    }

    public function test_is_available_when_enabled(): void
    {
        $GLOBALS['__wp_options']['jankx_notify_email_enabled'] = true;
        $this->assertTrue($this->channel->isAvailable());
    }

    public function test_is_not_available_when_disabled(): void
    {
        $GLOBALS['__wp_options']['jankx_notify_email_enabled'] = false;
        $this->assertFalse($this->channel->isAvailable());
    }
}
