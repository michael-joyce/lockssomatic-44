<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Tests\Services;

use AppBundle\DataFixtures\ORM\LoadDeposit;
use AppBundle\Entity\Au;
use AppBundle\Entity\AuProperty;
use AppBundle\Entity\ContentOwner;
use AppBundle\Entity\ContentProvider;
use AppBundle\Entity\Deposit;
use AppBundle\Entity\Pln;
use AppBundle\Entity\Plugin;
use AppBundle\Entity\PluginProperty;
use AppBundle\Repository\AuRepository;
use AppBundle\Services\AuManager;
use ArrayObject;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Nines\UtilBundle\Tests\Util\BaseTestCase;
use ReflectionProperty;

/**
 * Description of AuManagerTest
 *
 * @author michael
 */
class AuManagerTest extends BaseTestCase {

    /**
     * @var AuManager
     */
    private $manager;

    protected function setUp() {
        parent::setUp();
        $this->manager = $this->container->get(AuManager::class);
    }

    protected function getFixtures() {
        return [
            LoadDeposit::class
        ];
    }

    public function testSanity() {
        $this->assertInstanceOf(AuManager::class, $this->manager);
    }

    public function testAuSize() {
        $this->assertEquals(600, $this->manager->auSize($this->getReference('au.1')));
    }

    public function testAuEmptySize() {
        $this->assertEquals(0, $this->manager->auSize($this->getReference('au.2')));
    }

    public function testBuildAu() {
        $plugin = $this->createMock(Plugin::class);
        $provider = new ContentProvider();
        $provider->setPlugin($plugin);

        $deposit = $this->createMock(Deposit::class);
        $deposit->method('getContentProvider')->will($this->returnValue($provider));
        $au = $this->manager->buildAu($deposit, 'auid123');
        $this->assertInstanceOf(Au::class, $au);
        $this->assertEquals($provider, $au->getContentProvider());
        $this->assertEquals($plugin, $au->getPlugin());
        $this->assertEquals('auid123', $au->getAuid());
    }

