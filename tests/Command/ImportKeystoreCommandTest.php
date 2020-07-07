<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace AppBundle\Tests\Command;

use AppBundle\DataFixtures\ORM\LoadPln;
use AppBundle\Entity\Pln;
use Nines\UtilBundle\Tests\Util\BaseTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ImportKeystoreCommandTest extends BaseTestCase {
    public const NAME = 'lom:import:keystore';

    public const KEYSTORE = 'src/AppBundle/Tests/Data/dummy.keystore';

    /**
     * @var CommandTester
     */
    private $tester;

    public function getFixtures() {
        return [
            LoadPln::class,
        ];
    }

    public function testExecute() : void {
        $this->tester->execute([
            'command' => self::NAME,
            'plnId' => 1,
            'path' => self::KEYSTORE,
        ]);
        $pln = $this->getDoctrine()->find(Pln::class, 1);
        $this->assertSame(basename(self::KEYSTORE), $pln->getKeystoreFilename());
    }

    public function setup() : void {
        parent::setUp();
        $application = new Application(static::$kernel);
        $command = $application->find(self::NAME);
        $this->tester = new CommandTester($command);
    }
}
