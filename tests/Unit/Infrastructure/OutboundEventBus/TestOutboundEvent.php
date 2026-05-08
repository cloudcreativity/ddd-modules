<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Infrastructure\OutboundEventBus;

use CloudCreativity\Modules\Contracts\Messaging\IntegrationEvent;
use CloudCreativity\Modules\Toolkit\Identifiers\UuidV4;
use DateTimeImmutable;

abstract class TestOutboundEvent implements IntegrationEvent
{
    public readonly UuidV4 $uuid;

    public readonly DateTimeImmutable $occurredAt;

    /**
     * TestIntegrationEvent constructor.
     */
    public function __construct()
    {
        $this->uuid = UuidV4::make();
        $this->occurredAt = new DateTimeImmutable();
    }

    public function getUuid(): UuidV4
    {
        return $this->uuid;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
