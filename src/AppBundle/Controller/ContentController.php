<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\Content;
use AppBundle\Form\ContentType;

/**
 * Content controller.
 *
 * @Route("/pln/{plnId}/deposit/{depositId}/content")
 */
class ContentController extends Controller {

    /**
     * Lists all Content entities.
     *
     * @Route("/", name="deposit_content_index")
     * @Method("GET")
     * @Template()
     * @param Request $request
     */
    public function indexAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from(Content::class, 'e')->orderBy('e.id', 'ASC');
        $query = $qb->getQuery();
        $paginator = $this->get('knp_paginator');
        $contents = $paginator->paginate($query, $request->query->getint('page', 1), 25);

        return array(
            'contents' => $contents,
        );
    }

    /**
     * Search for Content entities.
     *
     * To make this work, add a method like this one to the 
     * AppBundle:Content repository. Replace the fieldName with
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
     * @Route("/search", name="deposit_content_search")
     * @Method("GET")
     * @Template()
     * @param Request $request
     */
    public function searchAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:Content');
        $q = $request->query->get('q');
        if ($q) {
            $query = $repo->searchQuery($q);
            $paginator = $this->get('knp_paginator');
            $contents = $paginator->paginate($query, $request->query->getInt('page', 1), 25);
        } else {
            $contents = array();
        }

        return array(
            'contents' => $contents,
            'q' => $q,
        );
    }

    /**
     * Full text search for Content entities.
     *
     * To make this work, add a method like this one to the 
     * AppBundle:Content repository. Replace the fieldName with
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
     * fulltext indexes on your Content entity.
     *     ORM\Index(name="alias_name_idx",columns="name", flags={"fulltext"})
     *
     *
     * @Route("/fulltext", name="deposit_content_fulltext")
     * @Method("GET")
     * @Template()
     * @param Request $request
     * @return array
     */
    public function fulltextAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:Content');
        $q = $request->query->get('q');
        if ($q) {
            $query = $repo->fulltextQuery($q);
            $paginator = $this->get('knp_paginator');
            $contents = $paginator->paginate($query, $request->query->getInt('page', 1), 25);
        } else {
            $contents = array();
        }

        return array(
            'contents' => $contents,
            'q' => $q,
        );
    }

    /**
     * Creates a new Content entity.
     *
     * @Route("/new", name="deposit_content_new")
     * @Method({"GET", "POST"})
     * @Template()
     * @param Request $request
     */
    public function newAction(Request $request) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $content = new Content();
        $form = $this->createForm(ContentType::class, $content);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($content);
            $em->flush();

            $this->addFlash('success', 'The new content was created.');
            return $this->redirectToRoute('deposit_content_show', array('id' => $content->getId()));
        }

        return array(
            'content' => $content,
            'form' => $form->createView(),
        );
    }

    /**
     * Finds and displays a Content entity.
     *
     * @Route("/{id}", name="deposit_content_show")
     * @Method("GET")
     * @Template()
     * @param Content $content
     */
    public function showAction(Content $content) {

        return array(
            'content' => $content,
        );
    }

    /**
     * Displays a form to edit an existing Content entity.
     *
     * @Route("/{id}/edit", name="deposit_content_edit")
     * @Method({"GET", "POST"})
     * @Template()
     * @param Request $request
     * @param Content $content
     */
    public function editAction(Request $request, Content $content) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $editForm = $this->createForm(ContentType::class, $content);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            $this->addFlash('success', 'The content has been updated.');
            return $this->redirectToRoute('deposit_content_show', array('id' => $content->getId()));
        }

        return array(
            'content' => $content,
            'edit_form' => $editForm->createView(),
        );
    }

    /**
     * Deletes a Content entity.
     *
     * @Route("/{id}/delete", name="deposit_content_delete")
     * @Method("GET")
     * @param Request $request
     * @param Content $content
     */
    public function deleteAction(Request $request, Content $content) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $em = $this->getDoctrine()->getManager();
        $em->remove($content);
        $em->flush();
        $this->addFlash('success', 'The content was deleted.');

        return $this->redirectToRoute('deposit_content_index');
    }

}
