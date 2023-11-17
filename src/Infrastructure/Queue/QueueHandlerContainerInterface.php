<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Infrastructure\Queue;

interface QueueHandlerContainerInterface
{
    /**
     * Get a queue handler for the specified message.
     *
     * @param string $queueableName
     * @return QueueHandlerInterface
     */
    public function get(string $queueableName): QueueHandlerInterface;
}