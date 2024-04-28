<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\Bus;

use CloudCreativity\Modules\Application\Messages\DispatchThroughMiddleware;
use CloudCreativity\Modules\Application\Messages\QueryInterface;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;

interface QueryHandlerInterface extends DispatchThroughMiddleware
{
    /**
     * Execute the query.
     *
     * @param QueryInterface $query
     * @return ResultInterface<mixed>
     */
    public function __invoke(QueryInterface $query): ResultInterface;
}
