<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\DepositStatus;
use AppBundle\Form\DepositStatusType;

/**
 * DepositStatus controller.
 *
 * @Route("/pln/{plnId}/deposit/{depositId}/status")
 */
class DepositStatusController extends Controller {

    /**
     * Lists all DepositStatus entities.
     *
     * @Route("/", name="deposit_status_index")
     * @Method("GET")
     * @Template()
     * @param Request $request
     */
    public function indexAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from(DepositStatus::class, 'e')->orderBy('e.id', 'ASC');
        $query = $qb->getQuery();
        $paginator = $this->get('knp_paginator');
        $depositStatuses = $paginator->paginate($query, $request->query->getint('page', 1), 25);

        return array(
            'depositStatuses' => $depositStatuses,
        );
    }

    /**
     * Search for DepositStatus entities.
     *
     * To make this work, add a method like this one to the 
     * AppBundle:DepositStatus repository. Replace the fieldName with
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
     * @Route("/search", name="deposit_status_search")
     * @Method("GET")
     * @Template()
     * @param Request $request
     */
    public function searchAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:DepositStatus');
        $q = $request->query->get('q');
        if ($q) {
            $query = $repo->searchQuery($q);
            $paginator = $this->get('knp_paginator');
            $depositStatuses = $paginator->paginate($query, $request->query->getInt('page', 1), 25);
        } else {
            $depositStatuses = array();
        }

        return array(
            'depositStatuses' => $depositStatuses,
            'q' => $q,
        );
    }

    /**
     * Full text search for DepositStatus entities.
     *
     * To make this work, add a method like this one to the 
     * AppBundle:DepositStatus repository. Replace the fieldName with
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
     * fulltext indexes on your DepositStatus entity.
     *     ORM\Index(name="alias_name_idx",columns="name", flags={"fulltext"})
     *
     *
     * @Route("/fulltext", name="deposit_status_fulltext")
     * @Method("GET")
     * @Template()
     * @param Request $request
     * @return array
     */
    public function fulltextAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:DepositStatus');
        $q = $request->query->get('q');
        if ($q) {
            $query = $repo->fulltextQuery($q);
            $paginator = $this->get('knp_paginator');
            $depositStatuses = $paginator->paginate($query, $request->query->getInt('page', 1), 25);
        } else {
            $depositStatuses = array();
        }

        return array(
            'depositStatuses' => $depositStatuses,
            'q' => $q,
        );
    }

    /**
     * Creates a new DepositStatus entity.
     *
     * @Route("/new", name="deposit_status_new")
     * @Method({"GET", "POST"})
     * @Template()
     * @param Request $request
     */
    public function newAction(Request $request) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $depositStatus = new DepositStatus();
        $form = $this->createForm(DepositStatusType::class, $depositStatus);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($depositStatus);
            $em->flush();

            $this->addFlash('success', 'The new depositStatus was created.');
            return $this->redirectToRoute('deposit_status_show', array('id' => $depositStatus->getId()));
        }

        return array(
            'depositStatus' => $depositStatus,
            'form' => $form->createView(),
        );
    }

    /**
     * Finds and displays a DepositStatus entity.
     *
     * @Route("/{id}", name="deposit_status_show")
     * @Method("GET")
     * @Template()
     * @param DepositStatus $depositStatus
     */
    public function showAction(DepositStatus $depositStatus) {

        return array(
            'depositStatus' => $depositStatus,
        );
    }

    /**
     * Displays a form to edit an existing DepositStatus entity.
     *
     * @Route("/{id}/edit", name="deposit_status_edit")
     * @Method({"GET", "POST"})
     * @Template()
     * @param Request $request
     * @param DepositStatus $depositStatus
     */
    public function editAction(Request $request, DepositStatus $depositStatus) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $editForm = $this->createForm(DepositStatusType::class, $depositStatus);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            $this->addFlash('success', 'The depositStatus has been updated.');
            return $this->redirectToRoute('deposit_status_show', array('id' => $depositStatus->getId()));
        }

        return array(
            'depositStatus' => $depositStatus,
            'edit_form' => $editForm->createView(),
        );
    }

    /**
     * Deletes a DepositStatus entity.
     *
     * @Route("/{id}/delete", name="deposit_status_delete")
     * @Method("GET")
     * @param Request $request
     * @param DepositStatus $depositStatus
     */
    public function deleteAction(Request $request, DepositStatus $depositStatus) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $em = $this->getDoctrine()->getManager();
        $em->remove($depositStatus);
        $em->flush();
        $this->addFlash('success', 'The depositStatus was deleted.');

        return $this->redirectToRoute('deposit_status_index');
    }

}
