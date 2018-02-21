<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Au;
use AppBundle\Entity\ContentOwner;
use AppBundle\Entity\ContentProvider;
use AppBundle\Entity\Pln;
use AppBundle\Entity\Plugin;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

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
     * Get a LOCKSS configuration xml file.
     *
     * @param Request $request
     *   Dependency injected http request.
     * @param \AppBundle\Controller\Pln $pln
     *   PLN configured by the file.
     *
     * @Route("/properties/lockss.{_format}", name="lockss_config", requirements={"_format": "xml"})
     * @Method("GET")
     */
    public function lockssAction(Request $request, Pln $pln) {
    }
    
    /**
     * Fetch one title db file.
     *
     * @param Request $request
     *   Dependency injected http request.
     * @param \AppBundle\Controller\Pln $pln
     *   PLN configured by the file.
     * @param ContentOwner $owner
     *   Content ownter for the title db.
     * @param ContentProvider $provider
     *   Content provider for the title db.
     * @param string $filename
     *   Name of the title db file.
     *
     * @Route("/titledbs/{ownerId}/{providerId}/{filename}", name="lockss_titledb")
     * @ParamConverter("owner", options={"id"="ownerId"})
     * @ParamConverter("provider", options={"id"="providerId"})
     */
    public function titleDbAction(Request $request, Pln $pln, ContentOwner $owner, ContentProvider $provider, $filename) {
    }
    
    /**
     * Fetch the manifest file for one AU.
     *
     * @param Request $request
     *   Dependency injected http request.
     * @param \AppBundle\Controller\Pln $pln
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
    }
    
    /**
     * Get the java keystore file for the LOCKSS plugins.
     *
     * @param Request $request
     *   Dependency injected http request.
     * @param \AppBundle\Controller\Pln $pln
     *   PLN configured by the file.
     *
     * @Route("/plugins/lockss.keystore", name="lockss_keystore")
     */
    public function keystoreAction(Request $request, Pln $pln) {
    }
    
    /**
     * Get the plugin manifest.
     *
     * @param Request $request
     *   Dependency injected http request.
     * @param \AppBundle\Controller\Pln $pln
     *   PLN configured by the file.
     *
     * @Route("/plugins/index.html", name="lockss_plugin_list")
     * @Route("/plugins/")
     * @Route("/plugins")
     */
    public function pluginListAction(Request $request, Pln $pln) {
    }
    
    /**
     * Get one plugin.
     *
     * @param Request $request
     *   Dependency injected http request.
     * @param \AppBundle\Controller\Pln $pln
     *   PLN configured by the file.
     * @param Plugin $plugin
     *   The plugin to fetch.
     *
     * @Route("/plugins/{filename}", name="lockss_plugin")
     * @ParamConverter("plugin", class="AppBundle:Plugin", options={"mapping": {"filename": "filename"}})
     */
    public function pluginAction(Request $request, Pln $pln, Plugin $plugin) {
    }
    
}
