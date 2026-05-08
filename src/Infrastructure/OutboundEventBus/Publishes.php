<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Infrastructure\OutboundEventBus;

use Attribute;
use CloudCreativity\Modules\Contracts\Messaging\IntegrationEvent;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class Publishes
{
    /**
     * @param class-string<IntegrationEvent> $event
     * @param class-string $publisher
     */
    public function __construct(public string $event, public string $publisher)
    {
    }
}
