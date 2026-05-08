<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Integration\Bus;

use CloudCreativity\Modules\Bus\CommandDispatcher;
use CloudCreativity\Modules\Bus\Middleware\LogMessageDispatch;
use CloudCreativity\Modules\Bus\WithCommand;
use CloudCreativity\Modules\Toolkit\Pipeline\Through;

#[Through(LogMessageDispatch::class)]
#[WithCommand(AddCommand::class, AddCommandHandler::class)]
#[WithCommand(MultiplyCommand::class, MultiplyCommandHandler::class)]
final class MathCommandBus extends CommandDispatcher
{
}
