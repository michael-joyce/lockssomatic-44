<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Tests\Services;

use AppBundle\Entity\Box;
use AppBundle\Entity\BoxStatus;
use AppBundle\Services\BoxNotifier;
use Nines\UtilBundle\Tests\Util\BaseTestCase;
use Swift_Message;
use Swift_Plugins_MessageLogger;

/**
 * Description of BoxNotifierTest
 *
 * @author michael
 */
class BoxNotifierTest extends BaseTestCase {

    /**
     * @var BoxNotifier
     */
    private $notifier;

    /**
     * @var Swift_Plugins_MessageLogger
     */
    private $messageLogger;

    protected function setup() : void {
        parent::setUp();
        $this->notifier = $this->container->get(BoxNotifier::class);
        $this->messageLogger = $this->container->get('swiftmailer.mailer.default.plugin.messagelogger');
    }

    public function testSanity() {
        $this->assertInstanceOf(BoxNotifier::class, $this->notifier);
    }

    public function testUnreachable() {
        $box = new Box();
        $box->setSendNotifications(true);
        $box->setContactEmail("box@example.com");
        $boxStatus = new BoxStatus();
        $boxStatus->setErrors("This is a test.");

        $this->notifier->unreachable($box, $boxStatus);
        $this->assertEquals(1, $this->messageLogger->countMessages());
        $message = $this->messageLogger->getMessages()[0];
        $this->assertInstanceOf(Swift_Message::class, $message);
        $this->assertEquals('LOCKSSOMatic Notification: Box Unreachable', $message->getSubject());
        $this->assertEquals('noreply@example.com', key($message->getFrom()));
        $this->assertEquals('box@example.com', key($message->getTo()));
        $this->assertStringContainsStringIgnoringCase("This is a test.", $message->getBody());
    }

    public function testUnreachableDisabled() {
        $box = new Box();
        $box->setSendNotifications(false);
        $box->setContactEmail("box@example.com");
        $boxStatus = new BoxStatus();
        $boxStatus->setErrors("This is a test.");

        $this->notifier->unreachable($box, $boxStatus);
        $this->assertEquals(0, $this->messageLogger->countMessages());
    }

    public function testUnreachableNoEmail() {
        $box = new Box();
        $box->setSendNotifications(true);
        $boxStatus = new BoxStatus();
        $boxStatus->setErrors("This is a test.");

        $this->notifier->unreachable($box, $boxStatus);
        $this->assertEquals(0, $this->messageLogger->countMessages());
    }

    public function testFreeSpaceWarning() {
        $box = new Box();
        $box->setSendNotifications(true);
        $box->setContactEmail("box@example.com");
        $boxStatus = new BoxStatus();
        $boxStatus->setErrors("This is a test.");

        $this->notifier->freeSpaceWarning($box, $boxStatus);
        $this->assertEquals(1, $this->messageLogger->countMessages());
        $message = $this->messageLogger->getMessages()[0];
        $this->assertInstanceOf(Swift_Message::class, $message);
        $this->assertEquals('LOCKSSOMatic Notification: Disk Space Warning', $message->getSubject());
        $this->assertEquals('noreply@example.com', key($message->getFrom()));
        $this->assertEquals('box@example.com', key($message->getTo()));
        $this->assertStringContainsStringIgnoringCase("running low", $message->getBody());
    }

    public function testFreeSpaceWarningDisabled() {
        $box = new Box();
        $box->setSendNotifications(false);
        $box->setContactEmail("box@example.com");
        $boxStatus = new BoxStatus();
        $boxStatus->setErrors("This is a test.");

        $this->notifier->freeSpaceWarning($box, $boxStatus);
        $this->assertEquals(0, $this->messageLogger->countMessages());
    }

    public function testFreeSpaceWarningNoEmail() {
        $box = new Box();
        $box->setSendNotifications(true);
        $boxStatus = new BoxStatus();
        $boxStatus->setErrors("This is a test.");

        $this->notifier->freeSpaceWarning($box, $boxStatus);
        $this->assertEquals(0, $this->messageLogger->countMessages());
    }



}
