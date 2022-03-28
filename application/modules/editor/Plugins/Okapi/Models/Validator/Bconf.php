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

class editor_Plugins_Okapi_Models_Validator_Bconf extends ZfExtended_Models_Validator_Abstract {

    /**
     * Validators for Okapi Bconf Entity
     */
    protected function defineValidators() {
        $this->addValidator('id', 'int');
        $this->addValidator('default', 'boolean');
        $this->addValidator('description', 'stringLength', array('min' => 1, 'max' => 255));
        /*
        $this->addValidator('name', 'stringLength', array('min' => 3, 'max' => 64));
        $this->addValidator('cssname', 'stringLength', array('min' => 3, 'max' => 128));
        $this->addValidator('filename', 'stringLength', array('min' => 5, 'max' => 64));
        $this->addValidator('formats', 'stringLength', array('min' => 3, 'max' => 32));
        $this->addValidator('weight', 'int');
        $this->addValidator('weightname', 'stringLength', array('min' => 0, 'max' => 32));
        $this->addValidator('style', 'stringLength', array('min' => 0, 'max' => 32));
        $this->addValidator('available', 'int');
        $this->addValidator('created', 'date', array('Y-m-d H:i:s'));
        */
    }
}
