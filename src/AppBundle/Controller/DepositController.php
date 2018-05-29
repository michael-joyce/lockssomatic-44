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
use AppBundle\Form\DepositType;
use DateTime;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

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
     *   The HTTP request instance.
     * @param Pln $pln
     *   The PLN, determined from the URL.
     *
     * @return array
     *   Array data for the template processor.
     *
     * @Route("/", name="deposit_index")
     * @Method("GET")
     * @Template()
     */
    public function indexAction(Request $request, Pln $pln) {
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from(Deposit::class, 'e')->orderBy('e.id', 'ASC');
        $query = $qb->getQuery();
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
     * To make this work, add a method like this one to the
     * AppBundle:Deposit repository. Replace the fieldName with
     * something appropriate, and adjust the generated search.html.twig
     * template.
     *
     * <code><pre>
     *     public function searchQuery($q) {
     *         $qb = $this->createQueryBuilder('e');
     *         $qb->where("e.fieldName like '%$q%'");
     *         return $qb->getQuery();
     *     }
     * </pre></code>
     *
     * @param Request $request
     *   The HTTP request instance.
     * @param Pln $pln
     *   The PLN, determined from the URL.
     *
     * @return array
     *   Array data for the template processor.
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
            $query = $repo->searchQuery($q);
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
     *   The deposit, as determined by the URL.
     * @param Pln $pln
     *   The PLN, as determined by the URL.
     *
     * @return array
     *   Array data for the template processor.
     *
     * @Route("/{id}", name="deposit_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction(Deposit $deposit, Pln $pln) {

        return array(
            'deposit' => $deposit,
            'pln' => $pln,
        );
    }

}
