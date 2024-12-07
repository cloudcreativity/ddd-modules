<?php

/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Contracts\Toolkit\Pipeline;

interface Processor
{
    /**
     * Process the payload through the provided stages.
     *
     * @param mixed $payload
     * @param callable ...$stages
     * @return mixed
     */
    public function process(mixed $payload, callable ...$stages): mixed;
}
