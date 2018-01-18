<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Au;
use AppBundle\Entity\Pln;
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
     * @Route("/", name="au_index")
     * @Method("GET")
     * @Template()
     * @param Request $request
     */
    public function indexAction(Request $request, Pln $pln) {
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from(Au::class, 'e')->orderBy('e.id', 'ASC');
        $query = $qb->getQuery();
        $paginator = $this->get('knp_paginator');
        $aus = $paginator->paginate($query, $request->query->getint('page', 1), 25);

        return array(
            'aus' => $aus,
            'pln' => $pln,
        );
    }

    /**
     * Finds and displays a Au entity.
     *
     * @Route("/{id}", name="au_show")
     * @Method("GET")
     * @Template()
     * @param Au $au
     */
    public function showAction(Au $au, Pln $pln) {

        return array(
            'au' => $au,
            'pln' => $pln,
        );
    }
    
}
