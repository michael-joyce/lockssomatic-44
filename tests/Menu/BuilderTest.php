<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
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
        $menu = self::$container->get(Builder::class)->mainMenu([]);
        $this->assertInstanceOf(MenuItem::class, $menu);
        $this->assertCount(1, $menu->getChildren());
    }

    public function testUserLockssMenu() : void {
        $this->login('user.user');
        // must make a request to get the auth set up properly in the container.
        $this->client->request('get', '/');
        $menu = self::$container->get(Builder::class)->mainMenu([]);
        $this->assertInstanceOf(MenuItem::class, $menu);
        $this->assertCount(3, $menu->getChildren());
    }
}
