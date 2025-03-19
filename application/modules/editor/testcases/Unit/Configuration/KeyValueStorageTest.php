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

declare(strict_types=1);

namespace MittagQI\Translate5\Test\Unit\Segment;

use MittagQI\Translate5\Configuration\KeyValueStorage;
use PHPUnit\Framework\TestCase;
use Zend_Db_Adapter_Abstract;

class KeyValueStorageTest extends TestCase
{
    private $db;

    private KeyValueStorage $keyValueStorage;

    protected function setUp(): void
    {
        $this->db = $this->createMock(Zend_Db_Adapter_Abstract::class);
        $this->keyValueStorage = new KeyValueStorage($this->db);
    }

    public function testGetExistingValue(): void
    {
        $paramValue = 'testValue';
        $this->db->method('fetchOne')->willReturn($paramValue);
        // expects no value saving query
        $this->db
            ->expects($this->never())
            ->method('query');
        $this->assertEquals($paramValue, $this->keyValueStorage->get('paramName', 'defaultValue'));
    }

    public function testGetAbsentValue(): void
    {
        $paramValue = 'testValue';
        $this->db->method('fetchOne')->willReturn(false);
        // expects default value saving
        $this->db
            ->expects($this->once())
            ->method('query');
        $this->assertEquals($paramValue, $this->keyValueStorage->get('paramName', $paramValue));
    }

    public function testSetValue(): void
    {
        $paramValue = 'testValue';
        // expects value saving
        $this->db
            ->expects($this->once())
            ->method('query');
        $this->keyValueStorage->set('paramName', $paramValue);
    }
}
