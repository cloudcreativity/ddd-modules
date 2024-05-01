<?php

declare(strict_types=1);
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

namespace CloudCreativity\Modules\Contracts\Toolkit\Identifiers;

interface IdentifierFactory
{
    /**
     * Make an identifier.
     *
     * @param mixed $id
     * @return Identifier
     */
    public function make(mixed $id): Identifier;
}
