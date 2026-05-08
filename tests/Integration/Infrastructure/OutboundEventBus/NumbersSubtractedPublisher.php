<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Integration\Infrastructure\OutboundEventBus;

use CloudCreativity\Modules\Tests\Integration\Bus\NumbersSubtracted;

final class NumbersSubtractedPublisher
{
    /**
     * @var array<NumbersSubtracted>
     */
    public array $published = [];

    public function publish(NumbersSubtracted $event): void
    {
        $this->published[] = $event;
    }
}
