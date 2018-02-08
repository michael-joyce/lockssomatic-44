<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Au;
use AppBundle\Entity\ContentProvider;
use AppBundle\Entity\Deposit;
use AppBundle\Entity\Plugin;
use AppBundle\Services\AuBuilder;
use AppBundle\Services\AuIdGenerator;
use AppBundle\Services\ContentBuilder;
use AppBundle\Services\DepositBuilder;
use AppBundle\Utilities\Namespaces;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use SimpleXMLElement;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
            throw new NotFoundHttpException("Content provider not found.", null, Response::HTTP_NOT_FOUND); 
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
        $provider = $this->getProvider(strtoupper($uuid));
        $plugin = $provider->getPlugin();
        $hashMethods = $this->getParameter('lom.hash_methods');
        return array(
            'plugin' => $plugin,
            'provider' => $provider,
            'hashMethods' => $hashMethods,
        );
    }
    
    /**
     * Make sure that the required content properties exist in the XML for a
     * plugin.
     *
     * @param SimpleXMLElement $content
     * @param Plugin $plugin
     * @throws BadRequestException
     */
    private function precheckContentProperties(SimpleXMLElement $content, Plugin $plugin) {
        foreach ($plugin->getDefinitionalProperties() as $property) {
            $nodes = $content->xpath("lom:property[@name='$property']");
            if (count($nodes) === 0) {
                throw new BadRequestHttpException("{$property} is a required property.", null, Response::HTTP_BAD_REQUEST);
            }
            if (count($nodes) > 1) {
                throw new BadRequestHttpException("{$property} must be unique.", null, Response::HTTP_BAD_REQUEST);
            }
            $property = $nodes[0];
            if (!$property->attributes()->value) {
                throw new BadRequestHttpException("{$property} must have a value.", null, Response::HTTP_BAD_REQUEST);
            }
        }
    }
    
    /**
     * Precheck a deposit for the required properties and make sure the properties
     * all make some sense.
     *
     * @param SimpleXMLElement $atom
     * @param ContentProvider $provider
     *
     * @throws BadRequestException
     * @throws HostMismatchException
     * @throws MaxUploadSizeExceededException
     */
    private function precheckDeposit(SimpleXMLElement $atom, ContentProvider $provider) {
        if (count($atom->xpath('//lom:content')) === 0) {
            throw new BadRequestHttpException('Empty deposits are not allowed.', null, Response::HTTP_BAD_REQUEST);
        }
        $plugin = $provider->getPlugin();

        $permissionHost = $provider->getPermissionHost();
        foreach ($atom->xpath('//lom:content') as $content) {
            // check required properties.
            $this->precheckContentProperties($content, $plugin);
            $url = trim((string) $content);
            $host = parse_url($url, PHP_URL_HOST);
            if ($permissionHost !== $host) {
                throw new HostMismatchException("Content host:{$host} Permission host: {$permissionHost}", null, Response::HTTP_BAD_REQUEST);
            }

            if ($content->attributes()->size > $provider->getMaxFileSize()) {
                $size = $content->attributes()->size;
                $max = $provider->getMaxFileSize();
                throw new MaxUploadSizeExceededException("Content size {$size} exceeds provider's maximum: {$max}", null, Response::HTTP_BAD_REQUEST);
            }
        }
    }
    
    /**
     * Given a deposit and content provider, render a deposit reciept.
     *
     * @param ContentProvider $contentProvider
     * @param Deposit $deposit
     *
     * @return Response containing the XML.
     */
    private function renderDepositReceipt(ContentProvider $contentProvider, Deposit $deposit) {
        // @TODO this should be a call to render depositReceiptAction() or something.
        // Return the deposit receipt.
        $response = $this->render(
            'LOCKSSOMaticSwordBundle:Sword:depositReceipt.xml.twig',
            array(
            'contentProvider' => $contentProvider,
            'deposit' => $deposit,
            )
        );
        $response->headers->set('Content-Type', 'text/xml');

        return $response;
    }
    
    private function getXml(Request $request) {
        $content = $request->getContent();
        if( ! $content || !is_string($content)) {
            throw new BadRequestHttpException("Expected request body. Found none.", null, Response::HTTP_BAD_REQUEST);
        }
        try {
            $xml = simplexml_load_string($content);
            Namespaces::registerNamespaces($xml);
            return $xml;
        } catch(\Exception $e) {
            throw new BadRequestHttpException("Cannot parse request XML.", $e, Response::HTTP_BAD_REQUEST);
        }
    }
    
    /**
     * Create a deposit by posting XML to this URL, aka col-iri.
     *
     * @Route("/col-iri/{providerUuid}", name="sword_collection", requirements={
     *      "providerUuid": ".{36}"
     * })
     * @Method({"POST"})
     * @ParamConverter("provider", class="AppBundle:ContentProvider", options={"mapping": {"providerUuid"="uuid"}})
     *
     * @param Request $request
     * @param ContentProvider $provider
     * @param EntityManagerInterface $em
     * @param DepositBuilder $depositBuilder
     * @param ContentBuilder $contentBuilder
     * @param AuBuilder $auBuilder
     * @param AuIdGenerator $idGenerator
     *
     * @throws BadRequestException
     * @throws HostMismatchException
     * @throws MaxUploadSizeExceededException
     * 
     * @return Response
     */
    public function createDepositAction(Request $request, ContentProvider $provider, EntityManagerInterface $em, DepositBuilder $depositBuilder, ContentBuilder $contentBuilder, AuBuilder $auBuilder, AuIdGenerator $idGenerator) {
        $atom = $this->getXml($request);
        $this->precheckDeposit($atom, $provider);        
        $deposit = $depositBuilder->fromXml($atom, $provider);
        foreach($atom->xpath('lom:content') as $node) {
            $content = $contentBuilder->fromXml($node);
            dump($content);
            $content->setDeposit($deposit);
            $auid = $idGenerator->fromContent($content);
            $au = $em->getRepository(Au::class)->findOneBy(array(
                'auid' => $auid,
            ));
            if( ! $au) {
                $au = $auBuilder->fromContent($content);
            }
            $content->setAu($au);            
        }
        $em->flush();
        $response = $this->render('sword/deposit_receipt.xml.twig', array(
            'provider' => $provider,
            'deposit' => $deposit,
        ));
        $response->headers->set('Location', $this->generateUrl('sword_reciept', array(
            'providerUuid' => $provider->getUuid(),
            'depositUuid' => $deposit->getUuid(),
        ), UrlGeneratorInterface::ABSOLUTE_URL));
        $response->setStatusCode(Response::HTTP_CREATED);
        return $response;        
    }
    
    /**
     * Get a deposit statement, showing the status of the deposit in LOCKSS,
     * from this URL. Also known as state-iri. Includes a sword:originalDeposit element for
     * each content item in the deposit.
     *
     * @Route("/cont-iri/{providerUuid}/{depositUuid}/state", name="sword_statement", requirements={
     *      "providerUuid": ".{36}",
     *      "depositUuid": ".{36}"
     * })
     * @Method({"GET"})
     * @ParamConverter("provider", options={"uuid"="providerUuid"})
     * @ParamConverter("deposit", options={"uuid"="depositUuid"})
     *
     * @param string $providerUuid
     * @param string $depositUuid
     *
     * @return Response
     */
    public function statementAction(Request $request, ContentProvider $provider, Deposit $deposit) {
        
    }
    
    /**
     * Get a deposit receipt from this URL, also known as the edit-iri.
     *
     * @Route("/cont-iri/{providerUuid}/{depositUuid}/edit", name="sword_reciept", requirements={
     *      "providerUuid": ".{36}",
     *      "depositUuid": ".{36}"
     * })
     * @Method({"GET"})
     * @ParamConverter("provider", options={"uuid"="providerUuid"})
     * @ParamConverter("deposit", options={"uuid"="depositUuid"})
     *
     * @param string $providerUuid
     * @param string $depositUuid
     *
     * @return Response
     */    
    public function receiptAction(Request $request, ContentProvider $provider, Deposit $deposit) {
        
    }
    
}
