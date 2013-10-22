<?php
namespace Lemon\ReportBundle\EventListener;

use Lemon\BootstrapBundle\Event\ConfigureMenuEvent;

class ConfigureMenuListener
{
    public function onMenuConfigure(ConfigureMenuEvent $event)
    {
        $menu = $event->getMenu();

        $menu->addChild('My Reports', array(
            'route' => 'lemon_report_list'
        ));
    }
}
