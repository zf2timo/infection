<?php
/**
 * This code is licensed under the BSD 3-Clause License.
 *
 * Copyright (c) 2017-2018, Maks Rafalko
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * * Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 * * Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 * * Neither the name of the copyright holder nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

declare(strict_types=1);

namespace Infection\Tests\Config\ValueProvider;

use Infection\Config\ConsoleHelper;
use Infection\Config\ValueProvider\PhpUnitCustomExecutablePathProvider;
use Infection\Finder\Exception\FinderException;
use Infection\Finder\TestFrameworkFinder;
use Mockery;
use Symfony\Component\Console\Exception\RuntimeException as SymfonyRuntimeException;
use function Infection\Tests\normalizePath as p;

/**
 * @internal
 */
final class PhpUnitCustomExecutablePathProviderTest extends AbstractBaseProviderTest
{
    public function test_it_returns_null_if_executable_is_found(): void
    {
        $finderMock = Mockery::mock(TestFrameworkFinder::class);
        $finderMock->shouldReceive('find')->once();

        $provider = new PhpUnitCustomExecutablePathProvider(
            $finderMock,
            $this->createMock(ConsoleHelper::class),
            $this->getQuestionHelper()
        );

        $result = $provider->get($this->createStreamableInputInterfaceMock(), $this->createOutputInterface());

        $this->assertNull($result);
    }

    public function test_it_asks_question_if_no_config_is_found_in_current_dir(): void
    {
        $finderMock = Mockery::mock(TestFrameworkFinder::class);
        $finderMock->shouldReceive('find')->once()->andThrow(new FinderException());

        $consoleMock = $this->createMock(ConsoleHelper::class);
        $consoleMock->expects($this->once())->method('getQuestion')->willReturn('foobar');

        $provider = new PhpUnitCustomExecutablePathProvider(
            $finderMock,
            $consoleMock,
            $this->getQuestionHelper()
        );

        $customExecutable = p(realpath(__DIR__ . '/../../Fixtures/Files/phpunit/phpunit.phar'));

        $path = $provider->get(
            $this->createStreamableInputInterfaceMock($this->getInputStream("{$customExecutable}\n")),
            $this->createOutputInterface()
        );

        $this->assertSame($customExecutable, $path);
    }

    public function test_validates_incorrect_dir(): void
    {
        if (!$this->hasSttyAvailable()) {
            $this->markTestSkipped('Stty is not available');
        }

        $finderMock = Mockery::mock(TestFrameworkFinder::class);

        $finderMock->shouldReceive('find')->once()->andThrow(new FinderException());

        $consoleMock = $this->createMock(ConsoleHelper::class);
        $consoleMock->expects($this->once())->method('getQuestion')->willReturn('foobar');

        $provider = new PhpUnitCustomExecutablePathProvider(
            $finderMock,
            $consoleMock,
            $this->getQuestionHelper()
        );

        $this->expectException(SymfonyRuntimeException::class);

        $provider->get(
            $this->createStreamableInputInterfaceMock($this->getInputStream("abc\n")),
            $this->createOutputInterface()
        );
    }
}
