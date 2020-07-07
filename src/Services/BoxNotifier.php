<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Services;

use App\Entity\Box;
use App\Entity\BoxStatus;
use Swift_Mailer;
use Swift_Message;
use Symfony\Component\Templating\EngineInterface;

/**
 * Description of BoxNotifier.
 */
class BoxNotifier {
    private $sender;

    private $contact;

    private $templating;

    private $mailer;

    public function __construct($sender, $contact, EngineInterface $templating, Swift_Mailer $mailer) {
        $this->sender = $sender;
        $this->contact = $contact;
        $this->templating = $templating;
        $this->mailer = $mailer;
    }

    public function unreachable(Box $box, BoxStatus $boxStatus) : void {
        if ( ! $box->getSendNotifications() || ! $box->getContactEmail()) {
            return;
        }
        $message = new Swift_Message('LOCKSSOMatic Notification: Box Unreachable', null, 'text/plain', '7bit');
        $message->setTo($box->getContactEmail());
        $message->setCc($this->contact);
        $message->setFrom($this->sender);
        $message->setBody($this->templating->render('App:box:unreachable.txt.twig', [
            'box' => $box,
            'boxStatus' => $boxStatus,
            'contact' => $this->contact,
        ]));
        $this->mailer->send($message);
    }

    public function freeSpaceWarning(Box $box, BoxStatus $boxStatus) : void {
        if ( ! $box->getSendNotifications() || ! $box->getContactEmail()) {
            return;
        }

        $message = new Swift_Message('LOCKSSOMatic Notification: Disk Space Warning', null, 'text/plain', '7bit');
        $message->setTo($box->getContactEmail());
        $message->setCc($this->contact);
        $message->setFrom($this->sender);
        $message->setBody($this->templating->render('App:box:diskspace.txt.twig', [
            'box' => $box,
            'boxStatus' => $boxStatus,
            'contact' => $this->contact,
        ]));
        $this->mailer->send($message);
    }
}
