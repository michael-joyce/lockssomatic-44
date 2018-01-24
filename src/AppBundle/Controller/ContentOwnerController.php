<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Controller;

use AppBundle\Entity\ContentOwner;
use AppBundle\Form\ContentOwnerType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * ContentOwner controller.
 *
 * @Route("/content_owner")
 * @Security("has_role('ROLE_USER')")
 */
class ContentOwnerController extends Controller {

    /**
     * Lists all ContentOwner entities.
     *
     * @param Request $request
     *   The HTTP request instance.
     *
     * @Route("/", name="content_owner_index")
     * @Method("GET")
     * @Template()
     */
    public function indexAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from(ContentOwner::class, 'e')->orderBy('e.id', 'ASC');
        $query = $qb->getQuery();
        $paginator = $this->get('knp_paginator');
        $contentOwners = $paginator->paginate($query, $request->query->getint('page', 1), 25);

        return array(
            'contentOwners' => $contentOwners,
        );
    }

    /**
     * Creates a new ContentOwner entity.
     *
     * @param Request $request
     *   The HTTP request instance.
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/new", name="content_owner_new")
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function newAction(Request $request) {
        $contentOwner = new ContentOwner();
        $form = $this->createForm(ContentOwnerType::class, $contentOwner);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($contentOwner);
            $em->flush();

            $this->addFlash('success', 'The new contentOwner was created.');
            return $this->redirectToRoute('content_owner_show', array('id' => $contentOwner->getId()));
        }

        return array(
            'contentOwner' => $contentOwner,
            'form' => $form->createView(),
        );
    }

    /**
     * Finds and displays a ContentOwner entity.
     *
     * @param ContentOwner $contentOwner
     *   Content owner to show, determined by the URL.
     *
     * @Route("/{id}", name="content_owner_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction(ContentOwner $contentOwner) {

        return array(
            'contentOwner' => $contentOwner,
        );
    }

    /**
     * Displays a form to edit an existing ContentOwner entity.
     *
     * @param Request $request
     *   The HTTP request instance.
     * @param ContentOwner $contentOwner
     *   Content owner to show, determined by the URL.
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/{id}/edit", name="content_owner_edit")
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function editAction(Request $request, ContentOwner $contentOwner) {
        $editForm = $this->createForm(ContentOwnerType::class, $contentOwner);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            $this->addFlash('success', 'The contentOwner has been updated.');
            return $this->redirectToRoute('content_owner_show', array('id' => $contentOwner->getId()));
        }

        return array(
            'contentOwner' => $contentOwner,
            'edit_form' => $editForm->createView(),
        );
    }

    /**
     * Deletes a ContentOwner entity.
     *
     * @param Request $request
     *   The HTTP request instance.
     * @param ContentOwner $contentOwner
     *   Content owner to show, determined by the URL.
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/{id}/delete", name="content_owner_delete")
     * @Method("GET")
     */
    public function deleteAction(Request $request, ContentOwner $contentOwner) {
        $em = $this->getDoctrine()->getManager();
        $em->remove($contentOwner);
        $em->flush();
        $this->addFlash('success', 'The contentOwner was deleted.');

        return $this->redirectToRoute('content_owner_index');
    }

}
