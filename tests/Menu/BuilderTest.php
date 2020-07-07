<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Menu;

use App\Menu\Builder;
use Knp\Menu\MenuItem;
use Nines\UserBundle\DataFixtures\UserFixtures;
use Nines\UtilBundle\Tests\ControllerBaseCase;

class BuilderTest extends ControllerBaseCase {
    protected function fixtures() : array {
        return [
            UserFixtures::class,
        ];
    }

    public function testAnonLockssMenu() : void {
        $this->client->
        $menu = $this->client->getContainer()->get(Builder::class)->mainMenu([]);
        $this->assertInstanceOf(MenuItem::class, $menu);
        $this->assertSame(1, count($menu->getChildren()));
    }

    public function testUserLockssMenu() : void {
        $client = $this->makeClient([
            'username' => 'user@example.com',
            'password' => 'secret',
        ]);
        // must make a request to get the auth set up properly in the container.
        $this->client->request('get', '/');
        $menu = $this->client->getContainer()->get(Builder::class)->mainMenu([]);
        $this->assertInstanceOf(MenuItem::class, $menu);
        $this->assertSame(3, count($menu->getChildren()));
    }
}
