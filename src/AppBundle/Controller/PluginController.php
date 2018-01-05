<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\Plugin;
use AppBundle\Form\PluginType;

/**
 * Plugin controller.
 *
 * @Route("/plugin")
 */
class PluginController extends Controller {

    /**
     * Lists all Plugin entities.
     *
     * @Route("/", name="plugin_index")
     * @Method("GET")
     * @Template()
     * @param Request $request
     */
    public function indexAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from(Plugin::class, 'e')->orderBy('e.id', 'ASC');
        $query = $qb->getQuery();
        $paginator = $this->get('knp_paginator');
        $plugins = $paginator->paginate($query, $request->query->getint('page', 1), 25);

        return array(
            'plugins' => $plugins,
        );
    }

    /**
     * Search for Plugin entities.
     *
     * To make this work, add a method like this one to the 
     * AppBundle:Plugin repository. Replace the fieldName with
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
     * @Route("/search", name="plugin_search")
     * @Method("GET")
     * @Template()
     * @param Request $request
     */
    public function searchAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:Plugin');
        $q = $request->query->get('q');
        if ($q) {
            $query = $repo->searchQuery($q);
            $paginator = $this->get('knp_paginator');
            $plugins = $paginator->paginate($query, $request->query->getInt('page', 1), 25);
        } else {
            $plugins = array();
        }

        return array(
            'plugins' => $plugins,
            'q' => $q,
        );
    }

    /**
     * Full text search for Plugin entities.
     *
     * To make this work, add a method like this one to the 
     * AppBundle:Plugin repository. Replace the fieldName with
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
     * fulltext indexes on your Plugin entity.
     *     ORM\Index(name="alias_name_idx",columns="name", flags={"fulltext"})
     *
     *
     * @Route("/fulltext", name="plugin_fulltext")
     * @Method("GET")
     * @Template()
     * @param Request $request
     * @return array
     */
    public function fulltextAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:Plugin');
        $q = $request->query->get('q');
        if ($q) {
            $query = $repo->fulltextQuery($q);
            $paginator = $this->get('knp_paginator');
            $plugins = $paginator->paginate($query, $request->query->getInt('page', 1), 25);
        } else {
            $plugins = array();
        }

        return array(
            'plugins' => $plugins,
            'q' => $q,
        );
    }

    /**
     * Creates a new Plugin entity.
     *
     * @Route("/new", name="plugin_new")
     * @Method({"GET", "POST"})
     * @Template()
     * @param Request $request
     */
    public function newAction(Request $request) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $plugin = new Plugin();
        $form = $this->createForm(PluginType::class, $plugin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($plugin);
            $em->flush();

            $this->addFlash('success', 'The new plugin was created.');
            return $this->redirectToRoute('plugin_show', array('id' => $plugin->getId()));
        }

        return array(
            'plugin' => $plugin,
            'form' => $form->createView(),
        );
    }

    /**
     * Finds and displays a Plugin entity.
     *
     * @Route("/{id}", name="plugin_show")
     * @Method("GET")
     * @Template()
     * @param Plugin $plugin
     */
    public function showAction(Plugin $plugin) {

        return array(
            'plugin' => $plugin,
        );
    }

    /**
     * Displays a form to edit an existing Plugin entity.
     *
     * @Route("/{id}/edit", name="plugin_edit")
     * @Method({"GET", "POST"})
     * @Template()
     * @param Request $request
     * @param Plugin $plugin
     */
    public function editAction(Request $request, Plugin $plugin) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $editForm = $this->createForm(PluginType::class, $plugin);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            $this->addFlash('success', 'The plugin has been updated.');
            return $this->redirectToRoute('plugin_show', array('id' => $plugin->getId()));
        }

        return array(
            'plugin' => $plugin,
            'edit_form' => $editForm->createView(),
        );
    }

    /**
     * Deletes a Plugin entity.
     *
     * @Route("/{id}/delete", name="plugin_delete")
     * @Method("GET")
     * @param Request $request
     * @param Plugin $plugin
     */
    public function deleteAction(Request $request, Plugin $plugin) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $em = $this->getDoctrine()->getManager();
        $em->remove($plugin);
        $em->flush();
        $this->addFlash('success', 'The plugin was deleted.');

        return $this->redirectToRoute('plugin_index');
    }

}
