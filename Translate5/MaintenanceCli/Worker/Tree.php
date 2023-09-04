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

namespace Translate5\MaintenanceCli\Worker;

use Zend_Db_Adapter_Abstract;

final class Tree
{
    /**
     * Compare leafs by weight
     * @param Leaf $a
     * @param Leaf $b
     * @return int
     */
    public static function compare(Leaf $a, Leaf $b): int
    {
        if ($a->getWeight() > $b->getWeight()) {
            return 1;
        } else if ($a->getWeight() < $b->getWeight()) {
            return -1;
        }
        return 0;
    }

    /**
     * condensing visible name
     * @param string $worker
     * @return string
     */
    public static function createTitle(string $worker): string
    {
        if (str_starts_with($worker, 'MittagQI\\Translate5\\')) {
            return substr($worker, 20);
        }
        return $worker;
    }

    /**
     * Finds the children for a worker by talking the operation(s) into account
     * @param Zend_Db_Adapter_Abstract $dbAdapter
     * @param string $worker
     * @param array $operations
     * @return array
     */
    public static function findChildren(Zend_Db_Adapter_Abstract $dbAdapter, string $worker, array $operations): array
    {
        $leafs = [];
        foreach (self::fetchAllDependencies($dbAdapter) as $row) {
            if ($row['dependency'] === $worker && Operation::workerValidForOperation($row['worker'], $operations)) {
                $leafs[] = $row['worker'];
            }
        }
        return $leafs;
    }

    /**
     * Find's all Workers
     * TODO FIXME: we should find the workers in the Code instead !
     * @param Zend_Db_Adapter_Abstract $dbAdapter
     * @return array
     */
    public static function findAllWorkers(Zend_Db_Adapter_Abstract $dbAdapter): array
    {
        $all = [];
        foreach (self::fetchAllDependencies($dbAdapter) as $row) {
            $all[$row['dependency']] = 1;
            $all[$row['worker']] = 1;
        }
        return array_keys($all);
    }

    private static function fetchAllDependencies(Zend_Db_Adapter_Abstract $dbAdapter): array
    {
        if (!isset(self::$dependencies)) {
            self::$dependencies = $dbAdapter->fetchAssoc('SELECT * FROM `Zf_worker_dependencies`');
        }
        return self::$dependencies;
    }

    private static array $dependencies;

    /**
     * @var Leaf[]
     */
    private array $leafs;

    public function __construct(private Zend_Db_Adapter_Abstract $dbAdapter)
    {
    }

    public function load(): void
    {
        $this->leafs = [];
        $leafWorkers = $this->dbAdapter
            ->fetchCol('SELECT DISTINCT d1.dependency FROM `Zf_worker_dependencies` d1 WHERE NOT EXISTS (SELECT d2.worker FROM `Zf_worker_dependencies` d2 WHERE d2.worker = d1.dependency)');
        foreach ($leafWorkers as $leafWorker) {
            $leaf = new Leaf($this->dbAdapter, $leafWorker);
            $leaf->load();
            $this->leafs[] = $leaf;
        }
    }

    public function build(): void
    {
        // build children
        foreach ($this->leafs as $leaf) {
            $leaf->build();
        }
        // order children by depth
        usort($this->leafs, Leaf::class . '::compare');
    }


    public function render(bool $isFull, string $newline = "\n"): string
    {
        usort($this->leafs, Tree::class . '::compare');

        $text =
            $newline
            . $newline
            . 'WORKER TREE'
            . $newline
            . '==========='
            . $newline
            . $newline;
        foreach ($this->leafs as $leaf) {
            $title = 'Operation(s): "' . implode('", "', $leaf->getOperations()) . '"';
            $text .=
                $newline
                . $title
                . $newline
                . str_repeat('-', strlen($title))
                . $newline
                . $leaf->render($newline, $isFull)
                . $newline
                . $newline;
        }

        // report all unused workers as potential errors!
        $allWorkers = self::findAllWorkers($this->dbAdapter);
        $allUsed = [];
        foreach ($this->leafs as $leaf) {
            $leaf->addUsed($allUsed);
        }
        $allUsed = array_keys($allUsed);
        sort($allWorkers);
        sort($allUsed);
        $unused = array_diff($allWorkers, $allUsed);

        if(count($unused) > 0){
            $text .=
                $newline
                . $newline
                . 'POTENTIAL ERROR: UNUSED WORKERS'
                . $newline
                . '==============================='
                . $newline
                . $newline;
            foreach($unused as $worker){
                $text .= self::createTitle($worker) . $newline;
            }
        }

        return $text;
    }
}
