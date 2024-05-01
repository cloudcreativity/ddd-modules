<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Application\DomainEventDispatching;

use CloudCreativity\Modules\Contracts\Domain\Events\DomainEvent;
use DateTimeImmutable;

class TestDomainEvent implements DomainEvent
{
    /**
     * @inheritDoc
     */
    public function getOccurredAt(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
