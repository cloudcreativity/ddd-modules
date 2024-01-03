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

namespace CloudCreativity\Modules\Tests\Unit\Infrastructure\EventBus;

use CloudCreativity\Modules\Infrastructure\EventBus\IntegrationEventInterface;
use CloudCreativity\Modules\Toolkit\Identifiers\Uuid;
use DateTimeImmutable;

class TestIntegrationEvent implements IntegrationEventInterface
{
    /**
     * @var Uuid
     */
    public readonly Uuid $uuid;

    /**
     * @var DateTimeImmutable
     */
    public readonly DateTimeImmutable $occurredAt;

    /**
     * TestIntegrationEvent constructor.
     */
    public function __construct()
    {
        $this->uuid = Uuid::random();
        $this->occurredAt = new DateTimeImmutable();
    }

    /**
     * @inheritDoc
     */
    public function uuid(): Uuid
    {
        return $this->uuid;
    }

    /**
     * @inheritDoc
     */
    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
