<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Bus\Validation;

use CloudCreativity\BalancedEvent\Common\Bus\QueryInterface;
use CloudCreativity\BalancedEvent\Common\Bus\Results\ErrorIterableInterface;
use CloudCreativity\BalancedEvent\Common\Bus\Results\ListOfErrors;

class QueryValidator extends AbstractValidator implements QueryValidatorInterface
{
    /**
     * @inheritDoc
     */
    public function validate(QueryInterface $query): ErrorIterableInterface
    {
        return $this->getPipeline()->process($query) ?? new ListOfErrors();
    }
}
