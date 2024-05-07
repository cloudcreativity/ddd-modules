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
use CloudCreativity\Modules\Contracts\Application\Bus\QueryMiddleware;
use CloudCreativity\Modules\Contracts\Application\Bus\Validator;
use CloudCreativity\Modules\Contracts\Application\Messages\Query;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result as IResult;
use CloudCreativity\Modules\Toolkit\Result\Result;

abstract class ValidateQuery implements QueryMiddleware
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
     * @param Validator $validator
     */
    public function __construct(private readonly Validator $validator)
    {
    }

    /**
     * @inheritDoc
     */
    public function __invoke(Query $query, Closure $next): IResult
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
