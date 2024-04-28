<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\Bus\Middleware;

use Closure;
use CloudCreativity\Modules\Application\Messages\CommandInterface;
use CloudCreativity\Modules\Application\Messages\QueryInterface;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;

final class TearDownAfterDispatch implements BusMiddlewareInterface
{
    /**
     * TearDownAfterDispatch constructor.
     *
     * @param Closure(): void $callback
     */
    public function __construct(private readonly Closure $callback)
    {
    }

    /**
     * @inheritDoc
     */
    public function __invoke(CommandInterface|QueryInterface $message, Closure $next): ResultInterface
    {
        try {
            return $next($message);
        } finally {
            ($this->callback)();
        }
    }
}
