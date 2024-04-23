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

use CloudCreativity\Modules\Domain\Events\DomainEventInterface;
use CloudCreativity\Modules\Domain\Events\OccursImmediately;
use DateTimeImmutable;

class TestImmediateDomainEvent implements DomainEventInterface, OccursImmediately
{
    /**
     * @inheritDoc
     */
    public function occurredAt(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
