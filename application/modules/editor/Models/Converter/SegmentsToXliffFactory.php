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

use editor_Models_Converter_SegmentsToXliff as SegmentsToXliff;
use editor_Models_Converter_SegmentsToXliff2 as SegmentsToXliff2;
use editor_Models_Converter_SegmentsToXliffAbstract as SegmentsToXliffAbstract;

class editor_Models_Converter_SegmentsToXliffFactory
{
    /**
     * @throws \ReflectionException
     */
    public static function create(string $currentStep, \Zend_Config $config): SegmentsToXliffAbstract
    {
        $xliff2Active = (bool) $config->runtimeOptions->editor->notification->xliff2Active;

        //if the config is active, convert segments to xliff2 format
        if ($xliff2Active) {
            $xliffConf = [
                SegmentsToXliffAbstract::CONFIG_ADD_TERMINOLOGY => true,
                SegmentsToXliffAbstract::CONFIG_INCLUDE_DIFF => true,
                SegmentsToXliff2::CONFIG_ADD_QM => true,
            ];

            return ZfExtended_Factory::get(SegmentsToXliff2::class, [$xliffConf, $currentStep]);
        }

        $xliffConf = [
            SegmentsToXliff::CONFIG_INCLUDE_DIFF => (bool) $config->runtimeOptions->editor->notification->includeDiff,
            SegmentsToXliff::CONFIG_PLAIN_INTERNAL_TAGS => true,
            SegmentsToXliff::CONFIG_ADD_ALTERNATIVES => true,
            SegmentsToXliff::CONFIG_ADD_TERMINOLOGY => true,
        ];

        return ZfExtended_Factory::get(SegmentsToXliff::class, [$xliffConf]);
    }
}
