<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Plugin;
use AppBundle\Form\FileUploadType;
use AppBundle\Form\PluginType;
use AppBundle\Services\FilePaths;
use AppBundle\Services\PluginImporter;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use ZipArchive;

/**
 * Plugin controller.
 *
 * @Security("has_role('ROLE_USER')")
 * @Route("/plugin")
 */
class PluginController extends Controller {

    /**
     * Lists all Plugin entities.
     *
     * @param Request $request
     *   The HTTP request instance.
     *
     * @return array
     *   Array data for the template processor.
     *
     * @Route("/", name="plugin_index")
     * @Method("GET")
     * @Template()
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
     * Creates a new Plugin entity.
     *
     * @param Request $request
     *   The HTTP request instance.
     * @param PluginImporter $pluginImporter
     *   Dependency injected plugin importer service.
     * @param FilePaths $filePaths
     *   Dependency injected file path service.
     *
     * @return array
     *   Array data for the template processor.
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/new", name="plugin_new")
     * @Method({"GET", "POST"})
     * @Template()
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
            if (!in_array($file->getMimeType(), PluginImporter::MIMETYPES)) {
                throw new Exception("Uploaded file has bad mimetype is {$file->getMimeType()}");
            }
            if (!preg_match('/^[a-zA-Z0-9 .-]+\.jar$/', $file->getClientOriginalName())) {
                throw new Exception("Uploaded file name name is strange.");
            }
            $zipArchive = new ZipArchive();
            $result = $zipArchive->open($file->getPathName());
            if ($result !== true) {
                throw new Exception("Cannot read from uploaded file: " . $result);
            }
            $plugin = $pluginImporter->import($zipArchive, false);
            $filename = basename($file->getClientOriginalName(), '.jar') . '-v' . $plugin->getVersion() . '.jar';
            $file->move($filePaths->getPluginsDir(), $filename);
            $plugin->setPath($filePaths->getPluginsDir() . '/' . $filename);
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            $this->addFlash('success', 'The new plugin was created.');
            return $this->redirectToRoute('plugin_show', array('id' => $plugin->getId()));
        }

        return array(
            'form' => $form->createView(),
        );
    }

    /**
     * Finds and displays a Plugin entity.
     *
     * @param Plugin $plugin
     *   Plugin to show.
     *
     * @return array
     *   Array data for the template processor.
     *
     * @Route("/{id}", name="plugin_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction(Plugin $plugin) {

        return array(
            'plugin' => $plugin,
        );
    }

    /**
     * Edit a plugin configuration.
     *
     * The edit action lets a user configure the plugin's settings inside
     * LOCKSSOMatic. To add a new version of the plugin JAR file use the new
     * action.
     *
     * @param Request $request
     *   Dependency injected request.
     * @param Plugin $plugin
     *   Dependency injected plugin being edited.
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/{id}/edit", name="plugin_edit")
     * @Method({"GET","POST"})
     * @Template()
     */
    public function editAction(Request $request, Plugin $plugin) {
        $editForm = $this->createForm(PluginType::class, $plugin, array(
            'plugin' => $plugin,
        ));
        $editForm->handleRequest($request);
        
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            $this->addFlash('success', 'The plugin settings have been updated.');
            return $this->redirectToRoute('plugin_show', array('id' => $plugin->getId()));
        }
        return array(
            'plugin' => $plugin,
            'edit_form' => $editForm->createView(),
        );
    }
    
}
