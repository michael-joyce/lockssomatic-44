<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\PluginProperty;
use AppBundle\Form\PluginPropertyType;

/**
 * PluginProperty controller.
 *
 * @Route("/plugin/{pluginId}/property")
 */
class PluginPropertyController extends Controller {

    /**
     * Lists all PluginProperty entities.
     *
     * @Route("/", name="plugin_property_index")
     * @Method("GET")
     * @Template()
     * @param Request $request
     */
    public function indexAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from(PluginProperty::class, 'e')->orderBy('e.id', 'ASC');
        $query = $qb->getQuery();
        $paginator = $this->get('knp_paginator');
        $pluginProperties = $paginator->paginate($query, $request->query->getint('page', 1), 25);

        return array(
            'pluginProperties' => $pluginProperties,
        );
    }

    /**
     * Search for PluginProperty entities.
     *
     * To make this work, add a method like this one to the 
     * AppBundle:PluginProperty repository. Replace the fieldName with
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
     * @Route("/search", name="plugin_property_search")
     * @Method("GET")
     * @Template()
     * @param Request $request
     */
    public function searchAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:PluginProperty');
        $q = $request->query->get('q');
        if ($q) {
            $query = $repo->searchQuery($q);
            $paginator = $this->get('knp_paginator');
            $pluginProperties = $paginator->paginate($query, $request->query->getInt('page', 1), 25);
        } else {
            $pluginProperties = array();
        }

        return array(
            'pluginProperties' => $pluginProperties,
            'q' => $q,
        );
    }

    /**
     * Full text search for PluginProperty entities.
     *
     * To make this work, add a method like this one to the 
     * AppBundle:PluginProperty repository. Replace the fieldName with
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
     * fulltext indexes on your PluginProperty entity.
     *     ORM\Index(name="alias_name_idx",columns="name", flags={"fulltext"})
     *
     *
     * @Route("/fulltext", name="plugin_property_fulltext")
     * @Method("GET")
     * @Template()
     * @param Request $request
     * @return array
     */
    public function fulltextAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:PluginProperty');
        $q = $request->query->get('q');
        if ($q) {
            $query = $repo->fulltextQuery($q);
            $paginator = $this->get('knp_paginator');
            $pluginProperties = $paginator->paginate($query, $request->query->getInt('page', 1), 25);
        } else {
            $pluginProperties = array();
        }

        return array(
            'pluginProperties' => $pluginProperties,
            'q' => $q,
        );
    }

    /**
     * Creates a new PluginProperty entity.
     *
     * @Route("/new", name="plugin_property_new")
     * @Method({"GET", "POST"})
     * @Template()
     * @param Request $request
     */
    public function newAction(Request $request) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $pluginProperty = new PluginProperty();
        $form = $this->createForm(PluginPropertyType::class, $pluginProperty);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($pluginProperty);
            $em->flush();

            $this->addFlash('success', 'The new pluginProperty was created.');
            return $this->redirectToRoute('plugin_property_show', array('id' => $pluginProperty->getId()));
        }

        return array(
            'pluginProperty' => $pluginProperty,
            'form' => $form->createView(),
        );
    }

    /**
     * Finds and displays a PluginProperty entity.
     *
     * @Route("/{id}", name="plugin_property_show")
     * @Method("GET")
     * @Template()
     * @param PluginProperty $pluginProperty
     */
    public function showAction(PluginProperty $pluginProperty) {

        return array(
            'pluginProperty' => $pluginProperty,
        );
    }

    /**
     * Displays a form to edit an existing PluginProperty entity.
     *
     * @Route("/{id}/edit", name="plugin_property_edit")
     * @Method({"GET", "POST"})
     * @Template()
     * @param Request $request
     * @param PluginProperty $pluginProperty
     */
    public function editAction(Request $request, PluginProperty $pluginProperty) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $editForm = $this->createForm(PluginPropertyType::class, $pluginProperty);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            $this->addFlash('success', 'The pluginProperty has been updated.');
            return $this->redirectToRoute('plugin_property_show', array('id' => $pluginProperty->getId()));
        }

        return array(
            'pluginProperty' => $pluginProperty,
            'edit_form' => $editForm->createView(),
        );
    }

    /**
     * Deletes a PluginProperty entity.
     *
     * @Route("/{id}/delete", name="plugin_property_delete")
     * @Method("GET")
     * @param Request $request
     * @param PluginProperty $pluginProperty
     */
    public function deleteAction(Request $request, PluginProperty $pluginProperty) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $em = $this->getDoctrine()->getManager();
        $em->remove($pluginProperty);
        $em->flush();
        $this->addFlash('success', 'The pluginProperty was deleted.');

        return $this->redirectToRoute('plugin_property_index');
    }

}
