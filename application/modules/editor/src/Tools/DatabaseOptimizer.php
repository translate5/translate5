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

namespace MittagQI\Translate5\Tools;

use MittagQI\Translate5\Tools\DatabaseOptimizer\ReportDto;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Statement_Exception;

class DatabaseOptimizer
{
    private const TABLES_NEED_OPTIMIZE_DAILY = [
        'LEK_languageresources_batchresults',
        'Zf_worker',
    ];

    public function __construct(
        private readonly Zend_Db_Adapter_Abstract $db,
    ) {
    }

    public function optimizeAll(?callable $callback = null): void
    {
        $this->optimize($this->db->listTables(), $callback);
    }

    public function optimizeDaily(?callable $callback = null): void
    {
        $this->optimize(self::TABLES_NEED_OPTIMIZE_DAILY, $callback);
    }

    /**
     * Optimizes the given tables and executes the callback with the optimization results.
     *
     * @param ?callable $callback A callback function to process the optimization results.
     *                           callback signature: function (string $table, string $msgType, string $msgText): void
     */
    private function optimize(array $tables, ?callable $callback = null): void
    {
        foreach ($tables as $table) {
            try {
                $stmt = $this->db->query('OPTIMIZE TABLE `' . $table . '`');
                $data = $stmt->fetchAll();
            } catch (Zend_Db_Statement_Exception $e) {
                $data = [[
                    'Msg_type' => 'Exception',
                    'Msg_text' => $e->getMessage(),
                ]];
            }
            if (is_null($callback)) {
                continue;
            }

            $reportDto = new ReportDto();
            $reportDto->table = $table;
            $reportDto->statusOk = false;

            $text = [];
            foreach ($data as $row) {
                if ($row['Msg_type'] === 'status') {
                    if ($row['Msg_text'] === 'OK') {
                        $reportDto->statusOk = true;

                        continue;
                    }
                    $text[] = $row['Msg_text'];

                    continue;
                }
                $text[] = $row['Msg_type'] . ': ' . $row['Msg_text'];
            }
            $reportDto->text = join(',', $text);

            $callback(
                $reportDto,
            );
        }
    }
}
