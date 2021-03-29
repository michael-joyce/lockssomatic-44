<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Controller;

use App\Entity\Plugin;
use App\Form\FileUploadType;
use App\Form\PluginType;
use App\Services\FilePaths;
use App\Services\PluginImporter;
use Exception;
use Knp\Bundle\PaginatorBundle\Definition\PaginatorAwareInterface;
use Nines\UtilBundle\Controller\PaginatorTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use ZipArchive;

/**
 * Plugin controller.
 *
 * @Security("is_granted('ROLE_USER')")
 * @Route("/plugin")
 */
class PluginController extends AbstractController implements PaginatorAwareInterface
{
    use PaginatorTrait;

    /**
     * Lists all Plugin entities.
     *
     * @return array
     *
     * @Route("/", name="plugin_index", methods={"GET"})
     *
     * @Template
     */
    public function indexAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from(Plugin::class, 'e')->orderBy('e.id', 'ASC');
        $query = $qb->getQuery();

        $plugins = $this->paginator->paginate($query, $request->query->getint('page', 1), 25);

        return [
            'plugins' => $plugins,
        ];
    }

    /**
     * Creates a new Plugin entity.
     *
     * @return array
     *
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/new", name="plugin_new", methods={"GET", "POST"})
     *
     * @Template
     */
    public function newAction(Request $request, PluginImporter $pluginImporter, FilePaths $filePaths) {
        $form = $this->createForm(FileUploadType::class, null, [
            'help' => 'Select a LOCKSS plugin .jar file.',
            'label' => 'JAR File',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $file = $data['file'];
            if ( ! in_array($file->getMimeType(), PluginImporter::MIMETYPES, true)) {
                throw new Exception("Uploaded file has bad mimetype is {$file->getMimeType()}");
            }
            if ( ! preg_match('/^[a-zA-Z0-9 .-]+\.jar$/', $file->getClientOriginalName())) {
                throw new Exception('Uploaded file name name is strange.');
            }
            $zipArchive = new ZipArchive();
            $result = $zipArchive->open($file->getPathName());
            if (true !== $result) {
                throw new Exception('Cannot read from uploaded file: ' . $result);
            }
            $plugin = $pluginImporter->import($zipArchive, false);
            $filename = basename($file->getClientOriginalName(), '.jar') . '-v' . $plugin->getVersion() . '.jar';
            $file->move($filePaths->getPluginsDir(), $filename);
            $plugin->setPath($filePaths->getPluginsDir() . '/' . $filename);
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            $this->addFlash('success', 'The new plugin was created.');

            return $this->redirectToRoute('plugin_show', ['id' => $plugin->getId()]);
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * Finds and displays a Plugin entity.
     *
     * @return array
     *
     * @Route("/{id}", name="plugin_show", methods={"GET"})
     *
     * @Template
     */
    public function showAction(Plugin $plugin) {
        return [
            'plugin' => $plugin,
        ];
    }

    /**
     * Edit a plugin configuration.
     *
     * The edit action lets a user configure the plugin's settings inside
     * LOCKSSOMatic. To add a new version of the plugin JAR file use the new
     * action.
     *
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/{id}/edit", name="plugin_edit", methods={"GET", "POST"})
     *
     * @Template
     */
    public function editAction(Request $request, Plugin $plugin) {
        $editForm = $this->createForm(PluginType::class, $plugin, [
            'plugin' => $plugin,
        ]);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            $this->addFlash('success', 'The plugin settings have been updated.');

            return $this->redirectToRoute('plugin_show', ['id' => $plugin->getId()]);
        }

        return [
            'plugin' => $plugin,
            'edit_form' => $editForm->createView(),
        ];
    }
}
