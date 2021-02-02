<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Command;

use Nines\UtilBundle\Tests\ControllerBaseCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ImportPluginCommandTest extends ControllerBaseCase {
    public const NAME = 'lom:import:plugin';

    public const JARS = [
        'tests/Data/DummyPlugin.jar',
    ];

    /**
     * @var CommandTester
     */
    private $tester;

    public function testSanity() : void {
        $this->tester->execute([
            'command' => self::NAME,
            'files' => [],
        ]);
        $this->assertSame('', $this->tester->getDisplay());
    }

    public function testExecute() : void {
        $this->tester->execute([
            'command' => self::NAME,
            'files' => self::JARS,
        ]);
        $this->assertSame(implode("\n", self::JARS) . "\n", $this->tester->getDisplay());
    }

    public function setup() : void {
        parent::setUp();
        $application = new Application(static::$kernel);
        $command = $application->find(self::NAME);
        $this->tester = new CommandTester($command);
    }
}
