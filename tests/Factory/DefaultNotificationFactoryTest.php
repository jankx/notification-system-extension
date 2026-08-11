<?php

namespace Jankx\Extensions\NotificationSystem\Tests;

use Jankx\Extensions\NotificationSystem\Factory\DefaultNotificationFactory;
use Jankx\Extensions\NotificationSystem\Contracts\NotificationDTO;

class DefaultNotificationFactoryTest extends NotificationSystemTestCase
{
    private DefaultNotificationFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new DefaultNotificationFactory();
    }

    public function test_from_row(): void
    {
        $row = [
            'id'         => 10,
            'user_id'    => 5,
            'type'       => 'order.completed',
            'title'      => 'Order shipped',
            'message'    => 'Your order has been shipped!',
            'data'       => ['order_id' => 123],
            'created_at' => '2026-08-11 12:00:00',
        ];

        $dto = $this->factory->fromRow($row);

        $this->assertSame(10, $dto->id);
        $this->assertSame(5, $dto->userId);
        $this->assertSame('order.completed', $dto->type);
        $this->assertSame('Order shipped', $dto->title);
        $this->assertSame('Your order has been shipped!', $dto->message);
        $this->assertSame(['order_id' => 123], $dto->data);
    }

    public function test_from_row_handles_json_data_string(): void
    {
        // NotificationDTO::fromRow expects data to be an array.
        // A raw JSON string becomes [] (NotificationModel::decode is responsible
        // for decoding before the DTO is created).
        $row = [
            'id'   => 1,
            'data' => '{"key":"value"}',
        ];

        $dto = $this->factory->fromRow($row);
        $this->assertSame([], $dto->data);
    }

    public function test_from_row_handles_array_data(): void
    {
        $row = [
            'id'   => 1,
            'data' => ['key' => 'value'],
        ];

        $dto = $this->factory->fromRow($row);
        $this->assertSame(['key' => 'value'], $dto->data);
    }

    public function test_dto_to_array(): void
    {
        $dto = new NotificationDTO();
        $dto->id = 1;
        $dto->userId = 2;
        $dto->type = 'test';
        $dto->title = 'Test';
        $dto->message = 'Hello';
        $dto->data = ['foo' => 'bar'];
        $dto->createdAt = '2026-08-11';

        $arr = $dto->toArray();

        $this->assertSame(1, $arr['id']);
        $this->assertSame(2, $arr['user_id']);
        $this->assertSame('test', $arr['type']);
        $this->assertSame(['foo' => 'bar'], $arr['data']);
    }
}
