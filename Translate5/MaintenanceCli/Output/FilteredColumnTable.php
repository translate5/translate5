<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace Translate5\MaintenanceCli\Output;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * prints a table prepends data column filtering
 */
class FilteredColumnTable extends Table
{
    protected $columns = [];

    /**
     * Configures the column keys to be used in the data table
     * @param array $columnsToBeUsed the column keys to be displayed
     */
    public function __construct(OutputInterface $output, array $columnsToBeUsed)
    {
        $this->columns = $columnsToBeUsed;
        parent::__construct($output);
    }

    /**
     * @see \Symfony\Component\Console\Helper\Table::setRows()
     */
    public function setRows(array $rows)
    {
        parent::setRows(array_map(function ($item) {
            $result = [];
            foreach ($this->columns as $column) {
                $result[] = $item[$column];
            }

            return $result;
            //oneliner, but does not maintain order:
            //return array_intersect_key($item, array_flip($this->columns));
        }, $rows));
    }
}
