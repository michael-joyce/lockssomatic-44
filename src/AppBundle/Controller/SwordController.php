<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Au;
use AppBundle\Entity\Content;
use AppBundle\Entity\ContentProvider;
use AppBundle\Entity\Deposit;
use AppBundle\Entity\Plugin;
use AppBundle\Services\AuManager;
use AppBundle\Services\AuIdGenerator;
use AppBundle\Services\ContentBuilder;
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
     *   Dependency injected logger.
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
     *   Request which should contain the header.
     * @param string $key
     *   Name of the header.
     * @param string $required
     *   If true, an exception will be thrown if the header is missing.
     *
     * @return string|null
     *   The value of the header or null if that's OK.
     *
     * @throws BadRequestException
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
     *   The UUID of the content provider.
     *
     * @return ContentProvider
     *   The content provider.
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
     * Get a content item.
     *
     * @param Deposit $deposit
     *   Deposit containing the content.
     * @param string $url
     *   URL identifying the content.
     *
     * @return Content|null
     *   The content matching the deposit and URL.
     */
    private function getContent(Deposit $deposit, $url) {
        return $this->getDoctrine()->getRepository(Content::class)->findOneBy(array(
                    'deposit' => $deposit,
                    'url' => trim($url),
        ));
    }

    /**
     * SWORD service document.
     *
     * @param Request $request
     *   Dependency injected request.
     *
     * @return array
     *   The array is passed to the template handler.
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
     * Precheck a deposit for the required properties.
     *
     * Also makes sure the properties all make some sense.
     *
     * @param SimpleXMLElement $atom
     *   The deposit data.
     * @param ContentProvider $provider
     *   The provider making the deposit.
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
            $url = trim((string) $content);
            $host = parse_url($url, PHP_URL_HOST);
            if ($permissionHost !== $host) {
                throw new BadRequestHttpException("Permission host does not match content host. Content host:{$host} Permission host: {$permissionHost}", null, Response::HTTP_BAD_REQUEST);
            }

            if ($content->attributes()->size > $provider->getMaxFileSize()) {
                $size = $content->attributes()->size;
                $max = $provider->getMaxFileSize();
                throw new BadRequestHttpException("Content size {$size} exceeds provider's maximum: {$max}", null, Response::HTTP_BAD_REQUEST);
            }
        }
    }

    /**
     * Given a deposit and content provider, render a deposit reciept.
     *
     * @param ContentProvider $provider
     *   The provider making the deposit.
     * @param Deposit $deposit
     *   The deposit jsut received.
     *
     * @return Response
     *   Containing the XML.
     */
    private function renderDepositReceipt(ContentProvider $provider, Deposit $deposit) {
        // @TODO this should be a call to render depositReceiptAction() or something.
        // Return the deposit receipt.
        $response = $this->render('sword/deposit_receipt.xml.twig', array(
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
     *   Depedency injected http request.
     *
     * @return SimpleXMLElement
     *   Parsed XML.
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
     *   Dependency injected http request.
     * @param ContentProvider $provider
     *   Content provider making the request, determined from the URL.
     * @param EntityManagerInterface $em
     *   Entity manager for the database.
     * @param DepositBuilder $depositBuilder
     *   Dependency injected deposit builder.
     * @param ContentBuilder $contentBuilder
     *   Dependency injected content builder.
     * @param AuManager $auManager
     *   Dependency injected archival unit builder.
     * @param AuIdGenerator $idGenerator
     *   Dependency injected AUID generator.
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
     *   The HTTP response containing a location header and the SWORD body.
     */
    public function createDepositAction(Request $request, ContentProvider $provider, EntityManagerInterface $em, DepositBuilder $depositBuilder, ContentBuilder $contentBuilder, AuManager $auManager, AuIdGenerator $idGenerator) {
        $atom = $this->getXml($request);
        $this->precheckDeposit($atom, $provider);
        $deposit = $depositBuilder->fromXml($atom, $provider);
        foreach ($atom->xpath('lom:content') as $node) {
            $content = $contentBuilder->fromXml($node);
            $content->setDeposit($deposit);
            $au = $auManager->fromContent($content);
        }
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
     *   Dependency injected http request.
     * @param ContentProvider $provider
     *   Content provider making the request, determined from the URL.
     * @param Deposit $deposit
     *   Deposit being edited.
     * @param EntityManagerInterface $em
     *   Database entity manager.
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
     *   The sword HTTP response with a success message.
     */
    public function editDepositAction(Request $request, ContentProvider $provider, Deposit $deposit, EntityManagerInterface $em) {
        $atom = $this->getXml($request);
        $this->precheckDeposit($atom, $provider);
        foreach ($atom->xpath('lom:content') as $node) {
            $content = $this->getContent($deposit, (string) $node);
            if (!$content) {
                $this->logger->warning("Cannot edit content for deposit {$deposit->getId()} with URL " . $node);
                continue;
            }
            $content->setChecksumType($node['checksumType']);
            $content->setChecksumValue($node['checksumValue']);
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
     *   Dependency injected http request.
     * @param ContentProvider $provider
     *   Content provider that originally made the deposit.
     * @param Deposit $deposit
     *   Deposit being edited.
     *
     * @return array
     *   The templating engine will process the returned data.
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
     *   Dependency injected http request.
     * @param ContentProvider $provider
     *   Provider that made the deposit.
     * @param Deposit $deposit
     *   Deposit for the statement.
     *
     * @return Response
     *   SWORD API resonse with a success message.
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
     *   Dependency injected http request.
     * @param ContentProvider $provider
     *   Provider that made the deposit.
     * @param Deposit $deposit
     *   Deposit for the statement.
     *
     * @return Response
     *   The SWORD response with the deposit reciept in the body.
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
     * Attempt to fetch the original deposit from LOCKSS, store it to
     * the file system in a temp file, and then serve it to the user agent.
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
