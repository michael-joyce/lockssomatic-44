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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

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
     *   The HTTP request instance.
     * @param Pln $pln
     *   The pln, determined from the URL.
     *
     * @return array
     *   Array data for the template processor.
     *
     * @Route("/", name="au_index")
     * @Method("GET")
     * @Template()
     */
    public function indexAction(Request $request, Pln $pln, AuManager $manager) {
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from(Au::class, 'e')->orderBy('e.id', 'ASC');
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
     *   The AU to show.
     * @param Pln $pln
     *   The pln, determined from the URL.
     *
     * @return array
     *   Array data for the template processor.
     *
     * @Route("/{id}", name="au_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction(Au $au, Pln $pln, AuManager $manager) {

        return array(
            'au' => $au,
            'pln' => $pln,
            'manager' => $manager,
        );
    }
    
}
