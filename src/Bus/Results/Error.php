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

use InvalidArgumentException;

class Error implements ErrorInterface
{
    /**
     * @var string|null
     */
    private ?string $key;

    /**
     * @var string
     */
    private string $message;

    /**
     * @var mixed|null
     */
    private mixed $code;

    /**
     * Error constructor.
     *
     * @param string|null $key
     * @param string $message
     * @param mixed|null $code
     */
    public function __construct(?string $key, string $message, mixed $code = null)
    {
        if (empty($message)) {
            throw new InvalidArgumentException('Expecting a non-empty error message.');
        }

        $this->key = $key ?: null;
        $this->message = $message;
        $this->code = $code;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->message();
    }

    /**
     * @return string|null
     */
    public function key(): ?string
    {
        return $this->key;
    }

    /**
     * @inheritDoc
     */
    public function message(): string
    {
        return $this->message;
    }

    /**
     * @inheritDoc
     */
    public function code(): mixed
    {
        return $this->code;
    }

    /**
     * @inheritDoc
     */
    public function context(): array
    {
        return array_filter([
            'key' => $this->key,
            'message' => $this->message,
            'code' => $this->code,
        ], static fn($value) => $value !== null);
    }
}
