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

use CloudCreativity\Modules\Toolkit\Messages\QueryInterface;
use CloudCreativity\Modules\Toolkit\Result\ListOfErrorsInterface;

interface QueryValidatorInterface extends ValidatorInterface
{
    /**
     * Validate the provided query.
     *
     * @param QueryInterface $query
     * @return ListOfErrorsInterface
     */
    public function validate(QueryInterface $query): ListOfErrorsInterface;
}
