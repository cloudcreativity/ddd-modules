<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\Bus;

use CloudCreativity\Modules\Contracts\Application\Bus\CommandHandler as ICommandHandler;
use CloudCreativity\Modules\Contracts\Application\Messages\DispatchThroughMiddleware;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\Command;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;

final readonly class CommandHandler implements ICommandHandler
{
    public function __construct(private object $handler)
    {
    }

    public function __invoke(Command $command): Result
    {
        assert(method_exists($this->handler, 'execute'), sprintf(
            'Cannot dispatch "%s" - handler "%s" does not have an execute method.',
            $command::class,
            $this->handler::class,
        ));

        $result = $this->handler->execute($command);

        assert($result instanceof Result, 'Expecting command handler to return a result.');

        return $result;
    }

    public function middleware(): array
    {
        if ($this->handler instanceof DispatchThroughMiddleware) {
            return $this->handler->middleware();
        }

        return [];
    }
}
