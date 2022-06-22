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
 * Abstract Exception Class to get more details Information on SpellCheck-Error.
 */
abstract class editor_Plugins_SpellCheck_Exception_Abstract extends ZfExtended_ErrorCodeException {

    /**
     * @var string
     */
    protected $domain = 'editor.spellcheck';
    
    /**
     * Basically the spell-check exceptions produce just a warning
     *
     * @var integer
     */á
    protected $level = ZfExtended_Logger::LEVEL_WARN;

    /**
     * Error codes for spellcheck messages
     *
     * @var array
     */
    protected static $localErrorCodes = [
        //'E1117' => 'Could not load TBX into TermTagger: TermTagger HTTP result was not successful!',
        //'E1118' => 'Could not load TBX into TermTagger: TermTagger HTTP result could not be decoded!',
        //'E1121' => 'TermTagger result could not be decoded.',

        'E1129' => 'SpellCheck DOWN: The configured LanguageTool "{languageToolUrl}" is not reachable and is deactivated in translate5 temporary.',
        'E1131' => 'SpellCheck DOWN: No LanguageTool instances are available, please enable them and reimport this task.',
        'E1240' => 'SpellCheck TIMEOUT: The configured LanguageTool "{languageToolUrl}" did not respond in an appropriate time.',
        'E1326' => 'SpellCheck can not work when target language is not supported by LanguageTool.',
    ];
}