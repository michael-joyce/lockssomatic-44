<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Controller;

use App\Entity\ContentProvider;
use App\Form\ContentProviderType;
use Knp\Bundle\PaginatorBundle\Definition\PaginatorAwareInterface;
use Nines\UtilBundle\Controller\PaginatorTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * ContentProvider controller.
 *
 * @Security("is_granted('ROLE_USER')")
 * @Route("/content_provider")
 */
class ContentProviderController extends AbstractController implements PaginatorAwareInterface {
    use PaginatorTrait;

    /**
     * Lists all ContentProvider entities.
     *
     * @return array
     *
     * @Route("/", name="content_provider_index", methods={"GET"})
     * @Template
     */
    public function indexAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from(ContentProvider::class, 'e')->orderBy('e.id', 'ASC');
        $query = $qb->getQuery();

        $contentProviders = $this->paginator->paginate($query, $request->query->getint('page', 1), 25);

        return [
            'contentProviders' => $contentProviders,
        ];
    }

    /**
     * Creates a new ContentProvider entity.
     *
     * @return array
     *
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/new", name="content_provider_new", methods={"GET", "POST"})
     * @Template
     */
    public function newAction(Request $request) {
        $contentProvider = new ContentProvider();
        $form = $this->createForm(ContentProviderType::class, $contentProvider);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($contentProvider);
            $em->flush();

            $this->addFlash('success', 'The new contentProvider was created.');

            return $this->redirectToRoute('content_provider_show', ['id' => $contentProvider->getId()]);
        }

        return [
            'contentProvider' => $contentProvider,
            'form' => $form->createView(),
        ];
    }

    /**
     * Finds and displays a ContentProvider entity.
     *
     * @return array
     *
     * @Route("/{id}", name="content_provider_show", methods={"GET"})
     *
     * @Template
     */
    public function showAction(ContentProvider $contentProvider) {
        return [
            'contentProvider' => $contentProvider,
        ];
    }

    /**
     * Displays a form to edit an existing ContentProvider entity.
     *
     * @return array
     *
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/{id}/edit", name="content_provider_edit", methods={"GET", "POST"})
     *
     * @Template
     */
    public function editAction(Request $request, ContentProvider $contentProvider) {
        $editForm = $this->createForm(ContentProviderType::class, $contentProvider);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            $this->addFlash('success', 'The contentProvider has been updated.');

            return $this->redirectToRoute('content_provider_show', ['id' => $contentProvider->getId()]);
        }

        return [
            'contentProvider' => $contentProvider,
            'edit_form' => $editForm->createView(),
        ];
    }

    /**
     * Deletes a ContentProvider entity.
     *
     * @return array
     *
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/{id}/delete", name="content_provider_delete", methods={"GET"})
     */
    public function deleteAction(Request $request, ContentProvider $contentProvider) {
        $em = $this->getDoctrine()->getManager();
        $em->remove($contentProvider);
        $em->flush();
        $this->addFlash('success', 'The contentProvider was deleted.');

        return $this->redirectToRoute('content_provider_index');
    }
}
