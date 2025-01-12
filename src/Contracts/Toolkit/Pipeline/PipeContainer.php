<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Contracts\Toolkit\Pipeline;

interface PipeContainer
{
    /**
     * Get a pipe by its name.
     *
     * @param string $pipeName
     * @return callable
     */
    public function get(string $pipeName): callable;
}
