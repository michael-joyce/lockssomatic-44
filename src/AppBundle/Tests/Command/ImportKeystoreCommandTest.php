<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Tests\Command;

use AppBundle\DataFixtures\ORM\LoadPln;
use AppBundle\Entity\Pln;
use Nines\UtilBundle\Tests\Util\BaseTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ImportKeystoreCommandTest extends BaseTestCase
{
    const NAME = 'lom:import:keystore';
    
    const KEYSTORE = 'src/AppBundle/Tests/Data/dummy.keystore';

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
    
    public function getFixtures() {
        return [
            LoadPln::class,
        ];
    }
    
    public function testExecute() {
        $this->tester->execute([
            'command' => self::NAME,
            'plnId' => 1,
            'path' => self::KEYSTORE,
        ]);
        $pln = $this->getDoctrine()->find(Pln::class, 1);
        $this->assertEquals(basename(self::KEYSTORE), $pln->getKeystoreFilename());
    }
}
