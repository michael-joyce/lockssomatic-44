<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Menu;

use AppBundle\Entity\Pln;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

class Builder  implements ContainerAwareInterface {
    use ContainerAwareTrait;

    const CARET = ' ▾'; // U+25BE, black down-pointing small triangle.
    
    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var AuthorizationChecker
     */
    private $authChecker;

    /**
     * @var TokenStorage
     */
    private $tokenStorage;
    
    /**
     * @var ObjectManager
     */
    private $em;

    public function __construct(FactoryInterface $factory, AuthorizationChecker $authChecker, TokenStorage $tokenStorage, ObjectManager $em) {
        $this->factory = $factory;
        $this->authChecker = $authChecker;
        $this->tokenStorage = $tokenStorage;
        $this->em = $em;
    }

    private function hasRole($role) {
        if (!$this->tokenStorage->getToken()) {
            return false;
        }
        return $this->authChecker->isGranted($role);
    }

    public function mainMenu(array $options) {
        $menu = $this->factory->createItem('root');
        $menu->setChildrenAttributes(array(
            'class' => 'nav navbar-nav',
        ));
        
        $menu->addChild('home', array(
            'label' => 'Home',
            'route' => 'homepage',
        ));
        
        if( ! $this->hasRole('ROLE_USER')) {
            return $menu;
        }
        
        $menu->addChild('lockss', array(
            'uri' => '#',
            'label' => 'LOCKSS ' . self::CARET,
        ));
        $menu['lockss']->setAttribute('dropdown', true);
        $menu['lockss']->setLinkAttribute('class', 'dropdown-toggle');
        $menu['lockss']->setLinkAttribute('data-toggle', 'dropdown');
        $menu['lockss']->setChildrenAttribute('class', 'dropdown-menu');
        
        $menu['lockss']->addChild('Content Owners', array(
            'route' => 'content_owner_index'
        ));
        $menu['lockss']->addChild('Networks', array(
            'route' => 'pln_index'
        ));
        $menu['lockss']->addChild('LOCKSS Plugins', array(
            'route' => 'plugin_index'
        ));
        $menu['lockss']->addChild('Content Providers', array(
            'route' => 'content_provider_index'
        ));
        
        $networkMenu = $menu->addChild('networks', array(
            'uri' => '#',
            'label' => 'Networks ' . self::CARET,
        ));
        $networkMenu->setAttribute('dropdown', true);
        $networkMenu->setLinkAttribute('class', 'dropdown-toggle');
        $networkMenu->setLinkAttribute('data-toggle', 'dropdown');
        $networkMenu->setChildrenAttribute('class', 'dropdown-menu');
        
        $networks = $this->em->getRepository(Pln::class)->findAll();
        foreach($networks as $pln) {
            $id = 'network_' . $pln->getId();
            $item = $networkMenu->addChild($id, array(
                'uri' => '#',
                'label' => $pln->getName(),
            ));
            $item->setAttribute('class', 'dropdown-submenu');
            $item->setChildrenAttribute('class', 'dropdown-menu');
            $item->setLinkAttribute('data-toggle', 'dropdown');
            $item->setLinkAttribute('class', 'dropdown-toggle');
            
            $item->addChild($pln->getName(), array(
                'route' => 'pln_show', 
                'routeParameters' => array(
                    'id' => $pln->getId()
                )
            ));
            $item->addChild('Archival Units', array(
                'route' => 'au_index', 
                'routeParameters' => array(
                    'plnId' => $pln->getId()
                )
            ));
            $item->addChild('Boxes', array(
                'route' => 'box_index', 
                'routeParameters' => array(
                    'plnId' => $pln->getId()
                )
            ));
            $item->addChild('Deposits', array(
                'route' => 'deposit_index', 
                'routeParameters' => array(
                    'plnId' => $pln->getId()
                )
            ));
        }
        
        return $menu;        
    }

}