    public function testFindOpenAuEmpty() {
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getIdentifier')->will($this->returnValue('ca.example.plugin'));
        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
                    'foo', 'bar', 'bax'
        ]));
        $plugin->method('getGeneratedParams')->will($this->returnValue([
                    'bar'
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
        $this->assertEquals('ca|example|plugin&bax~property+the+third%21&foo~Some+complex+title', $au->getAuid());
    }

    public function testFindOpenAuWithContent() {
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getIdentifier')->will($this->returnValue('ca.example.plugin'));
        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
                    'foo', 'bar', 'bax'
        ]));
        $plugin->method('getGeneratedParams')->will($this->returnValue([
                    'bar'
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
        $this->assertEquals('ca|example|plugin&bax~property+the+third%21&foo~Some+complex+title', $au1->getAuid());

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
        $this->assertEquals('ca|example|plugin&bax~property+the+third%21&foo~Some+complex+title', $au2->getAuid());

        $this->assertEquals($au1, $au2);
    }

    public function testFindOpenAuContentDifferentAus() {
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getIdentifier')->will($this->returnValue('ca.example.plugin'));
        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
                    'foo', 'bar', 'bax'
        ]));
        $plugin->method('getGeneratedParams')->will($this->returnValue([
                    'bar'
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
        $this->assertEquals('ca|example|plugin&bax~property+the+Billionth&foo~Some+complex+title', $au1->getAuid());

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
        $this->assertEquals('ca|example|plugin&bax~property+the+third%21&foo~Some+complex+title', $au2->getAuid());

        $this->assertNotEquals($au1, $au2);
    }

    public function testFindOpenAuSizeOverflow() {
        $au = new Au();
        $repo = $this->createMock(AuRepository::class);
        $repo->method('getAuSize')->will($this->returnValue(500));
        $repo->method('findOpenAu')->will($this->returnValue($au));
        $this->manager->setAuRepository($repo);

        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getIdentifier')->will($this->returnValue('ca.example.plugin'));
        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
                    'foo'
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
        $this->assertNotEquals($au, $foundAu);
    }

    public function testValidateSingle() {
        $au = new Au();
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
                    'foo'
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

        $this->assertEquals(0, $this->manager->validate($au));
    }

    public function testValidateEmpty() {
        $au = new Au();
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
                    'foo'
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

        $this->assertEquals(0, $this->manager->validate($au));
    }

    public function testValidate() {
        $au = new Au();
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
                    'foo'
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

        $this->assertEquals(0, $this->manager->validate($au));
    }

    public function testValidateFail() {
        $au = new Au();
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
                    'foo'
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

        $this->assertEquals(9, $this->manager->validate($au));
    }

    /**
     * @expectedException \Exception
     */
    public function testValidateNoPlugin() {
        $au = new Au();
        $this->manager->validate($au);
    }

    /**
     * @expectedException \Exception
     */
    public function testValidateNoDefinitionalProps() {
        $au = new Au();
        $plugin = $this->createMock(Plugin::class);
        $au->setPlugin($plugin);
        $this->manager->validate($au);
    }

    /**
     * @expectedException \Exception
     */
    public function testValidateNoDeposits() {
        $au = new Au();
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
            'foo'
        ]));
        $au->setPlugin($plugin);
        $this->manager->validate($au);
    }

    public function testBuildProperty() {
        $au = new Au();
        $key = 'foobar';
        $value = 'some long complex value.';
        $property = $this->manager->buildProperty($au, $key, $value);
        $this->assertInstanceOf(AuProperty::class, $property);
        $this->assertEquals($key, $property->getPropertyKey());
        $this->assertEquals($value, $property->getPropertyValue());
        $this->assertEquals(1, $au->getAuProperties()->count());
    }

    public function testBuildPropetyChild() {
        $au = new Au();
        $key = 'foobar';
        $value = 'some long complex value.';
        $parent = new AuProperty();
        $property = $this->manager->buildProperty($au, $key, $value, $parent);
        $this->assertTrue($parent->hasChildren());
        $this->assertEquals($parent, $property->getParent());
        $this->assertEquals(1, $au->getAuProperties()->count()); // didn't add parent to AU.
    }

    /**
     * @dataProvider generateStringData
     */
    public function testGenerateString($expected, $value, $map) {
        $au = $this->createMock(Au::class);
        $au->method('getAuPropertyValue')->will($this->returnValueMap($map));

        $this->assertEquals($expected, $this->manager->generateString($au, $value));
    }

    public function generateStringData() {
        return [
            // [expected, value, map]
            ['', '""', []],
            ['Some testing.', '"Some testing."', []],
            ['Some testing.', '"Some %s.", foo', [
                    ['foo', 'testing']
                ]],
            ['Test number 1 etc.', '"Test number %d etc.", bling', [
                    ['bling', 1]
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
     * @expectedException Exception
     */
    public function testGenerateBadString($expected, $value, $map) {
        $au = $this->createMock(Au::class);
        $au->method('getAuPropertyValue')->will($this->returnValueMap($map));

        $this->assertEquals($expected, $this->manager->generateString($au, $value));
    }

    public function generateStringBadData() {
        return [
            // [expected, value, map]
            ['', '', []],
            ['Some testing.', '"Some %s."', [
                    ['foo', 'testing']
                ]],
            ['Test number 1 etc.', '"Test number %d etc., bling', [
                    ['bling', 1]
                ]],
        ];
    }

    /**
     * @dataProvider testGenerateSymbolData
     */
    public function testGenerateSymbolString(array $data) {
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
        $this->assertEquals($data['expected'], $str);
    }

    /**
     * @dataProvider testGenerateSymbolData
     * @expectedException \Exception
     */
    public function testGenerateSymbolMissingPlugin(array $data) {
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
        $this->assertEquals($data['expected'], $str);
    }

    /**
     * @dataProvider testGenerateSymbolData
     * @expectedException \Exception
     */
    public function testGenerateSymbolMissingParameter(array $data) {
        $property = $this->createMock(PluginProperty::class);
        $property->method('isList')->will($this->returnValue($data['list']));
        $property->method('getPropertyValue')->will($this->returnValue($data['propValue']));

        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getProperty')->will($this->returnValue(null));
        $plugin->method('getName')->will($this->returnValue('dummy'));

        $au = $this->createMock(Au::class);
        $au->method('getPlugin')->willReturn(null);
        $au->method('getAuPropertyValue')->will($this->returnValueMap($data['auValues']));

        $str = $this->manager->generateSymbol($au, 'testable');
        $this->assertEquals($data['expected'], $str);
    }

    public function testGenerateSymbolData() {
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
                ['color', 'black']
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
                ['color', 'black']
            ],
            'expected' => [
                'item cheddar is a cheese',
                'Sand is coarse and black'
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
                ['color', 'black']
            ],
            'expected' => [
                'item cheddar is a cheese',
                'Sand is cheddar and cheddar'
            ],
                ]],
        ];
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

    public function testBaseProperties() {
        $au = new Au();
        $root = new AuProperty();
        $deposit = new Deposit();
        $deposit->setTitle("Deposit");
        $deposit->setProperty('journalTitle', "Fooooo");
        $deposit->setTitle("Deposit Title");
        $plugin = new Plugin();
        $plugin->setIdentifier('com.example.plugin');
        $au->setPlugin($plugin);
        $deposit->setProperty('publisher', 'Publishing House');

        $this->manager->baseProperties($au, $root, $deposit);
        $this->assertEquals(4, count($au->getAuProperties()));
        $this->assertEquals('Fooooo', $au->getSimpleAuProperty('journalTitle'));
        // two spaces in AU..Deposit because the AU has a null $id property.
        $this->assertEquals('LOCKSSOMatic AU  Deposit Title', $au->getSimpleAuProperty('title'));
        $this->assertEquals('com.example.plugin', $au->getSimpleAuProperty('plugin'));
        $this->assertEquals('Publishing House', $au->getSimpleAuProperty('attributes.publisher'));
    }

    public function testConfigProperties() {
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
        $deposit->setUrl("http://example.com/path/item");
        $deposit->setTitle("Item");

        // definitional
        $deposit->setProperty('container_number', 1);

        $propertyNames = [
            'base_url', 'container_number',
            'permission_url', 'manifest_url'
        ];

        $root = new AuProperty();
        $root->setAu($au);
        $au->addAuProperty($root);

        $this->manager->configProperties($propertyNames, $au, $root, $deposit);
        // 1 for root, 3 for each property (one to group, one key, one value)
        $this->assertEquals(13, count($au->getAuProperties()));
        $this->assertEquals('http://example.com', $au->getAuPropertyValue('base_url'));
        $this->assertEquals(1, $au->getAuPropertyValue('container_number'));
        $this->assertEquals('http://example.com/permission', $au->getAuPropertyValue('permission_url'));
        $this->assertStringEndsWith('plnconfigs/1/manifests/3/5/manifest_7.html', $au->getAuPropertyValue('manifest_url'));
    }

    public function testContentProperties() {
        $au = new Au();
        $root = new AuProperty();
        $au->addAuProperty($root);
        $deposit = new Deposit();
        $deposit->setProperty("foo", "barr");
        $deposit->setProperty("spackle", "made from dust.");
        $this->manager->contentProperties($au, $root, $deposit);
        $this->assertEquals(3, count($au->getAuProperties()));
        $this->assertEquals('barr', $au->getSimpleAuProperty('attributes.pkppln.foo'));
        $this->assertEquals('made from dust.', $au->getSimpleAuProperty('attributes.pkppln.spackle'));
    }

    public function testContentPropertiesList() {
        $au = new Au();
        $root = new AuProperty();
        $au->addAuProperty($root);
        $deposit = new Deposit();
        $deposit->setProperty("foo", ["barr"]);
        $deposit->setProperty("spackle", "made from dust.");
        $this->manager->contentProperties($au, $root, $deposit);
        $this->assertEquals(2, count($au->getAuProperties())); // property foois skipped.
        $this->assertEquals(null, $au->getSimpleAuProperty('attributes.pkppln.foo'));
        $this->assertEquals('made from dust.', $au->getSimpleAuProperty('attributes.pkppln.spackle'));
    }

    public function testGenerateProperties() {
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
        $au->setContentProvider($provider);

        $au->setPlugin($plugin);
        $this->buildContentItems($au);

        $this->manager->generateProperties($au);
        $this->assertEquals(23, count($au->getAuProperties()));
        $this->assertEquals('http://example.com', $au->getAuPropertyValue('base_url'));
        $this->assertEquals(1, $au->getAuPropertyValue('container_number'));
        $this->assertEquals('http://example.com/permission', $au->getAuPropertyValue('permission_url'));
        $this->assertStringEndsWith('plnconfigs/1/manifests/3/5/manifest_7.html', $au->getAuPropertyValue('manifest_url'));
    }

    public function testGeneratePropertiesClear() {
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
        $dummy = new AuProperty();
        $dummy->setAu($au);
        $dummy->setPropertyKey("dummy");
        $dummy->setPropertyValue("also dummy.");
        $au->addAuProperty($dummy);

        $auRef = new ReflectionProperty(Au::class, 'id');
        $auRef->setAccessible(true);
        $auRef->setValue($au, 7);
        $au->setPln($pln);
        $au->setContentProvider($provider);

        $au->setPlugin($plugin);
        $this->buildContentItems($au);

        $this->manager->generateProperties($au, true);
        $this->assertEquals('', $au->getAuPropertyValue("dummy"));
        $this->assertEquals(23, count($au->getAuProperties()));
        $this->assertEquals('http://example.com', $au->getAuPropertyValue('base_url'));
        $this->assertEquals(1, $au->getAuPropertyValue('container_number'));
        $this->assertEquals('http://example.com/permission', $au->getAuPropertyValue('permission_url'));
        $this->assertStringEndsWith('plnconfigs/1/manifests/3/5/manifest_7.html', $au->getAuPropertyValue('manifest_url'));
    }

    public function testGenerateAuidFromDeposit() {
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getIdentifier')->will($this->returnValue('ca.example.plugin'));
        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
            'foo', 'bar', 'bax'
        ]));
        $plugin->method('getGeneratedParams')->will($this->returnValue([
            'bar'
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
        $this->assertEquals('ca|example|plugin&bar~other%2Eproperty&bax~property+the+third%21&foo~Some+complex+title', $id);
    }

    /**
     * @expectedException \Exception
     */
    public function testGenerateAuidFromDepositMissingProperty() {
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getIdentifier')->will($this->returnValue('ca.example.plugin'));
        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
            'foo', 'bar', 'bax'
        ]));
        $plugin->method('getGeneratedParams')->will($this->returnValue([
            'bar'
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
        $this->assertEquals('ca|example|plugin&bar~other%2Eproperty&bax~property+the+third%21&foo~Some+complex+title', $id);
    }

    public function testGenerateAuidFromDepositNonLockss() {
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getIdentifier')->will($this->returnValue('ca.example.plugin'));
        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
            'foo', 'bar', 'bax'
        ]));
        $plugin->method('getGeneratedParams')->will($this->returnValue([
            'bar'
        ]));
        $deposit = $this->createMock(Deposit::class);
        $deposit->method('getPlugin')->will($this->returnValue($plugin));
        $deposit->method('getProperty')->will($this->returnValueMap([
            ['foo', 'Some complex title'],
            ['bar', 'other.property'],
            ['bax', 'property the third!'],
        ]));
        $id = $this->manager->generateAuidFromDeposit($deposit, false);
        $this->assertEquals('ca|example|plugin&bax~property+the+third%21&foo~Some+complex+title', $id);
    }

    public function testGenerateAuidFromAu() {
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getIdentifier')->will($this->returnValue('ca.example.plugin'));
        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
            'foo', 'bar', 'bax'
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
        $this->assertEquals('ca|example|plugin&bar~other%2Eproperty&bax~property+the+third%21&foo~Some+complex+title', $id);
    }

    public function testGenerateAuidFromAuNoDeposits() {
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getIdentifier')->will($this->returnValue('ca.example.plugin'));
        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
            'foo', 'bar', 'bax'
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

    public function testGenerateAuidFromAuNoPlugin() {
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

    public function testGenerateAuidFromAuNonLockss() {
        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getIdentifier')->will($this->returnValue('ca.example.plugin'));
        $plugin->method('getDefinitionalPropertyNames')->will($this->returnValue([
            'foo', 'bar', 'bax'
        ]));
        $plugin->method('getGeneratedParams')->will($this->returnValue([
            'bar'
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
        $this->assertEquals('ca|example|plugin&bax~property+the+third%21&foo~Some+complex+title', $id);
    }
}
