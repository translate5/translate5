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

namespace MittagQI\Translate5\Plugins\Okapi\Db\Validator;

use ZfExtended_Models_Validator_Abstract;

class BconfFilterValidator extends ZfExtended_Models_Validator_Abstract
{
    /**
     * Validators for Okapi Bconf Filter Entity
     * @throws \Zend_Exception
     */
    protected function defineValidators()
    {
        $this->addValidator('id', 'int');
        $this->addValidator('bconfId', 'int');
        $this->addValidator('okapiType', 'stringLength', [
            'min' => 1,
            'max' => 50,
        ]);
        $this->addValidator('okapiId', 'stringLength', [
            'min' => 1,
            'max' => 255,
        ]);
        $this->addValidator('mimeType', 'stringLength', [
            'min' => 0,
            'max' => 50,
        ]);
        $this->addValidator('name', 'stringLength', [
            'min' => 1,
            'max' => 100,
        ]);
        $this->addValidator('description', 'stringLength', [
            'min' => 0,
            'max' => 255,
        ]);
        $this->addValidator('hash', 'stringLength', [
            'min' => 32,
            'max' => 32,
        ]);
    }
}
