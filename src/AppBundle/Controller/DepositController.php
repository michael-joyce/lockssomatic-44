<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Deposit;
use AppBundle\Entity\Pln;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Deposit controller.
 *
 * @Security("has_role('ROLE_USER')")
 * @Route("/pln/{plnId}/deposit")
 * @ParamConverter("pln", options={"id"="plnId"})
 */
class DepositController extends Controller {

    /**
     * Lists all Deposit entities.
     *
     * @param Request $request
     * @param Pln $pln
     *
     * @return array
     *
     * @Route("/", name="deposit_index")
     * @Method("GET")
     * @Template()
     */
    public function indexAction(Request $request, Pln $pln) {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository(Deposit::class);
        $query = $repo->indexQuery($pln);
        $paginator = $this->get('knp_paginator');
        $deposits = $paginator->paginate($query, $request->query->getint('page', 1), 25);

        return array(
            'deposits' => $deposits,
            'pln' => $pln,
        );
    }

    /**
     * Search for Deposit entities.
     *
     * @param Request $request
     * @param Pln $pln
     *
     * @return array
     *
     * @Route("/search", name="deposit_search")
     * @Method("GET")
     * @Template()
     */
    public function searchAction(Request $request, Pln $pln) {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:Deposit');
        $q = $request->query->get('q');
        if ($q) {
            $query = $repo->searchQuery($q, $pln);
            $paginator = $this->get('knp_paginator');
            $deposits = $paginator->paginate($query, $request->query->getInt('page', 1), 25);
        } else {
            $deposits = array();
        }

        return array(
            'deposits' => $deposits,
            'q' => $q,
            'pln' => $pln,
        );
    }

    /**
     * Finds and displays a Deposit entity.
     *
     * @param Deposit $deposit
     * @param Pln $pln
     *
     * @return array
     *
     * @Route("/{id}", name="deposit_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction(Deposit $deposit, Pln $pln) {
        if($deposit->getAu()->getPln() !== $pln) {
            throw new NotFoundHttpException('No such deposit.');
        }
        return array(
            'deposit' => $deposit,
            'pln' => $pln,
        );
    }

}
