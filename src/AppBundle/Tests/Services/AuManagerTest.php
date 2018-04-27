<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Tests\Services;

use AppBundle\DataFixtures\ORM\LoadContent;
use AppBundle\Entity\Au;
use AppBundle\Entity\Content;
use AppBundle\Entity\ContentProvider;
use AppBundle\Entity\Plugin;
use AppBundle\Repository\AuRepository;
use AppBundle\Services\AuManager;
use Nines\UtilBundle\Tests\Util\BaseTestCase;

/*
 * Au Manager tests
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
            LoadContent::class
        ];
    }

    public function testInstance() {
        $this->assertInstanceOf(AuManager::class, $this->manager);
    }

    public function testAuSize() {
        $this->assertEquals(600, $this->manager->auSize($this->getReference('au.1')));
    }

    public function testAuEmptySize() {
        $this->assertEquals(0, $this->manager->auSize($this->getReference('au.2')));
    }

    public function testFromContent() {
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
        $content = $this->createMock(Content::class);
        $content->method('getContentProvider')->will($this->returnValue($provider));
        $content->method('getPlugin')->will($this->returnValue($plugin));
        $content->method('getProperty')->will($this->returnValueMap([
            ['foo', 'Some complex title'],
            ['bar', 'other.property'],
            ['bax', 'property the third!'],
        ]));

        $au = $this->manager->findOpenAu($content);
        $this->assertInstanceOf(Au::class, $au);
        $this->assertEquals('ca|example|plugin&bax~property+the+third%21&foo~Some+complex+title', $au->getAuid());
    }

    public function testFromContentSameAus() {
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

        $content1 = $this->createMock(Content::class);
        $content1->method('getContentProvider')->will($this->returnValue($provider));
        $content1->method('getPlugin')->will($this->returnValue($plugin));
        $content1->method('getProperty')->will($this->returnValueMap([
            ['foo', 'Some complex title'],
            ['bar', 'different.property'],
            ['bax', 'property the third!'],
        ]));

        $au1 = $this->manager->findOpenAu($content1);
        $this->assertInstanceOf(Au::class, $au1);
        $this->assertEquals('ca|example|plugin&bax~property+the+third%21&foo~Some+complex+title', $au1->getAuid());

        $content2 = $this->createMock(Content::class);
        $content2->method('getContentProvider')->will($this->returnValue($provider));
        $content2->method('getPlugin')->will($this->returnValue($plugin));
        $content2->method('getProperty')->will($this->returnValueMap([
            ['foo', 'Some complex title'],
            ['bar', 'other.property'],
            ['bax', 'property the third!'],
        ]));

        $au2 = $this->manager->findOpenAu($content2);
        $this->assertInstanceOf(Au::class, $au1);
        $this->assertEquals('ca|example|plugin&bax~property+the+third%21&foo~Some+complex+title', $au2->getAuid());

        $this->assertEquals($au1, $au2);
    }

    public function testFromContentDifferentAus() {
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

        $content1 = $this->createMock(Content::class);
        $content1->method('getContentProvider')->will($this->returnValue($provider));
        $content1->method('getPlugin')->will($this->returnValue($plugin));
        $content1->method('getProperty')->will($this->returnValueMap([
            ['foo', 'Some complex title'],
            ['bar', 'different.property'],
            ['bax', 'property the Billionth'],
        ]));

        $au1 = $this->manager->findOpenAu($content1);
        $this->assertInstanceOf(Au::class, $au1);
        $this->assertEquals('ca|example|plugin&bax~property+the+Billionth&foo~Some+complex+title', $au1->getAuid());

        $content2 = $this->createMock(Content::class);
        $content2->method('getContentProvider')->will($this->returnValue($provider));
        $content2->method('getPlugin')->will($this->returnValue($plugin));
        $content2->method('getProperty')->will($this->returnValueMap([
            ['foo', 'Some complex title'],
            ['bar', 'other.property'],
            ['bax', 'property the third!'],
        ]));

        $au2 = $this->manager->findOpenAu($content2);
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

        $content = $this->createMock(Content::class);
        $content->method('getSize')->willReturn(400);
        $content->method('getContentProvider')->will($this->returnValue($provider));
        $content->method('getPlugin')->will($this->returnValue($plugin));
        $content->method('getProperty')->will($this->returnValueMap([
            ['foo', 'bar'],
        ]));

        $foundAu = $this->manager->findOpenAu($content);
        $this->assertFalse($au->isOpen());
        $this->assertNotEquals($au, $foundAu);
    }

}
