<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\BoxStatus;
use AppBundle\Form\BoxStatusType;

/**
 * BoxStatus controller.
 *
 * @Route("/pln/{plnId}/box/{boxId}/status")
 */
class BoxStatusController extends Controller {

    /**
     * Lists all BoxStatus entities.
     *
     * @Route("/", name="box_status_index")
     * @Method("GET")
     * @Template()
     * @param Request $request
     */
    public function indexAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from(BoxStatus::class, 'e')->orderBy('e.id', 'ASC');
        $query = $qb->getQuery();
        $paginator = $this->get('knp_paginator');
        $boxStatuses = $paginator->paginate($query, $request->query->getint('page', 1), 25);

        return array(
            'boxStatuses' => $boxStatuses,
        );
    }

    /**
     * Search for BoxStatus entities.
     *
     * To make this work, add a method like this one to the 
     * AppBundle:BoxStatus repository. Replace the fieldName with
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
     * @Route("/search", name="box_status_search")
     * @Method("GET")
     * @Template()
     * @param Request $request
     */
    public function searchAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:BoxStatus');
        $q = $request->query->get('q');
        if ($q) {
            $query = $repo->searchQuery($q);
            $paginator = $this->get('knp_paginator');
            $boxStatuses = $paginator->paginate($query, $request->query->getInt('page', 1), 25);
        } else {
            $boxStatuses = array();
        }

        return array(
            'boxStatuses' => $boxStatuses,
            'q' => $q,
        );
    }

    /**
     * Full text search for BoxStatus entities.
     *
     * To make this work, add a method like this one to the 
     * AppBundle:BoxStatus repository. Replace the fieldName with
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
     * fulltext indexes on your BoxStatus entity.
     *     ORM\Index(name="alias_name_idx",columns="name", flags={"fulltext"})
     *
     *
     * @Route("/fulltext", name="box_status_fulltext")
     * @Method("GET")
     * @Template()
     * @param Request $request
     * @return array
     */
    public function fulltextAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:BoxStatus');
        $q = $request->query->get('q');
        if ($q) {
            $query = $repo->fulltextQuery($q);
            $paginator = $this->get('knp_paginator');
            $boxStatuses = $paginator->paginate($query, $request->query->getInt('page', 1), 25);
        } else {
            $boxStatuses = array();
        }

        return array(
            'boxStatuses' => $boxStatuses,
            'q' => $q,
        );
    }

    /**
     * Creates a new BoxStatus entity.
     *
     * @Route("/new", name="box_status_new")
     * @Method({"GET", "POST"})
     * @Template()
     * @param Request $request
     */
    public function newAction(Request $request) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $boxStatus = new BoxStatus();
        $form = $this->createForm(BoxStatusType::class, $boxStatus);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($boxStatus);
            $em->flush();

            $this->addFlash('success', 'The new boxStatus was created.');
            return $this->redirectToRoute('box_status_show', array('id' => $boxStatus->getId()));
        }

        return array(
            'boxStatus' => $boxStatus,
            'form' => $form->createView(),
        );
    }

    /**
     * Finds and displays a BoxStatus entity.
     *
     * @Route("/{id}", name="box_status_show")
     * @Method("GET")
     * @Template()
     * @param BoxStatus $boxStatus
     */
    public function showAction(BoxStatus $boxStatus) {

        return array(
            'boxStatus' => $boxStatus,
        );
    }

    /**
     * Displays a form to edit an existing BoxStatus entity.
     *
     * @Route("/{id}/edit", name="box_status_edit")
     * @Method({"GET", "POST"})
     * @Template()
     * @param Request $request
     * @param BoxStatus $boxStatus
     */
    public function editAction(Request $request, BoxStatus $boxStatus) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $editForm = $this->createForm(BoxStatusType::class, $boxStatus);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            $this->addFlash('success', 'The boxStatus has been updated.');
            return $this->redirectToRoute('box_status_show', array('id' => $boxStatus->getId()));
        }

        return array(
            'boxStatus' => $boxStatus,
            'edit_form' => $editForm->createView(),
        );
    }

    /**
     * Deletes a BoxStatus entity.
     *
     * @Route("/{id}/delete", name="box_status_delete")
     * @Method("GET")
     * @param Request $request
     * @param BoxStatus $boxStatus
     */
    public function deleteAction(Request $request, BoxStatus $boxStatus) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $em = $this->getDoctrine()->getManager();
        $em->remove($boxStatus);
        $em->flush();
        $this->addFlash('success', 'The boxStatus was deleted.');

        return $this->redirectToRoute('box_status_index');
    }

}
