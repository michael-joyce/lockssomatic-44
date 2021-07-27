<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\EventListener;

use App\Controller\SwordController;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Description of SwordExceptionListener.
 */
class SwordExceptionListener {
    /**
     * Controller that threw the exception.
     *
     * @var AbstractController
     */
    private $controller;

    /**
     * Twig instance.
     *
     * @var Environment
     */
    private $templating;

    /**
     * Symfony environment.
     *
     * @var string
     */
    private $env;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Construct the listener.
     *
     * @param string $env
     */
    public function __construct($env, Environment $templating) {
        $this->templating = $templating;
        $this->env = $env;
    }

    /**
     * @required
     */
    public function setLogger(LoggerInterface $swordLogger) : void {
        $this->logger = $swordLogger;
    }

    /**
     * Once the controller has been initialized, this event is fired.
     *
     * Grab a reference to the active controller.
     */
    public function onKernelController(ControllerEvent $event) : void {
        $this->controller = $event->getController();
    }

    /**
     * Exception handler for all controller events.
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function onKernelException(ExceptionEvent $event) : void {
        if ( ! $this->controller[0] instanceof SwordController) {
            return;
        }

        $exception = $event->getThrowable();
        $response = new Response();
        if ($exception instanceof HttpExceptionInterface) {
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->replace($exception->getHeaders());
            $this->logger->error("Caught exception: {$exception->getStatusCode()} {$exception->getMessage()}");
        } else {
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->logger->error("Caught exception: 500 {$exception->getMessage()}");
        }
        $response->headers->set('Content-Type', 'text/xml');
        $response->setContent($this->templating->render('sword/exception_document.xml.twig', [
            'exception' => $exception,
            'env' => $this->env,
        ]));
        $event->setResponse($response);
    }
}
