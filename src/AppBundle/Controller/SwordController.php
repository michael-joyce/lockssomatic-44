<?php

namespace AppBundle\Controller;

use AppBundle\Entity\ContentProvider;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Sword controller.
 * 
 * @Route("/api/sword/2.0")
 */
class SwordController extends Controller {

    /**
     * Fetch an HTTP header.
     * 
     * Checks the HTTP headers for $key and X-$key variant. If the app
     * is in the dev environment, will also check the query parameters for 
     * $key. 
     * 
     * If $required is true and the header is not present BadRequestException
     * will be thrown.
     * 
     * @param Request $request
     * @param string $key
     * @param string $required
     * @return string|null
     * @throws BadRequestException
     */
    private function fetchHeader(Request $request, $key, $required = false) {
        if($request->headers->has($key)) {
            return $request->headers->get($key);
        }
        if($request->headers->has('X-' . $key)) {
            return $request->get('X-' . $key);
        }
        if($this->getParameter('kernel.environment') === 'dev'
                && $request->query->has($key)) {
            return $request->query->get($key);
        }
        if($required) {
            throw new BadRequestHttpException("HTTP header {$key} is required.", null, Response::HTTP_BAD_REQUEST);
        }
        return null;
    }
    
    /**
     * Get a content provider from it's UUID.
     * 
     * @param string $uuid
     * @return ContentProvider
     */
    private function getProvider($uuid) {
        $em = $this->getDoctrine()->getManager();
        $provider = $em->getRepository(ContentProvider::class)->findOneBy(array(
            'uuid' => $uuid,
        ));
        if( ! $provider) {
            throw new NotFoundHttpException("Content provider not found."); 
        }
        return $provider;
    }
    
    /**
     * SWORD service document.
     * 
     * @param Request $request
     * @return array
     * 
     * @Route("/sd-iri.{_format}", name="sword_service_document", defaults={"_format": "xml"})
     * @Template()
     */
    public function serviceDocumentAction(Request $request) {
        $uuid = $this->fetchHeader($request, 'On-Behalf-Of', true);
        $provider = $this->getProvider($uuid);
        $plugin = $provider->getPlugin();
        $hashMethods = $this->getParameter('lom.hash_methods');
        return array(
            'plugin' => $plugin,
            'provider' => $provider,
            'hashMethods' => $hashMethods,
        );
    }
    
}
