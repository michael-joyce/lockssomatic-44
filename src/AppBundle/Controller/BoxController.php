<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Box;
use AppBundle\Entity\Pln;
use AppBundle\Form\BoxType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Box controller.
 *
 * @Security("has_role('ROLE_USER')")
 * @Route("/pln/{plnId}/box")
 * @ParamConverter("pln", options={"id"="plnId"})
 */
class BoxController extends Controller {

    /**
     * Lists all Box entities.
     *
     * @param Request $request
     * @param Pln $pln
     *
     * @return array
     *
     * @Route("/", name="box_index")
     * @Method("GET")
     * @Template()
     */
    public function indexAction(Request $request, Pln $pln) {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository(Box::class);
        $query = $repo->findBy(array('pln' => $pln), array('id' => 'ASC'));
        $paginator = $this->get('knp_paginator');
        $boxes = $paginator->paginate($query, $request->query->getint('page', 1), 25);

        return array(
            'boxes' => $boxes,
            'pln' => $pln,
        );
    }

    /**
     * Creates a new Box entity.
     *
     * @param Request $request
     * @param Pln $pln
     *
     * @return array
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/new", name="box_new")
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function newAction(Request $request, Pln $pln) {
        $box = new Box();
        $box->setPln($pln);
        $form = $this->createForm(BoxType::class, $box);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($box);
            $em->flush();

            $this->addFlash('success', 'The new box was created. You should check deposit status manually with --force once content is copied.');
            return $this->redirectToRoute('box_show', array('id' => $box->getId(), 'plnId' => $pln->getId()));
        }

        return array(
            'box' => $box,
            'pln' => $pln,
            'form' => $form->createView(),
        );
    }

    /**
     * Finds and displays a Box entity.
     *
     * @param Pln $pln
     * @param Box $box
     *
     * @return array
     *
     * @Route("/{id}", name="box_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction(Pln $pln, Box $box) {
        if ($box->getPln() !== $pln) {
            throw new NotFoundHttpException("No such box.");
        }
        return array(
            'box' => $box,
            'pln' => $pln,
        );
    }

    /**
     * Displays a form to edit an existing Box entity.
     *
     * @param Request $request
     * @param Pln $pln
     * @param Box $box
     *
     * @return array
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/{id}/edit", name="box_edit")
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function editAction(Request $request, Pln $pln, Box $box) {
        if ($box->getPln() !== $pln) {
            throw new NotFoundHttpException("No such box.");
        }
        $editForm = $this->createForm(BoxType::class, $box);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            $this->addFlash('success', 'The box has been updated.');
            return $this->redirectToRoute('box_show', array('id' => $box->getId(), 'plnId' => $pln->getId()));
        }

        return array(
            'box' => $box,
            'pln' => $pln,
            'edit_form' => $editForm->createView(),
        );
    }

    /**
     * Deletes a Box entity.
     *
     * @param Request $request
     * @param Pln $pln
     * @param Box $box
     *
     * @return array
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/{id}/delete", name="box_delete")
     * @Method("GET")
     */
    public function deleteAction(Request $request, Pln $pln, Box $box) {
        if ($box->getPln() !== $pln) {
            throw new NotFoundHttpException("No such box.");
        }
        $em = $this->getDoctrine()->getManager();
        $em->remove($box);
        $em->flush();
        $this->addFlash('success', 'The box was deleted.');

        return $this->redirectToRoute('box_index', array(
            'plnId' => $pln->getId(),
        ));
    }

}
