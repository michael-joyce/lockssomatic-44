<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Pln;
use AppBundle\Form\FileUploadType;
use AppBundle\Form\PlnType;
use AppBundle\Services\ConfigExporter;
use AppBundle\Services\ConfigUpdater;
use AppBundle\Services\FilePaths;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

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
     * @param Request $request
     *   The HTTP request instance.
     *
     * @return array
     *   Array data for the template processor.
     *
     * @Route("/", name="pln_index")
     * @Method("GET")
     * @Template()
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
     * @param Request $request
     *   The HTTP request instance.
     *
     * @return array
     *   Array data for the template processor.
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/new", name="pln_new")
     * @Method({"GET", "POST"})
     * @Template()
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
            return $this->redirectToRoute('pln_show', array(
                'id' => $pln->getId(),
            ));
        }

        return array(
            'pln' => $pln,
            'form' => $form->createView(),
        );
    }

    /**
     * Upload and add/replace the java keystore file for the Pln's plugins.
     *
     * @param Request $request
     *   The HTTP request instance.
     * @param Pln $pln
     *   The pln, determined from the URL.
     * @param FilePaths $filePaths
     *   Dependency injected file path service.
     *
     * @return array
     *   Array data for the template processor.
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/{id}/keystore", name="pln_keystore")
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function keystoreAction(Request $request, Pln $pln, FilePaths $filePaths) {
        $form = $this->createForm(FileUploadType::class, null, [
            'help' => 'Select a java keystore file.',
            'label' => 'Keystore File',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $file = $data['file'];
            if (!in_array($file->getMimeType(), Pln::KEYSTORE_MIMETYPES)) {
                throw new Exception("Upload does not look like a keystore. Mime type is {$file->getMimeType()}");
            }
            if (!preg_match('/^[a-zA-Z0-9 .-]+\.keystore$/', $file->getClientOriginalName())) {
                throw new Exception("Upload does not look like a keystore. File name is strange.");
            }
            $filename = $file->getClientOriginalName();
            $file->move($filePaths->getLockssKeystoreDir($pln), $filename);
            $pln->setKeystore($filePaths->getLockssKeystoreDir($pln) . '/' . $filename);

            $em = $this->getDoctrine()->getManager();
            $em->flush();

            $this->addFlash('success', 'The keystore has been updated.');
            return $this->redirectToRoute('pln_show', array(
                'id' => $pln->getId(),
            ));
        }

        return array(
            'form' => $form->createView(),
            'pln' => $pln,
        );
    }

    /**
     * Finds and displays a Pln entity.
     *
     * @param Pln $pln
     *   Pln to show, as determined by the URL.
     *
     * @return array
     *   Array data for the template processor.
     *
     * @Route("/{id}", name="pln_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction(Pln $pln) {

        return array(
            'pln' => $pln,
        );
    }

    /**
     * Displays a form to edit an existing Pln entity.
     *
     * @param Request $request
     *   The HTTP request instance.
     * @param Pln $pln
     *   Pln to show, as determined by the URL.
     *
     * @return array
     *   Array data for the template processor.
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/{id}/edit", name="pln_edit")
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function editAction(Request $request, Pln $pln) {
        $editForm = $this->createForm(PlnType::class, $pln);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            $this->addFlash('success', 'The pln has been updated.');
            return $this->redirectToRoute('pln_show', array(
                'id' => $pln->getId(),
            ));
        }

        return array(
            'pln' => $pln,
            'edit_form' => $editForm->createView(),
        );
    }

    /**
     * Exports and updates the PLN configuration.
     * 
     * Updates all configuration for a PLN and exports it to disk for LOCKSS
     * to access. Usually this should be done in a regularly scheduled cron 
     * job.
     *
     * @param Request $request
     *   The HTTP request instance.
     * @param Pln $pln
     *   Pln to show, as determined by the URL.
     *
     * @return RedirectResponse
     *   Redirects to the show action with an appropriate message.
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/{id}/export", name="pln_export")
     * @Method({"GET"})
     * @Template()
     */
    public function exportAction(Request $request, Pln $pln, ConfigExporter $exporter, ConfigUpdater $updater) {
        $updater->update($pln);
        $exporter->export($pln);
        $this->addFlash('success', 'The pln configuration has been updated and exported.');
        return $this->redirectToRoute('pln_show', array(
            'id' => $pln->getId(),
        ));
    }
    
    /**
     * Deletes a Pln entity.
     *
     * @param Request $request
     *   The HTTP request instance.
     * @param Pln $pln
     *   Pln to delete, as determined by the URL.
     *
     * @return array
     *   Array data for the template processor.
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/{id}/delete", name="pln_delete")
     * @Method("GET")
     */
    public function deleteAction(Request $request, Pln $pln) {
        $em = $this->getDoctrine()->getManager();
        $em->remove($pln);
        $em->flush();
        $this->addFlash('success', 'The pln was deleted.');

        return $this->redirectToRoute('pln_index');
    }

}
