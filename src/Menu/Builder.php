<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Menu;

use App\Entity\Pln;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
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
     *
     * @return ItemInterface
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

        $lockssMenu = $menu->addChild('lockss', [
            'uri' => '#',
            'label' => 'LOCKSS',
        ]);
        $lockssMenu->setAttribute('dropdown', true);
        $lockssMenu->setLinkAttribute('class', 'dropdown-toggle');
        $lockssMenu->setLinkAttribute('data-toggle', 'dropdown');
        $lockssMenu->setChildrenAttribute('class', 'dropdown-menu');

        $lockssMenu->addChild('Content Owners', [
            'route' => 'content_owner_index',
        ]);
        $lockssMenu->addChild('Networks', [
            'route' => 'pln_index',
        ]);
        $lockssMenu->addChild('LOCKSS Plugins', [
            'route' => 'plugin_index',
        ]);
        $lockssMenu->addChild('Content Providers', [
            'route' => 'content_provider_index',
        ]);

        $networkMenu = $menu->addChild('networks', [
            'uri' => '#',
            'label' => 'Networks',
        ]);
        $networkMenu->setAttribute('dropdown', true);
        $networkMenu->setLinkAttribute('class', 'dropdown-toggle');
        $networkMenu->setLinkAttribute('data-toggle', 'dropdown');
        $networkMenu->setChildrenAttribute('class', 'dropdown-menu');

        $networkMenu->addChild('All Networks', [
            'route' => 'pln_index',
        ]);

        $networks = $this->em->getRepository(Pln::class)->findAll();
        $divider = $networkMenu->addChild('divider', [
            'label' => '',
        ]);
        $divider->setAttributes([
            'role' => 'separator',
            'class' => 'divider',
        ]);

        foreach ($networks as $pln) {
            $networkMenu->addChild($pln->getName(), [
                'route' => 'pln_show',
                'routeParameters' => [
                    'id' => $pln->getId(),
                ],
                'class' => 'subhead',
            ])->setLinkAttribute('class', 'subhead');
            $networkMenu->addChild('Archival Units', [
                'route' => 'au_index',
                'routeParameters' => [
                    'plnId' => $pln->getId(),
                ],
            ]);
            $networkMenu->addChild('Boxes', [
                'route' => 'box_index',
                'routeParameters' => [
                    'plnId' => $pln->getId(),
                ],
            ]);
            $networkMenu->addChild('Deposits', [
                'route' => 'deposit_index',
                'routeParameters' => [
                    'plnId' => $pln->getId(),
                ],
            ]);
        }

        return $menu;
    }
}
