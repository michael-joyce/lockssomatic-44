<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Au;
use AppBundle\Entity\Pln;
use AppBundle\Services\AuManager;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Au controller.
 *
 * @Security("has_role('ROLE_USER')")
 * @Route("/pln/{plnId}/au")
 * @ParamConverter("pln", options={"id"="plnId"})
 */
class AuController extends Controller {

    /**
     * Lists all Au entities.
     *
     * @param Request $request
     * @param Pln $pln
     * @param AuManager $manager
     *
     * @return array
     *
     * @Route("/", name="au_index")
     * @Method("GET")
     * @Template()
     */
    public function indexAction(Request $request, Pln $pln, AuManager $manager) {
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from(Au::class, 'e')->where('e.pln = :pln')->orderBy('e.id', 'ASC');
        $qb->setParameter('pln', $pln);
        $query = $qb->getQuery();
        $paginator = $this->get('knp_paginator');
        $aus = $paginator->paginate($query, $request->query->getint('page', 1), 25);

        return array(
            'aus' => $aus,
            'pln' => $pln,
            'manager' => $manager,
        );
    }

    /**
     * Finds and displays a Au entity.
     *
     * @param Au $au
     * @param Pln $pln
     * @param AuManager $manager
     *
     * @return array
     *
     * @Route("/{id}", name="au_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction(Au $au, Pln $pln, AuManager $manager) {
        if($au->getPln() !== $pln) {
            throw new NotFoundHttpException("PLN {$pln->getName()} does not contain that AU.");
        }
        return array(
            'au' => $au,
            'pln' => $pln,
            'manager' => $manager,
        );
    }

    /**
     * Finds and displays a Au entity.
     *
     * @param Request $request
     * @param Pln $pln
     * @param Au $au
     * @param EntityManagerInterface $em
     *
     * @return array
     *
     * @Route("/{id}", name="au_deposits")
     * @Method("GET")
     * @Template()
     */
    public function depositsAction(Request $request, Pln $pln, Au $au, EntityManagerInterface $em) {
        if($au->getPln() !== $pln) {
            throw new NotFoundHttpException("PLN {$pln->getName()} does not contain that AU.");
        }
        $repo = $em->getRepository(Au::class);
        $query = $repo->queryDeposits($au);
        $paginator = $this->get('knp_paginator');
        $deposits = $paginator->paginate($query, $request->query->getint('page', 1), 25);

        return array(
            'au' => $au,
            'pln' => $pln,
            'deposits' => $deposits,
        );
    }

}
