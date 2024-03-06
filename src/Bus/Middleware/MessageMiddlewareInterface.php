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
use CloudCreativity\Modules\Toolkit\Messages\MessageInterface;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;

interface MessageMiddlewareInterface
{
    /**
     * Handle the message.
     *
     * @param MessageInterface $message
     * @param Closure(MessageInterface): ResultInterface<mixed> $next
     * @return ResultInterface<mixed>
     */
    public function __invoke(MessageInterface $message, Closure $next): ResultInterface;
}
