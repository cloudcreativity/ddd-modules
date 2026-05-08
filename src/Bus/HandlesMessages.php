<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Bus;

use CloudCreativity\Modules\Contracts\Bus\DispatchThroughMiddleware;
use CloudCreativity\Modules\Toolkit\Pipeline\Through;
use ReflectionClass;

trait HandlesMessages
{
    public function middleware(): array
    {
        if ($this->handler instanceof DispatchThroughMiddleware) {
            return $this->handler->middleware();
        }

        $reflection = new ReflectionClass($this->handler);

        foreach ($reflection->getAttributes(Through::class) as $attribute) {
            $instance = $attribute->newInstance();
            return $instance->pipes;
        }

        return [];
    }
}
