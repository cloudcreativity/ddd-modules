<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Toolkit\Loggable;

use CloudCreativity\Modules\Contracts\Toolkit\Loggable\ContextProvider;
use CloudCreativity\Modules\Contracts\Toolkit\Loggable\Contextual;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Error;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;

final readonly class ResultDecorator implements ContextProvider
{
    /**
     * ResultDecorator constructor.
     *
     * @param Result<mixed> $result
     */
    public function __construct(private Result $result)
    {
    }

    /**
     * @inheritDoc
     */
    public function context(): array
    {
        if ($this->result instanceof ContextProvider) {
            return $this->result->context();
        }

        $value = $this->result->safe();
        $errors = $this->errors();
        $error = null;

        if (
            count($errors) === 1 &&
            count($errors[0]) === 1 &&
            (isset($errors[0]['message']) || isset($errors[0]['code']))
        ) {
            $error = $errors[0]['message'] ?? $errors[0]['code'];
            $errors = null;
        }

        return array_filter([
            'success' => $this->result->didSucceed(),
            'value' => match(true) {
                $value instanceof ContextProvider => $value->context(),
                $value instanceof Contextual => $value->context(),
                is_scalar($value) => $value,
                default => null,
            },
            'error' => $error,
            'errors' => $errors ?: null,
            'meta' => $this->result->meta()->all() ?: null,
        ], static fn ($value) => $value !== null);
    }

    /**
     * @return array<array<string, mixed>>
     */
    private function errors(): array
    {
        return array_map(
            fn (Error $error): array => $this->error($error),
            $this->result->errors()->all(),
        );
    }

    /**
     * @param Error $error
     * @return array<string, mixed>
     */
    private function error(Error $error): array
    {
        if ($error instanceof ContextProvider) {
            return $error->context();
        }

        return array_filter([
            'code' => $error->code()?->value,
            'key' => $error->key(),
            'message' => $error->message(),
        ]);
    }
}
