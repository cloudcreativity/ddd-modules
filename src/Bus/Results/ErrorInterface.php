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

use CloudCreativity\BalancedEvent\Common\Infrastructure\Log\ContextProviderInterface;
use Stringable;

interface ErrorInterface extends Stringable, ContextProviderInterface
{
    /**
     * Get the error key.
     *
     * @return string|null
     */
    public function key(): ?string;

    /**
     * Get the error detail.
     *
     * @return string
     */
    public function message(): string;

    /**
     * Get the error code.
     *
     * @return mixed|null
     */
    public function code(): mixed;
}
