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
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

/**
 * Description of BoxNotifier.
 */
class BoxNotifier {
    private $sender;

    private $contact;

    private $templating;

    private $mailer;

    public function __construct($sender, $contact, Environment $templating, MailerInterface $mailer) {
        $this->sender = $sender;
        $this->contact = $contact;
        $this->templating = $templating;
        $this->mailer = $mailer;
    }

    public function unreachable(Box $box, BoxStatus $boxStatus) : void {
        if ( ! $box->getSendNotifications() || ! $box->getContactEmail()) {
            return;
        }
        $message = new Email(); //'LOCKSSOMatic Notification: Box Unreachable', null, 'text/plain', '7bit');
        $message->subject('LOCKSSOMatic Notification: Box Unreachable');
        $message->to($box->getContactEmail());
        $message->cc($this->contact);
        $message->from($this->sender);
        $message->text($this->templating->render('box/unreachable.txt.twig', [
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

        $message = new Email(); //
        $message->subject('LOCKSSOMatic Notification: Disk Space Warning');
        $message->to($box->getContactEmail());
        $message->cc($this->contact);
        $message->from($this->sender);
        $message->text($this->templating->render('box/diskspace.txt.twig', [
            'box' => $box,
            'boxStatus' => $boxStatus,
            'contact' => $this->contact,
        ]));
        $this->mailer->send($message);
    }
}
