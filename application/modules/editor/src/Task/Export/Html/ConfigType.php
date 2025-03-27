<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of ZfExtended library

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU LESSER GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file lgpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU LESSER GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
https://www.gnu.org/licenses/lgpl-3.0.txt

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU LESSER GENERAL PUBLIC LICENSE version 3
             https://www.gnu.org/licenses/lgpl-3.0.txt

END LICENSE AND COPYRIGHT
*/


declare(strict_types=1);

namespace MittagQI\Translate5\Task\Export\Html;

/**
 * Contains the config handler for core types
 */
class ConfigType extends \ZfExtended_DbConfig_Type_CoreTypes
{
    /**
     * returns the GUI view class to be used or null for default handling
     */
    public function getGuiViewCls(): ?string
    {
        return 'Editor.view.admin.config.type.TaskHtmlExport';
    }

    /**
     * returns true if there are "defaults" values and the given value is one of them
     */
    public function isValidInDefaults(\editor_Models_Config $config, string $value): bool
    {
        $autoStates = new \editor_Models_Segment_AutoStates();

        // Check value is in those ids
        return strlen(trim($value)) === 0 || in_array((int) $value, $autoStates->getStates(), true);
    }
}
