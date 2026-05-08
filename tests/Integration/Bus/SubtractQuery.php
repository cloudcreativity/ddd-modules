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

use CloudCreativity\Modules\Contracts\Messaging\Query;

final readonly class SubtractQuery implements Query
{
    public function __construct(public int $a, public int $b)
    {
    }
}
