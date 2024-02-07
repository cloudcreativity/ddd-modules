<?php
/*
 * Copyright 2024 Cloud Creativity Limited
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

namespace CloudCreativity\Modules\Tests\Unit\Bus;

use CloudCreativity\Modules\Bus\DispatchThroughMiddleware;
use CloudCreativity\Modules\Toolkit\Result\Result;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;

class TestCommandHandler implements TestCommandHandlerInterface, DispatchThroughMiddleware
{
    /**
     * @inheritDoc
     */
    public function execute(TestCommand $command): ResultInterface
    {
        return Result::ok();
    }

    /**
     * @inheritDoc
     */
    public function middleware(): array
    {
        return [];
    }
}
