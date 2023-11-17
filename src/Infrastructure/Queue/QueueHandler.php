<?php
/*
 * Copyright 2023 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Infrastructure\Queue;

use Closure;

final class QueueHandler implements QueueHandlerInterface
{
    /**
     * QueueHandler constructor.
     *
     * @param object $handler
     */
    public function __construct(private readonly object $handler)
    {
    }

    /**
     * @inheritDoc
     */
    public function __invoke(QueueableInterface $message): void
    {
        if ($this->handler instanceof Closure) {
            ($this->handler)($message);
            return;
        }

        assert(method_exists($this->handler, 'queue'), sprintf(
            'Cannot queue "%s" - handler "%s" does not have a queue method.',
            $message::class,
            $this->handler::class,
        ));

        $this->handler->queue($message);
    }

    /**
     * @inheritDoc
     */
    public function withBatch(QueueableBatch $batch): void
    {
        if ($this->handler instanceof QueuesBatches) {
            $this->handler->withBatch($batch);
        }
    }

    /**
     * @inheritDoc
     */
    public function middleware(): array
    {
        if ($this->handler instanceof QueueThroughMiddleware) {
            return $this->handler->middleware();
        }

        return [];
    }
}
