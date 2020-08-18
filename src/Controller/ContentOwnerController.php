<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Controller;

use App\Entity\ContentOwner;
use App\Form\ContentOwnerType;
use Knp\Bundle\PaginatorBundle\Definition\PaginatorAwareInterface;
use Nines\UtilBundle\Controller\PaginatorTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * ContentOwner controller.
 *
 * @Route("/content_owner")
 * @Security("is_granted('ROLE_USER')")
 */
class ContentOwnerController extends AbstractController implements PaginatorAwareInterface {
    use PaginatorTrait;

    /**
     * Lists all ContentOwner entities.
     *
     * @return array
     *
     * @Route("/", name="content_owner_index", methods={"GET"})
     * @Template()
     */
    public function indexAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from(ContentOwner::class, 'e')->orderBy('e.id', 'ASC');
        $query = $qb->getQuery();

        $contentOwners = $this->paginator->paginate($query, $request->query->getint('page', 1), 25);

        return [
            'contentOwners' => $contentOwners,
        ];
    }

    /**
     * Creates a new ContentOwner entity.
     *
     * @return array
     *
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/new", name="content_owner_new", methods={"GET","POST"})
     * @Template()
     */
    public function newAction(Request $request) {
        $contentOwner = new ContentOwner();
        $form = $this->createForm(ContentOwnerType::class, $contentOwner);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($contentOwner);
            $em->flush();

            $this->addFlash('success', 'The new contentOwner was created.');

            return $this->redirectToRoute('content_owner_show', ['id' => $contentOwner->getId()]);
        }

        return [
            'contentOwner' => $contentOwner,
            'form' => $form->createView(),
        ];
    }

    /**
     * Finds and displays a ContentOwner entity.
     *
     * @return array
     *
     * @Route("/{id}", name="content_owner_show", methods={"GET"})
     * @Template()
     */
    public function showAction(ContentOwner $contentOwner) {
        return [
            'contentOwner' => $contentOwner,
        ];
    }

    /**
     * Displays a form to edit an existing ContentOwner entity.
     *
     * @return array
     *
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/{id}/edit", name="content_owner_edit", methods={"GET","POST"})
     * @Template()
     */
    public function editAction(Request $request, ContentOwner $contentOwner) {
        $editForm = $this->createForm(ContentOwnerType::class, $contentOwner);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            $this->addFlash('success', 'The contentOwner has been updated.');

            return $this->redirectToRoute('content_owner_show', ['id' => $contentOwner->getId()]);
        }

        return [
            'contentOwner' => $contentOwner,
            'edit_form' => $editForm->createView(),
        ];
    }

    /**
     * Deletes a ContentOwner entity.
     *
     * @return array
     *
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/{id}/delete", name="content_owner_delete", methods={"GET"})
     */
    public function deleteAction(Request $request, ContentOwner $contentOwner) {
        $em = $this->getDoctrine()->getManager();
        $em->remove($contentOwner);
        $em->flush();
        $this->addFlash('success', 'The contentOwner was deleted.');

        return $this->redirectToRoute('content_owner_index');
    }
}
