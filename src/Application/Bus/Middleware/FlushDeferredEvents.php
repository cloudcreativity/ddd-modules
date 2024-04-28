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
use CloudCreativity\Modules\Application\DomainEventDispatching\DeferredDispatcherInterface;
use CloudCreativity\Modules\Application\Messages\CommandInterface;
use CloudCreativity\Modules\Application\Messages\QueryInterface;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;
use Throwable;

final class FlushDeferredEvents implements BusMiddlewareInterface
{
    /**
     * FlushDeferredEvents constructor.
     *
     * @param DeferredDispatcherInterface $dispatcher
     */
    public function __construct(private readonly DeferredDispatcherInterface $dispatcher)
    {
    }

    /**
     * @inheritDoc
     */
    public function __invoke(CommandInterface|QueryInterface $message, Closure $next): ResultInterface
    {
        try {
            $result = $next($message);
        } catch (Throwable $ex) {
            $this->dispatcher->forget();
            throw $ex;
        }

        if ($result->didSucceed()) {
            $this->dispatcher->flush();
        } else {
            $this->dispatcher->forget();
        }

        return $result;
    }
}
