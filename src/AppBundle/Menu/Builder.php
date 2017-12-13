<?php

namespace AppBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

class Builder {
    
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
    
    public function __construct(FactoryInterface $factory, AuthorizationChecker $authChecker, TokenStorage $tokenStorage) {
        $this->factory = $factory;
        $this->authChecker = $authChecker;
        $this->tokenStorage = $tokenStorage;
    }
    
    private function hasRole($role) {
        if( ! $this->tokenStorage->getToken()) { 
            return false;
        }
        return $this->authChecker->isGranted($role);
    }
    
    private function getUser() {
        if(! $this->tokenStorage->getToken()) {
            return false;
        }
        return $this->tokenStorage->getToken()->getUser();
    }
    
    public function buildMainMenu(array $options) {
        $menu = $this->factory->createItem('root');
        $menu->setChildrenAttributes(array(
            'class' => 'dropdown-menu',
        ));
        $menu->setAttribute('dropdown', true);
        
        $menu->addChild('Home', array(
            'route' => 'homepage',
        ));
        
        return $menu;
    }
    
}
