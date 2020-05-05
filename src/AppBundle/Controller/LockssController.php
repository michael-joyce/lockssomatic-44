<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Au;
use AppBundle\Entity\Box;
use AppBundle\Entity\ContentOwner;
use AppBundle\Entity\ContentProvider;
use AppBundle\Entity\Pln;
use AppBundle\Services\FilePaths;
use Psr\Log\LoggerAwareTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * LOCKSS Controller.
 *
 * All of the LOCKSS boxes should interact with LOCKSSOMatic with
 * this controller only.
 *
 * @Route("/plnconfigs/{plnId}")
 * @ParamConverter("pln", options={"id"="plnId"})
 * @Method("GET")
 */
class LockssController extends Controller {
    use LoggerAwareTrait;

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
        $boxIps = array_map(function (Box $box) {
            return $box->getIpAddress();
        }, $pln->getBoxes()->toArray());
        $allowed = array_merge($boxIps, $this->getParameter('lom.allowed_ips'));
        $ip = $request->getClientIp();
        if ( ! IpUtils::checkIp($ip, $allowed)) {
            $this->logger->critical("Client IP {$ip} is not authorized for {$pln->getName()}.");

            throw new AccessDeniedHttpException("Client IP {$ip} is not authorized for this PLN.");
        }
    }

    /**
     * Get a LOCKSS configuration xml file.
     *
     * @Route("/properties/lockss.xml", name="lockss_config")
     *
     * @Method("GET")
     */
    public function lockssAction(Request $request, Pln $pln) {
        $this->logger->notice("{$request->getClientIp()} - lockss.xml - {$pln->getName()}");
        $this->checkIp($request, $pln);
        $path = $this->fp->getLockssXmlFile($pln);
        if ( ! file_exists($path)) {
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
     * @ParamConverter("owner", options={"id"="ownerId"})
     * @ParamConverter("provider", options={"id"="providerId"})
     */
    public function titleDbAction(Request $request, Pln $pln, ContentOwner $owner, ContentProvider $provider, $id) {
        $this->logger->notice("{$request->getClientIp()} - titledb - {$pln->getName()} - {$owner->getName()} - {$provider->getName()} - titledb_{$id}.xml");
        $this->checkIp($request, $pln);
        $path = $this->fp->getTitleDbPath($provider, $id);
        if ( ! file_exists($path)) {
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
     * @ParamConverter("owner", options={"id"="ownerId"})
     * @ParamConverter("provider", options={"id"="providerId"})
     * @ParamConverter("au", options={"id"="auId"})
     */
    public function manifestAction(Request $request, Pln $pln, ContentOwner $owner, ContentProvider $provider, Au $au) {
        $this->logger->notice("{$request->getClientIp()} - manifest - {$pln->getName()} - {$owner->getName()} - {$provider->getName()} - Au #{$au->getId()}");
        $this->checkIp($request, $pln);
        $path = $this->fp->getManifestPath($au);
        if ( ! file_exists($path)) {
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
        $this->logger->notice("{$request->getClientIp()} - keystore - {$pln->getName()}");
        $this->checkIp($request, $pln);
        $keystore = $pln->getKeystorePath();
        if ( ! $keystore) {
            throw new NotFoundHttpException('The requested keystore does not exist.');
        }
        $path = $this->fp->getPluginsExportDir($pln) . '/lockss.keystore';
        if ( ! file_exists($path)) {
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
        $this->logger->notice("{$request->getClientIp()} - plugin list - {$pln->getName()}");
        $this->checkIp($request, $pln);
        $path = $this->fp->getPluginsManifestFile($pln);
        if ( ! file_exists($path)) {
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
        $this->logger->notice("{$request->getClientIp()} - plugin - {$pln->getName()} - {$filename}");
        $this->checkIp($request, $pln);

        $dir = $this->fp->getPluginsExportDir($pln);
        $path = $dir . '/' . $filename;
        if ( ! file_exists($path)) {
            throw new NotFoundHttpException('The requested plugin does not exist at ' . $path);
        }

        return new BinaryFileResponse($path, 200, [
            'Content-Type' => 'application/java-archive',
        ]);
    }
}
