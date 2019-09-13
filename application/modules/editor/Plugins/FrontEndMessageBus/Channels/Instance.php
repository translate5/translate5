<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * encapsulates defined commands directly to the MessageBus
 * @method void startSession() startSession($sessionId, stdClass $userData)
 * @method void stopSession() stopSession($sessionId)
 */
class editor_Plugins_FrontEndMessageBus_Channels_Instance extends editor_Plugins_FrontEndMessageBus_Channels_Abstract {
    const CHANNEL = 'instance';
    
    //here methods could be implemented if more logic is needed as just passing the arguments directly to the MessageBus via __call 
    // this could be for example necessary to convert entities like editor_Models_Task to native stdClass / array data. 
    // Since only the latter ones can be send to the MessageBus 
}