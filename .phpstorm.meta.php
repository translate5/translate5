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
            'integration.segment.update' => \MittagQI\Translate5\Integration\UpdateSegmentService::class,
            'integration.segment.update.dto_factory' => \MittagQI\Translate5\Integration\SegmentUpdateDtoFactory::class,
        ])
    );
}