<?php
namespace PHPSTORM_META {
    //metadata directives
    override(
        \ZfExtended_Factory::get(0),
        map([
            '' => '@'
        ])
    );

    override(
        \Zend_Registry::get(0),
        map([
            'logger' => \ZfExtended_Logger::class,
            'config' => \Zend_Config::class,
            'cache' => \Zend_Cache_Core::class,
        ])
    );
}