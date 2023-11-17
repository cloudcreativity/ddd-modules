<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Toolkit\Pipeline;

interface PipelineInterface
{
    /**
     * Process the payload.
     *
     * @param mixed $payload
     * @return mixed
     */
    public function __invoke(mixed $payload): mixed;

    /**
     * Create a new pipeline with the appended stage.
     *
     * @param callable $stage
     * @return PipelineInterface
     */
    public function pipe(callable $stage): self;

    /**
     * Process the payload through the pipeline.
     *
     * @param mixed $payload
     * @return mixed
     */
    public function process(mixed $payload): mixed;
}
