<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Tests\Unit\Http;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Prophecy\Argument;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Http\MiddlewareStackResolver;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class MiddlewareStackResolverTest extends UnitTestCase
{
    /**
     * @test
     */
    public function resolveReturnsMiddlewareStack()
    {
        $package1 = $this->prophesize(Package::class);
        $package1->getPackagePath()->willReturn(__DIR__ . '/' . 'Fixtures/Package1/');
        $package2 = $this->prophesize(Package::class);
        $package2->getPackagePath()->willReturn(__DIR__ . '/' . 'Fixtures/Package2/');
        $packageManagerProphecy = $this->prophesize(PackageManager::class);
        $packageManagerProphecy->getActivePackages()->willReturn([$package1->reveal(), $package2->reveal()]);
        $dependencyOrderingServiceProphecy = $this->prophesize(DependencyOrderingService::class);
        $dependencyOrderingServiceProphecy->orderByDependencies(Argument::cetera())->willReturnArgument(0);
        $phpFrontendCacheProphecy = $this->prophesize(PhpFrontend::class);
        $phpFrontendCacheProphecy->has(Argument::cetera())->willReturn(false);
        $phpFrontendCacheProphecy->set(Argument::cetera())->willReturn(false);

        $subject = new MiddlewareStackResolver(
            $packageManagerProphecy->reveal(),
            $dependencyOrderingServiceProphecy->reveal(),
            $phpFrontendCacheProphecy->reveal()
        );
        $expected = [
            'secondMiddleware' => 'anotherClassName',
            'firstMiddleware' => 'aClassName',
        ];
        $this->assertEquals($expected, $subject->resolve('testStack'));
    }

    /**
     * @test
     */
    public function resolveAllowsDisablingAMiddleware()
    {
        $package1 = $this->prophesize(Package::class);
        $package1->getPackagePath()->willReturn(__DIR__ . '/' . 'Fixtures/Package1/');
        $package2 = $this->prophesize(Package::class);
        $package2->getPackagePath()->willReturn(__DIR__ . '/' . 'Fixtures/Package2Disables1/');
        $packageManagerProphecy = $this->prophesize(PackageManager::class);
        $packageManagerProphecy->getActivePackages()->willReturn([$package1->reveal(), $package2->reveal()]);
        $dependencyOrderingServiceProphecy = $this->prophesize(DependencyOrderingService::class);
        $dependencyOrderingServiceProphecy->orderByDependencies(Argument::cetera())->willReturnArgument(0);
        $phpFrontendCacheProphecy = $this->prophesize(PhpFrontend::class);
        $phpFrontendCacheProphecy->has(Argument::cetera())->willReturn(false);
        $phpFrontendCacheProphecy->set(Argument::cetera())->willReturn(false);

        $subject = new MiddlewareStackResolver(
            $packageManagerProphecy->reveal(),
            $dependencyOrderingServiceProphecy->reveal(),
            $phpFrontendCacheProphecy->reveal()
        );
        $expected = [
            // firstMiddleware is missing, RequestMiddlewares.php of Package2 sets disables=true on firstMiddleware
            'secondMiddleware' => 'anotherClassName',
        ];
        $this->assertEquals($expected, $subject->resolve('testStack'));
    }
}
