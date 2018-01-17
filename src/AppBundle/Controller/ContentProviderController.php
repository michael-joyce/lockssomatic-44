<?php

namespace AppBundle\Controller;

use AppBundle\Entity\ContentProvider;
use AppBundle\Form\ContentProviderType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
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
     * @Route("/", name="content_provider_index")
     * @Method("GET")
     * @Template()
     * @param Request $request
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
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/new", name="content_provider_new")
     * @Method({"GET", "POST"})
     * @Template()
     * @param Request $request
     */
    public function newAction(Request $request) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
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
     * @Route("/{id}", name="content_provider_show")
     * @Method("GET")
     * @Template()
     * @param ContentProvider $contentProvider
     */
    public function showAction(ContentProvider $contentProvider) {

        return array(
            'contentProvider' => $contentProvider,
        );
    }

    /**
     * Displays a form to edit an existing ContentProvider entity.
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/{id}/edit", name="content_provider_edit")
     * @Method({"GET", "POST"})
     * @Template()
     * @param Request $request
     * @param ContentProvider $contentProvider
     */
    public function editAction(Request $request, ContentProvider $contentProvider) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
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
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/{id}/delete", name="content_provider_delete")
     * @Method("GET")
     * @param Request $request
     * @param ContentProvider $contentProvider
     */
    public function deleteAction(Request $request, ContentProvider $contentProvider) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $em = $this->getDoctrine()->getManager();
        $em->remove($contentProvider);
        $em->flush();
        $this->addFlash('success', 'The contentProvider was deleted.');

        return $this->redirectToRoute('content_provider_index');
    }

}
