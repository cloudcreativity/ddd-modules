<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Bus\Middleware;

use Closure;
use CloudCreativity\Modules\Bus\Validation\WithRules;
use CloudCreativity\Modules\Contracts\Bus\Middleware\BusMiddleware;
use CloudCreativity\Modules\Contracts\Bus\Validation\Bail;
use CloudCreativity\Modules\Contracts\Bus\Validation\Validator;
use CloudCreativity\Modules\Contracts\Messaging\Message;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result as IResult;
use CloudCreativity\Modules\Toolkit\Result\Result;
use ReflectionClass;

abstract class ValidateMessage implements BusMiddleware
{
    public function __construct(private readonly Validator $validator)
    {
    }

    public function __invoke(Message $message, Closure $next): ?IResult
    {
        $errors = $this->validator
            ->using($this->rules())
            ->stopOnFirstFailure($this->stopOnFirstFailure($message))
            ->validate($message);

        if ($errors->isNotEmpty()) {
            return Result::failed($errors);
        }

        return $next($message);
    }

    /**
     * Get the rules for the validation.
     *
     * @return iterable<callable|string>
     */
    protected function rules(): iterable
    {
        $reflection = new ReflectionClass($this);

        foreach ($reflection->getAttributes(WithRules::class) as $attribute) {
            yield from $attribute->newInstance()->rules;
        }
    }

    protected function stopOnFirstFailure(Message $message): bool
    {
        return $this instanceof Bail;
    }
}
