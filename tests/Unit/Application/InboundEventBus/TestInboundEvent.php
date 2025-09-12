<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Application\InboundEventBus;

use CloudCreativity\Modules\Contracts\Toolkit\Messages\IntegrationEvent;
use CloudCreativity\Modules\Toolkit\Identifiers\Uuid;
use DateTimeImmutable;

class TestInboundEvent implements IntegrationEvent
{
    public function getUuid(): Uuid
    {
        return Uuid::random();
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
