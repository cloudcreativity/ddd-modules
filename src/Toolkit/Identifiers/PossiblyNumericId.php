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

final readonly class PossiblyNumericId implements JsonSerializable, Stringable
{
    public int|string $value;

    public static function from(int|string $value): self
    {
        return new self($value);
    }

    /**
     * PossiblyNumericId constructor
     *
     */
    public function __construct(int|string $value)
    {
        if (is_string($value) && 1 === preg_match('/^\d+$/', $value)) {
            $value = (int) $value;
        }

        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Fluent to-string method.
     *
     */
    public function toString(): string
    {
        return (string) $this->value;
    }

    public function toId(): IntegerId|StringId
    {
        if (is_int($this->value)) {
            return new IntegerId($this->value);
        }

        return new StringId($this->value);
    }

    public function jsonSerialize(): int|string
    {
        return $this->value;
    }
}
