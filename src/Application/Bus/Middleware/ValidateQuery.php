<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\Bus\Middleware;

use Closure;
use CloudCreativity\Modules\Application\Bus\Validation\QueryValidatorInterface;
use CloudCreativity\Modules\Toolkit\Messages\QueryInterface;
use CloudCreativity\Modules\Toolkit\Result\Result;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;

abstract class ValidateQuery implements QueryMiddlewareInterface
{
    /**
     * Get the rules for the validation.
     *
     * @return iterable<string|callable>
     */
    abstract protected function rules(): iterable;

    /**
     * ValidateQuery constructor.
     *
     * @param QueryValidatorInterface $validator
     */
    public function __construct(private readonly QueryValidatorInterface $validator)
    {
    }

    /**
     * @inheritDoc
     */
    public function __invoke(QueryInterface $query, Closure $next): ResultInterface
    {
        $errors = $this->validator
            ->using($this->rules())
            ->validate($query);

        if ($errors->isNotEmpty()) {
            return Result::failed($errors);
        }

        return $next($query);
    }
}
