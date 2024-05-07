<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Toolkit\Pipeline;

use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer;
use RuntimeException;
use Throwable;

final class LazyPipe
{
    /**
     * LazyPipe constructor.
     *
     * @param PipeContainer $container
     * @param string $pipeName
     */
    public function __construct(
        private readonly PipeContainer $container,
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
