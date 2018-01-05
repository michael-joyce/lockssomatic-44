<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\Pln;
use AppBundle\Form\PlnType;

/**
 * Pln controller.
 *
 * @Route("/pln")
 */
class PlnController extends Controller {

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
     * Search for Pln entities.
     *
     * To make this work, add a method like this one to the 
     * AppBundle:Pln repository. Replace the fieldName with
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
     * @Route("/search", name="pln_search")
     * @Method("GET")
     * @Template()
     * @param Request $request
     */
    public function searchAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:Pln');
        $q = $request->query->get('q');
        if ($q) {
            $query = $repo->searchQuery($q);
            $paginator = $this->get('knp_paginator');
            $plns = $paginator->paginate($query, $request->query->getInt('page', 1), 25);
        } else {
            $plns = array();
        }

        return array(
            'plns' => $plns,
            'q' => $q,
        );
    }

    /**
     * Full text search for Pln entities.
     *
     * To make this work, add a method like this one to the 
     * AppBundle:Pln repository. Replace the fieldName with
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
     * fulltext indexes on your Pln entity.
     *     ORM\Index(name="alias_name_idx",columns="name", flags={"fulltext"})
     *
     *
     * @Route("/fulltext", name="pln_fulltext")
     * @Method("GET")
     * @Template()
     * @param Request $request
     * @return array
     */
    public function fulltextAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:Pln');
        $q = $request->query->get('q');
        if ($q) {
            $query = $repo->fulltextQuery($q);
            $paginator = $this->get('knp_paginator');
            $plns = $paginator->paginate($query, $request->query->getInt('page', 1), 25);
        } else {
            $plns = array();
        }

        return array(
            'plns' => $plns,
            'q' => $q,
        );
    }

    /**
     * Creates a new Pln entity.
     *
     * @Route("/new", name="pln_new")
     * @Method({"GET", "POST"})
     * @Template()
     * @param Request $request
     */
    public function newAction(Request $request) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
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
     * @Route("/{id}/edit", name="pln_edit")
     * @Method({"GET", "POST"})
     * @Template()
     * @param Request $request
     * @param Pln $pln
     */
    public function editAction(Request $request, Pln $pln) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
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
     * @Route("/{id}/delete", name="pln_delete")
     * @Method("GET")
     * @param Request $request
     * @param Pln $pln
     */
    public function deleteAction(Request $request, Pln $pln) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $em = $this->getDoctrine()->getManager();
        $em->remove($pln);
        $em->flush();
        $this->addFlash('success', 'The pln was deleted.');

        return $this->redirectToRoute('pln_index');
    }

}
