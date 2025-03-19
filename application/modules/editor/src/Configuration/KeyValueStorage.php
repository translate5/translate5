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

namespace MittagQI\Translate5\Configuration;

use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;

class KeyValueStorage
{
    private Zend_Db_Adapter_Abstract $db;

    private const TABLE = 'LEK_key_value_data';

    public function __construct(Zend_Db_Adapter_Abstract $db = null)
    {
        $this->db = $db !== null ? $db : Zend_Db_Table::getDefaultAdapter();
    }

    public function get(string $key, string $default = ''): string
    {
        $value = $this->db->fetchOne('SELECT value FROM ' . self::TABLE . ' WHERE id = ?', $key);
        if ($value === false) {
            $value = $default;
            $this->set($key, $value);
        }

        return $value;
    }

    public function set(string $key, string|int $value): void
    {
        $this->db->query(
            'INSERT INTO ' . self::TABLE . ' SET id = :id, value = :value ON DUPLICATE KEY UPDATE value = :value',
            [
                "id" => $key,
                "value" => $value,
            ]
        );
    }
}
