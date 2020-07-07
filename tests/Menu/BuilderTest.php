<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
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

    public function testAnonLockssMenu() : void {
        $client = $this->makeClient();
        $menu = $client->getContainer()->get(Builder::class)->mainMenu([]);
        $this->assertInstanceOf(MenuItem::class, $menu);
        $this->assertSame(1, count($menu->getChildren()));
    }

    public function testUserLockssMenu() : void {
        $client = $this->makeClient([
            'username' => 'user@example.com',
            'password' => 'secret',
        ]);
        // must make a request to get the auth set up properly in the container.
        $client->request('get', '/');
        $menu = $client->getContainer()->get(Builder::class)->mainMenu([]);
        $this->assertInstanceOf(MenuItem::class, $menu);
        $this->assertSame(3, count($menu->getChildren()));
    }
}
