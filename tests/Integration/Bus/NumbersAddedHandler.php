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

final class NumbersAddedHandler
{
    /**
     * @var array<NumbersAdded>
     */
    public array $handled = [];

    public function handle(NumbersAdded $event): void
    {
        $this->handled[] = $event;
    }
}
