<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * Abstract Base Connector
 */
abstract class editor_Plugins_TmMtIntegration_Connector_Abstract {
    /**
     * The name of the Resource
     * @var string
     */
    protected $name;
    
    /**
     * @var string
     */
    protected $sourceLanguage;
    
    /**
     * @var array
     */
    protected $targetLanguages;
    
    abstract public function __construct(stdClass $config);
    
    /**
     * returns a list with connector instances, one per resource
     */
    public static function createForAllResources(){
        //must be implemented in the subclass → FIXME bad design
    }
    
    /**
     * returns one connector instance to a given resource id
     */
    public static function createForResource(string $resourceId){
        //must be implemented in the subclass → FIXME bad design
    }
    
    
    abstract public function synchronizeTmList();
    
    /**
     * returns the resource name
     */
    public function getName() {
        return $this->name;
    }
    
    /**
     * Opens the desired Resource
     * @param editor_Plugins_TmMtIntegration_Models_TmMt $tmmt
     */
    abstract public function open(editor_Plugins_TmMtIntegration_Models_TmMt $tmmt);
    
    abstract public function translate(string $toTranslate);
}