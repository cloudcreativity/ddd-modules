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

namespace CloudCreativity\Modules\Toolkit\Identifiers;

use JsonSerializable;
use Stringable;

readonly class PossiblyNumericId implements JsonSerializable, Stringable
{
    /**
     * @var string|int
     */
    public string|int $value;

    /**
     * @param string|int $value
     * @return self
     */
    public static function from(string|int $value): self
    {
        return new self($value);
    }

    /**
     * PossiblyNumericId constructor
     *
     * @param string|int $value
     */
    public function __construct(string|int $value)
    {
        if (is_string($value) && 1 === preg_match('/^\d+$/', $value)) {
            $value = (int) $value;
        }

        $this->value = $value;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Fluent to-string method.
     *
     * @return string
     */
    public function toString(): string
    {
        return (string) $this->value;
    }

    /**
     * @return StringId|IntegerId
     */
    public function toId(): StringId|IntegerId
    {
        if (is_int($this->value)) {
            return new IntegerId($this->value);
        }

        return new StringId($this->value);
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): string|int
    {
        return $this->value;
    }
}
