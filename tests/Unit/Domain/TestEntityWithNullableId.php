<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Domain;

use CloudCreativity\Modules\Contracts\Domain\Entity;
use CloudCreativity\Modules\Contracts\Toolkit\Identifiers\Identifier;
use CloudCreativity\Modules\Domain\IsEntityWithNullableId;

class TestEntityWithNullableId implements Entity
{
    use IsEntityWithNullableId;

    /**
     * TestEntityWithNullableGuid constructor.
     */
    public function __construct(?Identifier $id = null)
    {
        $this->id = $id;
    }
}
