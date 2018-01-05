<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\ContentOwner;
use AppBundle\Form\ContentOwnerType;

/**
 * ContentOwner controller.
 *
 * @Route("/content_owner")
 */
class ContentOwnerController extends Controller {

    /**
     * Lists all ContentOwner entities.
     *
     * @Route("/", name="content_owner_index")
     * @Method("GET")
     * @Template()
     * @param Request $request
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
     * Search for ContentOwner entities.
     *
     * To make this work, add a method like this one to the 
     * AppBundle:ContentOwner repository. Replace the fieldName with
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
     * @Route("/search", name="content_owner_search")
     * @Method("GET")
     * @Template()
     * @param Request $request
     */
    public function searchAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:ContentOwner');
        $q = $request->query->get('q');
        if ($q) {
            $query = $repo->searchQuery($q);
            $paginator = $this->get('knp_paginator');
            $contentOwners = $paginator->paginate($query, $request->query->getInt('page', 1), 25);
        } else {
            $contentOwners = array();
        }

        return array(
            'contentOwners' => $contentOwners,
            'q' => $q,
        );
    }

    /**
     * Full text search for ContentOwner entities.
     *
     * To make this work, add a method like this one to the 
     * AppBundle:ContentOwner repository. Replace the fieldName with
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
     * fulltext indexes on your ContentOwner entity.
     *     ORM\Index(name="alias_name_idx",columns="name", flags={"fulltext"})
     *
     *
     * @Route("/fulltext", name="content_owner_fulltext")
     * @Method("GET")
     * @Template()
     * @param Request $request
     * @return array
     */
    public function fulltextAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:ContentOwner');
        $q = $request->query->get('q');
        if ($q) {
            $query = $repo->fulltextQuery($q);
            $paginator = $this->get('knp_paginator');
            $contentOwners = $paginator->paginate($query, $request->query->getInt('page', 1), 25);
        } else {
            $contentOwners = array();
        }

        return array(
            'contentOwners' => $contentOwners,
            'q' => $q,
        );
    }

    /**
     * Creates a new ContentOwner entity.
     *
     * @Route("/new", name="content_owner_new")
     * @Method({"GET", "POST"})
     * @Template()
     * @param Request $request
     */
    public function newAction(Request $request) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
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
     * @Route("/{id}", name="content_owner_show")
     * @Method("GET")
     * @Template()
     * @param ContentOwner $contentOwner
     */
    public function showAction(ContentOwner $contentOwner) {

        return array(
            'contentOwner' => $contentOwner,
        );
    }

    /**
     * Displays a form to edit an existing ContentOwner entity.
     *
     * @Route("/{id}/edit", name="content_owner_edit")
     * @Method({"GET", "POST"})
     * @Template()
     * @param Request $request
     * @param ContentOwner $contentOwner
     */
    public function editAction(Request $request, ContentOwner $contentOwner) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
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
     * @Route("/{id}/delete", name="content_owner_delete")
     * @Method("GET")
     * @param Request $request
     * @param ContentOwner $contentOwner
     */
    public function deleteAction(Request $request, ContentOwner $contentOwner) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $em = $this->getDoctrine()->getManager();
        $em->remove($contentOwner);
        $em->flush();
        $this->addFlash('success', 'The contentOwner was deleted.');

        return $this->redirectToRoute('content_owner_index');
    }

}
