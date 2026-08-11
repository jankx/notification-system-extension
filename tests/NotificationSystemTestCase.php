<?php

namespace Jankx\Extensions\NotificationSystem\Tests;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;

class NotificationSystemTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        stub_wp_notification_functions();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }
}
