<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\DomainEventDispatching;

interface DeferredDispatcherInterface extends DispatcherInterface
{
    /**
     * Dispatch any deferred events.
     *
     * @return void
     */
    public function flush(): void;

    /**
     * Clear deferred events without dispatching them.
     *
     * @return void
     */
    public function forget(): void;
}
