<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * Class representing a srx file
 * A srx is an xml with a defined structure
 */
final class editor_Plugins_Okapi_Bconf_Srx extends editor_Plugins_Okapi_Bconf_ResourceFile {

    // a SRX is generally a XML variant
    protected string $mime = 'text/xml';

    /**
     * Validates a SRX
     * TODO FIXME: this validation can be improved
     * @return bool
     */
    public function validate() : bool {
        $parser = new editor_Utils_Dom();
        $parser->loadXML($this->content);
        // sloppy checking here as we do not know how tolerant longhorn actually is
        if($parser->isValid()){
            $rootTag = strtolower($parser->firstChild?->tagName);
            if($rootTag === 'srx'){
                return true;
            } else {
                $this->validationError = "No 'srx' root tag found";
            }
        } else {
            $this->validationError = 'Invalid XML';
        }
        return false;
    }
}