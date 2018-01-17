<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\Pln;
use AppBundle\Form\PlnType;

/**
 * Pln controller.
 *
 * @Security("has_role('ROLE_USER')")
 * @Route("/pln")
 */
class PlnController extends Controller {

    /**
     * Lists all Pln entities.
     *
     * @Route("/", name="pln_index")
     * @Method("GET")
     * @Template()
     * @param Request $request
     */
    public function indexAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from(Pln::class, 'e')->orderBy('e.id', 'ASC');
        $query = $qb->getQuery();
        $paginator = $this->get('knp_paginator');
        $plns = $paginator->paginate($query, $request->query->getint('page', 1), 25);

        return array(
            'plns' => $plns,
        );
    }

    /**
     * Creates a new Pln entity.
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/new", name="pln_new")
     * @Method({"GET", "POST"})
     * @Template()
     * @param Request $request
     */
    public function newAction(Request $request) {
        $pln = new Pln();
        $form = $this->createForm(PlnType::class, $pln);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($pln);
            $em->flush();

            $this->addFlash('success', 'The new pln was created.');
            return $this->redirectToRoute('pln_show', array('id' => $pln->getId()));
        }

        return array(
            'pln' => $pln,
            'form' => $form->createView(),
        );
    }

    /**
     * Finds and displays a Pln entity.
     *
     * @Route("/{id}", name="pln_show")
     * @Method("GET")
     * @Template()
     * @param Pln $pln
     */
    public function showAction(Pln $pln) {

        return array(
            'pln' => $pln,
        );
    }

    /**
     * Displays a form to edit an existing Pln entity.
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/{id}/edit", name="pln_edit")
     * @Method({"GET", "POST"})
     * @Template()
     * @param Request $request
     * @param Pln $pln
     */
    public function editAction(Request $request, Pln $pln) {
        $editForm = $this->createForm(PlnType::class, $pln);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            $this->addFlash('success', 'The pln has been updated.');
            return $this->redirectToRoute('pln_show', array('id' => $pln->getId()));
        }

        return array(
            'pln' => $pln,
            'edit_form' => $editForm->createView(),
        );
    }

    /**
     * Deletes a Pln entity.
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/{id}/delete", name="pln_delete")
     * @Method("GET")
     * @param Request $request
     * @param Pln $pln
     */
    public function deleteAction(Request $request, Pln $pln) {
        $em = $this->getDoctrine()->getManager();
        $em->remove($pln);
        $em->flush();
        $this->addFlash('success', 'The pln was deleted.');

        return $this->redirectToRoute('pln_index');
    }

}
