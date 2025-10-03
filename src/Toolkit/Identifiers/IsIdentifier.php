<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Toolkit\Identifiers;

use CloudCreativity\Modules\Contracts\Toolkit\Identifiers\Identifier;

trait IsIdentifier
{
    public function any(?Identifier ...$others): bool
    {
        return array_any(
            $others,
            fn (?Identifier $other): bool => $this->is($other),
        );
    }
}
