<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Controller;

use AppBundle\Entity\ContentProvider;
use AppBundle\Form\ContentProviderType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * ContentProvider controller.
 *
 * @Security("has_role('ROLE_USER')")
 * @Route("/content_provider")
 */
class ContentProviderController extends Controller {

    /**
     * Lists all ContentProvider entities.
     *
     * @param Request $request
     *   The HTTP request instance.
     *
     * @return array
     *   Array data for the template processor.
     *
     * @Route("/", name="content_provider_index")
     * @Method("GET")
     * @Template()
     */
    public function indexAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from(ContentProvider::class, 'e')->orderBy('e.id', 'ASC');
        $query = $qb->getQuery();
        $paginator = $this->get('knp_paginator');
        $contentProviders = $paginator->paginate($query, $request->query->getint('page', 1), 25);

        return array(
            'contentProviders' => $contentProviders,
        );
    }

    /**
     * Creates a new ContentProvider entity.
     *
     * @param Request $request
     *   The HTTP request instance.
     *
     * @return array
     *   Array data for the template processor.
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/new", name="content_provider_new")
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function newAction(Request $request) {
        $contentProvider = new ContentProvider();
        $form = $this->createForm(ContentProviderType::class, $contentProvider);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($contentProvider);
            $em->flush();

            $this->addFlash('success', 'The new contentProvider was created.');
            return $this->redirectToRoute('content_provider_show', array('id' => $contentProvider->getId()));
        }

        return array(
            'contentProvider' => $contentProvider,
            'form' => $form->createView(),
        );
    }

    /**
     * Finds and displays a ContentProvider entity.
     *
     * @param ContentProvider $contentProvider
     *   Content provider to show, determined by the URL.
     *
     * @return array
     *   Array data for the template processor.
     *
     * @Route("/{id}", name="content_provider_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction(ContentProvider $contentProvider) {

        return array(
            'contentProvider' => $contentProvider,
        );
    }

    /**
     * Displays a form to edit an existing ContentProvider entity.
     *
     * @param Request $request
     *   The HTTP request instance.
     * @param ContentProvider $contentProvider
     *   Content provider to show, determined by the URL.
     *
     * @return array
     *   Array data for the template processor.
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/{id}/edit", name="content_provider_edit")
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function editAction(Request $request, ContentProvider $contentProvider) {
        $editForm = $this->createForm(ContentProviderType::class, $contentProvider);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            $this->addFlash('success', 'The contentProvider has been updated.');
            return $this->redirectToRoute('content_provider_show', array('id' => $contentProvider->getId()));
        }

        return array(
            'contentProvider' => $contentProvider,
            'edit_form' => $editForm->createView(),
        );
    }

    /**
     * Deletes a ContentProvider entity.
     *
     * @param Request $request
     *   The HTTP request instance.
     * @param ContentProvider $contentProvider
     *   Content provider to show, determined by the URL.
     *
     * @return array
     *   Array data for the template processor.
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/{id}/delete", name="content_provider_delete")
     * @Method("GET")
     */
    public function deleteAction(Request $request, ContentProvider $contentProvider) {
        $em = $this->getDoctrine()->getManager();
        $em->remove($contentProvider);
        $em->flush();
        $this->addFlash('success', 'The contentProvider was deleted.');

        return $this->redirectToRoute('content_provider_index');
    }

}
