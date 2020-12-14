<?php
/*
 START LICENSE AND COPYRIGHT
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
 
 This file is part of a plug-in for translate5.
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 For the license of this plug-in, please see below.
 
 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html
 
 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5.
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
 http://www.gnu.org/licenses/gpl.html
 http://www.translate5.net/plugin-exception.txt
 
 END LICENSE AND COPYRIGHT
 */

class editor_Segment_Quality_Dispatcher {
    
    /**
     * @var editor_Segment_Quality_Dispatcher
     */
    private static $_instance = null;
    /**
     *
     * @return editor_Segment_Quality_Dispatcher
     */
    public static function instance(){
        if(self::$_instance == null){
            self::$_instance = new editor_Segment_Quality_Dispatcher();
        }
        return self::$_instance;
    }
    /**
     * 
     * @var editor_Segment_Quality_ProviderInterface[]
     */
    private $registry;
    /**
     * To prevent any changes during import
     * @var boolean
     */
    private $locked = false;
    
    private function __construct(){
        
    }
    
    public function register(editor_Segment_Quality_ProviderInterface $provider){
        $this->registry[$provider->getType()] = $provider;
    }
    
    public function hasProvider(string $type){
        return array_key_exists($type, $this->registry);
    }
    
    public function processAllSegments(){
        
    }
    
    public function processSegment(){
        
    }
    
    public function createStatisticsData(){
        
    }
    
    public function createGridFilterData(){
        
    }
}
