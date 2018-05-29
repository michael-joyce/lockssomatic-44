<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Tests\Services;

use AppBundle\DataFixtures\ORM\LoadPln;
use AppBundle\Entity\Au;
use AppBundle\Entity\AuProperty;
use AppBundle\Entity\Box;
use AppBundle\Entity\ContentOwner;
use AppBundle\Entity\ContentProvider;
use AppBundle\Entity\Deposit;
use AppBundle\Entity\Pln;
use AppBundle\Entity\Plugin;
use AppBundle\Services\ConfigUpdater;
use Doctrine\Common\Collections\ArrayCollection;
use Nines\UtilBundle\Tests\Util\BaseTestCase;
use org\bovigo\vfs\vfsStreamDirectory;
use ReflectionProperty;

/**
 * Description of ConfigUpdaterTest
 *
 * @author michael
 */
class ConfigUpdaterTest extends BaseTestCase {

    /**
     * @var ConfigUpdater
     */
    private $updater;

    /**
     * @var vfsStreamDirectory
     */
    private $root;

    protected function setUp() {
        parent::setUp();
        $this->updater = $this->container->get(ConfigUpdater::class);
    }

    protected function getFixtures() {
        return [
            LoadPln::class,
        ];
    }

    public function testSanity() {
        $this->assertInstanceOf(ConfigUpdater::class, $this->updater);
    }

    public function testUpdatePeerList() {
        $box = new Box();
        $box->setIpAddress("127.0.0.1");
        $box->setPort(1234);
        $box->setProtocol("TCP");
        $pln = new Pln();
        $pln->addBox($box);

        $this->updater->updatePeerList($pln);
        $this->assertEquals(['TCP:[127.0.0.1]:1234'], $pln->getProperty('org.lockss.id.initialV3PeerList'));
    }

    public function testUpdateTitleDbs() {
        $pln = new Pln();
        $plnRef = new ReflectionProperty(Pln::class, 'id');
        $plnRef->setAccessible(true);
        $plnRef->setValue($pln, 1);

        $owner = new ContentOwner();
        $ownerRef = new ReflectionProperty(ContentOwner::class, 'id');
        $ownerRef->setAccessible(true);
        $ownerRef->setValue($owner, 3);

        $provider = new ContentProvider();
        $providerRef = new ReflectionProperty(ContentProvider::class, 'id');
        $providerRef->setAccessible(true);
        $providerRef->setValue($provider, 5);
        $provider->setContentOwner($owner);
        $provider->setPln($pln);
        $provider->setPermissionUrl('http://example.com/permission');
        $pln->addContentProvider($provider);

        $au = new Au();
        $auRef = new ReflectionProperty(Au::class, 'id');
        $auRef->setAccessible(true);
        $auRef->setValue($au, 7);
        $au->setPln($pln);
        $au->setContentProvider($provider);
        $provider->setAus(new ArrayCollection([$au]));

        $this->updater->updateTitleDbs($pln);
        $this->assertEquals(
            ['http://localhost/lom2/web/app_dev.php/plnconfigs/1/titledbs/3/5/titledb_1.xml'],
            $pln->getProperty('org.lockss.titleDbs')
        );
    }

    // If an AU already has properties, they should not be generated.
    public function testUpdateAuConfigsExistingProperties() {
        $pln = new Pln();
        $au = new Au();
        $au->setPln($pln);
        $property = new AuProperty();
        $au->addAuProperty($property);
        $pln->addAu($au);
        $this->updater->updateAuConfigs($pln);
        $this->assertEquals(1, $au->getAuProperties()->count());
    }

    public function buildContentItems(Au $au) {
        $deposit = $this->em->find(Deposit::class, 1);
        for ($i = 0; $i < 10; $i++) {
            $deposit = new Deposit();
            $deposit->setUrl("http://example.com/path/{$i}");
            $deposit->setTitle("Item {$i}");

            // definitional
            $deposit->setProperty('base_url', 'http://example.com/path');
            $deposit->setProperty('container_number', 1);
            $deposit->setProperty('permission_url', "http://example.com/permission/");
            $deposit->setProperty('manifest_url', "http://example.com/manifest/");
            //other properties.
            $deposit->setProperty('journalTitle', 'Journal Title');
            $deposit->setProperty('publisher', 'Journal Publisher');

            // deposit
            $deposit->setAu($au);
            $au->addDeposit($deposit);
            $this->em->persist($deposit);
        }
    }

