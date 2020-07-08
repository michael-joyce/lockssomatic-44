<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Services;

use App\DataFixtures\DepositFixtures;
use App\Entity\Au;
use App\Entity\AuProperty;
use App\Entity\ContentOwner;
use App\Entity\ContentProvider;
use App\Entity\Deposit;
use App\Entity\Pln;
use App\Entity\Plugin;
use App\Entity\PluginProperty;
use App\Repository\AuRepository;
use App\Services\AuManager;
use ArrayObject;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Nines\UtilBundle\Tests\ControllerBaseCase;
use ReflectionProperty;

/**
 * Description of AuManagerTest.
 *
 * @author michael
 */
class AuManagerTest extends ControllerBaseCase {
    /**
     * @var AuManager
     */
    private $manager;

    protected function fixtures() : array {
        return [
            DepositFixtures::class,
        ];
    }

    public function testSanity() : void {
        $this->assertInstanceOf(AuManager::class, $this->manager);
    }

    public function testAuSize() : void {
        $this->assertSame(600, $this->manager->auSize($this->getReference('au.1')));
    }

    public function testAuEmptySize() : void {
        $this->assertSame(0, $this->manager->auSize($this->getReference('au.2')));
    }

    public function testBuildAu() : void {
        $plugin = $this->createMock(Plugin::class);
        $provider = new ContentProvider();
        $provider->setPlugin($plugin);

        $deposit = $this->createMock(Deposit::class);
        $deposit->method('getContentProvider')->will($this->returnValue($provider));
        $au = $this->manager->buildAu($deposit, 'auid123');
        $this->assertInstanceOf(Au::class, $au);
        $this->assertSame($provider, $au->getContentProvider());
        $this->assertSame($plugin, $au->getPlugin());
        $this->assertSame('auid123', $au->getAuid());
    }

