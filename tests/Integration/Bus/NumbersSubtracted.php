<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Integration\Bus;

use CloudCreativity\Modules\Contracts\Messaging\IntegrationEvent;
use CloudCreativity\Modules\Toolkit\Contracts;
use CloudCreativity\Modules\Toolkit\Identifiers\UuidV4;
use DateTimeImmutable;

final readonly class NumbersSubtracted implements IntegrationEvent
{
    public UuidV4 $uuid;

    public function __construct(
        public int $a,
        public int $b,
        public int $sum,
        public DateTimeImmutable $calculatedAt = new DateTimeImmutable(),
    ) {
        Contracts::assert($sum === ($a - $b), 'The sum must be equal to a minus b.');
        $this->uuid = UuidV4::make();
    }

    public function getUuid(): UuidV4
    {
        return $this->uuid;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->calculatedAt;
    }
}
