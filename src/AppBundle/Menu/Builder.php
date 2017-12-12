<?php

namespace AppBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Menu builder for the navigation and search menus.
 */
class Builder implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * Build the navigation menu and return it.
     *
     * @param FactoryInterface $factory
     * @param array $options
     * @return ItemInterface
     */
    public function navMenu(FactoryInterface $factory, array $options) {
        $menu = $factory->createItem('root');
        $menu->setChildrenAttributes(array(
            'class' => 'dropdown-menu',
        ));
        $menu->setAttribute('dropdown', true);

//        $menu->addChild('Titles', array(
//            'route' => 'title_index',
//        ));
//        $menu->addChild('Persons', array(
//            'route' => 'person_index',
//        ));
//        $menu->addChild('Firms', array(
//            'route' => 'firm_index',
//        ));

        return $menu;
    }


}
