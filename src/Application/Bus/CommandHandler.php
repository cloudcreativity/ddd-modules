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

use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;
use CloudCreativity\Modules\Toolkit\Messages\DispatchThroughMiddleware;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;

final class CommandHandler implements CommandHandlerInterface
{
    /**
     * CommandHandler constructor.
     *
     * @param object $handler
     */
    public function __construct(private readonly object $handler)
    {
    }

    /**
     * @inheritDoc
     */
    public function __invoke(CommandInterface $command): ResultInterface
    {
        assert(method_exists($this->handler, 'execute'), sprintf(
            'Cannot dispatch "%s" - handler "%s" does not have an execute method.',
            $command::class,
            $this->handler::class,
        ));

        return $this->handler->execute($command);
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
