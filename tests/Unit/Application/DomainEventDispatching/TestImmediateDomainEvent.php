<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Application\DomainEventDispatching;

use CloudCreativity\Modules\Contracts\Domain\Events\DomainEvent;
use CloudCreativity\Modules\Contracts\Domain\Events\OccursImmediately;
use DateTimeImmutable;

final class TestImmediateDomainEvent implements DomainEvent, OccursImmediately
{
    public function getOccurredAt(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
