<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Services;

use App\DataFixtures\PlnFixtures;
use App\Entity\Au;
use App\Entity\AuProperty;
use App\Entity\Box;
use App\Entity\ContentOwner;
use App\Entity\ContentProvider;
use App\Entity\Deposit;
use App\Entity\Pln;
use App\Entity\Plugin;
use App\Services\ConfigUpdater;
use Doctrine\Common\Collections\ArrayCollection;
use Nines\UtilBundle\Tests\ControllerBaseCase;
use ReflectionProperty;

/**
 * Description of ConfigUpdaterTest.
 *
 * @author michael
 */
class ConfigUpdaterTest extends ControllerBaseCase {
    /**
     * @var ConfigUpdater
     */
    private $updater;

    protected function fixtures() : array {
        return [
            PlnFixtures::class,
        ];
    }

    public function testContainer() : void {
        $this->assertInstanceOf(ConfigUpdater::class, $this->updater);
    }

    public function testUpdatePeerList() : void {
        $box = new Box();
        $box->setIpAddress('127.0.0.1');
        $box->setPort(1234);
        $box->setProtocol('TCP');
        $pln = new Pln();
        $pln->addBox($box);

        $this->updater->updatePeerList($pln);
        $this->assertSame(['TCP:[127.0.0.1]:1234'], $pln->getProperty('org.lockss.id.initialV3PeerList'));
    }

    public function testUpdateTitleDbs() : void {
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
        $this->assertStringEndsWith('/plnconfigs/1/titledbs/3/5/titledb_1.xml', $pln->getProperty('org.lockss.titleDbs')[0]);
    }

    // If an AU already has properties, they should not be generated.
    public function testUpdateAuConfigsExistingProperties() : void {
        $pln = new Pln();
        $au = new Au();
        $au->setPln($pln);
        $property = new AuProperty();
        $au->addAuProperty($property);
        $pln->addAu($au);
        $this->updater->updateAuConfigs($pln);
        $this->assertSame(1, $au->getAuProperties()->count());
    }

    public function buildContentItems(Au $au) : void {
        $deposit = $this->entityManager->find(Deposit::class, 1);

        for ($i = 0; $i < 10; $i++) {
            $deposit = new Deposit();
            $deposit->setUrl("http://example.com/path/{$i}");
            $deposit->setTitle("Item {$i}");

            // definitional
            $deposit->setProperty('base_url', 'http://example.com/path');
            $deposit->setProperty('container_number', 1);
            $deposit->setProperty('permission_url', 'http://example.com/permission/');
            $deposit->setProperty('manifest_url', 'http://example.com/manifest/');
            //other properties.
            $deposit->setProperty('journalTitle', 'Journal Title');
            $deposit->setProperty('publisher', 'Journal Publisher');

            // deposit
            $deposit->setAu($au);
            $au->addDeposit($deposit);
            $this->entityManager->persist($deposit);
        }
    }

    public function testUpdateAuConfigs() : void {
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getIdentifier')->willReturn('ca.example.plugin');
        $plugin->method('getDefinitionalPropertyNames')->willReturn([
            'base_url', 'container_number', 'manifest_url', 'permission_url',
        ]);
        $plugin->method('getNonDefinitionalProperties')->willReturn([]);

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
        $this->assertCount(23, $au->getAuProperties());
        $this->assertSame('http://example.com', $au->getAuPropertyValue('base_url'));
        $this->assertSame(1, $au->getAuPropertyValue('container_number'));
        $this->assertSame('http://example.com/permission', $au->getAuPropertyValue('permission_url'));
        $this->assertStringEndsWith('plnconfigs/1/manifests/3/5/manifest_7.html', $au->getAuPropertyValue('manifest_url'));
    }

    public function testUpdateKeystoreLocationNoKeystore() : void {
        $pln = new Pln();
        $this->updater->updateKeystoreLocation($pln);
        $this->assertNull($pln->getProperty('org.lockss.plugin.keystore.location'));
    }

    public function testUpdateKeystoreLocationRemovedKeystore() : void {
        $pln = new Pln();
        $pln->setProperty('org.lockss.plugin.keystore.location', 'foo.keystore');
        $this->updater->updateKeystoreLocation($pln);
        $this->assertNull($pln->getProperty('org.lockss.plugin.keystore.location'));
    }

    public function testUpdateKeystoreLocation() : void {
        $pln = new Pln();
        $plnRef = new ReflectionProperty(Pln::class, 'id');
        $plnRef->setAccessible(true);
        $plnRef->setValue($pln, 1);
        $pln->setKeystore('/path/to/file.keystore');

        $this->updater->updateKeystoreLocation($pln);
        $this->assertStringEndsWith('/plnconfigs/1/plugins/lockssomatic.keystore', $pln->getProperty('org.lockss.plugin.keystore.location'));
    }

    public function testUpdateAuthenticationEmpty() : void {
        $pln = new Pln();
        $this->updater->updateAuthentication($pln);
        $this->assertNull($pln->getProperty('org.lockss.ui.users.lomauth.user'));
    }

    public function testUpdateAuthentication() : void {
        $pln = new Pln();
        $pln->setUsername('example');
        $pln->setPassword('password');
        $this->updater->updateAuthentication($pln);
        $this->assertSame(
            'example',
            $pln->getProperty('org.lockss.ui.users.lomauth.user')
        );
        $this->assertSame(
            'SHA-256:5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8',
            $pln->getProperty('org.lockss.ui.users.lomauth.password')
        );
        $this->assertSame(
            'accessContentRole',
            $pln->getProperty('org.lockss.ui.users.lomauth.roles')
        );
    }

    public function testUpdateContentUiDisabled() : void {
        $pln = new Pln();
        $pln->setEmail('pln@example.com');
        $pln->setEnableContentUi(false);
        $this->updater->updateContentUi($pln);
        $this->assertSame('false', $pln->getProperty('org.lockss.contentui.start'));
        $this->assertSame('pln@example.com', $pln->getProperty('org.lockss.ui.contactEmail'));
    }

    public function testUpdateContentUiEnabled() : void {
        $pln = new Pln();
        $pln->setEmail('pln@example.com');
        $pln->setEnableContentUi(true);
        $pln->setContentPort('8123');
        $this->updater->updateContentUi($pln);
        $this->assertSame('true', $pln->getProperty('org.lockss.contentui.start'));
        $this->assertSame('8123', $pln->getProperty('org.lockss.contentui.port'));
        $this->assertSame('pln@example.com', $pln->getProperty('org.lockss.ui.contactEmail'));
    }

    public function testUpdatePluginRegistries() : void {
        $pln = new Pln();
        $plnRef = new ReflectionProperty(Pln::class, 'id');
        $plnRef->setAccessible(true);
        $plnRef->setValue($pln, 1);
        $this->updater->updatePluginRegistries($pln);
        $this->assertStringEndsWith('/plnconfigs/1/plugins/index.html', $pln->getProperty('org.lockss.plugin.registries')[0]);
    }

    protected function setup() : void {
        parent::setUp();
        $this->updater = self::$container->get(ConfigUpdater::class);
    }
}
