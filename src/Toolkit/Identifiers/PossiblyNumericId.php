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

use JsonSerializable;
use Stringable;

final class PossiblyNumericId implements JsonSerializable, Stringable
{
    /**
     * @var string|int
     */
    public readonly string|int $value;

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
