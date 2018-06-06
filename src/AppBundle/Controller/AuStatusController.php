<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Au;
use AppBundle\Entity\AuStatus;
use AppBundle\Entity\Pln;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * AuStatus controller.
 *
 * @Security("has_role('ROLE_USER')")
 * @Route("/pln/{plnId}/au/{auId}/status")
 * @ParamConverter("pln", options={"id"="plnId"})
 * @ParamConverter("au", options={"id"="auId"})
 */
class AuStatusController extends Controller {

    /**
     * Lists all AuStatus entities.
     *
     * @param Request $request
     * @param Pln $pln
     * @param Au $au
     *
     * @return array
     *
     * @Route("/", name="au_status_index")
     * @Method("GET")
     * @Template()
     */
    public function indexAction(Request $request, Pln $pln, Au $au) {
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from(AuStatus::class, 'e')->orderBy('e.id', 'ASC');
        $query = $qb->getQuery();
        $paginator = $this->get('knp_paginator');
        $auStatuses = $paginator->paginate($query, $request->query->getint('page', 1), 25);

        return array(
            'auStatuses' => $auStatuses,
            'pln' => $pln,
            'au' => $au,
        );
    }

    /**
     * Finds and displays a AuStatus entity.
     *
     * @param AuStatus $auStatus
     * @param Pln $pln
     * @param Au $au
     *
     * @return array
     *
     * @Route("/{id}", name="au_status_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction(AuStatus $auStatus, Pln $pln, Au $au) {

        return array(
            'auStatus' => $auStatus,
            'pln' => $pln,
            'au' => $au,
        );
    }

}
