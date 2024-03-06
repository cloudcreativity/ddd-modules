<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Bus\Validation;

use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;
use CloudCreativity\Modules\Toolkit\Result\ListOfErrorsInterface;

interface CommandValidatorInterface extends ValidatorInterface
{
    /**
     * Validate the provided command.
     *
     * @param CommandInterface $command
     * @return ListOfErrorsInterface
     */
    public function validate(CommandInterface $command): ListOfErrorsInterface;
}
