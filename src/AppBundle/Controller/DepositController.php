<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\Deposit;
use AppBundle\Form\DepositType;

/**
 * Deposit controller.
 *
 * @Route("/pln/{plnId}/deposit")
 */
class DepositController extends Controller {

    /**
     * Lists all Deposit entities.
     *
     * @Route("/", name="deposit_index")
     * @Method("GET")
     * @Template()
     * @param Request $request
     */
    public function indexAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from(Deposit::class, 'e')->orderBy('e.id', 'ASC');
        $query = $qb->getQuery();
        $paginator = $this->get('knp_paginator');
        $deposits = $paginator->paginate($query, $request->query->getint('page', 1), 25);

        return array(
            'deposits' => $deposits,
        );
    }

    /**
     * Search for Deposit entities.
     *
     * To make this work, add a method like this one to the 
     * AppBundle:Deposit repository. Replace the fieldName with
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
     * @Route("/search", name="deposit_search")
     * @Method("GET")
     * @Template()
     * @param Request $request
     */
    public function searchAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:Deposit');
        $q = $request->query->get('q');
        if ($q) {
            $query = $repo->searchQuery($q);
            $paginator = $this->get('knp_paginator');
            $deposits = $paginator->paginate($query, $request->query->getInt('page', 1), 25);
        } else {
            $deposits = array();
        }

        return array(
            'deposits' => $deposits,
            'q' => $q,
        );
    }

    /**
     * Full text search for Deposit entities.
     *
     * To make this work, add a method like this one to the 
     * AppBundle:Deposit repository. Replace the fieldName with
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
     * fulltext indexes on your Deposit entity.
     *     ORM\Index(name="alias_name_idx",columns="name", flags={"fulltext"})
     *
     *
     * @Route("/fulltext", name="deposit_fulltext")
     * @Method("GET")
     * @Template()
     * @param Request $request
     * @return array
     */
    public function fulltextAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:Deposit');
        $q = $request->query->get('q');
        if ($q) {
            $query = $repo->fulltextQuery($q);
            $paginator = $this->get('knp_paginator');
            $deposits = $paginator->paginate($query, $request->query->getInt('page', 1), 25);
        } else {
            $deposits = array();
        }

        return array(
            'deposits' => $deposits,
            'q' => $q,
        );
    }

    /**
     * Creates a new Deposit entity.
     *
     * @Route("/new", name="deposit_new")
     * @Method({"GET", "POST"})
     * @Template()
     * @param Request $request
     */
    public function newAction(Request $request) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $deposit = new Deposit();
        $form = $this->createForm(DepositType::class, $deposit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($deposit);
            $em->flush();

            $this->addFlash('success', 'The new deposit was created.');
            return $this->redirectToRoute('deposit_show', array('id' => $deposit->getId()));
        }

        return array(
            'deposit' => $deposit,
            'form' => $form->createView(),
        );
    }

    /**
     * Finds and displays a Deposit entity.
     *
     * @Route("/{id}", name="deposit_show")
     * @Method("GET")
     * @Template()
     * @param Deposit $deposit
     */
    public function showAction(Deposit $deposit) {

        return array(
            'deposit' => $deposit,
        );
    }

    /**
     * Displays a form to edit an existing Deposit entity.
     *
     * @Route("/{id}/edit", name="deposit_edit")
     * @Method({"GET", "POST"})
     * @Template()
     * @param Request $request
     * @param Deposit $deposit
     */
    public function editAction(Request $request, Deposit $deposit) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $editForm = $this->createForm(DepositType::class, $deposit);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            $this->addFlash('success', 'The deposit has been updated.');
            return $this->redirectToRoute('deposit_show', array('id' => $deposit->getId()));
        }

        return array(
            'deposit' => $deposit,
            'edit_form' => $editForm->createView(),
        );
    }

    /**
     * Deletes a Deposit entity.
     *
     * @Route("/{id}/delete", name="deposit_delete")
     * @Method("GET")
     * @param Request $request
     * @param Deposit $deposit
     */
    public function deleteAction(Request $request, Deposit $deposit) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You must login to access this page.');
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $em = $this->getDoctrine()->getManager();
        $em->remove($deposit);
        $em->flush();
        $this->addFlash('success', 'The deposit was deleted.');

        return $this->redirectToRoute('deposit_index');
    }

}
