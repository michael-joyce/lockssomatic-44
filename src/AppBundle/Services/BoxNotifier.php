<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Services;

use AppBundle\Entity\Box;
use AppBundle\Entity\BoxStatus;
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

    public function unreachable(Box $box, BoxStatus $boxStatus) {
        if (!$box->getSendNotifications() || !$box->getContactEmail()) {
            return;
        }
        $message = new Swift_Message('LOCKSSOMatic Notification: Box Unreachable', null, 'text/plain', '7bit');
        $message->setTo($box->getContactEmail());
        $message->setCc($this->contact);
        $message->setFrom($this->sender);
        $message->setBody($this->templating->render('AppBundle:box:unreachable.txt.twig', array(
            'box' => $box,
            'boxStatus' => $boxStatus,
            'contact' => $this->contact,
        )));
        $this->mailer->send($message);
    }

    public function freeSpaceWarning(Box $box, BoxStatus $boxStatus) {
        if (!$box->getSendNotifications() || !$box->getContactEmail()) {
            return;
        }

        $message = new Swift_Message('LOCKSSOMatic Notification: Disk Space Warning', null, 'text/plain', '7bit');
        $message->setTo($box->getContactEmail());
        $message->setCc($this->contact);
        $message->setFrom($this->sender);
        $message->setBody($this->templating->render('AppBundle:box:diskspace.txt.twig', array(
            'box' => $box,
            'boxStatus' => $boxStatus,
            'contact' => $this->contact,
        )));
        $this->mailer->send($message);
    }

}
