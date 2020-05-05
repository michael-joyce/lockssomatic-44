<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace AppBundle\Tests\Services;

use AppBundle\Entity\Box;
use AppBundle\Entity\BoxStatus;
use AppBundle\Services\BoxNotifier;
use Nines\UtilBundle\Tests\Util\BaseTestCase;
use Swift_Message;
use Swift_Plugins_MessageLogger;

/**
 * Description of BoxNotifierTest.
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

    public function testSanity() : void {
        $this->assertInstanceOf(BoxNotifier::class, $this->notifier);
    }

    public function testUnreachable() : void {
        $box = new Box();
        $box->setSendNotifications(true);
        $box->setContactEmail('box@example.com');
        $boxStatus = new BoxStatus();
        $boxStatus->setErrors('This is a test.');

        $this->notifier->unreachable($box, $boxStatus);
        $this->assertSame(1, $this->messageLogger->countMessages());
        $message = $this->messageLogger->getMessages()[0];
        $this->assertInstanceOf(Swift_Message::class, $message);
        $this->assertSame('LOCKSSOMatic Notification: Box Unreachable', $message->getSubject());
        $this->assertSame('noreply@example.com', key($message->getFrom()));
        $this->assertSame('box@example.com', key($message->getTo()));
        $this->assertStringContainsStringIgnoringCase('This is a test.', $message->getBody());
    }

    public function testUnreachableDisabled() : void {
        $box = new Box();
        $box->setSendNotifications(false);
        $box->setContactEmail('box@example.com');
        $boxStatus = new BoxStatus();
        $boxStatus->setErrors('This is a test.');

        $this->notifier->unreachable($box, $boxStatus);
        $this->assertSame(0, $this->messageLogger->countMessages());
    }

    public function testUnreachableNoEmail() : void {
        $box = new Box();
        $box->setSendNotifications(true);
        $boxStatus = new BoxStatus();
        $boxStatus->setErrors('This is a test.');

        $this->notifier->unreachable($box, $boxStatus);
        $this->assertSame(0, $this->messageLogger->countMessages());
    }

    public function testFreeSpaceWarning() : void {
        $box = new Box();
        $box->setSendNotifications(true);
        $box->setContactEmail('box@example.com');
        $boxStatus = new BoxStatus();
        $boxStatus->setErrors('This is a test.');

        $this->notifier->freeSpaceWarning($box, $boxStatus);
        $this->assertSame(1, $this->messageLogger->countMessages());
        $message = $this->messageLogger->getMessages()[0];
        $this->assertInstanceOf(Swift_Message::class, $message);
        $this->assertSame('LOCKSSOMatic Notification: Disk Space Warning', $message->getSubject());
        $this->assertSame('noreply@example.com', key($message->getFrom()));
        $this->assertSame('box@example.com', key($message->getTo()));
        $this->assertStringContainsStringIgnoringCase('running low', $message->getBody());
    }

    public function testFreeSpaceWarningDisabled() : void {
        $box = new Box();
        $box->setSendNotifications(false);
        $box->setContactEmail('box@example.com');
        $boxStatus = new BoxStatus();
        $boxStatus->setErrors('This is a test.');

        $this->notifier->freeSpaceWarning($box, $boxStatus);
        $this->assertSame(0, $this->messageLogger->countMessages());
    }

    public function testFreeSpaceWarningNoEmail() : void {
        $box = new Box();
        $box->setSendNotifications(true);
        $boxStatus = new BoxStatus();
        $boxStatus->setErrors('This is a test.');

        $this->notifier->freeSpaceWarning($box, $boxStatus);
        $this->assertSame(0, $this->messageLogger->countMessages());
    }

    protected function setup() : void {
        parent::setUp();
        $this->notifier = $this->container->get(BoxNotifier::class);
        $this->messageLogger = $this->container->get('swiftmailer.mailer.default.plugin.messagelogger');
    }
}
