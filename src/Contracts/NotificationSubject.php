<?php

namespace Jankx\Extensions\NotificationSystem\Contracts;

/**
 * Subject interface — the observable that NotificationManager watches.
 */
interface NotificationSubject
{
    public function attach(NotificationObserver $observer): void;
    public function detach(NotificationObserver $observer): void;
    public function notify(string $event, mixed $data): void;
}
