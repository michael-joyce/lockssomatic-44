<?php

/*
 * This file is licensed under the MIT License version 3 or
 * later. See the LICENSE file for details.
 * 
 * Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\EventListener;

use AppBundle\Controller\SwordController;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Description of SwordExceptionListener.
 */
class SwordExceptionListener {

    private $controller;
    
    private $templating;
    
    private $env;
    
    /**
     *
     */
    public function __construct($env, EngineInterface $templating) {
        $this->templating = $templating;
        $this->env = $env;
    }
    
    /**
     * Once the controller has been initialized, this event is fired. Grab
     * a reference to the active controller.
     *
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event) {
        $this->controller = $event->getController();
    }
    
    /**
     *
     */
    public function onKernelException(GetResponseForExceptionEvent $event) {
        if (!$this->controller[0] instanceof SwordController) {
            return;
        }
        
        $exception = $event->getException();
        $response = new Response();
        if ($exception instanceof HttpExceptionInterface) {
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->replace($exception->getHeaders());
        } else {
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        $response->headers->set('Content-Type', 'text/xml');
        $response->setContent($this->templating->render('AppBundle:sword:exception_document.xml.twig', array(
            'exception' => $exception,
            'env' => $this->env,
        )));
        $event->setResponse($response);
    }

}
