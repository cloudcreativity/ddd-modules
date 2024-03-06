<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Domain\Events;

use DateTimeImmutable;

interface DomainEventInterface
{
    /**
     * The date/time the event occurred.
     *
     * @return DateTimeImmutable
     */
    public function occurredAt(): DateTimeImmutable;
}
