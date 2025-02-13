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

/**
 * This is the expected SQL, where the result is sorted after matchrate,
 * and the segment to compare has a matchRate of 20 and id 923695
    F > X_F || F = X_F && ID < X_ID
 */
class editor_Models_Segment_EditablesFinder
{
    protected ?editor_Models_Segment $segment = null;

    /**
     * filter instances used for inner and outer SQL
     */
    protected ZfExtended_Models_Filter $filterInner;

    protected ZfExtended_Models_Filter $filterOuter;

    /**
     * sort parameters which are added as sort and filter conditions
     */
    protected array $sortParameter;

    /**
     * sort parameters which are added as sort and filter conditions
     */
    protected array $fieldsToSelect;

    public function __construct(editor_Models_Segment $segment)
    {
        $this->segment = $segment;

        $this->filterInner = clone $this->segment->getFilter();
        $this->filterInner->setDefaultTable((string) $this->segment->db);

        $this->filterOuter = clone $this->segment->getFilter();
        $this->filterOuter->setDefaultTable('list');

        // remove id field, since this is added internally
        $this->sortParameter = $this->segment->getFilter()->getSort();
        $this->fieldsToSelect = [];
        foreach ($this->sortParameter as $id => $sort) {
            $this->fieldsToSelect[] = $this->getSortProperty($sort);
            if ($sort->property === 'id') {
                unset($this->sortParameter[$id]);
            }
        }
    }

    /**
     * calculates the next/prev editable segment and return the segment position
     * as integer and null if there is no next/prev segment
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Db_Table_Exception
     */
    public function find(bool $next, string $workflowStep = ''): ?int
    {
        $outerSql = $this->getOuterSql();
        //for the inner sort we have to swap the direction for the prev filter
        if (! $next) {
            $this->filterInner->swapSortDirection();
        }
        $this->prepareInnerEditableFilter();

        $findInWorkflow = $workflowStep !== '';
        if ($findInWorkflow && $this->filterInner->hasFilter('workflowStep')) {
            $this->filterInner->deleteFilter('workflowStep');
        }

        $innerSql = $this->getInnerSql();

        if ($findInWorkflow) {
            $innerSql->where('workflowStep != ? or workflowStep is null', $workflowStep);
        }

        foreach ($this->sortParameter as $sort) {
            $isAsc = strtolower($sort->direction) === 'asc';
            $prop = $this->getSortProperty($sort);

            //if we ever will have multiple sort parameters, this should work out of the box because of the loop
            $this->addSortInner($innerSql, $prop, $next, $isAsc);
            $this->addSortOuter($outerSql, $prop, $isAsc);
        }

        $outerSql->from([
            'pos' => $innerSql,
        ], null);
        $this->filterOuter->applyToSelect($outerSql);

        $this->debug($outerSql);

        $stmt = $this->segment->db->getAdapter()->query($outerSql);
        $res = $stmt->fetch();
        if (empty($res)) {
            return null;
        }

        return (int) $res['cnt'];
    }

    /**
     * gets the segment position (grid index) to the given segmentId and the configured filters
     * returns null if the segment is not in the filtered list
     * @throws Zend_Db_Table_Exception
     * @throws Zend_Db_Statement_Exception
     */
    public function getIndex($segmentId): ?int
    {
        $outerSql = $this->getOuterSql();

        foreach ($this->sortParameter as $sort) {
            $isAsc = strtolower($sort->direction) === 'asc';
            $prop = $this->getSortProperty($sort);

            //if we ever will have multiple sort parameters, this should work out of the box because of the loop
            $this->addSortOuter($outerSql, $prop, $isAsc);
        }

        $db = $this->segment->db;
        $innerSql = $db->select()
            ->from($db, $this->fieldsToSelect)
            ->where($this->segment->getTableName() . '.id = ?', $segmentId);

        $this->segment->addWatchlistJoin($innerSql);
        $this->watchList($this->filterInner, $this->segment->getTableName());
        $this->filterInner->applyToSelect($innerSql);

        $outerSql->from([
            'pos' => $innerSql,
        ], null);
        $this->filterOuter->applyToSelect($outerSql);

        $this->debug($outerSql);

        $stmt = $db->getAdapter()->query($outerSql);
        $res = $stmt->fetch();

        //the above SQL returns null if the desired segment is on first position (produced by the IF in the outer SQL)
        // it returns also null if the requested segmentId is not in the filtered set of segments.
        //to find out which of the both cases happened, we have to load the innerSql solely:
        $foundInFilter = true; //by default we assume that we found something
        if ($res['cnt'] === null) {
            $stmt = $db->getAdapter()->query($innerSql);
            //if there was a result, we have to return 0 which is done by the (int) cast at the end
            $foundInFilter = $stmt->fetch();
        }

        //if we got not result at all, or the desired segment was not in the filtered list, we return null
        if (empty($res) || empty($foundInFilter)) {
            return null;
        }

        return (int) $res['cnt'];
    }

