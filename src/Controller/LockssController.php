<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Controller;

use App\Entity\Au;
use App\Entity\Box;
use App\Entity\ContentOwner;
use App\Entity\ContentProvider;
use App\Entity\Pln;
use App\Services\FilePaths;
use Knp\Bundle\PaginatorBundle\Definition\PaginatorAwareInterface;
use Nines\UtilBundle\Controller\PaginatorTrait;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * LOCKSS Controller.
 *
 * All of the LOCKSS boxes should interact with LOCKSSOMatic with
 * this controller only.
 *
 * @Route("/plnconfigs/{plnId}", methods={"GET"})
 * @ParamConverter("pln", options={"id": "plnId"})
 */
class LockssController extends AbstractController implements PaginatorAwareInterface {
    use PaginatorTrait;

    /**
     * The logger instance.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * File path service.
     *
     * @var FilePaths
     */
    private $fp;

    /**
     * Construct the controller.
     */
    public function __construct(FilePaths $fp) {
        $this->fp = $fp;
    }

    /**
     * Check that a request came from a good IP address.
     *
     * @throws AccessDeniedHttpException
     */
    private function checkIp(Request $request, Pln $pln) : void {
        $boxIps = array_map(fn (Box $box) => $box->getIpAddress(), $pln->getBoxes()->toArray());
        $allowed = array_merge($boxIps, $this->getParameter('lom.allowed_ips'));
        $ip = $request->getClientIp();
        if ( ! IpUtils::checkIp($ip, $allowed)) {
            $this->logger->critical("Client IP {$ip} is not authorized for {$pln->getName()}.");

            throw new AccessDeniedHttpException("Client IP {$ip} is not authorized for this PLN.");
        }
    }

    /**
     * Sets a logger.
     */
    public function setLogger(LoggerInterface $lockssLogger) : void {
        $this->logger = $lockssLogger;
    }

    /**
     * Get a LOCKSS configuration xml file.
     *
     * @Route("/properties/lockss.xml", name="lockss_config")
     */
    public function lockssAction(Request $request, Pln $pln) {
        $this->logger->notice("lockss.xml - {$pln->getName()}");
        $this->checkIp($request, $pln);
        $path = $this->fp->getLockssXmlFile($pln);
        if ( ! file_exists($path)) {
            $this->logger->error("lockss.xml not found - {$pln->getName()}");

            throw new NotFoundHttpException('The requested file does not exist.');
        }

        return new BinaryFileResponse($path, 200, [
            'Content-Type' => 'text/xml',
        ]);
    }

    /**
     * Fetch one title db file.
     *
     * @param string $id
     *
     * @Route("/titledbs/{ownerId}/{providerId}/titledb_{id}.xml", name="lockss_titledb")
     * @ParamConverter("owner", options={"id": "ownerId"})
     * @ParamConverter("provider", options={"id": "providerId"})
     */
    public function titleDbAction(Request $request, Pln $pln, ContentOwner $owner, ContentProvider $provider, $id) {
        $this->logger->notice("titledb - {$pln->getName()} - {$owner->getName()} - {$provider->getName()} - titledb_{$id}.xml");
        $this->checkIp($request, $pln);
        $path = $this->fp->getTitleDbPath($provider, $id);
        if ( ! file_exists($path)) {
            $this->logger->error("titledb not found - {$pln->getName()} - {$owner->getName()} - {$provider->getName()} - titledb_{$id}.xml");

            throw new NotFoundHttpException('The requested file does not exist.');
        }

        return new BinaryFileResponse($path, 200, [
            'Content-Type' => 'text/xml',
        ]);
    }

    /**
     * Fetch the manifest file for one AU.
     *
     * @Route("/manifests/{ownerId}/{providerId}/manifest_{auId}.html", name="lockss_manifest")
     * @ParamConverter("owner", options={"id": "ownerId"})
     * @ParamConverter("provider", options={"id": "providerId"})
     * @ParamConverter("au", options={"id": "auId"})
     */
    public function manifestAction(Request $request, Pln $pln, ContentOwner $owner, ContentProvider $provider, Au $au) {
        $this->logger->notice("manifest - {$pln->getName()} - {$owner->getName()} - {$provider->getName()} - Au #{$au->getId()}");
        $this->checkIp($request, $pln);
        $path = $this->fp->getManifestPath($au);
        if ( ! file_exists($path)) {
            $this->logger->error("manifest not found - {$pln->getName()} - {$owner->getName()} - {$provider->getName()} - Au #{$au->getId()}");

            throw new NotFoundHttpException('The requested AU manifest does not exist.');
        }

        return new BinaryFileResponse($path, 200, [
            'Content-Type' => 'text/html',
        ]);
    }

    /**
     * Get the java keystore file for the LOCKSS plugins.
     *
     * @Route("/plugins/lockssomatic.keystore", name="lockss_keystore")
     */
    public function keystoreAction(Request $request, Pln $pln) {
        $this->logger->notice("keystore - {$pln->getName()}");
        $this->checkIp($request, $pln);
        $keystore = $pln->getKeystorePath();
        if ( ! $keystore) {
            $this->logger->error("keystore does not exist - {$pln->getName()}");

            throw new NotFoundHttpException('The requested keystore does not exist.');
        }
        $path = $this->fp->getPluginsExportDir($pln) . '/lockss.keystore';
        if ( ! file_exists($path)) {
            $this->logger->error("keystore file does not exist - {$pln->getName()}");

            throw new NotFoundHttpException('The requested keystore does not exist.');
        }

        return new BinaryFileResponse($path, 200, [
            'Content-Type' => 'application/x-java-keystore',
        ]);
    }

    /**
     * Get the plugin manifest.
     *
     * @Route("/plugins/index.html", name="lockss_plugin_list")
     * @Route("/plugins/")
     * @Route("/plugins")
     */
    public function pluginListAction(Request $request, Pln $pln) {
        $this->logger->notice("plugin list - {$pln->getName()}");
        $this->checkIp($request, $pln);
        $path = $this->fp->getPluginsManifestFile($pln);
        if ( ! file_exists($path)) {
            $this->logger->error("plugin list not found - {$pln->getName()}");

            throw new NotFoundHttpException('The requested plugin manifest does not exist.');
        }

        return new BinaryFileResponse($path, 200, [
            'Content-Type' => 'text/html',
        ]);
    }

    /**
     * Get one plugin.
     *
     * @param string $filename
     *
     * @Route("/plugins/{filename}", name="lockss_plugin")
     */
    public function pluginAction(Request $request, Pln $pln, $filename) {
        $this->logger->notice("plugin - {$pln->getName()} - {$filename}");
        $this->checkIp($request, $pln);

        $dir = $this->fp->getPluginsExportDir($pln);
        $path = $dir . '/' . $filename;
        if ( ! file_exists($path)) {
            $this->logger->error("plugin not found - {$pln->getName()} - {$filename}");

            throw new NotFoundHttpException('The requested plugin does not exist at ' . $path);
        }

        return new BinaryFileResponse($path, 200, [
            'Content-Type' => 'application/java-archive',
        ]);
    }
}
