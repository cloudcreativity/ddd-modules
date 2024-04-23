<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Application\InboundEventBus;

use CloudCreativity\Modules\Toolkit\Identifiers\Uuid;
use CloudCreativity\Modules\Toolkit\Messages\IntegrationEventInterface;
use DateTimeImmutable;

class TestInboundEvent implements IntegrationEventInterface
{
    /**
     * @return Uuid
     */
    public function uuid(): Uuid
    {
        return Uuid::random();
    }

    /**
     * @return DateTimeImmutable
     */
    public function occurredAt(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}