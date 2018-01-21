<?php

namespace AppBundle\EventListener;

// Mock the gethostbyname function in the AppBundle\EventListener namespace to 
// prevent actual DNS lookups and return known data for nonsense names.
function gethostbyname($hostname) {
    switch ($hostname) {
        case 'frobinicate.com':
            return "1.2.3.4";
        default:
            return $hostname;
    }
}

namespace AppBundle\Tests\EventListener;

use AppBundle\Entity\Box;
use AppBundle\EventListener\BoxListener;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Nines\UtilBundle\Tests\Util\BaseTestCase;

class BoxListenerTest extends BaseTestCase {

    private $listener;

    protected function setUp() {
        parent::setUp();
        $this->listener = $this->getContainer()->get(BoxListener::class);
    }

    /**
     * @dataProvider prePersistData
     */
    public function testPrePersist($hostname, $ip, $expected) {
        $box = new Box();
        $box->setHostname($hostname);
        $box->setIpAddress($ip);
        $args = new LifecycleEventArgs($box, $this->getDoctrine());
        $this->listener->prePersist($args);
        $this->assertEquals($expected, $box->getIpAddress());
    }

    public function prePersistData() {
        return [
            ['frobinicate.com', null, '1.2.3.4'],
            ['frobinicate.com', '10.0.0.12', '10.0.0.12'],
        ];
    }

    /**
     * @dataProvider preUpdateData
     */
    public function testPreUpdate($hostname, $ip, $expected) {
        $box = new Box();
        $box->setHostname($hostname);
        $box->setIpAddress($ip);
        $args = new LifecycleEventArgs($box, $this->getDoctrine());
        $this->listener->preUpdate($args);
        $this->assertEquals($expected, $box->getIpAddress());
    }

    public function preUpdateData() {
        return [
            ['frobinicate.com', null, '1.2.3.4'],
            ['frobinicate.com', '10.0.0.12', '1.2.3.4'],
        ];
    }

}
