<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Toolkit\Result;

use CloudCreativity\Modules\Contracts\Toolkit\Contextual;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Error;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;

final readonly class ContextualResult implements Contextual
{
    /**
     * @param Result<mixed> $result
     */
    public function __construct(private Result $result)
    {
    }

    public function success(): bool
    {
        return $this->result->didSucceed();
    }

    public function value(): mixed
    {
        $value = $this->result->safe();

        if ($value instanceof Contextual) {
            return $value->context();
        }

        // do not return strings as we do not know if they are sensitive or not.
        if (is_bool($value) || is_int($value) || is_float($value)) {
            return $value;
        }

        return null;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function meta(): ?array
    {
        $meta = $this->result->meta();

        if ($meta->isNotEmpty()) {
            return $meta->all();
        }

        return null;
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function errors(): array
    {
        return array_map(
            fn (Error $error): array => $this->error($error),
            $this->result->errors()->all(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        $errors = $this->errors();

        return array_filter([
            'success' => $this->success(),
            'value' => $this->value(),
            'error' => count($errors) === 1 ? $errors[0] : null,
            'errors' => count($errors) > 1 ? $errors : null,
            'meta' => $this->meta(),
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    private function error(Error $error): array
    {
        return array_filter([
            'code' => $error->code(),
            'key' => $error->key(),
            'message' => $error->message(),
        ]);
    }
}
