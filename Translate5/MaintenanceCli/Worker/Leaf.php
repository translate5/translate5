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

final class Leaf
{

    const INDENT = 4;

    /**
     * helper to sort leafs
     * @param Leaf $a
     * @param Leaf $b
     * @return int
     */
    public static function compare(Leaf $a, Leaf $b): int
    {
        if ($a->getDepth() < $b->getDepth()) {
            return 1;
        } else if ($a->getDepth() > $b->getDepth()) {
            return -1;
        } else {
            return strnatcasecmp($a->getTitle(), $b->getTitle());
        }
    }

    /**
     * @var Leaf[]
     */
    private array $leafs;
    private int $level;
    private int $depth = 0;
    private int $weight = -1;
    /**
     * Holds the operation(s) a branch represents
     * @var string[]
     */
    private array $operations;

    public function __construct(private Zend_Db_Adapter_Abstract $dbAdapter, private string $worker, private ?Leaf $parent = null)
    {
        // level is 0 for the workers starting a branch
        $this->level = ($parent === null) ? 0 : $parent->getLevel() + 1;
        // the operation is defined by the start worker and bubbles up otherwise
        $this->operations = ($parent === null) ?
            Operation::detectOperations($worker)
            : $parent->getOperations();
    }

    public function load(): void
    {
        $this->leafs = [];
        foreach (Tree::findChildren($this->dbAdapter, $this->worker, $this->operations) as $leafWorker) {
            $leaf = new Leaf($this->dbAdapter, $leafWorker, $this);
            $leaf->load();
            $this->leafs[] = $leaf;
        }
    }

    public function build(): void
    {
        $maxDepth = 0;
        // build children and find max depth
        foreach ($this->leafs as $leaf) {
            $leaf->build();
            $maxDepth = max($maxDepth, ($leaf->getDepth() + 1));
        }
        $this->depth = $maxDepth;
        // order children by depth
        usort($this->leafs, Leaf::class . '::compare');
    }

    public function render(string $newline = "\n", bool $uncondensed = false): string
    {
        // create combined tree
        $tree = [];
        $tree[0] = [$this->getTitle()];
        $this->combine($tree);
        $numLevel = count($tree);

        // now we condense the array, elements in the topmost branch do not need to appear in lower-level branches
        if (!$uncondensed) {

            $alreadyShown = [];
            for ($i = ($numLevel - 1); $i >= 0; $i--) {
                $newItems = [];
                foreach ($tree[$i] as $item) {
                    if (!array_key_exists($item, $alreadyShown)) {
                        $newItems[] = $item;
                        $alreadyShown[$item] = 1;
                    }
                }
                sort($newItems);
                $tree[$i] = $newItems;
            }
        }

        // and render
        $text = '';
        for ($i = 0; $i < $numLevel; $i++) {
            foreach ($tree[$i] as $item) {
                $text .= $newline . $this->createIndent($i) . $item;
            }
        }
        return $text;
    }

    public function combine(array &$tree): void
    {
        foreach ($this->leafs as $leaf) {
            $title = $leaf->getTitle();
            $level = $this->level + 1;
            if (!array_key_exists($level, $tree)) {
                $tree[$level] = [];
            }
            if (!in_array($title, $tree[$level])) {
                $tree[$level][] = $title;
            }
            $leaf->combine($tree);
        }
    }

    public function addUsed(array &$allUsed): void
    {
        $allUsed[$this->worker] = 1;
        foreach ($this->leafs as $leaf) {
            $leaf->addUsed($allUsed);
        }
    }

    public function getTitle(): string
    {
        return Tree::createTitle($this->worker);
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

    public function getWeight(): int
    {
        if($this->weight < 0){
            $this->weight = Operation::calculateWeight($this->operations);
        }
        return $this->weight;
    }

    public function getOperations(): array
    {
        return $this->operations;
    }

    public function getWorker(): string
    {
        return $this->worker;
    }

    private function createIndent(int $depth): string
    {
        return str_repeat(' ', ($depth * self::INDENT));
    }
}