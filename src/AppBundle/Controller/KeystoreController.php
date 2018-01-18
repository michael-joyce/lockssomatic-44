<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Keystore;
use AppBundle\Form\KeystoreType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Keystore controller.
 *
 * @Security("has_role('ROLE_USER')")
 * @Route("/pln/{plnId}/keystore")
 * @ParamConverter("pln", options={"id"="plnId"})
 */
class KeystoreController extends Controller {

    /**
     * Lists all Keystore entities.
     *
     * @Route("/", name="pln_keystore_index")
     * @Method("GET")
     * @Template()
     * @param Request $request
     */
    public function indexAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from(Keystore::class, 'e')->orderBy('e.id', 'ASC');
        $query = $qb->getQuery();
        $paginator = $this->get('knp_paginator');
        $keystores = $paginator->paginate($query, $request->query->getint('page', 1), 25);

        return array(
            'keystores' => $keystores,
        );
    }

    /**
     * Search for Keystore entities.
     *
     * To make this work, add a method like this one to the 
     * AppBundle:Keystore repository. Replace the fieldName with
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
     * @Route("/search", name="pln_keystore_search")
     * @Method("GET")
     * @Template()
     * @param Request $request
     */
    public function searchAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:Keystore');
        $q = $request->query->get('q');
        if ($q) {
            $query = $repo->searchQuery($q);
            $paginator = $this->get('knp_paginator');
            $keystores = $paginator->paginate($query, $request->query->getInt('page', 1), 25);
        } else {
            $keystores = array();
        }

        return array(
            'keystores' => $keystores,
            'q' => $q,
        );
    }

    /**
     * Full text search for Keystore entities.
     *
     * To make this work, add a method like this one to the 
     * AppBundle:Keystore repository. Replace the fieldName with
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
     * fulltext indexes on your Keystore entity.
     *     ORM\Index(name="alias_name_idx",columns="name", flags={"fulltext"})
     *
     *
     * @Route("/fulltext", name="pln_keystore_fulltext")
     * @Method("GET")
     * @Template()
     * @param Request $request
     * @return array
     */
    public function fulltextAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:Keystore');
        $q = $request->query->get('q');
        if ($q) {
            $query = $repo->fulltextQuery($q);
            $paginator = $this->get('knp_paginator');
            $keystores = $paginator->paginate($query, $request->query->getInt('page', 1), 25);
        } else {
            $keystores = array();
        }

        return array(
            'keystores' => $keystores,
            'q' => $q,
        );
    }

    /**
     * Creates a new Keystore entity.
     *
     * @Route("/new", name="pln_keystore_new")
     * @Method({"GET", "POST"})
     * @Template()
     * @param Request $request
     */
    public function newAction(Request $request) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $keystore = new Keystore();
        $form = $this->createForm(KeystoreType::class, $keystore);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($keystore);
            $em->flush();

            $this->addFlash('success', 'The new keystore was created.');
            return $this->redirectToRoute('pln_keystore_show', array('id' => $keystore->getId()));
        }

        return array(
            'keystore' => $keystore,
            'form' => $form->createView(),
        );
    }

    /**
     * Finds and displays a Keystore entity.
     *
     * @Route("/{id}", name="pln_keystore_show")
     * @Method("GET")
     * @Template()
     * @param Keystore $keystore
     */
    public function showAction(Keystore $keystore) {

        return array(
            'keystore' => $keystore,
        );
    }

    /**
     * Displays a form to edit an existing Keystore entity.
     *
     * @Route("/{id}/edit", name="pln_keystore_edit")
     * @Method({"GET", "POST"})
     * @Template()
     * @param Request $request
     * @param Keystore $keystore
     */
    public function editAction(Request $request, Keystore $keystore) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $editForm = $this->createForm(KeystoreType::class, $keystore);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            $this->addFlash('success', 'The keystore has been updated.');
            return $this->redirectToRoute('pln_keystore_show', array('id' => $keystore->getId()));
        }

        return array(
            'keystore' => $keystore,
            'edit_form' => $editForm->createView(),
        );
    }

    /**
     * Deletes a Keystore entity.
     *
     * @Route("/{id}/delete", name="pln_keystore_delete")
     * @Method("GET")
     * @param Request $request
     * @param Keystore $keystore
     */
    public function deleteAction(Request $request, Keystore $keystore) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $em = $this->getDoctrine()->getManager();
        $em->remove($keystore);
        $em->flush();
        $this->addFlash('success', 'The keystore was deleted.');

        return $this->redirectToRoute('pln_keystore_index');
    }

}
