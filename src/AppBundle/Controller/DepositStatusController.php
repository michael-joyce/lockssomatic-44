<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Deposit;
use AppBundle\Entity\DepositStatus;
use AppBundle\Entity\Pln;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * DepositStatus controller.
 *
 * @Security("has_role('ROLE_USER')")
 * @Route("/pln/{plnId}/deposit/{depositId}/status")
 * @ParamConverter("pln", options={"id"="plnId"})
 * @ParamConverter("deposit", options={"id"="depositId"})
 */
class DepositStatusController extends Controller {

    /**
     * Lists all DepositStatus entities.
     *
     * @param Request $request
     * @param Pln $pln
     * @param Deposit $deposit
     *
     * @return array
     *
     * @Route("/", name="deposit_status_index")
     * @Method("GET")
     * @Template()
     */
    public function indexAction(Request $request, Pln $pln, Deposit $deposit) {
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from(DepositStatus::class, 'e')->orderBy('e.id', 'ASC');
        $query = $qb->getQuery();
        $paginator = $this->get('knp_paginator');
        $depositStatuses = $paginator->paginate($query, $request->query->getint('page', 1), 25);

        return array(
            'depositStatuses' => $depositStatuses,
            'deposit' => $deposit,
            'pln' => $pln,
        );
    }

    /**
     * Finds and displays a DepositStatus entity.
     *
     * @param DepositStatus $depositStatus
     * @param Pln $pln
     * @param Deposit $deposit
     *
     * @return array
     *
     * @Route("/{id}", name="deposit_status_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction(DepositStatus $depositStatus, Pln $pln, Deposit $deposit) {

        return array(
            'depositStatus' => $depositStatus,
            'deposit' => $deposit,
            'pln' => $pln,
        );
    }

}
