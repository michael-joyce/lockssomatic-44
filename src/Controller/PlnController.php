<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Controller;

use App\Entity\Pln;
use App\Form\FileUploadType;
use App\Form\PlnType;
use App\Services\ConfigExporter;
use App\Services\ConfigUpdater;
use App\Services\FilePaths;
use Knp\Bundle\PaginatorBundle\Definition\PaginatorAwareInterface;
use Nines\UtilBundle\Controller\PaginatorTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Pln controller.
 *
 * @Security("is_granted('ROLE_USER')")
 * @Route("/pln")
 */
class PlnController extends AbstractController implements PaginatorAwareInterface {
    use PaginatorTrait;

    /**
     * Lists all Pln entities.
     *
     * @return array
     *
     * @Route("/", name="pln_index", methods={"GET"})
     *
     * @Template
     */
    public function indexAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from(Pln::class, 'e')->orderBy('e.id', 'ASC');
        $query = $qb->getQuery();

        $plns = $this->paginator->paginate($query, $request->query->getint('page', 1), 25);

        return [
            'plns' => $plns,
        ];
    }

    /**
     * Creates a new Pln entity.
     *
     * @return array
     *
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/new", name="pln_new", methods={"GET", "POST"})
     *
     * @Template
     */
    public function newAction(Request $request) {
        $pln = new Pln();
        $form = $this->createForm(PlnType::class, $pln);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($pln);
            $em->flush();

            $this->addFlash('success', 'The new pln was created.');

            return $this->redirectToRoute('pln_show', [
                'id' => $pln->getId(),
            ]);
        }

        return [
            'pln' => $pln,
            'form' => $form->createView(),
        ];
    }

    /**
     * Upload and add/replace the java keystore file for the Pln's plugins.
     *
     * @return array
     *
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/{id}/keystore", name="pln_keystore", methods={"GET", "POST"})
     *
     * @Template
     */
    public function keystoreAction(Request $request, Pln $pln, FilePaths $filePaths) {
        $form = $this->createForm(FileUploadType::class, null, [
            'help' => 'Select a java keystore file.',
            'label' => 'Keystore File',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $file = $data['file'];
            if ( ! in_array($file->getMimeType(), Pln::KEYSTORE_MIMETYPES, true)) {
                throw new BadRequestHttpException("Upload does not look like a keystore. Mime type is {$file->getMimeType()}");
            }
            if ( ! preg_match('/^[a-zA-Z0-9 .-]+\.keystore$/', $file->getClientOriginalName())) {
                throw new BadRequestHttpException('Upload does not look like a keystore. File name is strange.');
            }
            $filename = $file->getClientOriginalName();
            $file->move($filePaths->getLockssKeystoreDir($pln), $filename);
            $pln->setKeystore($filePaths->getLockssKeystoreDir($pln) . '/' . $filename);

            $em = $this->getDoctrine()->getManager();
            $em->flush();

            $this->addFlash('success', 'The keystore has been updated.');

            return $this->redirectToRoute('pln_show', [
                'id' => $pln->getId(),
            ]);
        }

        return [
            'form' => $form->createView(),
            'pln' => $pln,
        ];
    }

    /**
     * Finds and displays a Pln entity.
     *
     * @return array
     *
     * @Route("/{id}", name="pln_show", methods={"GET"})
     *
     * @Template
     */
    public function showAction(Pln $pln) {
        return [
            'pln' => $pln,
        ];
    }

    /**
     * Displays a form to edit an existing Pln entity.
     *
     * @return array
     *
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/{id}/edit", name="pln_edit", methods={"GET", "POST"})
     *
     * @Template
     */
    public function editAction(Request $request, Pln $pln) {
        $editForm = $this->createForm(PlnType::class, $pln);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            $this->addFlash('success', 'The pln has been updated.');

            return $this->redirectToRoute('pln_show', [
                'id' => $pln->getId(),
            ]);
        }

        return [
            'pln' => $pln,
            'edit_form' => $editForm->createView(),
        ];
    }

    /**
     * Exports and updates the PLN configuration.
     *
     * Updates all configuration for a PLN and exports it to disk for LOCKSS
     * to access. Usually this should be done in a regularly scheduled cron
     * job.
     *
     * @return RedirectResponse
     *
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/{id}/export", name="pln_export", methods={"GET"})
     *
     * @Template
     */
    public function exportAction(Request $request, Pln $pln, ConfigExporter $exporter, ConfigUpdater $updater) {
        $em = $this->getDoctrine()->getManager();
        $updater->update($pln);
        $em->flush();
        $exporter->export($pln);
        $this->addFlash('success', 'The pln configuration has been updated and exported.');

        return $this->redirectToRoute('pln_show', [
            'id' => $pln->getId(),
        ]);
    }

    /**
     * Deletes a Pln entity.
     *
     * @return array
     *
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/{id}/delete", name="pln_delete", methods={"GET"})
     */
    public function deleteAction(Request $request, Pln $pln) {
        $em = $this->getDoctrine()->getManager();
        $em->remove($pln);
        $em->flush();
        $this->addFlash('success', 'The pln was deleted.');

        return $this->redirectToRoute('pln_index');
    }
}
