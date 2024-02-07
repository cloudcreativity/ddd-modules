<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Domain;

use CloudCreativity\Modules\Domain\EntityInterface;
use CloudCreativity\Modules\Domain\EntityWithNullableIdTrait;
use CloudCreativity\Modules\Toolkit\Identifiers\IdentifierInterface;

class TestEntityWithNullableId implements EntityInterface
{
    use EntityWithNullableIdTrait;

    /**
     * TestEntityWithNullableGuid constructor.
     *
     * @param IdentifierInterface|null $id
     */
    public function __construct(IdentifierInterface $id = null)
    {
        $this->id = $id;
    }
}
