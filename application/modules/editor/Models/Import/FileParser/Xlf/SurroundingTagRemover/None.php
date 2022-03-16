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
 * calculates and removes leading and trailing paired and special single tags
 * This remover is just used for removing nothing in a polymorphism way
 */
class editor_Models_Import_FileParser_Xlf_SurroundingTagRemover_None extends editor_Models_Import_FileParser_Xlf_SurroundingTagRemover_Abstract {

    public function calculate(bool $preserveWhitespace, array $sourceChunks, array $targetChunks, editor_Models_Import_FileParser_XmlParser $xmlparser)
    {
        //for the "None" Remover nothing has to be removed and therefore calculated!
    }

    /**
     * calculates the tags to be cut off
     * @param array $sourceChunks
     * @param array $targetChunks
     * @return bool true if there is content to be cut off
     */
    protected function _calculate(array $sourceChunks, array $targetChunks): bool {
        //for the "None" Remover nothing has to be removed and therefore calculated
        return true;
    }

    /**
     * removes the leading and trailing tags as calculated before
     * @param array $chunks
     * @return array
     */
    public function sliceTags(array $chunks): array {
        return $chunks;
    }
    
    /**
     * nothing removed from the start here
     * @return string
     */
    public function getLeading(): string {
        return '';
    }

    /**
     * nothing removed from the end here
     * @return string
     */
    public function getTrailing(): string {
        return '';
    }
}