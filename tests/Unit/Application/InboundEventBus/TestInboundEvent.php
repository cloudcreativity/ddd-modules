<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Application\InboundEventBus;

use CloudCreativity\Modules\Contracts\Messaging\IntegrationEvent;
use CloudCreativity\Modules\Toolkit\Identifiers\UuidV4;
use DateTimeImmutable;

final class TestInboundEvent implements IntegrationEvent
{
    public function getUuid(): UuidV4
    {
        return UuidV4::make();
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
