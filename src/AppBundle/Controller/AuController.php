<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\Au;
use AppBundle\Form\AuType;

/**
 * Au controller.
 *
 * @Route("/pln/{plnId}/au")
 */
class AuController extends Controller {

    /**
     * Lists all Au entities.
     *
     * @Route("/", name="pln_au_index")
     * @Method("GET")
     * @Template()
     * @param Request $request
     */
    public function indexAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from(Au::class, 'e')->orderBy('e.id', 'ASC');
        $query = $qb->getQuery();
        $paginator = $this->get('knp_paginator');
        $aus = $paginator->paginate($query, $request->query->getint('page', 1), 25);

        return array(
            'aus' => $aus,
        );
    }

    /**
     * Search for Au entities.
     *
     * To make this work, add a method like this one to the 
     * AppBundle:Au repository. Replace the fieldName with
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
     * @Route("/search", name="pln_au_search")
     * @Method("GET")
     * @Template()
     * @param Request $request
     */
    public function searchAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:Au');
        $q = $request->query->get('q');
        if ($q) {
            $query = $repo->searchQuery($q);
            $paginator = $this->get('knp_paginator');
            $aus = $paginator->paginate($query, $request->query->getInt('page', 1), 25);
        } else {
            $aus = array();
        }

        return array(
            'aus' => $aus,
            'q' => $q,
        );
    }

    /**
     * Full text search for Au entities.
     *
     * To make this work, add a method like this one to the 
     * AppBundle:Au repository. Replace the fieldName with
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
     * fulltext indexes on your Au entity.
     *     ORM\Index(name="alias_name_idx",columns="name", flags={"fulltext"})
     *
     *
     * @Route("/fulltext", name="pln_au_fulltext")
     * @Method("GET")
     * @Template()
     * @param Request $request
     * @return array
     */
    public function fulltextAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:Au');
        $q = $request->query->get('q');
        if ($q) {
            $query = $repo->fulltextQuery($q);
            $paginator = $this->get('knp_paginator');
            $aus = $paginator->paginate($query, $request->query->getInt('page', 1), 25);
        } else {
            $aus = array();
        }

        return array(
            'aus' => $aus,
            'q' => $q,
        );
    }

    /**
     * Creates a new Au entity.
     *
     * @Route("/new", name="pln_au_new")
     * @Method({"GET", "POST"})
     * @Template()
     * @param Request $request
     */
    public function newAction(Request $request) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $au = new Au();
        $form = $this->createForm(AuType::class, $au);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($au);
            $em->flush();

            $this->addFlash('success', 'The new au was created.');
            return $this->redirectToRoute('pln_au_show', array('id' => $au->getId()));
        }

        return array(
            'au' => $au,
            'form' => $form->createView(),
        );
    }

    /**
     * Finds and displays a Au entity.
     *
     * @Route("/{id}", name="pln_au_show")
     * @Method("GET")
     * @Template()
     * @param Au $au
     */
    public function showAction(Au $au) {

        return array(
            'au' => $au,
        );
    }

    /**
     * Displays a form to edit an existing Au entity.
     *
     * @Route("/{id}/edit", name="pln_au_edit")
     * @Method({"GET", "POST"})
     * @Template()
     * @param Request $request
     * @param Au $au
     */
    public function editAction(Request $request, Au $au) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $editForm = $this->createForm(AuType::class, $au);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            $this->addFlash('success', 'The au has been updated.');
            return $this->redirectToRoute('pln_au_show', array('id' => $au->getId()));
        }

        return array(
            'au' => $au,
            'edit_form' => $editForm->createView(),
        );
    }

    /**
     * Deletes a Au entity.
     *
     * @Route("/{id}/delete", name="pln_au_delete")
     * @Method("GET")
     * @param Request $request
     * @param Au $au
     */
    public function deleteAction(Request $request, Au $au) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $em = $this->getDoctrine()->getManager();
        $em->remove($au);
        $em->flush();
        $this->addFlash('success', 'The au was deleted.');

        return $this->redirectToRoute('pln_au_index');
    }

}
