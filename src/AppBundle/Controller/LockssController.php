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
 * LOCKSS Controller
 * 
 * @Route("/plnconfigs/{plnId}")
 * @ParamConverter("pln", options={"id"="plnId"})
 * @Method("GET")
 */
class LockssController extends Controller {

    /**
     * @Route("/properties/lockss.{_format}", name="lockss_config", requirements={"_format": "xml"})
     * @Method("GET")
     * 
     * @param Request $request
     * @param \AppBundle\Controller\Pln $pln
     */
    public function lockssAction(Request $request, Pln $pln) {
        
    }
    
    /**
     * @Route("/titledbs/{ownerId}/{providerId}/{filename}", name="lockss_titledb")
     * @ParamConverter("owner", options={"id"="ownerId"})
     * @ParamConverter("provider", options={"id"="providerId"})
     * 
     * @param Request $request
     * @param Pln $pln
     * @param ContentOwner $owner
     * @param ContentProvider $provider
     * @param string $filename
     */
    public function titleDbAction(Request $request, Pln $pln, ContentOwner $owner, ContentProvider $provider, $filename) {
        
    }
    
    /**
     * @Route("/manifests/{ownerId}/{providerId}/{auId}", name="lockss_manifest")
     * @ParamConverter("owner", options={"id"="ownerId"})
     * @ParamConverter("provider", options={"id"="providerId"})
     * @ParamConverter("au", options={"id"="auId"})
     * 
     * @param Request $request
     * @param Pln $pln
     * @param ContentOwner $owner
     * @param ContentProvider $provider
     * @param Au $au
     */
    public function manifestAction(Request $request, Pln $pln, ContentOwner $owner, ContentProvider $provider, Au $au) {
        
    } 
    
    /**
     * @Route("/plugins/lockss.keystore", name="lockss_keystore")
     * 
     * @param Request $request
     * @param Pln $pln
     */
    public function keystoreAction(Request $request, Pln $pln) {
        
    }
    
    /**
     * @Route("/plugins/index.html", name="lockss_plugin_list")
     * @Route("/plugins/")
     * @Route("/plugins")
     * 
     * @param Request $request
     * @param Pln $pln
     */
    public function pluginListAction(Request $request, Pln $pln) {
        
    }
    
    /**
     * @Route("/plugins/{filename}")
     * @ParamConverter("plugin", class="AppBundle:Plugin", options={"mapping": {"filename": "filename"}})
     *  
     * @param Request $request
     * @param Pln $pln
     * @param Plugin $plugin
     */
    public function pluginAction(Request $request, Pln $pln, Plugin $plugin) {
        
    }
    
}
