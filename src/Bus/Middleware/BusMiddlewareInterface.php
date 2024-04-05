<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Bus\Middleware;

use Closure;
use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;
use CloudCreativity\Modules\Toolkit\Messages\QueryInterface;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;

interface BusMiddlewareInterface
{
    /**
     * Handle the command or query.
     *
     * @param CommandInterface|QueryInterface $message
     * @param Closure(CommandInterface|QueryInterface): ResultInterface<mixed> $next
     * @return ResultInterface<mixed>
     */
    public function __invoke(CommandInterface|QueryInterface $message, Closure $next): ResultInterface;
}
