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

namespace CloudCreativity\Modules\Toolkit;

use Stringable;
use UnexpectedValueException;

final class ModuleBasename implements Stringable
{
    /** @var string  */
    private const REGEX_MODULES = '/\\\\Modules\\\\(\w+)\\\\[\w\\\\]+\\\\(\w+)$/m';
    /** @var string */
    private const REGEX_WITHOUT_MODULES = '/\\\\(Queries|Commands|Domain\\\\Events|IntegrationEvents)(\\\\[\w\\\\]+)?\\\\(\w+)$/m';

    /**
     * Create a message name from a class string.
     *
     * @param object|string $class
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
     * @param object|string $class
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
     * @param string|null $module
     * @param string $name
     */
    private function __construct(
        public readonly ?string $module,
        public readonly string $name,
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
