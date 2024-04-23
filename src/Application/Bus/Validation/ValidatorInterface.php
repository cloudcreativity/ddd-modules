<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\Bus\Validation;

interface ValidatorInterface
{
    /**
     * Set the rules for the validation.
     *
     * @param iterable<string|callable> $rules
     * @return $this
     */
    public function using(iterable $rules): self;
}
