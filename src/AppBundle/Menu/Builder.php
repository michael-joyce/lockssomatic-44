<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
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
    public const CARET = ' ▾';

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
     */
    private function hasRole($role) {
        if ( ! $this->tokenStorage->getToken()) {
            return false;
        }

        return $this->authChecker->isGranted($role);
    }

    /**
     * Build the main menu.
     */
    public function mainMenu(array $options) {
        $menu = $this->factory->createItem('root');
        $menu->setChildrenAttributes([
            'class' => 'nav navbar-nav',
        ]);

        $menu->addChild('home', [
            'label' => 'Home',
            'route' => 'homepage',
        ]);

        if ( ! $this->hasRole('ROLE_USER')) {
            return $menu;
        }

        $menu->addChild('lockss', [
            'uri' => '#',
            'label' => 'LOCKSS ' . self::CARET,
        ]);
        $menu['lockss']->setAttribute('dropdown', true);
        $menu['lockss']->setLinkAttribute('class', 'dropdown-toggle');
        $menu['lockss']->setLinkAttribute('data-toggle', 'dropdown');
        $menu['lockss']->setChildrenAttribute('class', 'dropdown-menu');

        $menu['lockss']->addChild('Content Owners', [
            'route' => 'content_owner_index',
        ]);
        $menu['lockss']->addChild('Networks', [
            'route' => 'pln_index',
        ]);
        $menu['lockss']->addChild('LOCKSS Plugins', [
            'route' => 'plugin_index',
        ]);
        $menu['lockss']->addChild('Content Providers', [
            'route' => 'content_provider_index',
        ]);

        $networkMenu = $menu->addChild('networks', [
            'uri' => '#',
            'label' => 'Networks ' . self::CARET,
        ]);
        $networkMenu->setAttribute('dropdown', true);
        $networkMenu->setLinkAttribute('class', 'dropdown-toggle');
        $networkMenu->setLinkAttribute('data-toggle', 'dropdown');
        $networkMenu->setChildrenAttribute('class', 'dropdown-menu');

        $networks = $this->em->getRepository(Pln::class)->findAll();
        foreach ($networks as $pln) {
            $id = 'network_' . $pln->getId();
            $item = $networkMenu->addChild($id, [
                'uri' => '#',
                'label' => $pln->getName(),
            ]);
            $item->setAttribute('class', 'dropdown-submenu');
            $item->setChildrenAttribute('class', 'dropdown-menu');
            $item->setLinkAttribute('data-toggle', 'dropdown');
            $item->setLinkAttribute('class', 'dropdown-toggle');

            $item->addChild($pln->getName(), [
                'route' => 'pln_show',
                'routeParameters' => [
                    'id' => $pln->getId(),
                ],
            ]);
            $item->addChild('Archival Units', [
                'route' => 'au_index',
                'routeParameters' => [
                    'plnId' => $pln->getId(),
                ],
            ]);
            $item->addChild('Boxes', [
                'route' => 'box_index',
                'routeParameters' => [
                    'plnId' => $pln->getId(),
                ],
            ]);
            $item->addChild('Deposits', [
                'route' => 'deposit_index',
                'routeParameters' => [
                    'plnId' => $pln->getId(),
                ],
            ]);
        }

        return $menu;
    }
}
