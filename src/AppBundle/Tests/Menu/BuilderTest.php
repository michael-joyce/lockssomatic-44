<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Tests\Menu;

use AppBundle\Menu\Builder;
use Knp\Menu\MenuItem;
use Nines\UserBundle\DataFixtures\ORM\LoadUser;
use Nines\UtilBundle\Tests\Util\BaseTestCase;

class BuilderTest extends BaseTestCase {
    
    protected function getFixtures() {
        return [
            LoadUser::class,
        ];
    }

    public function testAnonLockssMenu() {
        $client = $this->makeClient();
        $menu = $client->getContainer()->get(Builder::class)->mainMenu([]);
        $this->assertInstanceOf(MenuItem::class, $menu);
        $this->assertEquals(1, count($menu->getChildren()));
    }
    
    public function testUserLockssMenu() {
        $client = $this->makeClient([
            'username' => 'user@example.com',
            'password' => 'secret',
        ]);
        // must make a request to get the auth set up properly in the container.
        $client->request('get', '/');
        $menu = $client->getContainer()->get(Builder::class)->mainMenu([]);
        $this->assertInstanceOf(MenuItem::class, $menu);
        $this->assertEquals(3, count($menu->getChildren()));
    }
}
