<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Bus\Validation;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class WithRules
{
    /**
     * @param array<callable|string> $rules
     */
    public function __construct(public readonly array $rules)
    {
    }
}