    /**
     * Adds the watchList defitions to the needed filters
     */
    protected function watchList(ZfExtended_Models_Filter $filter, string $tablename): void
    {
        $filter->setDefaultTable($tablename);
        $filter->addTableForField('isWatched', 'sua');
    }

    /**
     * adds the where statement to the inner SELECT, the SQL differs for the following cases:
        DESC PREV    sortField > currentSortValue || sortField = currentSortValue && idField < currentIdValue
     */
    protected function addSortInner(Zend_Db_Select $sql, string $prop, bool $next, bool $isAsc): void
    {
        $value = $this->segment->get($prop);

        $comparator = ($isAsc xor $next) ? '<' : '>';
        //id comparator depends only on prev/next, since order for id is always ASC!
        $f = $this->segment->db . '.`' . $prop . '` ';
        $sql->where('(' . $f . $comparator . ' ?', $value);
        $sql->orWhere('(' . $f . '= ?', $value);
        $sql->where($this->segment->db . '.id' . $comparator . ' ? ))', $this->segment->getId());
    }

    /**
     * adds the where statement to the outer select, the SQL differs for the following cases:
                DESC NEXT/PREV  sortField > innerSortValue || sortField = innerSortValue && idField > innerIdValue
     */
    protected function addSortOuter(Zend_Db_Select $sql, string $prop, bool $isAsc): void
    {
        //id comparator depends only on prev/next, since order for id is always ASC!
        $comparator = $isAsc ? '<' : '>';
        $where = 'list.`%1$s` %2$s pos.`%1$s`';
        $sql->where('(' . sprintf($where, $prop, $comparator));
        $sql->orWhere('(' . sprintf($where, $prop, ' = '));
        $sql->where(sprintf($where, 'id', $comparator) . '))');
    }

    /**
     * prepares the outer SQL and returns it
     */
    protected function getOuterSql(): Zend_Db_Table_Select
    {
        $outerSql = $this->segment->db->select()
            ->from([
                'list' => $this->segment->db,
            ], new Zend_Db_Expr('if(count(pos.id), count(list.id), null) AS cnt'));
        $this->watchList($this->filterOuter, 'list');

        return $this->segment->addWatchlistJoin($outerSql, 'list');
    }

    /**
     * prepares the inner SQL and returns it
     * @throws Zend_Db_Table_Exception
     */
    protected function getInnerSql(): Zend_Db_Select
    {
        $db = $this->segment->db;
        $tableName = $db->info($db::NAME);
        $innerSql = $db->select()
            ->from($db, $this->fieldsToSelect)
            ->limit(1);
        $innerSql = $this->segment->addWatchlistJoin($innerSql);
        $this->watchList($this->filterInner, $tableName);

        return $this->filterInner->applyToSelect($innerSql);
    }

    /**
     * prepares the editable filter condition for the inner filter
     */
    protected function prepareInnerEditableFilter(): void
    {
        $editableFilter = null;
        if ($this->filterInner->hasFilter('editable', $editableFilter)) {
            $editableFilter->value = 1;
            $editableFilter->type = 'boolean';
        } else {
            $this->filterInner->addFilter((object) [
                'field' => 'editable',
                'value' => 1,
                'type' => 'boolean',
            ]);
        }
    }

    /**
     * prepares and returns the sort property to be used
     */
    protected function getSortProperty(stdClass $sort): string
    {
        //mapSort adds also a table, this is not needed here!
        $prop = $this->segment->getFilter()->mapSort($sort->property);
        $prop = explode('.', $prop);

        return end($prop);
    }

    protected function debug($outerSql): void
    {
        return;
        //debug sql:
        file_put_contents('/tmp/foo.sql', $outerSql);
        //exec('sqlformat --reindent --keywords upper --identifiers lower /tmp/foo.sql', $out);
        exec('sqlformat --reindent --keywords upper /tmp/foo.sql', $out);
        error_log("\n" . join("\n", $out));
    }
}
