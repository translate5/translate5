<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

use MittagQI\Translate5\Plugins\Okapi\Bconf\Upgrader\UpgraderTo147;
use MittagQI\Translate5\Test\UnitTestAbstract;

class OkapiUpgradeTo147Test extends UnitTestAbstract
{
    private const TEST_DIR = __DIR__ . '/OkapiUpgradeTo147Test';

    /**
     * Test whether selected FPRMs update to v1.47 works as expected
     */
    public function testFprmUpgrade()
    {
        $dataDir = self::TEST_DIR;

        $json = json_decode(file_get_contents($dataDir . '/content.json'), true);
        foreach ($json['fprm'] as $fprmEntry) {
            $fn = $dataDir . '/' . $fprmEntry . '.fprm';
            copy($fn . '.OLD', $fn);
        }

        UpgraderTo147::upgradeFprms($dataDir, '1', 'TestCase');

        foreach ($json['fprm'] as $fprmEntry) {
            $fn = $dataDir . '/' . $fprmEntry . '.fprm';
            $this->assertFileEquals($fn, $fn . '.NEW');
            unlink($fn);
        }
    }

    public function testPipelineUpgrade()
    {
        $dataDir = self::TEST_DIR;
        $fn = $dataDir . '/pipeline.pln';
        copy($fn . '.OLD', $fn);
        UpgraderTo147::upgradePipeline($dataDir);
        $this->assertFileEquals($fn, $fn . '.NEW');
        unlink($fn);
    }
}
