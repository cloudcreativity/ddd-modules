<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Integration\Infrastructure\OutboundEventBus;

use CloudCreativity\Modules\Infrastructure\OutboundEventBus\ComponentPublisher;
use CloudCreativity\Modules\Infrastructure\OutboundEventBus\DefaultPublisher;
use CloudCreativity\Modules\Infrastructure\OutboundEventBus\Middleware\LogOutboundEvent;
use CloudCreativity\Modules\Infrastructure\OutboundEventBus\Publishes;
use CloudCreativity\Modules\Tests\Integration\Bus\NumbersAdded;
use CloudCreativity\Modules\Tests\Integration\Bus\NumbersSubtracted;
use CloudCreativity\Modules\Toolkit\Pipeline\Through;

#[DefaultPublisher(TestDefaultPublisher::class)]
#[Publishes(NumbersAdded::class, NumbersAddedPublisher::class)]
#[Publishes(NumbersSubtracted::class, NumbersSubtractedPublisher::class)]
#[Through(LogOutboundEvent::class)]
final class MathEventPublisher extends ComponentPublisher
{
}
