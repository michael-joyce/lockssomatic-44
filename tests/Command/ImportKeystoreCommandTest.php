<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Command;

use App\DataFixtures\PlnFixtures;
use App\Entity\Pln;
use Nines\UtilBundle\Tests\ControllerBaseCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ImportKeystoreCommandTest extends ControllerBaseCase {
    public const NAME = 'lom:import:keystore';

    public const KEYSTORE = 'tests/Data/dummy.keystore';

    /**
     * @var CommandTester
     */
    private $tester;

    public function fixtures() : array {
        return [
            PlnFixtures::class,
        ];
    }

    public function testExecute() : void {
        $this->tester->execute([
            'command' => self::NAME,
            'plnId' => 1,
            'path' => self::KEYSTORE,
        ]);
        $pln = $this->entityManager->find(Pln::class, 1);
        $this->assertSame(basename(self::KEYSTORE), $pln->getKeystoreFilename());
    }

    public function setup() : void {
        parent::setUp();
        $application = new Application(static::$kernel);
        $command = $application->find(self::NAME);
        $this->tester = new CommandTester($command);
    }
}
