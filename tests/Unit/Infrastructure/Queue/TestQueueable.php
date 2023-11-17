<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Tests\Unit\Common\Infrastructure\Queue;

use CloudCreativity\BalancedEvent\Common\Infrastructure\Queue\QueueableInterface;
use CloudCreativity\BalancedEvent\Common\Toolkit\Identifiers\Guid;

class TestQueueable implements QueueableInterface
{
    /**
     * @var Guid|null
     */
    private ?Guid $guid = null;

    /**
     * @return Guid|null
     */
    public function getGuid(): ?Guid
    {
        return $this->guid;
    }

    /**
     * @param Guid|null $guid
     * @return $this
     */
    public function setGuid(?Guid $guid): self
    {
        $this->guid = $guid;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function context(): array
    {
        return [];
    }
}
