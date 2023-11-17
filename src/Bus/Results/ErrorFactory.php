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
