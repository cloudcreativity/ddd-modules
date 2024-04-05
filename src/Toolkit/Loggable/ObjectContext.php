<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Toolkit\Loggable;

final class ObjectContext implements ContextProviderInterface
{
    /**
     * @param object $source
     * @return self
     */
    public static function from(object $source): self
    {
        return new self($source);
    }

    /**
     * ObjectContext constructor.
     *
     * @param object $source
     */
    public function __construct(private readonly object $source)
    {
    }

    /**
     * @inheritDoc
     */
    public function context(): array
    {
        if ($this->source instanceof ContextProviderInterface) {
            return $this->source->context();
        }

        return get_object_vars($this->source);
    }
}
