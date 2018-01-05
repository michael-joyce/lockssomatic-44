<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\AuProperty;
use AppBundle\Form\AuPropertyType;

/**
 * AuProperty controller.
 *
 * @Route("/pln/{plnId}/au/{auId}/property")
 */
class AuPropertyController extends Controller {

    /**
     * Lists all AuProperty entities.
     *
     * @Route("/", name="pln_au_property_index")
     * @Method("GET")
     * @Template()
     * @param Request $request
     */
    public function indexAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from(AuProperty::class, 'e')->orderBy('e.id', 'ASC');
        $query = $qb->getQuery();
        $paginator = $this->get('knp_paginator');
        $auProperties = $paginator->paginate($query, $request->query->getint('page', 1), 25);

        return array(
            'auProperties' => $auProperties,
        );
    }

    /**
     * Search for AuProperty entities.
     *
     * To make this work, add a method like this one to the 
     * AppBundle:AuProperty repository. Replace the fieldName with
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
     * @Route("/search", name="pln_au_property_search")
     * @Method("GET")
     * @Template()
     * @param Request $request
     */
    public function searchAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:AuProperty');
        $q = $request->query->get('q');
        if ($q) {
            $query = $repo->searchQuery($q);
            $paginator = $this->get('knp_paginator');
            $auProperties = $paginator->paginate($query, $request->query->getInt('page', 1), 25);
        } else {
            $auProperties = array();
        }

        return array(
            'auProperties' => $auProperties,
            'q' => $q,
        );
    }

    /**
     * Full text search for AuProperty entities.
     *
     * To make this work, add a method like this one to the 
     * AppBundle:AuProperty repository. Replace the fieldName with
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
     * fulltext indexes on your AuProperty entity.
     *     ORM\Index(name="alias_name_idx",columns="name", flags={"fulltext"})
     *
     *
     * @Route("/fulltext", name="pln_au_property_fulltext")
     * @Method("GET")
     * @Template()
     * @param Request $request
     * @return array
     */
    public function fulltextAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:AuProperty');
        $q = $request->query->get('q');
        if ($q) {
            $query = $repo->fulltextQuery($q);
            $paginator = $this->get('knp_paginator');
            $auProperties = $paginator->paginate($query, $request->query->getInt('page', 1), 25);
        } else {
            $auProperties = array();
        }

        return array(
            'auProperties' => $auProperties,
            'q' => $q,
        );
    }

    /**
     * Creates a new AuProperty entity.
     *
     * @Route("/new", name="pln_au_property_new")
     * @Method({"GET", "POST"})
     * @Template()
     * @param Request $request
     */
    public function newAction(Request $request) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $auProperty = new AuProperty();
        $form = $this->createForm(AuPropertyType::class, $auProperty);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($auProperty);
            $em->flush();

            $this->addFlash('success', 'The new auProperty was created.');
            return $this->redirectToRoute('pln_au_property_show', array('id' => $auProperty->getId()));
        }

        return array(
            'auProperty' => $auProperty,
            'form' => $form->createView(),
        );
    }

    /**
     * Finds and displays a AuProperty entity.
     *
     * @Route("/{id}", name="pln_au_property_show")
     * @Method("GET")
     * @Template()
     * @param AuProperty $auProperty
     */
    public function showAction(AuProperty $auProperty) {

        return array(
            'auProperty' => $auProperty,
        );
    }

    /**
     * Displays a form to edit an existing AuProperty entity.
     *
     * @Route("/{id}/edit", name="pln_au_property_edit")
     * @Method({"GET", "POST"})
     * @Template()
     * @param Request $request
     * @param AuProperty $auProperty
     */
    public function editAction(Request $request, AuProperty $auProperty) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $editForm = $this->createForm(AuPropertyType::class, $auProperty);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            $this->addFlash('success', 'The auProperty has been updated.');
            return $this->redirectToRoute('pln_au_property_show', array('id' => $auProperty->getId()));
        }

        return array(
            'auProperty' => $auProperty,
            'edit_form' => $editForm->createView(),
        );
    }

    /**
     * Deletes a AuProperty entity.
     *
     * @Route("/{id}/delete", name="pln_au_property_delete")
     * @Method("GET")
     * @param Request $request
     * @param AuProperty $auProperty
     */
    public function deleteAction(Request $request, AuProperty $auProperty) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $em = $this->getDoctrine()->getManager();
        $em->remove($auProperty);
        $em->flush();
        $this->addFlash('success', 'The auProperty was deleted.');

        return $this->redirectToRoute('pln_au_property_index');
    }

}
