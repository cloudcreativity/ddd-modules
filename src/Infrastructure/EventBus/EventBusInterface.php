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

namespace CloudCreativity\Modules\Infrastructure\EventBus;

use CloudCreativity\Modules\IntegrationEvents\IntegrationEventInterface;
use CloudCreativity\Modules\IntegrationEvents\PublisherInterface;

interface EventBusInterface extends PublisherInterface
{
    /**
     * Notify subscribers of an integration event.
     *
     * @param IntegrationEventInterface $event
     * @return void
     */
    public function notify(IntegrationEventInterface $event): void;

    /**
     * Subscribe to an integration event.
     *
     * @param class-string<IntegrationEventInterface> $event
     * @param string $listener
     * @return void
     */
    public function subscribe(string $event, string $listener): void;
}
