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

trait editor_Controllers_Traits_TermportalTrait
{
    // Declare trait usage
    use editor_Controllers_Traits_ControllerTrait;

    /**
     * Get [attrId => readonly] pairs for the current user
     *
     * @param bool $canDeleteOwn Flag indicating whether current user can't delete any attributes, but can delete own ones
     * @throws Zend_Db_Statement_Exception
     */
    public function getReadonlyFlags(array $attrIds, bool $canDeleteOwn = true): array
    {
        // Collect rights
        $rights = [];
        foreach (['propose', 'review', 'finalize'] as $right) {
            if ($this->isAllowed('editor_term', $right)) {
                $rights[] = $right;
            }
        }

        // Get [attrId => readonly] pairs
        return ZfExtended_Factory
            ::get('editor_Models_Terminology_Models_AttributeModel')
                ->getReadonlyByIds(
                    $attrIds,
                    $canDeleteOwn ? $this->user()->getId() : false,
                    $rights
                );
    }
}
