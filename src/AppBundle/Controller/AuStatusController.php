<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\AuStatus;
use AppBundle\Form\AuStatusType;

/**
 * AuStatus controller.
 *
 * @Route("/pln/{plnId}/au/{auId}/status")
 */
class AuStatusController extends Controller {

    /**
     * Lists all AuStatus entities.
     *
     * @Route("/", name="au_status_index")
     * @Method("GET")
     * @Template()
     * @param Request $request
     */
    public function indexAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from(AuStatus::class, 'e')->orderBy('e.id', 'ASC');
        $query = $qb->getQuery();
        $paginator = $this->get('knp_paginator');
        $auStatuses = $paginator->paginate($query, $request->query->getint('page', 1), 25);

        return array(
            'auStatuses' => $auStatuses,
        );
    }

    /**
     * Search for AuStatus entities.
     *
     * To make this work, add a method like this one to the 
     * AppBundle:AuStatus repository. Replace the fieldName with
     * something appropriate, and adjust the generated search.html.twig
     * template.
     * 
      //    public function searchQuery($q) {
      //        $qb = $this->createQueryBuilder('e');
      //        $qb->where("e.fieldName like '%$q%'");
      //        return $qb->getQuery();
      //    }
     *
     *
     * @Route("/search", name="au_status_search")
     * @Method("GET")
     * @Template()
     * @param Request $request
     */
    public function searchAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:AuStatus');
        $q = $request->query->get('q');
        if ($q) {
            $query = $repo->searchQuery($q);
            $paginator = $this->get('knp_paginator');
            $auStatuses = $paginator->paginate($query, $request->query->getInt('page', 1), 25);
        } else {
            $auStatuses = array();
        }

        return array(
            'auStatuses' => $auStatuses,
            'q' => $q,
        );
    }

    /**
     * Full text search for AuStatus entities.
     *
     * To make this work, add a method like this one to the 
     * AppBundle:AuStatus repository. Replace the fieldName with
     * something appropriate, and adjust the generated fulltext.html.twig
     * template.
     * 
      //    public function fulltextQuery($q) {
      //        $qb = $this->createQueryBuilder('e');
      //        $qb->addSelect("MATCH_AGAINST (e.name, :q 'IN BOOLEAN MODE') as score");
      //        $qb->add('where', "MATCH_AGAINST (e.name, :q 'IN BOOLEAN MODE') > 0.5");
      //        $qb->orderBy('score', 'desc');
      //        $qb->setParameter('q', $q);
      //        return $qb->getQuery();
      //    }
     * 
     * Requires a MatchAgainst function be added to doctrine, and appropriate
     * fulltext indexes on your AuStatus entity.
     *     ORM\Index(name="alias_name_idx",columns="name", flags={"fulltext"})
     *
     *
     * @Route("/fulltext", name="au_status_fulltext")
     * @Method("GET")
     * @Template()
     * @param Request $request
     * @return array
     */
    public function fulltextAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:AuStatus');
        $q = $request->query->get('q');
        if ($q) {
            $query = $repo->fulltextQuery($q);
            $paginator = $this->get('knp_paginator');
            $auStatuses = $paginator->paginate($query, $request->query->getInt('page', 1), 25);
        } else {
            $auStatuses = array();
        }

        return array(
            'auStatuses' => $auStatuses,
            'q' => $q,
        );
    }

    /**
     * Creates a new AuStatus entity.
     *
     * @Route("/new", name="au_status_new")
     * @Method({"GET", "POST"})
     * @Template()
     * @param Request $request
     */
    public function newAction(Request $request) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $auStatus = new AuStatus();
        $form = $this->createForm(AuStatusType::class, $auStatus);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($auStatus);
            $em->flush();

            $this->addFlash('success', 'The new auStatus was created.');
            return $this->redirectToRoute('au_status_show', array('id' => $auStatus->getId()));
        }

        return array(
            'auStatus' => $auStatus,
            'form' => $form->createView(),
        );
    }

    /**
     * Finds and displays a AuStatus entity.
     *
     * @Route("/{id}", name="au_status_show")
     * @Method("GET")
     * @Template()
     * @param AuStatus $auStatus
     */
    public function showAction(AuStatus $auStatus) {

        return array(
            'auStatus' => $auStatus,
        );
    }

    /**
     * Displays a form to edit an existing AuStatus entity.
     *
     * @Route("/{id}/edit", name="au_status_edit")
     * @Method({"GET", "POST"})
     * @Template()
     * @param Request $request
     * @param AuStatus $auStatus
     */
    public function editAction(Request $request, AuStatus $auStatus) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $editForm = $this->createForm(AuStatusType::class, $auStatus);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            $this->addFlash('success', 'The auStatus has been updated.');
            return $this->redirectToRoute('au_status_show', array('id' => $auStatus->getId()));
        }

        return array(
            'auStatus' => $auStatus,
            'edit_form' => $editForm->createView(),
        );
    }

    /**
     * Deletes a AuStatus entity.
     *
     * @Route("/{id}/delete", name="au_status_delete")
     * @Method("GET")
     * @param Request $request
     * @param AuStatus $auStatus
     */
    public function deleteAction(Request $request, AuStatus $auStatus) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $em = $this->getDoctrine()->getManager();
        $em->remove($auStatus);
        $em->flush();
        $this->addFlash('success', 'The auStatus was deleted.');

        return $this->redirectToRoute('au_status_index');
    }

}
