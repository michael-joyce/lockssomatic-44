<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Controller;

use App\Entity\Box;
use App\Entity\Pln;
use App\Form\BoxType;
use Knp\Bundle\PaginatorBundle\Definition\PaginatorAwareInterface;
use Nines\UtilBundle\Controller\PaginatorTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Box controller.
 *
 * @Security("is_granted('ROLE_USER')")
 * @Route("/pln/{plnId}/box")
 * @ParamConverter("pln", options={"id"="plnId"})
 */
class BoxController extends AbstractController implements PaginatorAwareInterface {
    use PaginatorTrait;

    /**
     * Lists all Box entities.
     *
     * @return array
     *
     * @Route("/", name="box_index", methods={"GET"})
     * @Template()
     */
    public function indexAction(Request $request, Pln $pln) {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository(Box::class);
        $query = $repo->findBy(['pln' => $pln], ['id' => 'ASC']);

        $boxes = $this->paginator->paginate($query, $request->query->getint('page', 1), 25);

        return [
            'boxes' => $boxes,
            'pln' => $pln,
        ];
    }

    /**
     * Creates a new Box entity.
     *
     * @return array
     *
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/new", name="box_new", methods={"GET","POST"})
     * @Template()
     */
    public function newAction(Request $request, Pln $pln) {
        $box = new Box();
        $box->setPln($pln);
        $form = $this->createForm(BoxType::class, $box);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($box);
            $em->flush();

            $this->addFlash('success', 'The new box was created. You should check deposit status manually with --force once content is copied.');

            return $this->redirectToRoute('box_show', ['id' => $box->getId(), 'plnId' => $pln->getId()]);
        }

        return [
            'box' => $box,
            'pln' => $pln,
            'form' => $form->createView(),
        ];
    }

    /**
     * Finds and displays a Box entity.
     *
     * @return array
     *
     * @Route("/{id}", name="box_show", methods={"GET"})
     * @Template()
     */
    public function showAction(Pln $pln, Box $box) {
        if ($box->getPln() !== $pln) {
            throw new NotFoundHttpException('No such box.');
        }

        return [
            'box' => $box,
            'pln' => $pln,
        ];
    }

    /**
     * Displays a form to edit an existing Box entity.
     *
     * @return array
     *
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/{id}/edit", name="box_edit", methods={"GET","POST"})
     * @Template()
     */
    public function editAction(Request $request, Pln $pln, Box $box) {
        if ($box->getPln() !== $pln) {
            throw new NotFoundHttpException('No such box.');
        }
        $editForm = $this->createForm(BoxType::class, $box);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            $this->addFlash('success', 'The box has been updated.');

            return $this->redirectToRoute('box_show', ['id' => $box->getId(), 'plnId' => $pln->getId()]);
        }

        return [
            'box' => $box,
            'pln' => $pln,
            'edit_form' => $editForm->createView(),
        ];
    }

    /**
     * Deletes a Box entity.
     *
     * @return array
     *
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/{id}/delete", name="box_delete", methods={"GET"})
     */
    public function deleteAction(Request $request, Pln $pln, Box $box) {
        if ($box->getPln() !== $pln) {
            throw new NotFoundHttpException('No such box.');
        }
        $em = $this->getDoctrine()->getManager();
        $em->remove($box);
        $em->flush();
        $this->addFlash('success', 'The box was deleted.');

        return $this->redirectToRoute('box_index', [
            'plnId' => $pln->getId(),
        ]);
    }
}
