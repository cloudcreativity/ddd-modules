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

use CloudCreativity\Modules\Bus\InboundEventDispatcher;
use CloudCreativity\Modules\Bus\Middleware\LogMessageDispatch;
use CloudCreativity\Modules\Bus\WithDefault;
use CloudCreativity\Modules\Bus\WithEvent;
use CloudCreativity\Modules\Toolkit\Pipeline\Through;

#[Through(LogMessageDispatch::class)]
#[WithDefault(DefaultEventHandler::class)]
#[WithEvent(NumbersAdded::class, NumbersAddedHandler::class)]
#[WithEvent(NumbersSubtracted::class, NumbersSubtractedHandler::class)]
final class MathEventBus extends InboundEventDispatcher
{
}
