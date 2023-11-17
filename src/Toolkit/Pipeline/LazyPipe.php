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

use RuntimeException;
use Throwable;

class LazyPipe
{
    /**
     * LazyPipe constructor.
     *
     * @param PipeContainerInterface $container
     * @param string $pipeName
     */
    public function __construct(
        private readonly PipeContainerInterface $container,
        private readonly string $pipeName,
    ) {
    }

    /**
     * @param mixed ...$args
     * @return mixed
     */
    public function __invoke(mixed ...$args): mixed
    {
        try {
            $pipe = $this->container->get($this->pipeName);
        } catch (Throwable $ex) {
            throw new RuntimeException(sprintf(
                'Failed to get pipe "%s" from container.',
                $this->pipeName,
            ), 0, $ex);
        }

        return $pipe(...$args);
    }
}
