<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Default controller for LOCKSSOMatic. Totally open to the public.
 */
class DefaultController extends Controller {

    /**
     * LOCKSSOMatic home page.
     *
     * @param Request $request
     *   The HTTP Request.
     *
     * @return array
     *   Array data for the template processor.
     *
     * @Route("/", name="homepage")
     * @Template()
     */
    public function indexAction(Request $request) {
        return [];
    }
    
    /**
     * Simple rest-client page.
     * 
     * @return array
     *   Array data for the template processor.
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/client", name="client")
     * @Template()
     */
    public function clientAction() {
        return [
            'urls' => [
                'echo' => $this->generateUrl('echo', array(), UrlGeneratorInterface::ABSOLUTE_PATH),
            ],
        ];
    }

    /**
     * Echo page.
     * 
     * @return JsonResponse
     *   Json response with some of the request data echoed back.
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/echo", name="echo")
     */
    public function echoAction(Request $request) {
        $method = $request->getMethod();
        $body = null;
        if($method === 'POST') {
            $body = $request->getContent();
        }
        $params = [];
        foreach($request->query->keys() as $key) {
            $params[$key] = $request->get($key);
        }
            
        return new JsonResponse([
            'method' => $method,
            'query' => $params,
            'body' => $body,
            'ajax' => $request->isXmlHttpRequest(),
        ]);
    }
}
