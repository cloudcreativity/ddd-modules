<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
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
