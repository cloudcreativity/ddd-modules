<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Toolkit\Identifiers;

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