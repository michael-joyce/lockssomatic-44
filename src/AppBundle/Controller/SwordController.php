<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Controller;

use AppBundle\Entity\ContentProvider;
use AppBundle\Entity\Deposit;
use AppBundle\Entity\Plugin;
use AppBundle\Services\AuManager;
use AppBundle\Services\DepositBuilder;
use AppBundle\Utilities\Namespaces;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
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
     * Logger for the controller.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Build the controller.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

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
     *
     * @return string|null
     *   The value of the header or null if that's OK.
     *
     * @throws BadRequestException
     *   If the header is required but cannot be found.
     */
    private function fetchHeader(Request $request, $key, $required = false) {
        if ($request->headers->has($key)) {
            return $request->headers->get($key);
        }
        if ($this->getParameter('kernel.environment') === 'dev' && $request->query->has($key)) {
            return $request->query->get($key);
        }
        if ($required) {
            throw new BadRequestHttpException("HTTP header {$key} is required.", null, Response::HTTP_BAD_REQUEST);
        }
        return null;
    }

    /**
     * Get a content provider from it's UUID.
     *
     * @param string $uuid
     *
     * @return ContentProvider
     *
     * @throws NotFoundHttpException
     *    Throws if the provider is missing.
     */
    private function getProvider($uuid) {
        $em = $this->getDoctrine()->getManager();
        $provider = $em->getRepository(ContentProvider::class)->findOneBy(array(
            'uuid' => $uuid,
        ));
        if (!$provider) {
            throw new NotFoundHttpException("Content provider not found.", null, Response::HTTP_NOT_FOUND);
        }
        return $provider;
    }

    /**
     * SWORD service document.
     *
     * @param Request $request
     *
     * @return array
     *
     * @Route("/sd-iri",
     *  name="sword_service_document",
     *  defaults={"_format": "xml"},
     *  requirements={"_format": "xml"}
     * )
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
     * Precheck deposit properties before taking action.
     *
     * @throws BadRequestHttpException
     *   If the deposit doesn't meet requirements.
     */
    private function precheckContentProperties(SimpleXMLElement $content, Plugin $plugin) {
        foreach ($plugin->getDefinitionalPropertyNames() as $name) {
            if (in_array($name, $plugin->getGeneratedParams())) {
                continue;
            }
            $nodes = $content->xpath("lom:property[@name='{$name}']");
            if (count($nodes) === 0) {
                throw new BadRequestHttpException("{$name} is a required property.");
            }
            if (count($nodes) > 1) {
                throw new BadRequestHttpException("{$name} cannot be repeated.");
            }
            $value = (string) ($nodes[0]->attributes()->value);
            if (!$value) {
                throw new BadRequestHttpException("{$name} must have a value.");
            }
        }
    }

    /**
     * Precheck a deposit for the required properties.
     *
     * Also makes sure the properties all make some sense.
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
        if (count($atom->xpath('//lom:content')) > 1) {
            throw new BadRequestHttpException('Deposits with multiple content elements are not allowed.', null, Response::HTTP_BAD_REQUEST);
        }
        $plugin = $provider->getPlugin();

        $permissionHost = $provider->getPermissionHost();
        foreach ($atom->xpath('//lom:content') as $content) {
            $url = trim((string) $content);
            $host = parse_url($url, PHP_URL_HOST);
            if ($permissionHost !== $host) {
                throw new BadRequestHttpException("Permission host for {$url} does not match content host. Content host:{$host} Permission host: {$permissionHost}", null, Response::HTTP_BAD_REQUEST);
            }

            if ($content->attributes()->size > $provider->getMaxFileSize()) {
                $size = $content->attributes()->size;
                $max = $provider->getMaxFileSize();
                throw new BadRequestHttpException("Content size {$size} exceeds provider's maximum: {$max}", null, Response::HTTP_BAD_REQUEST);
            }

            $this->precheckContentProperties($content, $plugin);
        }
    }

    /**
     * Given a deposit and content provider, render a deposit reciept.
     *
     * @param ContentProvider $provider
     * @param Deposit $deposit
     *
     * @return Response
     */
    private function renderDepositReceipt(ContentProvider $provider, Deposit $deposit) {
        $response = $this->render('sword/receipt.xml.twig', array(
            'provider' => $provider,
            'deposit' => $deposit,
        ));
        $response->headers->set('Content-Type', 'text/xml');

        return $response;
    }

    /**
     * Get the XML from an HTTP request.
     *
     * @param Request $request
     *
     * @return SimpleXMLElement
     *
     * @throws BadRequestHttpException
     */
    private function getXml(Request $request) {
        $content = $request->getContent();
        if (!$content || !is_string($content)) {
            throw new BadRequestHttpException("Expected request body. Found none.", null, Response::HTTP_BAD_REQUEST);
        }
        try {
            $xml = simplexml_load_string($content);
            Namespaces::registerNamespaces($xml);
            return $xml;
        } catch (\Exception $e) {
            throw new BadRequestHttpException("Cannot parse request XML.", $e, Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Create a deposit by posting XML to this URL, aka col-iri.
     *
     * @param Request $request
     * @param ContentProvider $provider
     * @param EntityManagerInterface $em
     * @param DepositBuilder $depositBuilder
     * @param AuManager $auManager
     *
     * @Route("/col-iri/{providerUuid}", name="sword_collection", requirements={
     *      "providerUuid": ".{36}"
     * })
     * @Method({"POST"})
     * @ParamConverter("provider", class="AppBundle:ContentProvider", options={"mapping": {"providerUuid"="uuid"}})
     *
     * @throws BadRequestException
     * @throws HostMismatchException
     * @throws MaxUploadSizeExceededException
     *
     * @return Response
     */
    public function createDepositAction(Request $request, ContentProvider $provider, EntityManagerInterface $em, DepositBuilder $depositBuilder, AuManager $auManager) {
        $atom = $this->getXml($request);
        $this->precheckDeposit($atom, $provider);
        $deposit = $depositBuilder->fromXml($atom, $provider);
        $au = $auManager->findOpenAu($deposit);
        $em->flush();
        $response = $this->renderDepositReceipt($provider, $deposit);
        $response->headers->set('Location', $this->generateUrl('sword_reciept', array(
                'providerUuid' => $provider->getUuid(),
                'depositUuid' => $deposit->getUuid(),
                ), UrlGeneratorInterface::ABSOLUTE_URL));
        $response->setStatusCode(Response::HTTP_CREATED);
        return $response;
    }

    /**
     * HTTP PUT to this URL to edit a deposit.
     *
     * This URL is the same as the recieptAction URL (aka
     * edit-iri) but requires an HTTP PUT.
     *
     * @param Request $request
     * @param ContentProvider $provider
     * @param Deposit $deposit
     * @param EntityManagerInterface $em
     *
     * @Route("/cont-iri/{providerUuid}/{depositUuid}/edit", name="sword_edit", requirements={
     *      "providerUuid": ".{36}",
     *      "depositUuid": ".{36}"
     * })
     * @Method({"PUT"})
     * @ParamConverter("provider", class="AppBundle:ContentProvider", options={"mapping": {"providerUuid"="uuid"}})
     * @ParamConverter("deposit", class="AppBundle:Deposit", options={"mapping": {"depositUuid"="uuid"}})
     *
     * @todo what does the recrawl attribute do?
     *
     * @return Response
     */
    public function editDepositAction(Request $request, ContentProvider $provider, Deposit $deposit, EntityManagerInterface $em) {
        $atom = $this->getXml($request);
        $this->precheckDeposit($atom, $provider);
        foreach ($atom->xpath('lom:content') as $node) {
            $deposit->setChecksumType($node['checksumType']);
            $deposit->setChecksumValue($node['checksumValue']);
        }
        $em->flush();
        $response = $this->renderDepositReceipt($provider, $deposit);
        $response->headers->set('Location', $this->generateUrl('sword_reciept', array(
                'providerUuid' => $provider->getUuid(),
                'depositUuid' => $deposit->getUuid(),
                ), UrlGeneratorInterface::ABSOLUTE_URL));
        $response->setStatusCode(Response::HTTP_OK);
        return $response;
    }

    /**
     * Fetch a representation of the deposit from this URL, aka cont-iri.
     *
     * @param Request $request
     * @param ContentProvider $provider
     * @param Deposit $deposit
     *
     * @return array
     *
     * @todo needs testing.
     *
     * @Route("/cont-iri/{providerUuid}/{depositUuid}",
     *  name="sword_view",
     *  defaults={"_format": "xml"},
     *  requirements={
     *      "providerUuid": ".{36}",
     *      "depositUuid": ".{36}",
     *      "_format": "xml"
     *  })
     * @Method({"GET"})
     * @ParamConverter("provider", class="AppBundle:ContentProvider", options={"mapping": {"providerUuid"="uuid"}})
     * @ParamConverter("deposit", class="AppBundle:Deposit", options={"mapping": {"depositUuid"="uuid"}})
     *
     * @Template
     */
    public function viewDepositAction(Request $request, ContentProvider $provider, Deposit $deposit) {
        return array(
            'provider' => $provider,
            'deposit' => $deposit,
        );
    }

    /**
     * Get a deposit statement.
     *
     * In the SWORD api, the statement shows the status of the deposit in LOCKSS,
     * from this URL. Also known as state-iri. Includes a sword:originalDeposit element for
     * each content item in the deposit.
     *
     * @param Request $request
     * @param ContentProvider $provider
     * @param Deposit $deposit
     *
     * @return Response
     *
     * @todo finish this action.
     *
     * @Route("/cont-iri/{providerUuid}/{depositUuid}/state", name="sword_statement",
     *  defaults={"_format": "xml"},
     *  requirements={
     *      "providerUuid": ".{36}",
     *      "depositUuid": ".{36}",
     *      "_format": "xml"
     *  })
     * @Method({"GET"})
     * @Template()
     * @ParamConverter("provider", class="AppBundle:ContentProvider", options={"mapping": {"providerUuid"="uuid"}})
     * @ParamConverter("deposit", class="AppBundle:Deposit", options={"mapping": {"depositUuid"="uuid"}})
     */
    public function statementAction(Request $request, ContentProvider $provider, Deposit $deposit) {
        if ($deposit->getAgreement() == 1) {
            $state = 'agreement';
            $stateDescription = 'LOCKSS boxes have harvested the content and agree on the checksum.';
        } else {
            $state = 'inProgress';
            $stateDescription = 'LOCKSS boxes have not completed harvesting the content.';
        }
        return array(
            'state' => $state,
            'stateDescription' => $stateDescription,
            'provider' => $provider,
            'deposit' => $deposit,
        );
    }

    /**
     * Get a deposit receipt from this URL, also known as the edit-iri.
     *
     * @param Request $request
     * @param ContentProvider $provider
     * @param Deposit $deposit
     *
     * @return Response
     *
     * @Route("/cont-iri/{providerUuid}/{depositUuid}/edit", name="sword_reciept",
     *  defaults={"_format": "xml"},
     *  requirements={
     *      "providerUuid": ".{36}",
     *      "depositUuid": ".{36}",
     *      "_format": "xml"
     *  })
     *
     * @Method({"GET"})
     * @Template()
     * @ParamConverter("provider", class="AppBundle:ContentProvider", options={"mapping": {"providerUuid"="uuid"}})
     * @ParamConverter("deposit", class="AppBundle:Deposit", options={"mapping": {"depositUuid"="uuid"}})
     */
    public function receiptAction(Request $request, ContentProvider $provider, Deposit $deposit) {
        return array(
            'provider' => $provider,
            'deposit' => $deposit,
        );
    }

    /**
     * Attempt to fetch the original deposit from LOCKSS,.
     *
     * Stores it to the file system in a temp file, and then serve it to the
     * user agent.
     *
     * @param ContentProvider $provider
     * @param Deposit $deposit
     * @param string $filename
     *
     * @Route("/cont-iri/{providerUuid}/{depositUuid}/{filename}/original", name="original_deposit", requirements={
     *      "providerUuid": ".{36}",
     *      "depositUuid": ".{36}"
     * })
     *
     * @ParamConverter("provider", options={"uuid"="providerUuid"})
     * @ParamConverter("deposit", options={"uuid"="depositUuid"})
     */
    public function originalDepositAction(ContentProvider $provider, Deposit $deposit, $filename) {

    }

}
