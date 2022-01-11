<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 There is a plugin exception available for use with this release of translate5 for
 translate5: Please see http://www.translate5.net/plugin-exception.txt or 
 plugin-exception.txt in the root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Fallback Workflow Class, needed only to simulate the old default workflow for updating from older versions to the version with workflows out of DB
 */
class editor_Workflow_DefaultFallback extends editor_Workflow_Default {
    public function __construct($name) {
        $this->definition = ZfExtended_Factory::get('editor_Workflow_CachableDefinition');

	    $this->definition->name = $name;
        $this->definition->label = ZfExtended_Zendoverwrites_Translate::getInstance()->_('Standard Workflow');

        $this->definition->stepChain[] = self::STEP_NO_WORKFLOW;
        $this->definition->stepChain[] = 'translation';
        $this->definition->stepChain[] = 'reviewing';
        $this->definition->stepChain[] = 'translatorCheck';
        $this->definition->stepChain[] = self::STEP_WORKFLOW_ENDED;

        $this->definition->steps2Roles['translation'] = 'translator';
        $this->definition->steps2Roles['reviewing'] = 'reviewer';
        $this->definition->steps2Roles['visiting'] = 'visitor';

        $this->definition->stepsWithFilter[] = 'translatorCheck';

        $this->definition->labels['STEP_TRANSLATION']     = 'Übersetzung';
        $this->definition->labels['STEP_REVIEWING']       = 'Lektorat';
        $this->definition->labels['STEP_TRANSLATORCHECK'] = 'Zweites Lektorat';
        $this->definition->labels['STEP_VISITING']        = 'Nur anschauen';

        $this->definition->steps['STEP_TRANSLATION']     = 'translation';
        $this->definition->steps['STEP_REVIEWING']       = 'reviewing';
        $this->definition->steps['STEP_TRANSLATORCHECK'] = 'translatorCheck';
        $this->definition->steps['STEP_VISITING']        = 'visiting';
        
        //calculate the valid states
        $this->initValidStates();

        $this->checkForMissingConfiguration();
        $this->hookin = ZfExtended_Factory::get('editor_Workflow_Default_Hooks',[$this]);
        $this->segmentHandler = ZfExtended_Factory::get('editor_Workflow_Default_SegmentHandler',[$this]);
    }
}
