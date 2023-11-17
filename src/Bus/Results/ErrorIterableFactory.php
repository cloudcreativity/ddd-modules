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

namespace CloudCreativity\BalancedEvent\Common\Bus\Results;

class ErrorIterableFactory implements ErrorIterableFactoryInterface
{
    /**
     * @var ErrorIterableFactoryInterface|null
     */
    private static ?ErrorIterableFactoryInterface $instance = null;

    /**
     * @return ErrorIterableFactoryInterface
     */
    public static function getInstance(): ErrorIterableFactoryInterface
    {
        if (self::$instance) {
            return self::$instance;
        }

        return self::$instance = new self(ErrorFactory::getInstance());
    }

    /**
     * @param ErrorIterableFactoryInterface|null $instance
     * @return void
     */
    public static function setInstance(?ErrorIterableFactoryInterface $instance): void
    {
        self::$instance = $instance;
    }

    /**
     * ErrorIterableFactory constructor.
     *
     * @param ErrorFactoryInterface $errorFactory
     */
    public function __construct(
        private readonly ErrorFactoryInterface $errorFactory,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function make(ErrorIterableInterface|ErrorInterface|iterable|string $errorOrErrors): ErrorIterableInterface
    {
        if ($errorOrErrors instanceof ErrorIterableInterface) {
            return $errorOrErrors;
        }

        if (is_iterable($errorOrErrors)) {
            $errors = [];
            foreach ($errorOrErrors as $value) {
                $errors[] = $this->errorFactory->make($value);
            }
            return new ListOfErrors(...$errors);
        }

        return new ListOfErrors(
            $this->errorFactory->make($errorOrErrors),
        );
    }
}
