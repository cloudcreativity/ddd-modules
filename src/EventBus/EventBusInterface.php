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

namespace CloudCreativity\Modules\EventBus;

use CloudCreativity\Modules\Toolkit\Messages\IntegrationEventInterface;

interface EventBusInterface
{
    /**
     * Publish an integration event (outbound).
     *
     * @param IntegrationEventInterface $event
     * @return void
     */
    public function publish(IntegrationEventInterface $event): void;

    /**
     * Notify subscribers of an inbound integration event.
     *
     * @param IntegrationEventInterface $event
     * @return void
     */
    public function notify(IntegrationEventInterface $event): void;
}
