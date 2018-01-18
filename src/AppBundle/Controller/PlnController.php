<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Pln;
use AppBundle\Form\FileUploadType;
use AppBundle\Form\PlnType;
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

    const KEYSTORE_MIMETYPES = array(
        'application/x-java-keystore',
    );
    
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
     * Upload and add/replace the java keystore file for the Pln's plugins.
     * 
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/{id}/keystore", name="pln_keystore")
     * @Method({"GET", "POST"})
     * @Template()
     * @param Request $request
     */
    public function keystoreAction(Request $request, Pln $pln, FilePaths $filePaths) {
        $form = $this->createForm(FileUploadType::class, null, [
            'help' => 'Select a java keystore file.',
            'label' => 'Keystore File',
        ]);
        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $file = $data['file'];
            if( ! in_array($file->getMimeType(), self::KEYSTORE_MIMETYPES)) {
                throw new Exception("Upload does not look like a keystore. Mime type is {$file->getMimeType()}");
            }
            if(!preg_match('/^[a-zA-Z0-9 .-]+\.keystore$/', $file->getClientOriginalName())) {
                throw new Exception("Upload does not look like a keystore. File name is strange.");
            }
            $filename = $file->getClientOriginalName();
            $file->move($filePaths->getLockssKeystoreDir($pln), $filename);
            $pln->setKeystore($filePaths->getLockssKeystoreDir($pln) . '/' . $filename);

            $em = $this->getDoctrine()->getManager();
            $em->flush();

            $this->addFlash('success', 'The keystore has been updated.');
            return $this->redirectToRoute('pln_show', array('id' => $pln->getId()));
        }
        
        return array(
            'form' => $form->createView(),
            'pln' => $pln,
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
