<?php

/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Domain;

use CloudCreativity\Modules\Contracts\Domain\Entity;
use CloudCreativity\Modules\Contracts\Toolkit\Identifiers\Identifier;
use CloudCreativity\Modules\Domain\IsEntity;

class TestEntity implements Entity
{
    use IsEntity;

    /**
     * TestEntity constructor
     *
     * @param Identifier $id
     */
    public function __construct(Identifier $id)
    {
        $this->id = $id;
    }
}
