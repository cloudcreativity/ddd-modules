<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Toolkit;

use Stringable;
use UnexpectedValueException;

readonly class ModuleBasename implements Stringable
{
    /** @var string  */
    private const REGEX_MODULES = '/Modules\\\\(\w+)\\\\[\w\\\\]+\\\\(\w+)$/m';

    /**
     * Create a message name from a class string.
     *
     * @param object|class-string $class
     * @return static
     */
    public static function from(object|string $class): self
    {
        if ($value = self::tryFrom($class)) {
            return $value;
        }

        throw new UnexpectedValueException('Unexpected fully-qualified message class name.');
    }

    /**
     * Try to create a message name from a class string.
     *
     * @param object|class-string $class
     * @return static|null
     */
    public static function tryFrom(object|string $class): ?self
    {
        if (is_object($class)) {
            $class = $class::class;
        }

        if (1 === preg_match(self::REGEX_MODULES, $class, $matches)) {
            return new self($matches[1], $matches[2]);
        }

        return null;
    }

    /**
     * ModuleBasename constructor.
     *
     * @param string $module
     * @param string $name
     */
    private function __construct(
        public string $module,
        public string $name,
    ) {
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Fluent to-string method.
     *
     * @param string $delimiter
     * @return string
     */
    public function toString(string $delimiter = ':'): string
    {
        return sprintf('%s%s%s', $this->module, $delimiter, $this->name);
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'module' => $this->module,
            'name' => $this->name,
        ];
    }
}
