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
            'logger' => \ZfExtended_logger::class,
        ])
    );
}