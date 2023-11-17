<?php
/*
 * Copyright 2023 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Bus\Validation;

use CloudCreativity\Modules\Bus\CommandInterface;
use CloudCreativity\Modules\Bus\Results\ErrorIterableInterface;
use CloudCreativity\Modules\Bus\Results\ListOfErrors;

final class CommandValidator extends AbstractValidator implements CommandValidatorInterface
{
    /**
     * @inheritDoc
     */
    public function validate(CommandInterface $command): ErrorIterableInterface
    {
        return $this->getPipeline()->process($command) ?? new ListOfErrors();
    }
}
