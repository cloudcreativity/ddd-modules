<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Integration\Infrastructure\Queue;

use CloudCreativity\Modules\Infrastructure\Queue\ComponentQueue;
use CloudCreativity\Modules\Infrastructure\Queue\DefaultEnqueuer;
use CloudCreativity\Modules\Infrastructure\Queue\Middleware\LogPushedToQueue;
use CloudCreativity\Modules\Infrastructure\Queue\Queues;
use CloudCreativity\Modules\Tests\Integration\Bus\AddCommand;
use CloudCreativity\Modules\Tests\Integration\Bus\MultiplyCommand;
use CloudCreativity\Modules\Toolkit\Pipeline\Through;

#[DefaultEnqueuer(TestDefaultEnqueuer::class)]
#[Queues(AddCommand::class, AddCommandEnqueuer::class)]
#[Queues([MultiplyCommand::class], MultiplyCommandEnqueuer::class)]
#[Through(LogPushedToQueue::class)]
final class MathQueue extends ComponentQueue
{
}
