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

use CloudCreativity\Modules\Application\Bus\Middleware\ExecuteInUnitOfWork;
use CloudCreativity\Modules\Toolkit\Pipeline\Through;

#[Through(ExecuteInUnitOfWork::class)]
final class NumbersSubtractedHandler
{
    /**
     * @var array<NumbersSubtracted>
     */
    public array $handled = [];

    public function handle(NumbersSubtracted $event): void
    {
        $this->handled[] = $event;
    }
}
