<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Toolkit\Pipeline;

use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipelineBuilder as IPipelineBuilder;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\Processor;
use RuntimeException;

final class PipelineBuilder implements IPipelineBuilder
{
    /**
     * @var callable[]
     */
    private array $stages = [];

    /**
     * Fluent constructor.
     *
     * @param PipeContainer|null $container
     * @return self
     */
    public static function make(?PipeContainer $container = null): self
    {
        return new self($container);
    }

    /**
     * PipelineBuilder constructor.
     *
     * @param PipeContainer|null $container
     */
    public function __construct(private readonly ?PipeContainer $container = null)
    {
    }

    /**
     * @inheritDoc
     */
    public function add(callable|string $stage): static
    {
        $this->stages[] = $this->normalize($stage);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function through(iterable $stages): static
    {
        foreach ($stages as $stage) {
            $this->add($stage);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function build(?Processor $processor = null): Pipeline
    {
        return new Pipeline($processor, $this->stages);
    }

    /**
     * @param callable|string $stage
     * @return callable
     */
    private function normalize(callable|string $stage): callable
    {
        if (is_callable($stage)) {
            return $stage;
        }

        if (is_string($stage) && $this->container) {
            return new LazyPipe($this->container, $stage);
        }

        throw new RuntimeException('Cannot use a string pipe name without a pipe container.');
    }
}
