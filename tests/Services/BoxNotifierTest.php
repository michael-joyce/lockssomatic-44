<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Services;

use App\Entity\Box;
use App\Entity\BoxStatus;
use App\Services\BoxNotifier;
use Nines\UtilBundle\Tests\ControllerBaseCase;
use Symfony\Component\Mime\Email;

/**
 * Description of BoxNotifierTest.
 *
 * @author michael
 */
class BoxNotifierTest extends ControllerBaseCase {
    /**
     * @var BoxNotifier
     */
    private $notifier;

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

        # sigh. Symfony cannot test that an email was sent without an HTTP request.
        $this->client->request('GET', '/');

        $this->assertEmailCount(1);
        $message = $this->getMailerMessage();
        $this->assertInstanceOf(Email::class, $message);

        $this->assertSame('LOCKSSOMatic Notification: Box Unreachable', $message->getSubject());
        $this->assertSame('noreply@example.com', $message->getFrom()[0]->getAddress());
        $this->assertSame('box@example.com', $message->getTo()[0]->getAddress());
        $this->assertStringContainsStringIgnoringCase('This is a test.', $message->getTextBody());
    }

    public function testUnreachableDisabled() : void {
        $box = new Box();
        $box->setSendNotifications(false);
        $box->setContactEmail('box@example.com');
        $boxStatus = new BoxStatus();
        $boxStatus->setErrors('This is a test.');

        $this->notifier->unreachable($box, $boxStatus);

        # sigh. Symfony cannot test that an email was sent without an HTTP request.
        $this->client->request('GET', '/');

        $this->assertEmailCount(0);
    }

    public function testUnreachableNoEmail() : void {
        $box = new Box();
        $box->setSendNotifications(true);
        $boxStatus = new BoxStatus();
        $boxStatus->setErrors('This is a test.');

        $this->notifier->unreachable($box, $boxStatus);

        # sigh. Symfony cannot test that an email was sent without an HTTP request.
        $this->client->request('GET', '/');

        $this->assertEmailCount(0);
    }

    public function testFreeSpaceWarning() : void {
        $box = new Box();
        $box->setSendNotifications(true);
        $box->setContactEmail('box@example.com');
        $boxStatus = new BoxStatus();
        $boxStatus->setErrors('This is a test.');

        $this->notifier->freeSpaceWarning($box, $boxStatus);

        # sigh. Symfony cannot test that an email was sent without an HTTP request.
        $this->client->request('GET', '/');

        $this->assertEmailCount(1);
        $message = $this->getMailerMessage();
        $this->assertInstanceOf(Email::class, $message);


        $this->assertSame('LOCKSSOMatic Notification: Disk Space Warning', $message->getSubject());
        $this->assertSame('noreply@example.com', $message->getFrom()[0]->getAddress());
        $this->assertSame('box@example.com', $message->getTo()[0]->getAddress());
        $this->assertStringContainsStringIgnoringCase('running low', $message->getTextBody());
    }

    public function testFreeSpaceWarningDisabled() : void {
        $box = new Box();
        $box->setSendNotifications(false);
        $box->setContactEmail('box@example.com');
        $boxStatus = new BoxStatus();
        $boxStatus->setErrors('This is a test.');

        $this->notifier->freeSpaceWarning($box, $boxStatus);
        $this->notifier->freeSpaceWarning($box, $boxStatus);

        # sigh. Symfony cannot test that an email was sent without an HTTP request.
        $this->client->request('GET', '/');

        $this->assertEmailCount(0);
    }

    public function testFreeSpaceWarningNoEmail() : void {
        $box = new Box();
        $box->setSendNotifications(true);
        $boxStatus = new BoxStatus();
        $boxStatus->setErrors('This is a test.');

        $this->notifier->freeSpaceWarning($box, $boxStatus);
        $this->notifier->freeSpaceWarning($box, $boxStatus);

        # sigh. Symfony cannot test that an email was sent without an HTTP request.
        $this->client->request('GET', '/');

        $this->assertEmailCount(0);
    }

    protected function setup() : void {
        parent::setUp();
        $this->notifier = self::$container->get(BoxNotifier::class);
    }
}
