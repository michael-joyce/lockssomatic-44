<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Content;
use AppBundle\Entity\Deposit;
use AppBundle\Entity\Pln;
use AppBundle\Form\ContentType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Content controller.
 *
 * @Security("has_role('ROLE_USER')")
 * @Route("/pln/{plnId}/deposit/{depositId}/content")
 * @ParamConverter("pln", options={"id"="plnId"})
 * @ParamConverter("deposit", options={"id"="depositId"})
 */
class ContentController extends Controller {

    /**
     * Lists all Content entities.
     *
     * @param Request $request
     *   The HTTP request instance.
     * @param Pln $pln
     *   The pln, determined from the URL.
     * @param Deposit $deposit
     *   The deposit determined from the URL.
     *
     * @Route("/", name="deposit_content_index")
     * @Method("GET")
     * @Template()
     */
    public function indexAction(Request $request, Pln $pln, Deposit $deposit) {
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from(Content::class, 'e')->orderBy('e.id', 'ASC');
        $query = $qb->getQuery();
        $paginator = $this->get('knp_paginator');
        $contents = $paginator->paginate($query, $request->query->getint('page', 1), 25);

        return array(
            'contents' => $contents,
            'pln' => $pln,
            'deposit' => $deposit,
        );
    }

    /**
     * Creates a new Content entity.
     *
     * @param Request $request
     *   The HTTP request instance.
     * @param Pln $pln
     *   The pln, determined from the URL.
     * @param Deposit $deposit
     *   The deposit determined from the URL.
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/new", name="deposit_content_new")
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function newAction(Request $request, Pln $pln, Deposit $deposit) {
        $content = new Content();
        $content->setDateDeposited($deposit->getDateDeposited());
        $form = $this->createForm(ContentType::class, $content);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($content);
            $em->flush();

            $this->addFlash('success', 'The new content was created.');
            return $this->redirectToRoute('deposit_content_show', array(
                        'id' => $content->getId(),
                        'plnId' => $pln->getId(),
                        'depositId' => $deposit->getId(),
            ));
        }

        return array(
            'content' => $content,
            'form' => $form->createView(),
            'pln' => $pln,
            'deposit' => $deposit,
        );
    }

    /**
     * Finds and displays a Content entity.
     *
     * @param Content $content
     *   The content to show, determined by the URL.
     * @param Pln $pln
     *   The pln, determined from the URL.
     * @param Deposit $deposit
     *   The deposit determined from the URL.
     *
     * @Route("/{id}", name="deposit_content_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction(Content $content, Pln $pln, Deposit $deposit) {

        return array(
            'content' => $content,
            'pln' => $pln,
            'deposit' => $deposit,
        );
    }

    /**
     * Displays a form to edit an existing Content entity.
     *
     * @param Request $request
     *   The HTTP request instance.
     * @param Content $content
     *   The content item, determined from the URL.
     * @param Pln $pln
     *   The pln, determined from the URL.
     * @param Deposit $deposit
     *   The deposit determined from the URL.
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/{id}/edit", name="deposit_content_edit")
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function editAction(Request $request, Content $content, Pln $pln, Deposit $deposit) {
        $editForm = $this->createForm(ContentType::class, $content);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            $this->addFlash('success', 'The content has been updated.');
            return $this->redirectToRoute('deposit_content_show', array(
                        'id' => $content->getId(),
                        'plnId' => $pln->getId(),
                        'depositId' => $deposit->getId(),
            ));
        }

        return array(
            'content' => $content,
            'edit_form' => $editForm->createView(),
            'pln' => $pln,
            'deposit' => $deposit,
        );
    }

    /**
     * Deletes a Content entity.
     *
     * @param Request $request
     *   The HTTP request instance.
     * @param Content $content
     *   The content item, determined from the URL.
     * @param Pln $pln
     *   The pln, determined from the URL.
     * @param Deposit $deposit
     *   The deposit determined from the URL.
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/{id}/delete", name="deposit_content_delete")
     * @Method("GET")
     */
    public function deleteAction(Request $request, Content $content, Pln $pln, Deposit $deposit) {
        $em = $this->getDoctrine()->getManager();
        $em->remove($content);
        $em->flush();
        $this->addFlash('success', 'The content was deleted.');

        return $this->redirectToRoute('deposit_content_index', array(
                    'plnId' => $pln->getId(),
                    'depositId' => $deposit->getId(),
        ));
    }

}
