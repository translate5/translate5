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
 * Abstract Exception Class to get more details Information on TermTagger-Error.
 */
abstract class editor_Plugins_TermTagger_Exception_Abstract extends ZfExtended_ErrorCodeException {
    /**
     * @var string
     */
    protected $domain = 'editor.terminology';
    
    /**
     * Basically the termtagger exceptions produce just a warning
     * @var integer
     */
    protected $level = ZfExtended_Logger::LEVEL_WARN;
    
    protected static $localErrorCodes = [
        'E1116' => 'Could not load TBX into TermTagger: TBX hash is empty.',
        'E1117' => 'Could not load TBX into TermTagger: TermTagger HTTP result was not successful!',
        'E1118' => 'Could not load TBX into TermTagger: TermTagger HTTP result could not be decoded!',
        'E1119' => 'TermTagger communication Error',
        'E1130' => 'TermTagger communication Error, probably crashing the TermTagger instance.',
        'E1120' => 'TermTagger returns an error on tagging segments: {reason}.',
        'E1121' => 'TermTagger result could not be decoded.',
        'E1129' => 'TermTagger DOWN: The configured TermTagger "{termTaggerUrl}" is not reachable and is deactivated in translate5 temporary.',
        'E1131' => 'TermTagger DOWN: No TermTagger instances are available, please enable them and reimport this task.',
        'E1240' => 'TermTagger TIMEOUT: The configured TermTagger "{termTaggerUrl}" did not respond in an appropriate time.',
        'E1326' => 'TermTagger can not work when source and target language are equal.',
    ];
}