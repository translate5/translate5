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
 * Class representing a SRX file
 * A SRX is an xml with a defined structure containing nodes with language specific RegEx rules
 * for more documentation, see editor_Plugins_Okapi_Bconf_Segmentation
 */
final class editor_Plugins_Okapi_Bconf_Segmentation_Srx extends editor_Plugins_Okapi_Bconf_ResourceFile {

    const EXTENSION = 'srx';

    // a SRX is generally a XML variant
    protected string $mime = 'text/xml';

    /**
     * Validates a SRX
     * TODO FIXME: this basic validation can be improved
     * @return bool
     */
    public function validate(bool $forImport=false) : bool {
        $parser = new editor_Utils_Dom();
        $parser->loadXML($this->content);
        // sloppy checking here as we do not know how tolerant longhorn actually is
        if($parser->isValid()){
            $rootTag = strtolower($parser->firstChild?->tagName);
            if($rootTag === 'srx'){
                return true;
            } else {
                // DEBUG
                if($this->doDebug){ error_log('SRX FILE '.basename($this->path).' is invalid: No "srx" root tag found'); }
                $this->validationError = 'No "srx" root tag found';
            }
        } else {
            // DEBUG
            if($this->doDebug){ error_log('SRX FILE '.basename($this->path).' is invalid: Invalid XML'); }
            $this->validationError = 'Invalid XML';
        }
        return false;
    }

    /**
     * Updates the contents of a SRX
     * @param string $content
     */
    public function setContent(string $content) {
        $this->content = $content;
    }

    /**
     * Updates our path
     * @param string $path
     */
    public function setPath(string $path) {
        $this->path = $path;
    }
}
