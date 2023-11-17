<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Tests\Unit\Common\Toolkit\Pipeline;

use CloudCreativity\BalancedEvent\Common\Toolkit\Pipeline\PipeContainer;
use PHPUnit\Framework\TestCase;

class PipeContainerTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $a = fn() => 1;
        $b = fn() => 2;

        $container = new PipeContainer();
        $container->bind('PipeA', fn() => $a);
        $container->bind('PipeB', fn() => $b);

        $this->assertSame($a, $container->get('PipeA'));
        $this->assertSame($b, $container->get('PipeB'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unrecognised pipe name: PipeC');

        $container->get('PipeC');
    }
}