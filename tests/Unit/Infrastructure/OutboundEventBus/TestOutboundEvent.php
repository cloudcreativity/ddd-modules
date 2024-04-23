<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Infrastructure\OutboundEventBus;

use CloudCreativity\Modules\Toolkit\Identifiers\Uuid;
use CloudCreativity\Modules\Toolkit\Messages\IntegrationEventInterface;
use DateTimeImmutable;

class TestOutboundEvent implements IntegrationEventInterface
{
    /**
     * @var Uuid
     */
    public readonly Uuid $uuid;

    /**
     * @var DateTimeImmutable
     */
    public readonly DateTimeImmutable $occurredAt;

    /**
     * TestIntegrationEvent constructor.
     */
    public function __construct()
    {
        $this->uuid = Uuid::random();
        $this->occurredAt = new DateTimeImmutable();
    }

    /**
     * @inheritDoc
     */
    public function uuid(): Uuid
    {
        return $this->uuid;
    }

    /**
     * @inheritDoc
     */
    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
