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

use CloudCreativity\Modules\Contracts\Bus\QueryHandler as IQueryHandler;
use CloudCreativity\Modules\Contracts\Messaging\Query;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;

final readonly class QueryHandler implements IQueryHandler
{
    use HandlesMessages;

    public function __construct(private object $handler)
    {
    }

    public function __invoke(Query $query): Result
    {
        assert(method_exists($this->handler, 'execute'), sprintf(
            'Cannot dispatch "%s" - handler "%s" does not have an execute method.',
            $query::class,
            $this->handler::class,
        ));

        $result = $this->handler->execute($query);

        assert($result instanceof Result, 'Expecting query handler to return a result.');

        return $result;
    }
}
