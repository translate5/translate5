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

namespace MittagQI\Translate5\Plugins\Okapi\tests;

use MittagQI\Translate5\Plugins\Okapi\Bconf\Filter\FprmUpdaterTo147;
use MittagQI\Translate5\Test\UnitTestAbstract;

/**
 * Test whether selected FPRMs update to v1.47 works as expected
 */
class OkapiUpdateFiltersTo147Test extends UnitTestAbstract
{
    public function testUpdater()
    {
        $bconfDir = __DIR__ . '/OkapiUpdateFiltersTo147Test';
        $updater = new FprmUpdaterTo147();

        $json = json_decode(file_get_contents($bconfDir . '/content.json'), true);
        foreach ($json['fprm'] as $fprmEntry) {
            $fn = $bconfDir . '/' . $fprmEntry . '.fprm';
            copy($fn . '.OLD', $fn);
        }

        $updater->updateInDir($bconfDir, '1', 'TestCase');

        foreach ($json['fprm'] as $fprmEntry) {
            $fn = $bconfDir . '/' . $fprmEntry . '.fprm';
            $this->assertEquals(
                file_get_contents($fn),
                file_get_contents($fn . '.NEW')
            );
            unlink($fn);
        }
    }
}
