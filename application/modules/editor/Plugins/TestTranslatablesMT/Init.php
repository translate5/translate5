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

declare(strict_types=1);

class editor_Plugins_TestTranslatablesMT_Init extends ZfExtended_Plugin_Abstract
{
    protected static string $description = 'Provides a demo MT engine for development purposes - ' .
    'is faking a machine translation by using text replaced by a dollar sign ($) for ' .
    'visualisation of what is translatable in translate5';

    protected static bool $activateForTests = true;

    protected static bool $enabledByDefault = true;

    public function init(): void
    {
        $serviceManager = ZfExtended_Factory::get(editor_Services_Manager::class);
        $serviceManager->addService('editor_Plugins_TestTranslatablesMT');
    }
}