    public function testUpdateAuConfigs() {
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getIdentifier')->will($this->returnValue('ca.example.plugin'));
        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
                    'base_url', 'container_number', 'manifest_url', 'permission_url'
        ]));
        $plugin->method('getNonDefinitionalProperties')->will($this->returnValue([]));

        $pln = new Pln();
        $plnRef = new ReflectionProperty(Pln::class, 'id');
        $plnRef->setAccessible(true);
        $plnRef->setValue($pln, 1);

        $owner = new ContentOwner();
        $ownerRef = new ReflectionProperty(ContentOwner::class, 'id');
        $ownerRef->setAccessible(true);
        $ownerRef->setValue($owner, 3);

        $provider = new ContentProvider();
        $providerRef = new ReflectionProperty(ContentProvider::class, 'id');
        $providerRef->setAccessible(true);
        $providerRef->setValue($provider, 5);
        $provider->setContentOwner($owner);
        $provider->setPln($pln);
        $provider->setPermissionUrl('http://example.com/permission');

        $au = new Au();
        $auRef = new ReflectionProperty(Au::class, 'id');
        $auRef->setAccessible(true);
        $auRef->setValue($au, 7);
        $au->setPln($pln);
        $pln->addAu($au);
        $au->setContentProvider($provider);

        $au->setPlugin($plugin);
        $this->buildContentItems($au);

        $this->updater->updateAuConfigs($pln);
        $this->assertEquals(23, count($au->getAuProperties()));
        $this->assertEquals('http://example.com', $au->getAuPropertyValue('base_url'));
        $this->assertEquals(1, $au->getAuPropertyValue('container_number'));
        $this->assertEquals('http://example.com/permission', $au->getAuPropertyValue('permission_url'));
        $this->assertStringEndsWith('plnconfigs/1/manifests/3/5/manifest_7.html', $au->getAuPropertyValue('manifest_url'));
    }

    public function testUpdateKeystoreLocationNoKeystore() {
        $pln = new Pln();
        $this->updater->updateKeystoreLocation($pln);
        $this->assertNull($pln->getProperty('org.lockss.plugin.keystore.location'));
    }

    public function testUpdateKeystoreLocationRemovedKeystore() {
        $pln = new Pln();
        $pln->setProperty('org.lockss.plugin.keystore.location', 'foo.keystore');
        $this->updater->updateKeystoreLocation($pln);
        $this->assertNull($pln->getProperty('org.lockss.plugin.keystore.location'));
    }

    public function testUpdateKeystoreLocation() {
        $pln = new Pln();
        $plnRef = new ReflectionProperty(Pln::class, 'id');
        $plnRef->setAccessible(true);
        $plnRef->setValue($pln, 1);
        $pln->setKeystore('/path/to/file.keystore');

        $this->updater->updateKeystoreLocation($pln);
        $this->assertEquals(
            'http://localhost/lom2/web/app_dev.php/plnconfigs/1/plugins/lockssomatic.keystore',
            $pln->getProperty('org.lockss.plugin.keystore.location')
        );
    }

    public function testUpdateAuthenticationEmpty() {
        $pln = new Pln();
        $this->updater->updateAuthentication($pln);
        $this->assertNull($pln->getProperty('org.lockss.ui.users.lomauth.user'));
    }

    public function testUpdateAuthentication() {
        $pln = new Pln();
        $pln->setUsername('example');
        $pln->setPassword('password');
        $this->updater->updateAuthentication($pln);
        $this->assertEquals(
            'example',
            $pln->getProperty('org.lockss.ui.users.lomauth.user')
        );
        $this->assertEquals(
            'SHA-256:5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8',
            $pln->getProperty('org.lockss.ui.users.lomauth.password')
        );
        $this->assertEquals(
            'accessContentRole',
            $pln->getProperty('org.lockss.ui.users.lomauth.roles')
        );
    }

    public function testUpdateContentUiDisabled() {
        $pln = new Pln();
        $pln->setEnableContentUi(false);
        $this->updater->updateContentUi($pln);
        $this->assertEquals('false', $pln->getProperty('org.lockss.contentui.start'));
    }

    public function testUpdateContentUiEnabled() {
        $pln = new Pln();
        $pln->setEnableContentUi(true);
        $pln->setContentPort('8123');
        $this->updater->updateContentUi($pln);
        $this->assertEquals('true', $pln->getProperty('org.lockss.contentui.start'));
        $this->assertEquals('8123', $pln->getProperty('org.lockss.contentui.port'));
    }

}
