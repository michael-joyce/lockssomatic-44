<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Menu;

use AppBundle\Entity\Pln;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Menu builder for LOCKSSOMatic.
 */
class Builder implements ContainerAwareInterface {
    use ContainerAwareTrait;

    /**
     * U+25BE, black down-pointing small triangle.
     */
    const CARET = ' ▾';
    
    /**
     * Menu item factory.
     *
     * @var FactoryInterface
     */
    private $factory;

    /**
     * Authorization checker for roles.
     *
     * @var AuthorizationCheckerInterface
     */
    private $authChecker;

    /**
     * User login token storage service.
     *
     * @var TokenStorageInterface
     */
    private $tokenStorage;
    
    /**
     * Doctrine instance.
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * Construct the menu builder.
     *
     * @param FactoryInterface $factory
     *   Dependency-injected menu item factory.
     * @param AuthorizationCheckerInterface $authChecker
     *   Dependency-injected auth interface to check user roles.
     * @param TokenStorageInterface $tokenStorage
     *   Dependency-injected token storage.
     * @param EntityManagerInterface $em
     *   Dependency-injected doctrine instance.
     */
    public function __construct(FactoryInterface $factory, AuthorizationCheckerInterface $authChecker, TokenStorageInterface $tokenStorage, EntityManagerInterface $em) {
        $this->factory = $factory;
        $this->authChecker = $authChecker;
        $this->tokenStorage = $tokenStorage;
        $this->em = $em;
    }

    /**
     * Check if the current user has $role.
     *
     * @param string $role
     *   The role to check.
     */
    private function hasRole($role) {
        if (!$this->tokenStorage->getToken()) {
            return false;
        }
        return $this->authChecker->isGranted($role);
    }

    /**
     * Build the main menu.
     *
     * @param array $options
     *   Unused options array.
     */
    public function mainMenu(array $options) {
        $menu = $this->factory->createItem('root');
        $menu->setChildrenAttributes(array(
            'class' => 'nav navbar-nav',
        ));
        
        $menu->addChild('home', array(
            'label' => 'Home',
            'route' => 'homepage',
        ));
        
        if (!$this->hasRole('ROLE_USER')) {
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
            'route' => 'content_owner_index',
        ));
        $menu['lockss']->addChild('Networks', array(
            'route' => 'pln_index',
        ));
        $menu['lockss']->addChild('LOCKSS Plugins', array(
            'route' => 'plugin_index',
        ));
        $menu['lockss']->addChild('Content Providers', array(
            'route' => 'content_provider_index',
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
        foreach ($networks as $pln) {
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
                'id' => $pln->getId(),
                ),
            ));
            $item->addChild('Archival Units', array(
                'route' => 'au_index',
                'routeParameters' => array(
                'plnId' => $pln->getId(),
                ),
            ));
            $item->addChild('Boxes', array(
                'route' => 'box_index',
                'routeParameters' => array(
                'plnId' => $pln->getId(),
                ),
            ));
            $item->addChild('Deposits', array(
                'route' => 'deposit_index',
                'routeParameters' => array(
                'plnId' => $pln->getId(),
                ),
            ));
        }
        
        return $menu;
    }

}
