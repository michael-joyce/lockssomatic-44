<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\EventListener;

// Mock the gethostbyname function in the App\EventListener namespace to
// prevent actual DNS lookups and return known data for nonsense names.
function gethostbyname($hostname) {
    switch ($hostname) {
        case 'frobinicate.com':
            return '1.2.3.4';
        default:
            return $hostname;
    }
}

namespace App\Tests\EventListener;

use App\Entity\Box;
use App\EventListener\BoxListener;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Nines\UtilBundle\Tests\ControllerBaseCase;

class BoxListenerTest extends ControllerBaseCase
{
    private $listener;

    /**
     * @dataProvider prePersistData
     *
     * @param mixed $hostname
     * @param mixed $ip
     * @param mixed $expected
     */
    public function testPrePersist($hostname, $ip, $expected) : void {
        $box = new Box();
        $box->setHostname($hostname);
        $box->setIpAddress($ip);
        $args = new LifecycleEventArgs($box, $this->entityManager);
        $this->listener->prePersist($args);
        $this->assertSame($expected, $box->getIpAddress());
    }

    public function prePersistData() {
        return [
            ['frobinicate.com', null, '1.2.3.4'],
            ['frobinicate.com', '10.0.0.12', '10.0.0.12'],
            ['notarealdomainname', null, null],
        ];
    }

    /**
     * @dataProvider preUpdateData
     *
     * @param mixed $hostname
     * @param mixed $ip
     * @param mixed $expected
     */
    public function testPreUpdate($hostname, $ip, $expected) : void {
        $box = new Box();
        $box->setHostname($hostname);
        $box->setIpAddress($ip);
        $args = new LifecycleEventArgs($box, $this->entityManager);
        $this->listener->preUpdate($args);
        $this->assertSame($expected, $box->getIpAddress());
    }

    public function preUpdateData() {
        return [
            ['frobinicate.com', null, '1.2.3.4'],
            ['frobinicate.com', '10.0.0.12', '10.0.0.12'],
            ['notarealdomainname', null, null],
        ];
    }

    protected function setup() : void {
        parent::setUp();
        $this->listener = self::$container->get(BoxListener::class);
    }
}
