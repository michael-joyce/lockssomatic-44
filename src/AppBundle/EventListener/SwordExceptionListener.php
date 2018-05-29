<?php

/*
 * This file is licensed under the MIT License version 3 or
 * later. See the LICENSE file for details.
 * 
 * Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\EventListener;

use AppBundle\Controller\SwordController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * SWORD exception listener to return XML errors to the clients.
 */
class SwordExceptionListener {

    /**
     * Controller that generated the error.
     *
     * @var Controller
     */
    private $controller;

    /**
     * Templating engine to generate the error message.
     *
     * @var EngineInterface
     */    
    private $templating;

    /**
     * Name of the symfony environment (dev, prod, etc).
     *
     * @var string
     */
    private $env;
    
    /**
     * Build the service.
     * 
     * @param string $env
     *   Name of the environment.
     * @param EngineInterface $templating
     *   Templating engine to generate XML errors.
     */
    public function __construct($env, EngineInterface $templating) {
        $this->templating = $templating;
        $this->env = $env;
    }
    
    /**
     * Fired when a kernel event occurs.
     * 
     * Once the controller has been initialized, this event is fired. Grab
     * a reference to the active controller.
     *
     * @param FilterControllerEvent $event
     *   The event causing the call.
     */
    public function onKernelController(FilterControllerEvent $event) {
        $this->controller = $event->getController();
    }
    
    /**
     * Fired on an exception in the SWORD controller.
     * 
     * Sets the response inside the $event parameter.
     * 
     * @param GetResponseForExceptionEvent $event
     *   Event that caused the exception.
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
