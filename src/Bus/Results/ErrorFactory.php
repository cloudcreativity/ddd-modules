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

namespace CloudCreativity\Modules\Bus\Results;

class ErrorFactory implements ErrorFactoryInterface
{
    /**
     * @var ErrorFactoryInterface|null
     */
    private static ?ErrorFactoryInterface $instance = null;

    /**
     * Get the global instance.
     *
     * @return ErrorFactoryInterface
     */
    public static function getInstance(): ErrorFactoryInterface
    {
        if (self::$instance) {
            return self::$instance;
        }

        return self::$instance = new self();
    }

    /**
     * Set the global instance.
     *
     * @param ErrorFactoryInterface|null $instance
     * @return void
     */
    public static function setInstance(?ErrorFactoryInterface $instance): void
    {
        self::$instance = $instance;
    }

    /**
     * @inheritDoc
     */
    public function make(ErrorInterface|string $value): ErrorInterface
    {
        if ($value instanceof ErrorInterface) {
            return $value;
        }

        return new Error(null, $value);
    }
}
