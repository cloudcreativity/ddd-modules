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

use RuntimeException;
use Throwable;

final class LazyPipe
{
    /**
     * LazyPipe constructor.
     *
     * @param PipeContainerInterface $container
     * @param string $pipeName
     */
    public function __construct(
        private readonly PipeContainerInterface $container,
        private readonly string $pipeName,
    ) {
    }

    /**
     * @param mixed ...$args
     * @return mixed
     */
    public function __invoke(mixed ...$args): mixed
    {
        try {
            $pipe = $this->container->get($this->pipeName);
        } catch (Throwable $ex) {
            throw new RuntimeException(sprintf(
                'Failed to get pipe "%s" from container.',
                $this->pipeName,
            ), 0, $ex);
        }

        return $pipe(...$args);
    }
}
