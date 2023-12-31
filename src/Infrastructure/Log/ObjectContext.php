<?php
/*
 * Copyright 2023 Cloud Creativity Limited
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

namespace CloudCreativity\Modules\Infrastructure\Log;

final class ObjectContext implements ContextProviderInterface
{
    /**
     * @param object $source
     * @return self
     */
    public static function from(object $source): self
    {
        return new self($source);
    }

    /**
     * ObjectContext constructor.
     *
     * @param object $source
     */
    public function __construct(private readonly object $source)
    {
    }

    /**
     * @inheritDoc
     */
    public function context(): array
    {
        if ($this->source instanceof ContextProviderInterface) {
            return $this->source->context();
        }

        return get_object_vars($this->source);
    }
}
