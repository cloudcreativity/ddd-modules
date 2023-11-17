<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Infrastructure\EventBus;

use CloudCreativity\BalancedEvent\Common\IntegrationEvents\IntegrationEventInterface;
use CloudCreativity\BalancedEvent\Common\IntegrationEvents\PublisherInterface;

interface EventBusInterface extends PublisherInterface
{
    /**
     * Notify subscribers of an integration event.
     *
     * @param IntegrationEventInterface $event
     * @return void
     */
    public function notify(IntegrationEventInterface $event): void;

    /**
     * Subscribe to an integration event.
     *
     * @param class-string<IntegrationEventInterface> $event
     * @param string $listener
     * @return void
     */
    public function subscribe(string $event, string $listener): void;
}