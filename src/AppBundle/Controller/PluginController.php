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
use AppBundle\Services\FilePaths;
use AppBundle\Services\PluginImporter;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
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
     * Creates a new Plugin entity.
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/new", name="plugin_new")
     * @Method({"GET", "POST"})
     * @Template()
     * @param Request $request
     * @param PluginImporter $pluginImporter 
     * @param FilePaths $filePaths
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
            if(! in_array($file->getMimeType(), PluginImporter::MIMETYPES)) {
                throw new Exception("Uploaded file does not look like a Java .jar file. Mime type is {$file->getMimeType()}");
            }
            if(!preg_match('/^[a-zA-Z0-9 .-]+\.jar$/', $file->getClientOriginalName())) {
                throw new Exception("Uploaded file does not look like a Java .jar file. File name is strange.");
            }
            $zipArchive = new ZipArchive();
            $result = $zipArchive->open($file->getPathName());
            if($result !== true) {
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
}
