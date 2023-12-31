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

namespace CloudCreativity\Modules\Toolkit\Pipeline;

use Closure;
use RuntimeException;

final class PipeContainer implements PipeContainerInterface
{
    /**
     * @var array<string,Closure>
     */
    private array $pipes = [];

    /**
     * Bind a pipe into the container.
     *
     * @param string $pipeName
     * @param Closure $factory
     * @return void
     */
    public function bind(string $pipeName, Closure $factory): void
    {
        $this->pipes[$pipeName] = $factory;
    }

    /**
     * @inheritDoc
     */
    public function get(string $pipeName): callable
    {
        $factory = $this->pipes[$pipeName] ?? null;

        if ($factory) {
            return $factory();
        }

        throw new RuntimeException('Unrecognised pipe name: ' . $pipeName);
    }
}
