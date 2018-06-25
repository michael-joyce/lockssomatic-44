<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Box;
use AppBundle\Entity\BoxStatus;
use AppBundle\Entity\Pln;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * BoxStatus controller.
 *
 * @Security("has_role('ROLE_USER')")
 * @Route("/pln/{plnId}/box/{boxId}/status")
 * @ParamConverter("pln", options={"id"="plnId"})
 * @ParamConverter("box", options={"id"="boxId"})
 */
class BoxStatusController extends Controller {

    /**
     * Lists all BoxStatus entities.
     *
     * @param Request $request
     * @param Pln $pln
     * @param Box $box
     *
     * @return array
     *
     * @Route("/", name="box_status_index")
     * @Method("GET")
     * @Template()
     */
    public function indexAction(Request $request, Pln $pln, Box $box) {
        if($box->getPln() !== $pln) {
            throw new NotFoundHttpException("Unknown box.");
        }
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from(BoxStatus::class, 'e')->where('e.box = :box')->orderBy('e.id', 'DESC');
        $qb->setParameter('box', $box);
        $query = $qb->getQuery();
        $paginator = $this->get('knp_paginator');
        $boxStatuses = $paginator->paginate($query, $request->query->getint('page', 1), 25);

        return array(
            'boxStatuses' => $boxStatuses,
            'pln' => $pln,
            'box' => $box,
        );
    }

    /**
     * Finds and displays a BoxStatus entity.
     *
     * @param BoxStatus $boxStatus
     * @param Pln $pln
     * @param Box $box
     *
     * @return array
     *
     * @Route("/{id}", name="box_status_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction(BoxStatus $boxStatus, Pln $pln, Box $box) {
        if($box->getPln() !== $pln) {
            throw new NotFoundHttpException("Unknown box.");
        }
        if($boxStatus->getBox() !== $box) {
            throw new NotFoundHttpException("Unknown status.");
        }

        return array(
            'boxStatus' => $boxStatus,
            'pln' => $pln,
            'box' => $box,
        );
    }

}
