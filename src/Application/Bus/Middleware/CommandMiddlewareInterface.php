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
use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;

interface CommandMiddlewareInterface
{
    /**
     * Handle the command.
     *
     * @param CommandInterface $command
     * @param Closure(CommandInterface): ResultInterface<mixed> $next
     * @return ResultInterface<mixed>
     */
    public function __invoke(CommandInterface $command, Closure $next): ResultInterface;
}