    public function testFindOpenAuEmpty() : void {
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getIdentifier')->will($this->returnValue('ca.example.plugin'));
        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
            'foo', 'bar', 'bax',
        ]));
        $plugin->method('getGeneratedParams')->will($this->returnValue([
            'bar',
        ]));
        $provider = new ContentProvider();
        $provider->setPlugin($plugin);
        $deposit = $this->createMock(Deposit::class);
        $deposit->method('getContentProvider')->will($this->returnValue($provider));
        $deposit->method('getPlugin')->will($this->returnValue($plugin));
        $deposit->method('getProperty')->will($this->returnValueMap([
            ['foo', 'Some complex title'],
            ['bar', 'other.property'],
            ['bax', 'property the third!'],
        ]));

        $au = $this->manager->findOpenAu($deposit);
        $this->assertInstanceOf(Au::class, $au);
        $this->assertSame('ca|example|plugin&bax~property+the+third%21&foo~Some+complex+title', $au->getAuid());
    }

    public function testFindOpenAuWithContent() : void {
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getIdentifier')->will($this->returnValue('ca.example.plugin'));
        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
            'foo', 'bar', 'bax',
        ]));
        $plugin->method('getGeneratedParams')->will($this->returnValue([
            'bar',
        ]));
        $provider = new ContentProvider();
        $provider->setPlugin($plugin);

        $deposit1 = $this->createMock(Deposit::class);
        $deposit1->method('getContentProvider')->will($this->returnValue($provider));
        $deposit1->method('getPlugin')->will($this->returnValue($plugin));
        $deposit1->method('getProperty')->will($this->returnValueMap([
            ['foo', 'Some complex title'],
            ['bar', 'different.property'],
            ['bax', 'property the third!'],
        ]));

        $au1 = $this->manager->findOpenAu($deposit1);
        $this->assertInstanceOf(Au::class, $au1);
        $this->assertSame('ca|example|plugin&bax~property+the+third%21&foo~Some+complex+title', $au1->getAuid());

        $deposit2 = $this->createMock(Deposit::class);
        $deposit2->method('getContentProvider')->will($this->returnValue($provider));
        $deposit2->method('getPlugin')->will($this->returnValue($plugin));
        $deposit2->method('getProperty')->will($this->returnValueMap([
            ['foo', 'Some complex title'],
            ['bar', 'other.property'],
            ['bax', 'property the third!'],
        ]));

        $au2 = $this->manager->findOpenAu($deposit2);
        $this->assertInstanceOf(Au::class, $au2);
        $this->assertSame('ca|example|plugin&bax~property+the+third%21&foo~Some+complex+title', $au2->getAuid());
    }

    public function testFindOpenAuContentDifferentAus() : void {
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getIdentifier')->will($this->returnValue('ca.example.plugin'));
        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
            'foo', 'bar', 'bax',
        ]));
        $plugin->method('getGeneratedParams')->will($this->returnValue([
            'bar',
        ]));
        $provider = new ContentProvider();
        $provider->setPlugin($plugin);

        $deposit1 = $this->createMock(Deposit::class);
        $deposit1->method('getContentProvider')->will($this->returnValue($provider));
        $deposit1->method('getPlugin')->will($this->returnValue($plugin));
        $deposit1->method('getProperty')->will($this->returnValueMap([
            ['foo', 'Some complex title'],
            ['bar', 'different.property'],
            ['bax', 'property the Billionth'],
        ]));

        $au1 = $this->manager->findOpenAu($deposit1);
        $this->assertInstanceOf(Au::class, $au1);
        $this->assertSame('ca|example|plugin&bax~property+the+Billionth&foo~Some+complex+title', $au1->getAuid());

        $deposit2 = $this->createMock(Deposit::class);
        $deposit2->method('getContentProvider')->will($this->returnValue($provider));
        $deposit2->method('getPlugin')->will($this->returnValue($plugin));
        $deposit2->method('getProperty')->will($this->returnValueMap([
            ['foo', 'Some complex title'],
            ['bar', 'other.property'],
            ['bax', 'property the third!'],
        ]));

        $au2 = $this->manager->findOpenAu($deposit2);
        $this->assertInstanceOf(Au::class, $au1);
        $this->assertSame('ca|example|plugin&bax~property+the+third%21&foo~Some+complex+title', $au2->getAuid());

        $this->assertNotSame($au1, $au2);
    }

    public function testFindOpenAuSizeOverflow() : void {
        $au = new Au();
        $repo = $this->createMock(AuRepository::class);
        $repo->method('getAuSize')->will($this->returnValue(500));
        $repo->method('findOpenAu')->will($this->returnValue($au));
        $this->manager->setAuRepository($repo);

        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getIdentifier')->will($this->returnValue('ca.example.plugin'));
        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
            'foo',
        ]));
        $plugin->method('getGeneratedParams')->will($this->returnValue([]));
        $provider = new ContentProvider();
        $provider->setPlugin($plugin);
        $provider->setMaxAuSize(600);

        $deposit = $this->createMock(Deposit::class);
        $deposit->method('getSize')->willReturn(400);
        $deposit->method('getContentProvider')->will($this->returnValue($provider));
        $deposit->method('getPlugin')->will($this->returnValue($plugin));
        $deposit->method('getProperty')->will($this->returnValueMap([
            ['foo', 'bar'],
        ]));

        $foundAu = $this->manager->findOpenAu($deposit);
        $this->assertFalse($au->isOpen());
        $this->assertNotSame($au, $foundAu);
    }

    public function testValidateSingle() : void {
        $au = new Au();
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
            'foo',
        ]));
        $au->setPlugin($plugin);

        $deposit = new Deposit();
        $deposit->setProperty('foo', 'chicanery');

        $data = new ArrayObject();
        $data[] = [$deposit, 0];
        $iterator = $data->getIterator();
        $iterator->rewind();

        $repo = $this->createMock(AuRepository::class);
        $repo->method('iterateDeposits')->will($this->returnValue(
            $iterator
        ));
        $this->manager->setAuRepository($repo);

        $this->assertSame(0, $this->manager->validate($au));
    }

    public function testValidateEmpty() : void {
        $au = new Au();
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
            'foo',
        ]));
        $au->setPlugin($plugin);

        $deposit = new Deposit();
        $deposit->setProperty('foo', 'chicanery');

        $data = new ArrayObject();
        $iterator = $data->getIterator();
        $iterator->rewind();

        $repo = $this->createMock(AuRepository::class);
        $repo->method('iterateDeposits')->willReturn($iterator);
        $repo->method('countDeposits')->willReturn(0);
        $this->manager->setAuRepository($repo);

        $this->assertSame(0, $this->manager->validate($au));
    }

    public function testValidate() : void {
        $au = new Au();
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
            'foo',
        ]));
        $au->setPlugin($plugin);

        $deposit = new Deposit();
        $deposit->setProperty('foo', 'chicanery');

        $data = new ArrayObject();
        $data[] = [$deposit, 0];
        $data[] = [$deposit, 0]; // same deposit.
        $iterator = $data->getIterator();
        $iterator->rewind();

        $repo = $this->createMock(AuRepository::class);
        $repo->method('iterateDeposits')->will($this->returnValue(
            $iterator
        ));
        $this->manager->setAuRepository($repo);

        $this->assertSame(0, $this->manager->validate($au));
    }

    public function testValidateFail() : void {
        $au = new Au();
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
            'foo',
        ]));
        $au->setPlugin($plugin);

        $data = new ArrayObject();
        for ($i = 0; $i < 10; $i++) {
            $deposit = new Deposit();
            $deposit->setProperty('foo', 'chicanery_' . $i);
            $data[] = [$deposit, 0];
        }
        $iterator = $data->getIterator();
        $iterator->rewind();

        $repo = $this->createMock(AuRepository::class);
        $repo->method('iterateDeposits')->will($this->returnValue(
            $iterator
        ));
        $this->manager->setAuRepository($repo);

        $this->assertSame(9, $this->manager->validate($au));
    }

    public function testValidateNoPlugin() : void {
        $this->expectException(Exception::class);
        $au = new Au();
        $this->manager->validate($au);
    }

    public function testValidateNoDefinitionalProps() : void {
        $this->expectException(Exception::class);
        $au = new Au();
        $plugin = $this->createMock(Plugin::class);
        $au->setPlugin($plugin);
        $this->manager->validate($au);
    }

    public function testValidateNoDeposits() : void {
        $this->expectException(Exception::class);
        $au = new Au();
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
            'foo',
        ]));
        $au->setPlugin($plugin);
        $this->manager->validate($au);
    }

    public function testBuildProperty() : void {
        $au = new Au();
        $key = 'foobar';
        $value = 'some long complex value.';
        $property = $this->manager->buildProperty($au, $key, $value);
        $this->assertInstanceOf(AuProperty::class, $property);
        $this->assertSame($key, $property->getPropertyKey());
        $this->assertSame($value, $property->getPropertyValue());
        $this->assertSame(1, $au->getAuProperties()->count());
    }

    public function testBuildPropetyChild() : void {
        $au = new Au();
        $key = 'foobar';
        $value = 'some long complex value.';
        $parent = new AuProperty();
        $property = $this->manager->buildProperty($au, $key, $value, $parent);
        $this->assertTrue($parent->hasChildren());
        $this->assertSame($parent, $property->getParent());
        $this->assertSame(1, $au->getAuProperties()->count()); // didn't add parent to AU.
    }

    /**
     * @dataProvider generateStringData
     *
     * @param mixed $expected
     * @param mixed $value
     * @param mixed $map
     */
    public function testGenerateString($expected, $value, $map) : void {
        $au = $this->createMock(Au::class);
        $au->method('getAuPropertyValue')->will($this->returnValueMap($map));

        $this->assertSame($expected, $this->manager->generateString($au, $value));
    }

    public function generateStringData() {
        return [
            // [expected, value, map]
            ['', '""', []],
            ['Some testing.', '"Some testing."', []],
            ['Some testing.', '"Some %s.", foo', [
                ['foo', 'testing'],
            ]],
            ['Test number 1 etc.', '"Test number %d etc.", bling', [
                ['bling', 1],
            ]],
            ['Test number 1 etc.', '"Test number %d etc.", bling', [
                ['bling', 1],
                ['murphy', 1],
                ['wilson', 1],
            ]],
            ['Test jango is number 1.', '"Test %s is number %d.", bling, murphy', [
                ['bling', 'jango'],
                ['murphy', 1],
            ]],
        ];
    }

    /**
     * @dataProvider generateStringBadData
     *
     * @param mixed $expected
     * @param mixed $value
     * @param mixed $map
     */
    public function testGenerateBadString($expected, $value, $map) : void {
        $this->expectException(Exception::class);
        $au = $this->createMock(Au::class);
        $au->method('getAuPropertyValue')->will($this->returnValueMap($map));

        $this->assertSame($expected, $this->manager->generateString($au, $value));
    }

    public function generateStringBadData() {
        return [
            // [expected, value, map]
            ['', '', []],
            ['Some testing.', '"Some %s."', [
                ['foo', 'testing'],
            ]],
            ['Test number 1 etc.', '"Test number %d etc., bling', [
                ['bling', 1],
            ]],
        ];
    }

    /**
     * @dataProvider generateSymbolData
     */
    public function testGenerateSymbolString(array $data) : void {
        $property = $this->createMock(PluginProperty::class);
        $property->method('isList')->will($this->returnValue($data['list']));
        $property->method('getPropertyValue')->will($this->returnValue($data['propValue']));

        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getProperty')->will($this->returnValue($property));
        $plugin->method('getName')->will($this->returnValue('dummy'));

        $au = $this->createMock(Au::class);
        $au->method('getPlugin')->will($this->returnValue($plugin));
        $au->method('getAuPropertyValue')->will($this->returnValueMap($data['auValues']));

        $str = $this->manager->generateSymbol($au, 'testable');
        $this->assertSame($data['expected'], $str);
    }

    /**
     * @dataProvider generateSymbolData
     */
    public function testGenerateSymbolMissingPlugin(array $data) : void {
        $this->expectException(Exception::class);
        $property = $this->createMock(PluginProperty::class);
        $property->method('isList')->will($this->returnValue($data['list']));
        $property->method('getPropertyValue')->will($this->returnValue($data['propValue']));

        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getProperty')->will($this->returnValue($property));
        $plugin->method('getName')->will($this->returnValue('dummy'));

        $au = $this->createMock(Au::class);
        $au->method('getPlugin')->willReturn(null);
        $au->method('getAuPropertyValue')->will($this->returnValueMap($data['auValues']));

        $str = $this->manager->generateSymbol($au, 'testable');
        $this->assertSame($data['expected'], $str);
    }

    /**
     * @dataProvider generateSymbolData
     */
    public function testGenerateSymbolMissingParameter(array $data) : void {
        $this->expectException(Exception::class);
        $property = $this->createMock(PluginProperty::class);
        $property->method('isList')->will($this->returnValue($data['list']));
        $property->method('getPropertyValue')->will($this->returnValue($data['propValue']));

        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getProperty')->will($this->returnValue(null));
        $plugin->method('getName')->will($this->returnValue('dummy'));

        $au = $this->createMock(Au::class);
        $au->method('getPlugin')->will($this->returnValue($plugin));
        $au->method('getAuPropertyValue')->will($this->returnValueMap($data['auValues']));

        $str = $this->manager->generateSymbol($au, 'testable');
        $this->assertSame($data['expected'], $str);
    }

    public function generateSymbolData() {
        return [
            [[
                'list' => false,
                'propValue' => '"item is a cheese"',
                'auValues' => [
                    ['fromage', 'cheddar'],
                ],
                'expected' => 'item is a cheese',
            ]],
            [[
                'list' => false,
                'propValue' => '"item %s is a cheese", fromage',
                'auValues' => [
                    ['fromage', 'cheddar'],
                ],
                'expected' => 'item cheddar is a cheese',
            ]],
            [[
                'list' => false,
                'propValue' => '"Sand is %s and %s", grit, color',
                'auValues' => [
                    ['fromage', 'cheddar'],
                    ['grit', 'coarse'],
                    ['color', 'black'],
                ],
                'expected' => 'Sand is coarse and black',
            ]],
            [[
                'list' => true,
                'propValue' => [
                    '"item %s is a cheese", fromage',
                    '"Sand is %s and %s", grit, color',
                ],
                'auValues' => [
                    ['fromage', 'cheddar'],
                    ['grit', 'coarse'],
                    ['color', 'black'],
                ],
                'expected' => [
                    'item cheddar is a cheese',
                    'Sand is coarse and black',
                ],
            ]],
            [[
                'list' => true,
                'propValue' => [
                    '"item %s is a cheese", fromage',
                    '"Sand is %s and %s", fromage, fromage',
                ],
                'auValues' => [
                    ['fromage', 'cheddar'],
                    ['grit', 'coarse'],
                    ['color', 'black'],
                ],
                'expected' => [
                    'item cheddar is a cheese',
                    'Sand is cheddar and cheddar',
                ],
            ]],
        ];
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

    public function testBaseProperties() : void {
        $au = new Au();
        $root = new AuProperty();
        $deposit = new Deposit();
        $deposit->setTitle('Deposit');
        $deposit->setProperty('journalTitle', 'Fooooo');
        $deposit->setTitle('Deposit Title');
        $plugin = new Plugin();
        $plugin->setIdentifier('com.example.plugin');
        $au->setPlugin($plugin);
        $deposit->setProperty('publisher', 'Publishing House');

        $this->manager->baseProperties($au, $root, $deposit);
        $this->assertSame(4, count($au->getAuProperties()));
        $this->assertSame('Fooooo', $au->getSimpleAuProperty('journalTitle'));
        // two spaces in AU..Deposit because the AU has a null $id property.
        $this->assertSame('LOCKSSOMatic AU  Deposit Title', $au->getSimpleAuProperty('title'));
        $this->assertSame('com.example.plugin', $au->getSimpleAuProperty('plugin'));
        $this->assertSame('Publishing House', $au->getSimpleAuProperty('attributes.publisher'));
    }

    public function testConfigProperties() : void {
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
        $au->setContentProvider($provider);

        $deposit = new Deposit();
        $deposit->setUrl('http://example.com/path/item');
        $deposit->setTitle('Item');

        // definitional
        $deposit->setProperty('container_number', 1);

        $propertyNames = [
            'base_url', 'container_number',
            'permission_url', 'manifest_url',
        ];

        $root = new AuProperty();
        $root->setAu($au);
        $au->addAuProperty($root);

        $this->manager->configProperties($propertyNames, $au, $root, $deposit);
        // 1 for root, 3 for each property (one to group, one key, one value)
        $this->assertSame(13, count($au->getAuProperties()));
        $this->assertSame('http://example.com', $au->getAuPropertyValue('base_url'));
        $this->assertSame(1, $au->getAuPropertyValue('container_number'));
        $this->assertSame('http://example.com/permission', $au->getAuPropertyValue('permission_url'));
        $this->assertStringEndsWith('plnconfigs/1/manifests/3/5/manifest_7.html', $au->getAuPropertyValue('manifest_url'));
    }

    public function testContentProperties() : void {
        $au = new Au();
        $root = new AuProperty();
        $au->addAuProperty($root);
        $deposit = new Deposit();
        $deposit->setProperty('foo', 'barr');
        $deposit->setProperty('spackle', 'made from dust.');
        $this->manager->contentProperties($au, $root, $deposit);
        $this->assertSame(3, count($au->getAuProperties()));
        $this->assertSame('barr', $au->getSimpleAuProperty('attributes.pkppln.foo'));
        $this->assertSame('made from dust.', $au->getSimpleAuProperty('attributes.pkppln.spackle'));
    }

    public function testContentPropertiesList() : void {
        $au = new Au();
        $root = new AuProperty();
        $au->addAuProperty($root);
        $deposit = new Deposit();
        $deposit->setProperty('foo', ['barr']);
        $deposit->setProperty('spackle', 'made from dust.');
        $this->manager->contentProperties($au, $root, $deposit);
        $this->assertSame(2, count($au->getAuProperties())); // property foois skipped.
        $this->assertSame(null, $au->getSimpleAuProperty('attributes.pkppln.foo'));
        $this->assertSame('made from dust.', $au->getSimpleAuProperty('attributes.pkppln.spackle'));
    }

    public function testGenerateProperties() : void {
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getIdentifier')->will($this->returnValue('ca.example.plugin'));
        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
            'base_url', 'container_number', 'manifest_url', 'permission_url',
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
        $au->setContentProvider($provider);

        $au->setPlugin($plugin);
        $this->buildContentItems($au);

        $this->manager->generateProperties($au);
        $this->assertSame(23, count($au->getAuProperties()));
        $this->assertSame('http://example.com', $au->getAuPropertyValue('base_url'));
        $this->assertSame(1, $au->getAuPropertyValue('container_number'));
        $this->assertSame('http://example.com/permission', $au->getAuPropertyValue('permission_url'));
        $this->assertStringEndsWith('plnconfigs/1/manifests/3/5/manifest_7.html', $au->getAuPropertyValue('manifest_url'));
    }

    public function testGeneratePropertiesClear() : void {
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getIdentifier')->will($this->returnValue('ca.example.plugin'));
        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
            'base_url', 'container_number', 'manifest_url', 'permission_url',
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
        $dummy = new AuProperty();
        $dummy->setAu($au);
        $dummy->setPropertyKey('dummy');
        $dummy->setPropertyValue('also dummy.');
        $au->addAuProperty($dummy);

        $auRef = new ReflectionProperty(Au::class, 'id');
        $auRef->setAccessible(true);
        $auRef->setValue($au, 7);
        $au->setPln($pln);
        $au->setContentProvider($provider);

        $au->setPlugin($plugin);
        $this->buildContentItems($au);

        $this->manager->generateProperties($au, true);
        $this->assertSame('', $au->getAuPropertyValue('dummy'));
        $this->assertSame(23, count($au->getAuProperties()));
        $this->assertSame('http://example.com', $au->getAuPropertyValue('base_url'));
        $this->assertSame(1, $au->getAuPropertyValue('container_number'));
        $this->assertSame('http://example.com/permission', $au->getAuPropertyValue('permission_url'));
        $this->assertStringEndsWith('plnconfigs/1/manifests/3/5/manifest_7.html', $au->getAuPropertyValue('manifest_url'));
    }

    public function testGenerateAuidFromDeposit() : void {
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getIdentifier')->will($this->returnValue('ca.example.plugin'));
        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
            'foo', 'bar', 'bax',
        ]));
        $plugin->method('getGeneratedParams')->will($this->returnValue([
            'bar',
        ]));
        $au = $this->createMock(Au::class);
        $au->method('getAuPropertyValue')->will($this->returnValueMap([
            ['foo', 'Some complex title'],
            ['bar', 'other.property'],
            ['bax', 'property the third!'],
        ]));
        $deposit = $this->createMock(Deposit::class);
        $deposit->method('getPlugin')->will($this->returnValue($plugin));
        $deposit->method('getProperty')->will($this->returnValueMap([
            ['foo', 'Some complex title'],
            ['bar', 'other.property'],
            ['bax', 'property the third!'],
        ]));
        $deposit->method('getAu')->willReturn($au);

        $id = $this->manager->generateAuidFromDeposit($deposit, true);
        $this->assertSame('ca|example|plugin&bar~other%2Eproperty&bax~property+the+third%21&foo~Some+complex+title', $id);
    }

    public function testGenerateAuidFromDepositMissingProperty() : void {
        $this->expectException(Exception::class);
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getIdentifier')->will($this->returnValue('ca.example.plugin'));
        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
            'foo', 'bar', 'bax',
        ]));
        $plugin->method('getGeneratedParams')->will($this->returnValue([
            'bar',
        ]));
        $au = $this->createMock(Au::class);
        $au->method('getAuPropertyValue')->will($this->returnValueMap([
            ['foo', 'Some complex title'],
        ]));
        $deposit = $this->createMock(Deposit::class);
        $deposit->method('getPlugin')->will($this->returnValue($plugin));
        $deposit->method('getProperty')->will($this->returnValueMap([
            ['foo', 'Some complex title'],
            ['bar', 'other.property'],
            ['bax', 'property the third!'],
        ]));
        $deposit->method('getAu')->willReturn($au);

        $id = $this->manager->generateAuidFromDeposit($deposit, true);
        $this->assertSame('ca|example|plugin&bar~other%2Eproperty&bax~property+the+third%21&foo~Some+complex+title', $id);
    }

    public function testGenerateAuidFromDepositNonLockss() : void {
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getIdentifier')->will($this->returnValue('ca.example.plugin'));
        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
            'foo', 'bar', 'bax',
        ]));
        $plugin->method('getGeneratedParams')->will($this->returnValue([
            'bar',
        ]));
        $deposit = $this->createMock(Deposit::class);
        $deposit->method('getPlugin')->will($this->returnValue($plugin));
        $deposit->method('getProperty')->will($this->returnValueMap([
            ['foo', 'Some complex title'],
            ['bar', 'other.property'],
            ['bax', 'property the third!'],
        ]));
        $id = $this->manager->generateAuidFromDeposit($deposit, false);
        $this->assertSame('ca|example|plugin&bax~property+the+third%21&foo~Some+complex+title', $id);
    }

    public function testGenerateAuidFromAu() : void {
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getIdentifier')->will($this->returnValue('ca.example.plugin'));
        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
            'foo', 'bar', 'bax',
        ]));
        $deposit = $this->createMock(Deposit::class);
        $deposit->method('getPlugin')->will($this->returnValue($plugin));
        $deposit->method('getProperty')->will($this->returnValueMap([
            ['foo', 'Some complex title'],
            ['bar', 'other.property'],
            ['bax', 'property the third!'],
        ]));
        $au = $this->createMock(Au::class);
        $au->method('getAuPropertyValue')->will($this->returnValueMap([
            ['foo', 'Some complex title'],
            ['bar', 'other.property'],
            ['bax', 'property the third!'],
        ]));
        $au->method('getDeposits')->willReturn(new ArrayCollection([$deposit]));
        $au->method('getPlugin')->willReturn($plugin);
        $deposit->method('getAu')->willReturn($au);
        $id = $this->manager->generateAuidFromAu($au);
        $this->assertSame('ca|example|plugin&bar~other%2Eproperty&bax~property+the+third%21&foo~Some+complex+title', $id);
    }

    public function testGenerateAuidFromAuNoDeposits() : void {
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getIdentifier')->will($this->returnValue('ca.example.plugin'));
        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
            'foo', 'bar', 'bax',
        ]));
        $au = $this->createMock(Au::class);
        $au->method('getAuPropertyValue')->will($this->returnValueMap([
            ['foo', 'Some complex title'],
            ['bar', 'other.property'],
            ['bax', 'property the third!'],
        ]));
        $au->method('getDeposits')->willReturn(new ArrayCollection([]));
        $au->method('getPlugin')->willReturn($plugin);
        $id = $this->manager->generateAuidFromAu($au);
        $this->assertNull($id);
    }

    public function testGenerateAuidFromAuNoPlugin() : void {
        $au = $this->createMock(Au::class);
        $au->method('getAuPropertyValue')->will($this->returnValueMap([
            ['foo', 'Some complex title'],
            ['bar', 'other.property'],
            ['bax', 'property the third!'],
        ]));
        $deposit = $this->createMock(Deposit::class);
        $deposit->method('getPlugin')->will($this->returnValue(null));
        $deposit->method('getProperty')->will($this->returnValueMap([
            ['foo', 'Some complex title'],
            ['bar', 'other.property'],
            ['bax', 'property the third!'],
        ]));
        $au->method('getDeposits')->willReturn(new ArrayCollection([$deposit]));
        $id = $this->manager->generateAuidFromAu($au);
        $this->assertNull($id);
    }

    public function testGenerateAuidFromAuNonLockss() : void {
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getIdentifier')->will($this->returnValue('ca.example.plugin'));
        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
            'foo', 'bar', 'bax',
        ]));
        $plugin->method('getGeneratedParams')->will($this->returnValue([
            'bar',
        ]));
        $deposit = $this->createMock(Deposit::class);
        $deposit->method('getPlugin')->will($this->returnValue($plugin));
        $deposit->method('getProperty')->will($this->returnValueMap([
            ['foo', 'Some complex title'],
            ['bar', 'other.property'],
            ['bax', 'property the third!'],
        ]));
        $au = $this->createMock(Au::class);
        $au->method('getAuPropertyValue')->will($this->returnValueMap([
            ['foo', 'Some complex title'],
            ['bar', 'other.property'],
            ['bax', 'property the third!'],
        ]));
        $au->method('getPlugin')->willReturn($plugin);
        $deposit->method('getAu')->willReturn($au);
        $au->method('getDeposits')->willReturn(new ArrayCollection([$deposit]));
        $id = $this->manager->generateAuidFromAu($au, false);
        $this->assertSame('ca|example|plugin&bax~property+the+third%21&foo~Some+complex+title', $id);
    }

    protected function setup() : void {
        parent::setUp();
        $this->manager = self::$container->get(AuManager::class);
    }
}
