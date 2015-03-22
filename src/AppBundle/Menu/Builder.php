<?php

namespace AppBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * Description of Builder
 *
 * @author Norman
 */
class Builder extends ContainerAware{
    
    public function mainMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');
        
        $menu->addChild('Home', array('route' => 'homepage'));
        $menu->addChild('Blogs', array('route' => 'blog'));
        
        return $menu;
    }
}
