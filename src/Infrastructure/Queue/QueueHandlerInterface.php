<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Infrastructure\Queue;

use CloudCreativity\Modules\Toolkit\Messages\DispatchThroughMiddleware;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;

interface QueueHandlerInterface extends DispatchThroughMiddleware
{
    /**
     * Handle the message.
     *
     * @param QueueableInterface $message
     * @return ResultInterface<mixed>
     */
    public function __invoke(QueueableInterface $message): ResultInterface;
}
