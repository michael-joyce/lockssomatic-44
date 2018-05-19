<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Tests\Services;

use AppBundle\DataFixtures\ORM\LoadDeposit;
use AppBundle\DataFixtures\ORM\LoadPln;
use AppBundle\Entity\Au;
use AppBundle\Entity\ContentProvider;
use AppBundle\Entity\Deposit;
use AppBundle\Entity\Pln;
use AppBundle\Services\AuValidator;
use AppBundle\Services\PluginImporter;
use DateTime;
use Nines\UtilBundle\Tests\Util\BaseTestCase;

/**
 * Description of AuPropertyGeneratorTest
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class AuValidatorTest extends BaseTestCase {

    /**
     * @var AuValidator
     */
    private $validator;

    protected function getFixtures() {
        return [
            LoadPln::class,
            LoadDeposit::class,
        ];
    }

    public function setUp() {
        parent::setUp();
        $this->validator = $this->container->get(AuValidator::class);
    }

    public function testSanity() {
        $this->assertInstanceOf(AuValidator::class, $this->validator);
    }

    /*
     * The references to fixtures in the test below keep getting lost, resulting
     * in strange error messages like
     * "Undefined index: 00000000763d670d000000016f52a1c9"
     *
     * So manually fetch all the content from the database for this test.
     */
    public function testValidateSingle() {
        $importer = $this->container->get(PluginImporter::class);
        $xml = simplexml_load_string($this->xmlData());
        $plugin = $importer->buildPlugin($xml);
        $au = new Au();
        // should be $this->getReference('provider.1') but that fails.
        $au->setContentProvider($this->em->find(ContentProvider::class, 1));
        $au->setPln($this->em->find(Pln::class, 1));
        $au->setPlugin($plugin);
        $au->setAuid('1b');
        $this->em->persist($au);

        $deposit = $this->em->find(Deposit::class, 1);
        $deposit->setUrl("http://example.com/path/1");
        $deposit->setTitle("Item 1");
        $deposit->setProperty('base_url', 'http://example.com/path');
        $deposit->setProperty('container_number', 1);
        $deposit->setProperty('permission_url', "http://example.com/permission/1");
        $deposit->setAu($au);
        $this->em->persist($deposit);
        $this->em->flush();

        $this->assertEquals(0, $this->validator->validate($au));
    }

    /*
     * The references to fixtures in the test below keep getting lost, resulting
     * in strange error messages like
     * "Undefined index: 00000000763d670d000000016f52a1c9"
     *
     * So manually fetch all the content from the database for this test.
     */
    public function testValidate() {
        $importer = $this->container->get(PluginImporter::class);
        $xml = simplexml_load_string($this->xmlData());
        $plugin = $importer->buildPlugin($xml);
        $au = new Au();
        // should be $this->getReference('provider.1') but that fails.
        $au->setContentProvider($this->em->find(ContentProvider::class, 1));
        $au->setAuid('1b');
        $au->setPln($this->em->find(Pln::class, 1));
        $au->setPlugin($plugin);
        $this->em->persist($au);

        for($i = 0; $i < 10; $i++) {
            $deposit = new Deposit();
            $deposit->setUrl("http://example.com/path/{$i}");
            $deposit->setTitle("Item {$i}");
            $deposit->setProperty('base_url', 'http://example.com/path');
            $deposit->setProperty('container_number', 1);
            $deposit->setProperty('permission_url', "http://example.com/permission/{$i}");
            $deposit->setAu($au);
            $deposit->setContentProvider($this->em->find(ContentProvider::class, 1));
            $this->em->persist($deposit);
        }
        $this->em->flush();

        $this->assertEquals(0, $this->validator->validate($au));
    }

    public function testValidateFail() {
        $importer = $this->container->get(PluginImporter::class);
        $xml = simplexml_load_string($this->xmlData());
        $plugin = $importer->buildPlugin($xml);
        $au = new Au();
        // should be $this->getReference('provider.1') but that fails.
        $au->setContentProvider($this->em->find(ContentProvider::class, 1));
        $au->setAuid('1b');
        $au->setPln($this->em->find(Pln::class, 1));
        $au->setPlugin($plugin);
        $this->em->persist($au);

        for($i = 0; $i < 10; $i++) {
            $deposit = new Deposit();
            $deposit->setUrl("http://example.com/path/{$i}");
            $deposit->setTitle("Item {$i}");
            $deposit->setProperty('base_url', "http://example.com/path/{$i}");
            $deposit->setProperty('container_number', 1);
            $deposit->setProperty('permission_url', "http://example.com/permission/{$i}");
            $deposit->setAu($au);
            $deposit->setContentProvider($this->em->find(ContentProvider::class, 1));
            $this->em->persist($deposit);
        }
        $this->em->flush();

        $this->assertEquals(9, $this->validator->validate($au));
    }

    public function xmlData() {
        return <<<'ENDXML'
<map>
    <entry>
        <string>plugin_config_props</string>
        <list>
            <org.lockss.daemon.ConfigParamDescr>
                <key>base_url</key>
                <definitional>true</definitional>
            </org.lockss.daemon.ConfigParamDescr>
            <org.lockss.daemon.ConfigParamDescr>
                <key>container_number</key>
                <definitional>true</definitional>
            </org.lockss.daemon.ConfigParamDescr>
            <org.lockss.daemon.ConfigParamDescr>
                <key>manifest_url</key>
                <definitional>false</definitional>
            </org.lockss.daemon.ConfigParamDescr>
            <org.lockss.daemon.ConfigParamDescr>
                <key>permission_url</key>
                <definitional>false</definitional>
            </org.lockss.daemon.ConfigParamDescr>
        </list>
    </entry>
    <entry>
        <string>plugin_version</string>
        <string>2</string>
    </entry>
    <entry>
        <string>plugin_name</string>
        <string>Plugin</string>
    </entry>
    <entry>
        <string>plugin_identifier</string>
        <string>ca.sfu.lib.test.plugin</string>
    </entry>
</map>
ENDXML;
    }

}
