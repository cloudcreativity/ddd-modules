<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Contracts\Application\DomainEventDispatching;

use CloudCreativity\Modules\Contracts\Domain\Events\DomainEventDispatcher;

interface DeferredDispatcher extends DomainEventDispatcher
{
    /**
     * Dispatch any deferred events.
     */
    public function flush(): void;

    /**
     * Clear deferred events without dispatching them.
     */
    public function forget(): void;
}
