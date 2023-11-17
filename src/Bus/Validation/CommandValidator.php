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

use CloudCreativity\BalancedEvent\Common\Bus\CommandInterface;
use CloudCreativity\BalancedEvent\Common\Bus\Results\ErrorIterableInterface;
use CloudCreativity\BalancedEvent\Common\Bus\Results\ListOfErrors;

class CommandValidator extends AbstractValidator implements CommandValidatorInterface
{
    /**
     * @inheritDoc
     */
    public function validate(CommandInterface $command): ErrorIterableInterface
    {
        return $this->getPipeline()->process($command) ?? new ListOfErrors();
    }
}
