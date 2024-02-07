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

namespace CloudCreativity\Modules\Domain;

use CloudCreativity\Modules\Toolkit\Identifiers\IdentifierInterface;

interface EntityInterface
{
    /**
     * Get the entity's identifier.
     *
     * @return IdentifierInterface|null
     */
    public function getId(): ?IdentifierInterface;

    /**
     * Is this entity the same as the provided entity?
     *
     * @param EntityInterface|null $other
     * @return bool
     */
    public function is(?EntityInterface $other): bool;

    /**
     * Is this entity not the same as the provided entity?
     *
     * @param EntityInterface|null $other
     * @return bool
     */
    public function isNot(?EntityInterface $other): bool;
}
