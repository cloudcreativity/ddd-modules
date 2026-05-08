<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Bus;

use Attribute;
use CloudCreativity\Modules\Contracts\Messaging\Query;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class WithQuery
{
    /**
     * @param class-string<Query> $query
     * @param class-string $handler
     */
    public function __construct(public string $query, public string $handler)
    {
    }
}
