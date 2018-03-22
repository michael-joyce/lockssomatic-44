<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Tests\Services;

use AppBundle\Entity\Au;
use AppBundle\Entity\Content;
use AppBundle\Entity\ContentProvider;
use AppBundle\Entity\Deposit;
use AppBundle\Entity\Plugin;
use AppBundle\Services\AuManager;
use Nines\UtilBundle\Tests\Util\BaseTestCase;

/**
 * Description of AuManagerTest
 */
class AuManagerTest extends BaseTestCase {

    /**
     * @var AuManager
     */
    private $builder;
    
    protected function setUp() {
        parent::setUp();
        $this->builder = $this->container->get(AuManager::class);
    }
    
    public function testInstance() {
        $this->assertInstanceOf(AuManager::class, $this->builder);
    }
    
    public function testFromContent() {
        $plugin = new Plugin();
        $provider = new ContentProvider();
        $provider->setPlugin($plugin);
        $deposit = new Deposit();
        $deposit->setContentProvider($provider);
        $content = new Content();
        $content->setProperty('journalTitle', 'Some Title');
        $content->setProperty('publisher', 'Some publisher');
        $content->setDeposit($deposit);
        
        $au = $this->builder->fromContent($content);
        
        $this->assertInstanceOf(Au::class, $au);
        $this->assertEquals($plugin, $au->getPlugin());
        $this->assertEquals(1, count($au->getContent()));
        $this->assertEquals($provider, $au->getContentProvider());
        $this->assertEquals('', $au->getAuId());
    }
    
}
