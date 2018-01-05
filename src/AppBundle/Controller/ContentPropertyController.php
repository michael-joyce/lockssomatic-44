<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\ContentProperty;
use AppBundle\Form\ContentPropertyType;

/**
 * ContentProperty controller.
 *
 * @Route("/pln/{plnId}/deposit/{depositId}/content/{contentId}/property")
 */
class ContentPropertyController extends Controller {

    /**
     * Lists all ContentProperty entities.
     *
     * @Route("/", name="pln_deposit_content_property_index")
     * @Method("GET")
     * @Template()
     * @param Request $request
     */
    public function indexAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from(ContentProperty::class, 'e')->orderBy('e.id', 'ASC');
        $query = $qb->getQuery();
        $paginator = $this->get('knp_paginator');
        $contentProperties = $paginator->paginate($query, $request->query->getint('page', 1), 25);

        return array(
            'contentProperties' => $contentProperties,
        );
    }

    /**
     * Search for ContentProperty entities.
     *
     * To make this work, add a method like this one to the 
     * AppBundle:ContentProperty repository. Replace the fieldName with
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
     * @Route("/search", name="pln_deposit_content_property_search")
     * @Method("GET")
     * @Template()
     * @param Request $request
     */
    public function searchAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:ContentProperty');
        $q = $request->query->get('q');
        if ($q) {
            $query = $repo->searchQuery($q);
            $paginator = $this->get('knp_paginator');
            $contentProperties = $paginator->paginate($query, $request->query->getInt('page', 1), 25);
        } else {
            $contentProperties = array();
        }

        return array(
            'contentProperties' => $contentProperties,
            'q' => $q,
        );
    }

    /**
     * Full text search for ContentProperty entities.
     *
     * To make this work, add a method like this one to the 
     * AppBundle:ContentProperty repository. Replace the fieldName with
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
     * fulltext indexes on your ContentProperty entity.
     *     ORM\Index(name="alias_name_idx",columns="name", flags={"fulltext"})
     *
     *
     * @Route("/fulltext", name="pln_deposit_content_property_fulltext")
     * @Method("GET")
     * @Template()
     * @param Request $request
     * @return array
     */
    public function fulltextAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:ContentProperty');
        $q = $request->query->get('q');
        if ($q) {
            $query = $repo->fulltextQuery($q);
            $paginator = $this->get('knp_paginator');
            $contentProperties = $paginator->paginate($query, $request->query->getInt('page', 1), 25);
        } else {
            $contentProperties = array();
        }

        return array(
            'contentProperties' => $contentProperties,
            'q' => $q,
        );
    }

    /**
     * Creates a new ContentProperty entity.
     *
     * @Route("/new", name="pln_deposit_content_property_new")
     * @Method({"GET", "POST"})
     * @Template()
     * @param Request $request
     */
    public function newAction(Request $request) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $contentProperty = new ContentProperty();
        $form = $this->createForm(ContentPropertyType::class, $contentProperty);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($contentProperty);
            $em->flush();

            $this->addFlash('success', 'The new contentProperty was created.');
            return $this->redirectToRoute('pln_deposit_content_property_show', array('id' => $contentProperty->getId()));
        }

        return array(
            'contentProperty' => $contentProperty,
            'form' => $form->createView(),
        );
    }

    /**
     * Finds and displays a ContentProperty entity.
     *
     * @Route("/{id}", name="pln_deposit_content_property_show")
     * @Method("GET")
     * @Template()
     * @param ContentProperty $contentProperty
     */
    public function showAction(ContentProperty $contentProperty) {

        return array(
            'contentProperty' => $contentProperty,
        );
    }

    /**
     * Displays a form to edit an existing ContentProperty entity.
     *
     * @Route("/{id}/edit", name="pln_deposit_content_property_edit")
     * @Method({"GET", "POST"})
     * @Template()
     * @param Request $request
     * @param ContentProperty $contentProperty
     */
    public function editAction(Request $request, ContentProperty $contentProperty) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $editForm = $this->createForm(ContentPropertyType::class, $contentProperty);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            $this->addFlash('success', 'The contentProperty has been updated.');
            return $this->redirectToRoute('pln_deposit_content_property_show', array('id' => $contentProperty->getId()));
        }

        return array(
            'contentProperty' => $contentProperty,
            'edit_form' => $editForm->createView(),
        );
    }

    /**
     * Deletes a ContentProperty entity.
     *
     * @Route("/{id}/delete", name="pln_deposit_content_property_delete")
     * @Method("GET")
     * @param Request $request
     * @param ContentProperty $contentProperty
     */
    public function deleteAction(Request $request, ContentProperty $contentProperty) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $em = $this->getDoctrine()->getManager();
        $em->remove($contentProperty);
        $em->flush();
        $this->addFlash('success', 'The contentProperty was deleted.');

        return $this->redirectToRoute('pln_deposit_content_property_index');
    }

}
