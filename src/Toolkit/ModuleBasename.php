<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Toolkit;

use Stringable;
use UnexpectedValueException;

final readonly class ModuleBasename implements Stringable
{
    /** @var string  */
    private const REGEX_MODULES = '/\\\\Modules\\\\(\w+)\\\\[\w\\\\]+\\\\(\w+)$/m';
    /** @var string */
    private const REGEX_WITHOUT_MODULES = '/\\\\(Queries|Commands|Domain\\\\Events|IntegrationEvents)(\\\\[\w\\\\]+)?\\\\(\w+)$/m';

    /**
     * Create a message name from a class string.
     *
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
     * Try to create a message name from a class.
     *
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

        if (1 === preg_match(self::REGEX_WITHOUT_MODULES, $class, $matches)) {
            return new self(null, $matches[3]);
        }

        return null;
    }

    /**
     * ModuleBasename constructor.
     *
     */
    private function __construct(
        public ?string $module,
        public string $name,
    ) {
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Fluent to-string method.
     *
     */
    public function toString(string $delimiter = ':'): string
    {
        if ($this->module === null) {
            return $this->name;
        }

        return sprintf('%s%s%s', $this->module, $delimiter, $this->name);
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'module' => $this->module,
            'name' => $this->name,
        ];
    }
}
