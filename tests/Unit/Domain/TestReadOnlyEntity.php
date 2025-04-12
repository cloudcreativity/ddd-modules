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
use CloudCreativity\Modules\Domain\IsEntity;

/**
 * @TODO remove when dropping PHP 8.1 and update `TestEntity` to be `readonly`.
 */
final readonly class TestReadOnlyEntity implements Entity
{
    use IsEntity;

    /**
     * TestReadOnlyEntity constructor
     *
     * @param Identifier $id
     * @param string $name
     */
    public function __construct(Identifier $id, private string $name)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
