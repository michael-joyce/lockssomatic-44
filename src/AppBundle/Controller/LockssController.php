<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Au;
use AppBundle\Entity\Box;
use AppBundle\Entity\ContentOwner;
use AppBundle\Entity\ContentProvider;
use AppBundle\Entity\Pln;
use AppBundle\Entity\Plugin;
use AppBundle\Services\FilePaths;
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

    /**
     * @var FilePaths
     */
    private $fp;
    
    /**
     * @param FilePaths $fp
     */    
    public function __construct(FilePaths $fp) {
        $this->fp = $fp;
    }
    
    /**
     * Check that a request came from a good IP address.
     * 
     * @param Request $request
     * @param Pln $pln
     */
    private function checkIp(Request $request, Pln $pln) {
        $boxIps = array_map(function(Box $box) {
            return $box->getIpAddress();
        }, $pln->getBoxes()->toArray());
        $allowed = array_merge($boxIps, $this->getParameter('lom.allowed_ips'));
        $ip = $request->getClientIp();
        if( ! IpUtils::checkIp($ip, $allowed)) {
            throw new AccessDeniedHttpException("Client IP {$ip} is not authorized for this PLN.");
        }
    }

    /**
     * Get a LOCKSS configuration xml file.
     *
     * @param Request $request
     *   Dependency injected http request.
     * @param Pln $pln
     *   PLN configured by the file.
     *
     * @Route("/properties/lockss.xml", name="lockss_config")
     * @Method("GET")
     */
    public function lockssAction(Request $request, Pln $pln) {
        $this->checkIp($request, $pln);
        $path = $this->fp->getLockssXmlFile($pln);
        if (!file_exists($path)) {
            throw new NotFoundHttpException('The requested file does not exist.');
        }

        return new BinaryFileResponse($path, 200, array(
            'Content-Type' => 'text/xml',
        ));        
    }

    /**
     * Fetch one title db file.
     *
     * @param Request $request
     *   Dependency injected http request.
     * @param Pln $pln
     *   PLN configured by the file.
     * @param ContentOwner $owner
     *   Content ownter for the title db.
     * @param ContentProvider $provider
     *   Content provider for the title db.
     * @param string $id
     *   Name of the title db file.
     *
     * @Route("/titledbs/{ownerId}/{providerId}/titledb_{id}.{_format}", name="lockss_titledb")
     * @ParamConverter("owner", options={"id"="ownerId"})
     * @ParamConverter("provider", options={"id"="providerId"})
     */
    public function titleDbAction(Request $request, Pln $pln, ContentOwner $owner, ContentProvider $provider, $id) {
        $this->checkIp($request, $pln);
        $path = $this->fp->getTitleDbPath($provider);
        if (!file_exists($path)) {
            throw new NotFoundHttpException("The requested file does not exist.");
        }

        return new BinaryFileResponse($path, 200, array(
            'Content-Type' => 'text/xml',
        ));        
    }

    /**
     * Fetch the manifest file for one AU.
     *
     * @param Request $request
     *   Dependency injected http request.
     * @param Pln $pln
     *   PLN configured by the file.
     * @param ContentOwner $owner
     *   Content ownter for the title db.
     * @param ContentProvider $provider
     *   Content provider for the title db.
     * @param Au $au
     *   The AU containing the content.
     *
     * @Route("/manifests/{ownerId}/{providerId}/manifest_{auId}.html", name="lockss_manifest")
     * @ParamConverter("owner", options={"id"="ownerId"})
     * @ParamConverter("provider", options={"id"="providerId"})
     * @ParamConverter("au", options={"id"="auId"})
     */
    public function manifestAction(Request $request, Pln $pln, ContentOwner $owner, ContentProvider $provider, Au $au) {
        $this->checkIp($request, $pln);
        $path = $this->fp->getManifestPath($au);
        if (!file_exists($path)) {
            throw new NotFoundHttpException("The requested AU manifest does not exist.");
        }
        return new BinaryFileResponse($path, 200, array(
            'Content-Type' => 'text/html',
        ));
        
    }

    /**
     * Get the java keystore file for the LOCKSS plugins.
     *
     * @param Request $request
     *   Dependency injected http request.
     * @param Pln $pln
     *   PLN configured by the file.
     *
     * @Route("/plugins/lockss.keystore", name="lockss_keystore")
     */
    public function keystoreAction(Request $request, Pln $pln) {
        $this->checkIp($request, $pln);
        $keystore = $pln->getKeystorePath();
        if (!$keystore) {
            throw new NotFoundHttpException('The requested keystore does not exist.');
        }
        $path = $this->fp->getPluginsExportDir($pln) . "/lockss.keystore";
        if( ! file_exists($path)) {
            throw new NotFoundHttpException('The requested keystore does not exist.');
        }
        return new BinaryFileResponse($path, 200, array(
            'Content-Type' => 'application/x-java-keystore',
        ));
    }

    /**
     * Get the plugin manifest.
     *
     * @param Request $request
     *   Dependency injected http request.
     * @param Pln $pln
     *   PLN configured by the file.
     *
     * @Route("/plugins/index.html", name="lockss_plugin_list")
     * @Route("/plugins/")
     * @Route("/plugins")
     */
    public function pluginListAction(Request $request, Pln $pln) {
        $this->checkIp($request, $pln);
        $path = $this->fp->getPluginsManifestFile($pln);
        if( ! $path) {
            throw new NotFoundHttpException('The requested plugin manifest does not exist.');
        }
        return new BinaryFileResponse($path, 200, array(
            'Content-Type' => 'text/html',
        ));        
    }

    /**
     * Get one plugin.
     *
     * @param Request $request
     *   Dependency injected http request.
     * @param Pln $pln
     *   PLN configured by the file.
     * @param Plugin $plugin
     *   The plugin to fetch.
     *
     * @Route("/plugins/{filename}", name="lockss_plugin")
     * @ParamConverter("plugin", class="AppBundle:Plugin", options={"mapping": {"filename": "filename"}})
     */
    public function pluginAction(Request $request, Pln $pln, Plugin $plugin) {
        $this->checkIp($request, $pln);
        $path = $this->fp->getPluginsExportFile($pln, $plugin);
        if( ! $path) {
            throw new NotFoundHttpException('The requested plugin does not exist.');
        }
        return new BinaryFileResponse($path, 200, array(
            'Content-Type' => 'application/java-archive',
        ));        
    }

}
