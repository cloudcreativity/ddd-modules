<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Bus\Middleware;

use Closure;
use CloudCreativity\BalancedEvent\Common\Bus\QueryInterface;
use CloudCreativity\BalancedEvent\Common\Bus\Results\Result;
use CloudCreativity\BalancedEvent\Common\Bus\Results\ResultInterface;
use CloudCreativity\BalancedEvent\Common\Bus\Validation\QueryValidatorInterface;

abstract class ValidateQuery implements QueryMiddlewareInterface
{
    /**
     * Get the rules for the validation.
     *
     * @return iterable
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
