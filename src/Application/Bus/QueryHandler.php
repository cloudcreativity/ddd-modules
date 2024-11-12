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

use CloudCreativity\Modules\Contracts\Application\Bus\QueryHandler as IQueryHandler;
use CloudCreativity\Modules\Contracts\Application\Messages\DispatchThroughMiddleware;
use CloudCreativity\Modules\Contracts\Application\Messages\Query;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;

final class QueryHandler implements IQueryHandler
{
    /**
     * QueryHandler constructor.
     *
     * @param object $handler
     */
    public function __construct(private readonly object $handler)
    {
    }

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
    public function middleware(): array
    {
        if ($this->handler instanceof DispatchThroughMiddleware) {
            return $this->handler->middleware();
        }

        return [];
    }
}
