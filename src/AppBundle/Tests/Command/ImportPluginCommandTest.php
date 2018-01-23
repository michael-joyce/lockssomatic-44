<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Tests\Command;

use Nines\UtilBundle\Tests\Util\BaseTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ImportPluginCommandTest extends BaseTestCase
{
    const NAME = 'lom:import:plugin';
    
    const JARS = [
        'src/AppBundle/Tests/Data/DummyPlugin.jar',
    ];

    /**
     * @var CommandTester
     */
    private $tester;
    
    public function setUp() {
        parent::setUp();
        $application = new Application(static::$kernel);
        $command = $application->find(self::NAME);
        $this->tester = new CommandTester($command);
    }
    
    public function testSanity() {
        $this->tester->execute([
            'command' => self::NAME,
            'files' => []
        ]);
        $this->assertEquals('', $this->tester->getDisplay());
    }
    
    public function testExecute() {
        $this->tester->execute([
            'command' => self::NAME,
            'files' => self::JARS
        ]);
        $this->assertEquals(implode("\n", self::JARS) . "\n", $this->tester->getDisplay());
    }
}
