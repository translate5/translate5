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
/**
 * Class editor_Models_Terms_Term
 * Term Instance
 *
 * @method integer getId() getId()
 * @method void setId() setId(integer $id)
 * @method integer getLanguageId() getLanguageId()
 * @method integer setLanguageId() setLanguageId(integer $languageId)
 * @method string getLanguage() getLanguage()
 * @method string setLanguage() setLanguage(string $language)
 * @method string getTermTbxId() getTermTbxId()
 * @method string setTermTbxId() setTermTbxId(string $termTbxId)
 * @method string getTerm() getTerm()
 * @method string setTerm() setTerm(string $term)
 * @method integer getCollectionId() getCollectionId()
 * @method integer setCollectionId() setCollectionId(integer $collectionId)
 * @method integer getTermEntryId() getTermEntryId()
 * @method integer setTermEntryId() setTermEntryId(integer $termEntryId)
 * @method string getTermEntryTbxId() getTermEntryTbxId()
 * @method string setTermEntryTbxId() setTermEntryTbxId(string $termEntryTbxId)
 * @method string getTermEntryGuid() getTermEntryGuid()
 * @method string setTermEntryGuid() setTermEntryGuid(string $entryGuid)
 * @method string getStatus() getStatus()
 * @method string setStatus() setStatus(string $Status)
 * @method string getProcessStatus() getProcessStatus()
 * @method string setProcessStatus() setProcessStatus(string $processStatus)
 * @method string getDefinition() getDefinition()
 * @method string setDefinition() setDefinition(string $definition)
 * @method string getLangSetGuid() getLangSetGuid()
 * @method string setLangSetGuid() setLangSetGuid(string $langSetGuid)
 * @method string getGuid() getGuid()
 * @method string setGuid() setGuid(string $guid)
 * @method string getUserGuid() getUserGuid()
 * @method string setUserGuid() setUserGuid(string $userGuid)
 * @method string getUserName() getUserName()
 * @method string setUserName() setUserName(string $userName)
 * @method string getCreated() getCreated()
 * @method void setCreated() setCreated(string $created)
 * @method string getUpdated() getUpdated()
 * @method void setUpdated() setUpdated(string $updated)
 */
class editor_Models_Terminology_Models_TermModel extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_Terminology_Term';
    protected $validatorInstanceClass = 'editor_Models_Validator_Term_Term';

    const PROCESS_STATUS_UNPROCESSED = 'unprocessed';
    const PROCESS_STATUS_PROV_PROCESSED = 'provisionallyProcessed';
    const PROCESS_STATUS_FINALIZED = 'finalized';

    const STAT_PREFERRED = 'preferredTerm';
    const STAT_ADMITTED = 'admittedTerm';
    const STAT_LEGAL = 'legalTerm';
    const STAT_REGULATED = 'regulatedTerm';
    const STAT_STANDARDIZED = 'standardizedTerm';
    const STAT_DEPRECATED = 'deprecatedTerm';
    const STAT_SUPERSEDED = 'supersededTerm';
    const STAT_NOT_FOUND = 'STAT_NOT_FOUND'; //Dieser Status ist nicht im Konzept definiert, sondern wird nur intern verwendet!

    const TRANSSTAT_FOUND = 'transFound';
    const TRANSSTAT_NOT_FOUND = 'transNotFound';
    const TRANSSTAT_NOT_DEFINED ='transNotDefined';

    const CSS_TERM_IDENTIFIER = 'term';

    /**
     * The above constants are needed in the application as list, since reflection usage is expensive we cache them here:
     * @var array
     */
    protected static array $statusCache = [];
    protected static array $termEntryTbxIdCache = [];
    protected editor_Models_Segment_TermTag $tagHelper;

    protected array $statOrder = [
        self::STAT_PREFERRED => 1,
        self::STAT_ADMITTED => 2,
        self::STAT_LEGAL => 2,
        self::STAT_REGULATED => 2,
        self::STAT_STANDARDIZED => 2,
        self::STAT_DEPRECATED => 3,
        self::STAT_SUPERSEDED => 3,
        self::STAT_NOT_FOUND => 99,
    ];
    /**
     * editor_Models_Terms_Term constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->tagHelper = ZfExtended_Factory::get('editor_Models_Segment_TermTag');
    }

    /**
     * creates a new, unsaved term history entity
     * @return editor_Models_Term_History
     */
    public function getNewHistoryEntity(): editor_Models_Term_History
    {
        $history = ZfExtended_Factory::get('editor_Models_Term_History');
        /* @var $history editor_Models_Term_History */
        $history->setTermId($this->getId());
        $history->setHistoryCreated(NOW_ISO);

        $fields = $history->getFieldsToUpdate();
        foreach ($fields as $field) {
            $history->__call('set' . ucfirst($field), array($this->get($field)));
        }

        return $history;
    }

    /**
     * returns a map CONSTNAME => value of all term status
     * @return array
     */
    static public function getAllStatus(): array
    {
        self::initConstStatus();

        return self::$statusCache['status'];
    }
    /**
     * returns an array with groupId and term to a given mid
     * in old table was:
     * termEntryTbxId = groupId
     * termId = mid
     *
     * @param string $termId
     * @param array $collectionIds
     * @return array|Zend_Db_Table_Row_Abstract
     */
    public function getTermAndGroupIdToMid(string $termId, array $collectionIds): ?array
    {
        if (!empty(self::$termEntryTbxIdCache[$termId])) {
            return self::$termEntryTbxIdCache[$termId];
        }

        $select = $this->db->select()
            ->from($this->db, array('termEntryTbxId', 'term'))
            ->where('collectionId IN(?)', $collectionIds)
            ->where('termTbxId = ?', $termId);
        $res = $this->db->fetchRow($select);
        if (empty($res)) {
            return $res;
        }
        self::$termEntryTbxIdCache[$termId] = $res;

        return $res->toArray();
    }

    /**
     * $fullResult[$term['termId'].'-'.$term['termEntryTbxId'].'-'.$term['collectionId']]
     * $fullResult['termId-termEntryTbxId-collectionId'] = TERM
     *
     * $simpleResult[$term['term']]
     * $simpleResult['term'] = termId
     * @param int $collectionId
     * @return array[]
     */
    public function getAllTermsByCollectionId(int $collectionId): array
    {
        $fullResult = [];

        $query = "SELECT * FROM terms_term WHERE collectionId = :collectionId";
        $queryResults = $this->db->getAdapter()->query($query, ['collectionId' => $collectionId]);

        foreach ($queryResults as $key => $term) {
            $fullResult[$term['termEntryId'].'-'.$term['language'].'-'.$term['termId']] = $term;
        }

        return $fullResult;
    }

    /***
     * check if the term with the same termEntry,collection but different termId exist
     *
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getRestTermsOfTermEntry($termEntryTbxId, $termId, $collectionId)
    {
        $s = $this->db->select()
            ->where('termEntryTbxId = ?', $termEntryTbxId)
            ->where('termTbxId != ?', $termId)
            ->where('collectionId = ?',$collectionId);

        return $this->db->fetchAll($s);
    }

    public function createImportTbx(string $sqlParam, string $sqlFields, array $sqlValue)
    {
        $this->init();
        $insertTerms = rtrim($sqlParam, ',');

        $query = "INSERT INTO terms_term ($sqlFields) VALUES $insertTerms";

        return $this->db->getAdapter()->query($query, $sqlValue);
    }
    /**
     * @param array $terms
     * @return bool
     */
    public function updateImportTbx(array $terms): bool
    {
        foreach ($terms as $term) {
            $this->db->update($term, ['id=?'=> $term['id']]);
        }

        return true;
    }

    /***
     * Load terms in given collection and languages. The returned data will be sorted by termEntryTbxId,language and id
     * @param array $collectionIds
     * @param array $langs
     * @return NULL|Zend_Db_Table_Rowset_Abstract
     */
    public function loadSortedByCollectionAndLanguages(array $collectionIds, $langs = []): ?Zend_Db_Table_Rowset_Abstract
    {
        $s = $this->db->select()
            ->where('collectionId IN(?)', $collectionIds);

        if (!empty($langs)) {
            $s->where('languageId in (?)', $langs);
        }

        $s->order('termEntryTbxId ASC')
            ->order('languageId ASC')
            ->order('id ASC');
        $data = $this->db->fetchAll($s);

        if ($data->count() === 0) {
            return null;
        }

        return $data;
    }

    public function searchTermByParams(array $params = [], &$total = null) {
        /*if (!$params)
        $params = [
            'query' => 'bi*',
            'language' => '5,251,252,367,368,369,370,371,372,373,374,375,376,377',
            'collectionIds' => '8,10,11,12',
            'processStatus' => 'provisionallyProcessed,rejected,finalized,unprocessed',
            'noTermDefinedFor' => '4',
            'limit' => '25,0',
            'attrs' => [
                6 => 'asd',   // 6 is the attribute's dataTypeId
                7 => 'qwe'
            ]
        ];*/

        // Get the comma-separated list of termEntryIds matching attr-filters
        foreach ($params['attrs'] as $aDataTypeId => $aValue) {

            // If wildcards are used, convert them to the mysql syntax
            $expr = str_replace(['*', '?'], ['%', '_'], $aValue);

            // Prepare query param bindings
            $bind = [':dataTypeId' => $aDataTypeId];

            // Build WHERE clause
            $attrWHERE = ['`dataTypeId` = :dataTypeId'];

            // If filter value is given
            if ($aValue) {

                // Append to WHERE clause
                $attrWHERE []= '`value`' . ($expr == $aValue ? ' = ' : ' LIKE ') . ':value';

                // Add bindings
                $bind += [':value' => $expr];
            }

            // Mind previous query results to apply intersection
            if ($termEntryIds) $attrWHERE []= '`termEntryId` IN (' . $termEntryIds . ')';

            // Get termEntryIds of matched attributes
            $termEntryIds = implode(',', db()->query('
                SELECT DISTINCT `termEntryId` 
                FROM `terms_attributes` 
                WHERE ' . implode(' AND ', $attrWHERE),
                $bind
            )->fetchAll(PDO::FETCH_COLUMN));

            // If nothing found
            if (!$termEntryIds) {

                // Setup &$total variable by reference, as 0
                $total = 0;

                // Return empty data
                return [];
            }
        }

        // If wildcards are used, convert them to the mysql syntax
        $keyword = str_replace(['*', '?'], ['%', '_'], $params['query']);

        // If we're not going to count $total - it means we're in autocomplete mode
        if ($total === false) $keyword .= '%';

        // Flag, indicating whether or not current user is allowed to propose terms
        $isProposer = ZfExtended_Factory::get('ZfExtended_Models_User')->hasRole('termProposer');

        // If current user has no 'termProposer' role - remove 'unprocessed'
        // from the values list of 'processStatus' filter, so that proposals
        // will be excluded from search results. Note that $params['processStatus']
        // is still kept here as comma-separated list, as it's the format
        // this param initially arrived in as an item within $params argument
        if (!$isProposer)
            $params['processStatus']
                = implode(',', array_diff(ar($params['processStatus']), [self::PROCESS_STATUS_UNPROCESSED]));

        // Shared WHERE clause, that will be used for querying both terms and proposals tables
        $where = [
            '`t`.`languageId` IN (' . $params['language'] . ')',
            '`t`.`collectionId` IN (' . $params['collectionIds'] . ')',
            '`t`.`processStatus` IN ("' . str_replace(',', '","', $params['processStatus']) . '")',
        ];

        // Mind attr-filters in WHERE clause
        if ($termEntryIds) array_unshift($where, '`t`.`termEntryId` IN (' . $termEntryIds . ')');

        // If 'noTermDefinedFor' param is given
        if ($_ = (int) $params['noTermDefinedFor']) {

            // Respect it in FROM clause
            $noTermDefinedFor = sprintf(' LEFT JOIN `terms_term` AS `t2` ON (
                `t`.`termEntryId` = `t2`.`termEntryId` AND `t2`.`languageId` = "%s"
            )', $_);

            // Respect it in WHERE clause
            $where []= 'ISNULL(`t2`.`term`)';
        }

        // Data columns, that would be fetched by search SQL query
        $termQueryCol = '
          `t`.`term` AS `label`, 
          `t`.`id` AS `value`, 
          `t`.`id`, 
          `t`.`term` AS `desc`, 
          `t`.`processStatus`, 
          `t`.`status`, 
          `t`.`definition`, 
          `t`.`termEntryTbxId`, 
          `t`.`collectionId`, 
          `t`.`termEntryId`, 
          `t`.`languageId` 
        ';

        // Term query template
        $termQueryTpl = '
            SELECT %s 
            FROM `terms_term` `t` %s
            WHERE LOWER(`t`.`term`) LIKE LOWER(?) COLLATE utf8mb4_bin AND ' . implode(' AND ', $where) . '
            ORDER BY `t`.`term` ASC
        ';

        // Assume limit arg can be comma-separated string containing '<LIMIT>,<OFFSET>'
        list($limit, $offset) = explode(',', $params['limit']);

        // If we should only search within terms-table (e.g. proposals-table won't be involved)
        if (!$isProposer || !in_array(self::PROCESS_STATUS_UNPROCESSED, explode(',', $params['processStatus']))) {

            // If we have to calculate total
            if ($total === true) {

                // Render query for getting total count of results
                $termQuery = sprintf($termQueryTpl, 'COUNT(*)', $noTermDefinedFor);

                // Setup &$total variable by reference
                $total = (int) db()->query($termQuery, $keyword)->fetchColumn();
            }

            // Render query for getting regular results
            $termQuery = sprintf($termQueryTpl, $termQueryCol, $noTermDefinedFor)
                . 'LIMIT ' . (int) $offset . ',' . (int) $limit;

            // Return results
            return db()->query($termQuery, $keyword)->fetchAll();
        }

        // Data columns, that would be fetched by search SQL query
        $proposalQueryCol = '
          `p`.`term` AS `label`, 
          `p`.`termId` AS `value`, 
          `p`.`termId` AS `id`, 
          `p`.`term` AS `desc`, 
          `t`.`processStatus`, 
          `t`.`status`, 
          `t`.`definition`, 
          `t`.`termEntryTbxId`, 
          `t`.`collectionId`, 
          `t`.`termEntryId`, 
          `t`.`languageId` 
        ';

        // Term query template
        $proposalQueryTpl = '
            SELECT %s 
            FROM 
              `terms_proposal` `p`
              INNER JOIN `terms_term` AS `t` ON `t`.`id` = `p`.`termId` %s
            WHERE LOWER(`p`.`term`) LIKE LOWER(?) COLLATE utf8mb4_bin AND ' . implode(' AND ', $where) . '
            ORDER BY `t`.`term` ASC
        ';

        // If we have to calculate total
        if ($total === true) {

            // Render query for getting total count of results in terms-table
            $termQuery = sprintf($termQueryTpl, 'COUNT(*)', $noTermDefinedFor);

            // Render query for getting total count of results in proposals-table
            $proposalQuery = sprintf($proposalQueryTpl, 'COUNT(*)', $noTermDefinedFor);

            // Render query for getting total results
            $totalQuery = 'SELECT (' . $termQuery . ') + (' . $proposalQuery . ') AS `total`';

            // Setup &$total variable by reference
            $total = (int) db()->query($totalQuery, [$keyword, $keyword])->fetchColumn();
        }

        // Render query for getting UNION-ed results from both terms and proposals tables
        $unionQuery = '('
            . sprintf($termQueryTpl, $termQueryCol, $noTermDefinedFor)
            . ') UNION ('
            . sprintf($proposalQueryTpl, $proposalQueryCol, $noTermDefinedFor)
            . ')'
            . 'LIMIT ' . (int) $offset . ',' . (int) $limit;

        // Return results
        return db()->query($unionQuery, [$keyword, $keyword])->fetchAll();
    }

    /**
     * Search terms in the term collection with the given search string and languages.
     * @param string $queryString
     * @param string $languages
     * @param array $collectionIds
     * @param mixed $limit
     * @param array $processStats
     * @param bool $total
     * @param int $noTermDefinedFor
     * @return array
     */
    public function searchTermByLanguage(string $queryString, string $languages, array $collectionIds, $limit = null, array $processStats, &$total = null, $noTermDefinedFor = null, $query = null): array
    {
        $termObject = ZfExtended_Factory::get('editor_Models_Terminology_TbxObjects_Term');

        //if wildcards are used, adopt them to the mysql needs
        $queryString = str_replace("*","%",$query ?: $queryString);
        $queryString = str_replace("?","_",$queryString);

        //when limit is provided -> autocomplete search
        if ($limit) {
            $queryString = $queryString.'%';
        }

        $isProposalAllowed = $this->isProposableAllowed();

        //remove the unprocessed status if the user is not allowed for proposals
        if (!$isProposalAllowed) {
            $processStats = array_diff($processStats,[self::PROCESS_STATUS_UNPROCESSED]);
        }

        $tableTerm = $this->db->info($this->db::NAME);
        $tableProposal = (new editor_Models_Db_Term_Proposal())->info($this->db::NAME);
        $s = $this->db->select()
            ->setIntegrityCheck(false)
            ->from($tableTerm, ['term as label', 'id as value', 'id', 'term as desc', 'processStatus', 'status', 'definition', 'termEntryTbxId', 'collectionId', 'termEntryId', 'languageId'])
            ->where('lower(`'.$tableTerm.'`.term) like lower(?) COLLATE utf8mb4_bin',$queryString)
            ->where('`'.$tableTerm.'`.languageId IN(?)', explode(',', $languages))
            ->where('`'.$tableTerm.'`.collectionId IN(?)',$collectionIds)
            ->where('`'.$tableTerm.'`.processStatus IN(?)',$processStats);

        if ($noTermDefinedFor) {
            $s->joinLeft(['t2' => $tableTerm], '`'.$tableTerm.'`.`termEntryId` = `t2`.`termEntryId` AND `t2`.`languageId` = "' . explode(',', $noTermDefinedFor)[0] . '"', '');
            $s->where('ISNULL(`t2`.`term`)');
        }
        $s->order($tableTerm.'.term asc');

        // Assume limit arg can be comma-separated string containing '<LIMIT>,<OFFSET>'
        if ($limit) list($limit, $offset) = explode(',', $limit);

        //
        if (!$isProposalAllowed || !in_array(self::PROCESS_STATUS_UNPROCESSED, $processStats)) {

            // Set LIMIT clause
            if ($limit) $s->limit($limit, $offset ?: 0);

            // Return results
            return $this->db->fetchAll($s)->toArray();
        }

        //if proposal is allowed, search also in the proposal table for results
        $tableProposal = (new editor_Models_Db_Term_Proposal())->info($this->db::NAME);
        $sp = $this->db->select()
            ->setIntegrityCheck(false)
            ->from($tableProposal, ['term as label', 'termId as value', 'termId as id', 'term as desc'])
            ->joinInner($tableTerm, '`'.$tableTerm.'`.`id` = `'.$tableProposal.'`.`termId`', ['processStatus', 'status', 'definition', 'termEntryTbxId', 'collectionId', 'termEntryId', 'languageId'])
            ->where('lower(`'.$tableProposal.'`.term) like lower(?) COLLATE utf8mb4_bin', $queryString)
            ->where('`'.$tableTerm.'`.languageId IN(?)', explode(',', $languages))
            ->where('`'.$tableTerm.'`.collectionId IN(?)', $collectionIds)
            ->order($tableTerm.'.term asc');

        if ($noTermDefinedFor) {
            $sp->joinLeft(['t2' => $tableTerm], '`'.$tableTerm.'`.`termEntryId` = `t2`.`termEntryId` AND `t2`.`languageId` = "' . explode(',', $noTermDefinedFor)[0] . '"', '');
            $sp->where('ISNULL(`t2`.`term`)');
        }

        // Build LIMIT clause
        $limit = rif($limit, 'LIMIT ' . rif($offset, '$1,') . '$1', '');
        //d($sp->assemble());
        // Append LIMIT clause to the UNION-ed query
        $sql = '(' . $s->assemble() . ') UNION (' . $sp->assemble() . ') ' . $limit;

        // If $total arg is given
        if (func_num_args() >= 6 && !$query) {

            // Replace columns with 'COUNT(*)' for both *_Select instances
            $s->reset(Zend_Db_Select::COLUMNS)->columns(['COUNT(*)']);
            $sp->reset(Zend_Db_Select::COLUMNS)->columns(['COUNT(*)']);

            // Get total
            $total = $this->db->getAdapter()->query(
              'SELECT (' . $s->assemble() . ') + (' . $sp->assemble() . ') AS `total`'
            )->fetchColumn();
        }

        // Fetch and return results
        return $this->db->getAdapter()->query($sql)->fetchAll();
    }

    /***
     * It is proposal when the user is allowed for term proposal operation
     * @return boolean
     */
    public function isProposableAllowed(): bool
    {
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        return $user->hasRole('termProposer');
    }

    /**
     * returns a map CONSTNAME => value of all term process-status
     * @return array
     */
    static public function getAllProcessStatus(): array
    {
        self::initConstStatus();

        return self::$statusCache['processStatus'];
    }

    /**
     * creates a internal list of the status constants
     */
    static protected function initConstStatus()
    {
        if (!empty(self::$statusCache)) {
            return;
        }

        self::$statusCache = [
            'status' => [],
            'translation' => [],
            'processStatus' => []
        ];

        $reflection = new ReflectionClass(__CLASS__);
        $constants = $reflection->getConstants();
        foreach($constants as $key => $val) {
            if (strpos($key, 'STAT_') === 0) {
                self::$statusCache['status'][$key] = $val;
            }
            if (strpos($key, 'TRANSSTAT_') === 0) {
                self::$statusCache['translation'][$key] = $val;
            }
            if (strpos($key, 'PROCESS_STATUS_') === 0) {
                self::$statusCache['processStatus'][$key] = $val;
            }
        }
    }

    /***
     * Get loaded data as object with term attributes included
     * @return stdClass
     */
    public function getDataObjectWithAttributes(): stdClass
    {
        $result = $this->getDataObject();
        //load all attributes for the term
        $rows = $this->groupTermsAndAttributes($this->findTermAndAttributes($result->id));
        $result->attributes = [];
        if (!empty($rows) && !empty($rows[0]['attributes'])) {
            $result->attributes = $rows[0]['attributes'];
        }

        return $result;
    }

    public function groupTerms(array $data): ?array
    {

    }

    /***
     * Group term and term attributes data by term. Each row will represent one term and its attributes in attributes array.
     * The term attributes itself will be grouped in parent-child structure
     * @param array $data
     * @return array
     */
    public function groupTermsAndAttributes(array $data): ?array
    {
        if (empty($data)) {
            return $data;
        }
        $map = [];
        $termColumns = [
            'definition',
            'groupId',
            'label',
            'value',
            'desc',
            'termStatus',
            'processStatus',
            'termTbxId',
            'termEntryId',
            'collectionId',
            'languageId',
            'langSetGuid',
            'term'
        ];
        //available term proposal columns
        $termProposalColumns = [
            'proposalTerm',
            'proposalId',
            'proposalCreated',
            'proposalUserName'
        ];
        //maping between database name and term proposal table real name
        $termProposalColumnsNameMap = [
            'proposalTerm' => 'term',
            'proposalId' => 'id',
            'proposalCreated' => 'created',
            'proposalUserName' => 'userName'
        ];

        //available attribute proposal columns
        $attributeProposalColumns = [
            'proposalAttributeValue',
            'proposalAttributelId'
        ];

        //maping between database name and attribute proposal table real name
        $attributeProposalColumnsNameMap = [
            'proposalAttributeValue' => 'value',
            'proposalAttributelId' => 'id'
        ];

        $attribute = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeModel');
        /* @var $attribute editor_Models_Terminology_Models_AttributeModel */

        //Group term-termattribute data by term. For each grouped attributes field will be created
        $oldKey = '';
        $groupOldKey = false;
        $termProposalData = [];

        $termModel = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        /* @var $termModel editor_Models_Terminology_Models_TermModel */
        $isTermProposalAllowed = $termModel->isProposableAllowed();

        $attributeModel = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeModel');
        /* @var $attributeModel editor_Models_Terminology_Models_AttributeModel */
        $isAttributeProposalAllowed = $attributeModel->isProposableAllowed();
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();

        //map the term id to array index (this is used because the jquery json decode changes the array sorting based on the termId)
        $keyMap = [];
        $indexKeyMap = function($termId) use (&$keyMap){
            if (!isset($keyMap[$termId])) {
                $keyMap[$termId] = count($keyMap);
                return $keyMap[$termId];
            }
            return $keyMap[$termId];
        };

        foreach ($data as $tmp) {
            $termKey = $indexKeyMap($tmp['termId']);

            if (!isset($map[$termKey])) {
                $termKey = $indexKeyMap($tmp['termId']);
                $map[$termKey] = [];
                $map[$termKey]['attributes'] = [];

                if (isset($oldKey) && isset($map[$oldKey])) {
//                    $map[$oldKey]['attributes'] = $attribute->createChildTree($map[$oldKey]['attributes']);
                    $groupOldKey = true;

                    $map[$oldKey]['proposable'] = $isTermProposalAllowed;
                    //collect the term proposal data if the user is allowed to
                    if ($isTermProposalAllowed) {
                        $map[$oldKey]['proposal'] = !empty($termProposalData['term']) ? $termProposalData : null;
                        $map[$oldKey]['attributes'] = $attribute->updateModificationGroupDate($map[$oldKey]['attributes'],isset($map[$oldKey]['proposal'])?$map[$oldKey]['proposal']:[]);
                        $termProposalData = [];
                    }
                }
            }

            //split the term fields and term attributes
            $atr = [];
            $attProposal = [];
            foreach ($tmp as $key => $value) {
                //check if it is term specific data
                if (in_array($key, $termColumns)) {
                    $map[$termKey][$key] = $value;
                    continue;
                }
                //is term attribute proposal specific data
                if (in_array($key, $attributeProposalColumns)) {
                    $attProposal[$attributeProposalColumnsNameMap[$key]] = $value;
                    continue;
                }
                //is term proposal specific columnt
                if (in_array($key, $termProposalColumns)) {
                    $termProposalData[$termProposalColumnsNameMap[$key]] = $value;
                    continue;
                }

                if ($key == 'headerText') {
                    $value = $translate->_($value);
                }

                //it is attribute column
                $atr[$key] = $value;

            }

            //is attribute proposable (is user attribute proposal allowed and the attribute is proposal whitelisted)
            $atr['proposable'] = $isAttributeProposalAllowed && $attribute->isProposable($atr['name'], $atr['attrType']);
            if ($isAttributeProposalAllowed) {
                $atr['proposal'] = !empty($attProposal['id']) ? $attProposal : null;
                $attProposal = [];
            }

//            $newAttributes = $attributeModel->load($tmp['attributeId'])->toArray();
//            $atr = $this->createNewAttributeTermArray($newAttributes);
//
//            if ($atr) {
                array_push($map[$termKey]['attributes'], $atr);
//            }



            $oldKey = $indexKeyMap($tmp['termId']);
            $groupOldKey = false;
        }

        //if not grouped after foreach, group the last result
        if (!$groupOldKey) {
            $map[$oldKey]['proposable'] = $isTermProposalAllowed;
//            $map[$oldKey]['attributes'] = $attribute->createChildTree($map[$oldKey]['attributes']);

            //collect the term proposal data if the user is allowed to
            if ($isTermProposalAllowed) {
                $map[$oldKey]['proposal'] = !empty($termProposalData['term']) ? $termProposalData : null;
//                $map[$oldKey]['attributes'] = $attribute->updateModificationGroupDate($map[$oldKey]['attributes'],isset($map[$oldKey]['proposal']) ? $map[$oldKey]['proposal'] : []);
            }
        }

        if (empty($map)) {
            return null;
        }

        return $map;
    }

    private function createNewAttributeTermArray(array $termArray): array
    {
        $attributeColumns = [
            'attrCreated',
            'attrDataType',
            'attrId',
            'attrLang',
            'attrProcessStatus',
            'attrTarget',
            'attrType',
            'attrUpdated',
            'attrValue',
            'attributeId',
            'attributeOriginType',
            'children',
            'headerText',
            'internalCount',
            'dataTypeId',
            'language',
            'name',
            'parentId',
            'proposable'
        ];
        $attNew = [];
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();

if($termArray['language'] !== null) {
    $attNew['attrCreated'] = $termArray['created'];
    $attNew['attrDataType'] = $termArray['dataType'];
    $attNew['attrId'] = $termArray['id'];
    $attNew['attrLang'] = $termArray['language'];
    $attNew['attrProcessStatus'] = $termArray['processStatus'];
    $attNew['attrTarget'] = $termArray['target'];
    $attNew['attrType'] = $termArray['type'];
    $attNew['attrUpdated'] = $termArray['updated'];
    $attNew['attrValue'] = $termArray['value'];
    $attNew['attributeId'] = $termArray['id'];
    $attNew['attributeOriginType'] = 'termAttribute';
    $attNew['children'] = [];
    $attNew['headerText'] = $translate->_($termArray['type']);
    $attNew['internalCount'] = '';
    $attNew['dataTypeId'] = '';
    $attNew['language'] = $termArray['language'];
    $attNew['name'] = $termArray['elementName'];
    $attNew['parentId'] = '';
    $attNew['proposable'] = true;
}

        return $attNew;
    }
    /**
     * Find term in collection by given language and term value
     * @param string $termText
     * @param int|null $languageId optional, if omitted use internal value
     * @param int|null $termCollection optional, if omitted use internal value
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function findTermInCollection(string $termText, int $languageId = null, int $termCollection = null): Zend_Db_Table_Rowset_Abstract
    {
        $s = $this->db->select()
            ->where('term = ?', $termText)
            ->where('languageId = ?', $languageId ?? $this->getLanguage())
            ->where('collectionId = ?',$termCollection ?? $this->getCollectionId());

        return $this->db->fetchAll($s);
    }

    /***
     * Find the term and the term attributes by given term id
     * @param int $termId
     * @return array
     */
    public function findTermAndAttributes(int $termId): array
    {
        $s = $this->getSearchTermSelect();
        $s->where('terms_term.termTbxId=?', $termId);
            $s->order('LEK_languages.rfc5646')
            ->order('terms_term.term');

        return $this->db->fetchAll($s)->toArray();
    }

    /***
     * Get term search select. It the user is proposal allowed, the term and attribute proposals will be joined.
     *
     * @return Zend_Db_Select
     */
    protected function getSearchTermSelect(): Zend_Db_Select
    {
        $attCols = [
            'terms_attributes.dataTypeId as dataTypeId',
            'terms_attributes.id AS attributeId',
//            'terms_attributes.parentId AS parentId',
            'terms_attributes.internalCount AS internalCount',
            'terms_attributes.elementName AS name',
            'terms_attributes.type AS attrType',
            'terms_attributes.target AS attrTarget',
            'terms_attributes.guid AS attrId',
            'terms_attributes.language AS attrLang',
            'terms_attributes.value AS attrValue',
            'terms_attributes.created AS attrCreated',
            'terms_attributes.updated AS attrUpdated',
            'terms_attributes.dataType AS attrDataType',
            'terms_attributes.processStatus AS attrProcessStatus',
            'terms_attributes.termId AS termGuid',
            'terms_attributes.langSetGuid AS langSetGuid',
            new Zend_Db_Expr('"termAttribute" as attributeOriginType') //this is needed as fixed value
        ];

        $cols = [
            'definition',
            'termEntryTbxId as groupId',
            'term as label',
            'term as term',//for consistency
            'id as value',
            'term as desc',
            'status as termStatus',
            'processStatus as processStatus',
            'id as termId',
            'termEntryId',
            'collectionId',
            'langSetGuid',
            'languageId as languageId'
        ];

        $s = $this->db->select()
            ->setIntegrityCheck(false)
            ->from($this->db, $cols)
            ->joinLeft('terms_attributes', 'terms_attributes.termId = terms_term.id ', $attCols)
            ->joinLeft('terms_attributes_datatype', 'terms_attributes_datatype.id = terms_attributes.dataTypeId',['terms_attributes_datatype.labelText as headerText'])
            ->join('LEK_languages', 'terms_term.languageId = LEK_languages.id', ['LEK_languages.rfc5646 AS language']);

        if($this->isProposableAllowed()){
            $s->joinLeft('terms_proposal', 'terms_proposal.termId = terms_term.id',[
                'terms_proposal.term as proposalTerm',
                'terms_proposal.id as proposalId',
                'terms_proposal.created as proposalCreated',
                'terms_proposal.userName as proposalUserName'
            ]);
        }

        $attribute = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeModel');
        /* @var $attribute editor_Models_Terminology_Models_AttributeModel */

        if($attribute->isProposableAllowed()){
            $s->joinLeft('terms_attributes_proposal', 'terms_attributes_proposal.attributeId = terms_attributes.id',[
                'terms_attributes_proposal.value as proposalAttributeValue',
                'terms_attributes_proposal.id as proposalAttributelId',
            ]);
        }else{
            //exclude the proposals
            $s->where('terms_term.processStatus!=?',self::PROCESS_STATUS_UNPROCESSED)
                ->where('terms_attributes.processStatus!=?',self::PROCESS_STATUS_UNPROCESSED);
        }

        return $s;
    }
    /***
     * Find term attributes in the given term entry (lek_terms groupId)
     *
     * @param string $termEntryId
     * @param array $collectionIds
     * @return array
     */
    public function searchTermAttributesInTermEntry(string $termEntryId, array $collectionIds): array
    {
        $s = $this->getSearchTermSelect();
        $s->where('terms_term.termEntryId = ?', (int)$termEntryId)
            ->where('terms_term.collectionId IN(?)', $collectionIds)
            ->order('LEK_languages.rfc5646')
            ->order('terms_term.term')
            ->order('terms_term.id');

        return $this->db->fetchAll($s)->toArray();
    }

    /***
     * Returns all terms of the given $searchTerms that don't exist in
     * any of the given collections.
     * @param array $searchTerms with objects {'text':'abc', 'id':123}
     * @param array $collectionIds
     * @param array $language
     * @return array $nonExistingTerms with objects {'text':'abc', 'id':123}
     */
    public function getNonExistingTermsInAnyCollection(array $searchTerms, array $collectionIds, array $language): array
    {
        $nonExistingTerms = [];
        if (empty($searchTerms) || empty($collectionIds) || empty($language)) {
            return $nonExistingTerms;
        }
        foreach ($searchTerms as $term) {
            $s = $this->db->select()
                ->where('term = ?', $term->text)
                ->where('collectionId IN(?)', $collectionIds)
                ->where('languageId IN (?)',$language);
            $terms = $this->db->fetchAll($s);

            if ($terms->count() === 0) {
                $nonExistingTerms[] = $term;
            }
        }
        return $nonExistingTerms;
    }
    /**
     * Returns the configured mapping of term-statuses
     * (= which statuses are allowed etc).
     * @return array
     */
    static public function getTermStatusMap(): array
    {
        $config = Zend_Registry::get('config');

        return $config->runtimeOptions->tbx->termLabelMap->toArray();
    }
    /**
     * Returns term-informations for $segmentId in $taskGuid.
     * Includes assoziated terms corresponding to the tagged terms
     *
     * @param string $taskGuid
     * @param int $segmentId
     * @return array
     */
    public function getByTaskGuidAndSegment(string $taskGuid, int $segmentId): array
    {
        if (empty($taskGuid) || empty($segmentId)) {
            return array();
        }

        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);

        if (!$task->getTerminologie()) {
            return array();
        }

        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        $segment->load($segmentId);
        $termIds = $this->getTermMidsFromTaskSegment($task, $segment);

        if (empty($termIds)) {
            return array();
        }

        $assoc = ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        /* @var $assoc editor_Models_TermCollection_TermCollection */
        $collections = $assoc->getCollectionsForTask($task->getTaskGuid());
        if (empty($collections)) {
            return array();
        }
        $result = $this->getSortedTermGroups($collections, $termIds, $task->getSourceLang(), $task->getTargetLang());

        if (empty($result)) {
            return array();
        }

        return $this->sortTerms($result);
    }
    /**
     * returns all term mids of the given segment in a multidimensional array.
     * First level contains source or target (the fieldname)
     * Second level contains a list of arrays with the found mids and div tags,
     * the div tag is needed for transfound check
     * @param editor_Models_Task $task
     * @param editor_Models_Segment $segment
     * @return array
     */
    protected function getTermMidsFromTaskSegment(editor_Models_Task $task, editor_Models_Segment $segment): array
    {
        $fieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        /* @var $fieldManager editor_Models_SegmentFieldManager */
        $fieldManager->initFields($task->getTaskGuid());

        //Currently only terminology is shown in the first fields see also TRANSLATE-461
        if ($task->getEnableSourceEditing()) {
            $sourceFieldName = $fieldManager->getEditIndex($fieldManager->getFirstSourceName());
            $sourceText = $segment->get($sourceFieldName);
        } else {
            $sourceFieldName = $fieldManager->getFirstSourceName();
            $sourceText = $segment->get($sourceFieldName);
        }

        $targetFieldName = $fieldManager->getEditIndex($fieldManager->getFirstTargetName());
        $targetText = $segment->get($targetFieldName);

        //tbxid should be sufficient as distinct identifier of term tags
        $getTermIdRegEx = '/<div[^>]+data-tbxid="([^"]*)"[^>]*>/';
        preg_match_all($getTermIdRegEx, $sourceText, $sourceMatches, PREG_SET_ORDER);
        preg_match_all($getTermIdRegEx, $targetText, $targetMatches, PREG_SET_ORDER);

        if (empty($sourceMatches) && empty($targetMatches)) {
            return [];
        }

        return ['source' => $sourceMatches, 'target' => $targetMatches];
    }
    /***
     * Export term and term attribute proposals in excel file.
     * When no path is provided, redirect the output to a client's web browser (Excel)
     *
     * @param array $rows
     * @param string|null $path: the path where the excel document will be saved
     */
    public function exportProposals(array $rows, string $path = null)
    {
        $excel = ZfExtended_Factory::get('ZfExtended_Models_Entity_ExcelExport');
        /* @var $excel ZfExtended_Models_Entity_ExcelExport */

        // set property for export-filename
        $excel->setProperty('filename', 'Term and term attributes proposals');

        $t = ZfExtended_Zendoverwrites_Translate::getInstance();
        /* @var $t ZfExtended_Zendoverwrites_Translate */

        // sample label-translations
        $excel->setLabel('termEntryId', $t->_('Eintrag'));
        $excel->setLabel('definition', $t->_('Definition'));
        $excel->setLabel('language', $t->_('Sprache'));
        $excel->setLabel('termTbxId', $t->_('Term-Id'));
        $excel->setLabel('term', $t->_('Term'));
        $excel->setLabel('termProposal', $t->_('Änderung zu bestehendem Term'));
        $excel->setLabel('processStatus', $t->_('Prozess-Status'));
        $excel->setLabel('attributeName', $t->_('Attributs-Schlüssel'));
        $excel->setLabel('attribute', $t->_('Attributs-Wert'));
        $excel->setLabel('attributeProposal', $t->_('Änderung zu bestehendem Attributs-Wert'));
        $excel->setLabel('lastEditor', $t->_('Letzter Bearbeiter'));
        $excel->setLabel('lastEditedDate', $t->_('Bearbeitungsdatum'));


        $autosizeCells=function($phpExcel) use ($excel){
            foreach ($phpExcel->getWorksheetIterator() as $worksheet) {
                $phpExcel->setActiveSheetIndex($phpExcel->getIndex($worksheet));
                $sheet = $phpExcel->getActiveSheet();

                //the highes column based on the current row columns
                $highestColumn='M';
                foreach(range('A',$highestColumn) as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }

                $highestColumnIndex = $excel->columnIndexFromString($highestColumn);

                // expects same number of row records for all columns
                $highestRow = $worksheet->getHighestRow();

                for ($col = 0; $col < $highestColumnIndex; $col++) {
                    // if you do not expect same number of row records for all columns
                    // get highest row index for each column
                    // $highestRow = $worksheet->getHighestRow();
                    for ($row = 1; $row <= $highestRow; $row++) {
                        $cell = $worksheet->getCellByColumnAndRow($col, $row);
                        if (strpos($cell->getValue(), '<changemycolortag>') !== false) {
                            $cell->setValue(str_replace('<changemycolortag>','', $cell->getValue()));
                            $sheet->getStyle($cell->getCoordinate())->getFill()->setFillType('solid')->getStartColor()->setRGB('f9f25c');
                        }
                    }
                }
            }
        };

        //if the path is provided, save the excel into the given path location
        if (!empty($path)) {
            $excel->loadArrayData($rows);
            $autosizeCells($excel->getSpreadsheet());
            $excel->saveToDisc($path);
            return;
        }

        //send the excel to browser download
        $excel->simpleArrayToExcel($rows,$autosizeCells);
    }
    /***
     * Update language assoc for given collections. The langages are merged from exsisting terms per collection.
     * @param array|null $collectionIds
     */
    public function updateAssocLanguages(array $collectionIds = null)
    {
        $s = $this->db->select()
            ->from(['t' =>'terms_term'], ['t.languageId', 't.collectionId'])
            ->join(['l' =>'LEK_languages'], 't.languageId = l.id', 'rfc5646');

        if (!empty($collectionIds)) {
            $s->where('t.collectionId IN(?)',$collectionIds);
        }

        $s->group('t.collectionId')->group('t.languageId')->setIntegrityCheck(false);
        $ret = $this->db->fetchAll($s)->toArray();

        $data = [];
        foreach ($ret as $lng) {
            if (!isset($data[$lng['collectionId']])) {
                $data[$lng['collectionId']] = [];
            }
            array_push($data[$lng['collectionId']], $lng);
        }

        foreach ($data as $key => $value) {
            $alreadyProcessed = array();
            //the term collection contains terms with only one language
            $isSingleCombination = count($value) == 1;
            foreach ($value as $x) {
                foreach ($value as $y) {
                    //keep track of what is already processed
                    $combination = array($x['languageId'], $y['languageId']);

                    //it is not the same number or single language combination and thay are not already processed
                    if (($x['languageId'] === $y['languageId'] && !$isSingleCombination) || in_array($combination, $alreadyProcessed)) {
                        continue;
                    }
                    //Add it to the list of what you've already processed
                    $alreadyProcessed[] = $combination;

                    //save the language combination
                    $model = ZfExtended_Factory::get('editor_Models_LanguageResources_Languages');
                    /* @var $model editor_Models_LanguageResources_Languages */

                    $model->setSourceLang($x['languageId']);
                    $model->setSourceLangCode($x['rfc5646']);

                    $model->setTargetLang($y['languageId']);
                    $model->setTargetLangCode($y['rfc5646']);

                    $model->setLanguageResourceId($key);
                    $model->save();
                }
            }
        }
    }
    /**
     * exports all terms of all termCollections associated to the task in the task's languages.
     * @param editor_Models_Task $task
     * @return string
     * @throws editor_Models_Term_TbxCreationException
     */
    public function exportForTagging(editor_Models_Task $task): string
    {
        $languageModel = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $languageModel editor_Models_Languages */

        $assoc = ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        /* @var $assoc editor_Models_TermCollection_TermCollection */
        $collectionIds = $assoc->getCollectionsForTask($task->getTaskGuid());

        if (empty($collectionIds)) {
            //No term collection assigned to task although tasks terminology flag is true.
            // This is normally not possible, since the terminology flag in the task is maintained on TC task assoc changes via API
            throw new editor_Models_Term_TbxCreationException('E1113', [
                'task' => $task
            ]);
        }

        //get source and target language fuzzies
        $langs = [];
        $langs = array_merge($langs,$languageModel->getFuzzyLanguages($task->getSourceLang()));
        $langs = array_merge($langs,$languageModel->getFuzzyLanguages($task->getTargetLang()));
        if ($task->getRelaisLang() > 0) {
            $langs = array_merge($langs,$languageModel->getFuzzyLanguages($task->getRelaisLang()));
        }
        $langs = array_unique($langs);

        $data = $this->loadSortedByCollectionAndLanguages($collectionIds, $langs);
        if (!$data) {
            //The associated collections don't contain terms in the languages of the task.
            // Should not be, should be checked already on assignment of collection to task.
            // Colud happen when all terms of a language are removed from a TermCollection via term import after associating that term collection to a task.
            throw new editor_Models_Term_TbxCreationException('E1114', [
                'task' => $task,
                'collectionIds' => $collectionIds,
                'languageIds' => $langs,
            ]);
        }

        $exporteur = ZfExtended_Factory::get('editor_Models_Export_Terminology_Tbx');
        /* @var $exporteur editor_Models_Export_Terminology_Tbx */
        $exporteur->setData($data);
        $result = $exporteur->export();
        if (empty($result)) {
            //collected terms could not be converted to XML.
            throw new editor_Models_Term_TbxCreationException('E1115', [
                'task' => $task,
                'collectionIds' => $collectionIds,
                'languageIds' => $langs,
            ]);
        }
        return $result;
    }

    /**
     * Sortiert die Terme innerhalb der Termgruppen:
     * @param array $termGroups
     * @return array
     */
    public function sortTerms(array $termGroups): array
    {
        foreach ($termGroups as $groupId => $group) {
            usort($group, [$this, 'compareTerms']);
            $termGroups[$groupId] = $group;
        }
        return $termGroups;
    }

    /**
     * Bewertet die Terme nach den folgenden Kriterien (siehe auch http://php.net/usort/)
     *  -- 1. Kriterium: Vorzugsbenennung vor erlaubter Benennung vor verbotener Benennung
     *  -- 2. Kriterium: In Quelle vorhanden
     *  -- 3. Kriterium: In Ziel vorhanden (damit ist die Original-Ãœbersetzung gemeint, nicht die editierte Variante)
     *  -- 4. Kriterium: Alphanumerische Sortierung
     *  Zusammenhang Parameter und Return Values siehe usort $cmp_function
     *
     *  @param $term1
     *  @param $term2
     *  @return int
     */
    protected function compareTerms($term1, $term2): int
    {
        // return > 0 => t1 > t2
        // return = 0 => t1 = t2
        // return < 0 => t1 < t2
        $term1 = is_array($term1) ? (object)$term1 : $term1;
        $term2 = is_array($term2) ? (object)$term2 : $term2;
        $status = $this->compareTermStatus($term1->status, $term2->status);

        if ($status !== 0) {
            return $status;
        }

        $isSource = 0;
        if (isset($term1->isSource)){
            $isSource = $this->compareTermLangUsage($term1->isSource, $term2->isSource);
        }

        if ($isSource !== 0) {
            return $isSource;
        }

        //Kriterium 4 - alphanumerische Sortierung:
        return strcmp(mb_strtolower($term1->term), mb_strtolower($term2->term));
    }
    /**
     * Vergleicht die Term Status
     * @param string $status1
     * @param string $status2
     * @return int
     */
    protected function compareTermStatus(string $status1, string $status2): int
    {
        //wenn beide stati gleich, dann wird kein weiterer Vergleich benoetigt
        if ($status1 === $status2) {
            return 0;
        }
        if (empty($this->statOrder[$status1])) {
            $status1 = self::STAT_NOT_FOUND;
        }
        if (empty($this->statOrder[$status2])) {
            $status2 = self::STAT_NOT_FOUND;
        }

        //je kleiner der statOrder, desto hÃ¶herwertiger ist der Status!
        //Da Hoeherwertig aber bedeutet, dass es in der Sortierung weiter oben erscheinen soll,
        //ist der Hoeherwertige Status im numerischen Wert kleiner!
        if ($this->statOrder[$status1] < $this->statOrder[$status2]) {
            return -1; //status1 ist hoeherwertiger, da der statOrdner kleiner ist
        }

        return 1; //status2 ist hoeherwertiger
    }

    /**
     * Vergleicht die Term auf Verwendung in Quell oder Zielspalte
     * @param string $isSource1
     * @param string $isSource2
     * @return int
     */
    protected function compareTermLangUsage(string $isSource1, string $isSource2): int
    {
        //Verwendung in Quelle ist hoeherwertiger als in Ziel (Kriterium 2 und 3)
        if ($isSource1 === $isSource2) {
            return 0;
        }
        if ($isSource1) {
            return 1;
        }

        return -1;
    }

    /**
     * Returns a multidimensional array.
     * 1. level: keys: groupId, values: array of terms grouped by groupId
     * 2. level: terms of group groupId
     *
     * !! TODO: Sortierung der Gruppen in der Reihenfolge wie sie im Segment auftauchen (order by seg2term.id sollte hinreichend sein)
     *
     * @param array $collectionIds term collections associated to the task
     * @param array $termIds as 2-dimensional array('source' => array(), 'target' => array())
     * @param $sourceLang
     * @param $targetLang
     *
     * @return array
     */
    protected function getSortedTermGroups(array $collectionIds, array $termIds, $sourceLang, $targetLang): array
    {
        $lang = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $lang editor_Models_Languages */
        $sourceLanguages = $lang->getFuzzyLanguages($sourceLang);
        $targetLanguages = $lang->getFuzzyLanguages($targetLang);
        $allLanguages = array_unique(array_merge($sourceLanguages, $targetLanguages));
        $sourceIds = array_column($termIds['source'], 1);
        $targetIds = array_column($termIds['target'], 1);
        $transFoundSearch = array_column($termIds['source'], 0, 1) + array_column($termIds['target'], 0, 1);
        $allIds = array_merge($sourceIds, $targetIds);

        $sql = $this->db->getAdapter()->select()
            ->from(['t1' =>'terms_term'], ['t2.*'])
            ->distinct()
            ->joinLeft(['t2' =>'terms_term'], 't1.termEntryId = t2.termEntryId AND t1.collectionId = t2.collectionId', null)
            ->join(['l' =>'LEK_languages'], 't2.languageId = l.id', 'rtl')
            ->where('t1.collectionId IN(?)', $collectionIds)
            //->where('t2.collectionId IN(?)', $collectionIds)
            ->where('t1.termTbxId IN(?)', $allIds)
            ->where('t1.languageId IN (?)', $allLanguages)
            ->where('t2.languageId IN (?)', $allLanguages);

        $terms = $this->db->getAdapter()->fetchAll($sql);

        $termGroups = [];
        foreach($terms as $term) {
            $term = (object) $term;

            settype($termGroups[$term->termEntryTbxId], 'array');

            $term->used = in_array($term->termId, $allIds);
            $term->isSource = in_array($term->languageId, $sourceLanguages);
            $term->transFound = false;
            if ($term->used) {
                $term->transFound = preg_match('/class="[^"]*transFound[^"]*"/', $transFoundSearch[$term->termId]);
            }

            $termGroups[$term->termEntryTbxId][] = $term;
        }

        return $termGroups;
    }
    /***
     * Remove terms where the updated date is older than the given one.
     *
     * @param array $collectionIds
     * @param string $olderThan
     * @return boolean
     */
    public function removeOldTerms(array $collectionIds, $olderThan): bool
    {
        //get all terms in the collection older than the date
        $s = $this->db->select()
            ->setIntegrityCheck(false)
            ->from(['t'=>'terms_term'],['t.id'])
            ->joinLeft(['p'=>'terms_proposal'],'p.termId=t.id ',['p.term','p.created','p.userGuid','p.userName'])
            ->where('t.updated < ?', $olderThan)
            ->where('t.collectionId in (?)',$collectionIds)
            ->where('t.processStatus NOT IN (?)',self::PROCESS_STATUS_UNPROCESSED);
        $result = $this->db->fetchAll($s)->toArray();

        if (empty($result)) {
            return false;
        }
        $term = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        /* @var $term editor_Models_Terminology_Models_TermModel */
        $transacGrp = ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel');
        /* @var $transacGrp editor_Models_Terminology_Models_TransacgrpModel */

        $deleteProposals = [];
        //for each of the terms with the proposals, use the proposal value as the
        //new term value in the original term, after the original term is updated, remove
        //the proposal
        foreach ($result as $key=>$res){
            if (empty($res['term'])) {
                continue;
            }
            $proposal = ZfExtended_Factory::get('editor_Models_Term_Proposal');
            /* @var $proposal editor_Models_Term_Proposal */
            $proposal->init([
                'created'=>$res['created'],
                'userGuid'=>$res['userGuid'],
                'userName'=>$res['userName'],
            ]);

            $term->load($res['id']);
            $term->setTerm($res['term']);
            $term->setCreated($res['created']);
            $term->setUpdated(NOW_ISO);
            $term->setUserGuid($res['userGuid']);
            $term->setUserName($res['userName']);
            $term->setProcessStatus(self::PROCESS_STATUS_UNPROCESSED);
            //TODO: with the next termportal step(add new attribute and so)
            //update/merge those new proposal attributes to
            //now only the transac group should be modefied
            // $transacGrp->updateTermTransacGroupFromProposal($term,$proposal);
            // $transacGrp->updateTermProcessStatus($term, $term::PROCESS_STATUS_UNPROCESSED);
            $term->save();
            $deleteProposals[] = $res['id'];
            unset($result[$key]);
        }
        //remove the collected proposals
        if (!empty($deleteProposals)) {
            $proposal = ZfExtended_Factory::get('editor_Models_Term_Proposal');
            /* @var $proposal editor_Models_Term_Proposal */
            $proposal->db->delete([
                'termId IN(?)' => $deleteProposals
            ]);
        }

        $result = array_column($result,'id');
        if (empty($result)) {
            return false;
        }
        //delete the collected old terms
        return $this->db->delete(['id IN(?)' => $result])>0;
    }
    /***
     * Load all term and attribute proposals, or if second parameter is given load only proposals younger as $youngerAs date within the given collection(s)
     * @param array $collectionIds
     * @param string $youngerAs optional, if omitted all proposals are loaded
     */
    public function loadProposalExportData(array $collectionIds, string $youngerAs = '')
    {
        if (empty($collectionIds)) {
            return [];
        }
        $adapter = $this->db->getAdapter();
        $bindParams = [];
        $termYoungerSql = $attrYoungerSql = '';
        if (!empty($youngerAs)) {
            $bindParams[] = $youngerAs;
            $bindParams[] = $youngerAs;
            $termYoungerSql = ' and (t.created >=? || tp.created >= ?)';
            $attrYoungerSql = ' and (ta.created >=? || tap.created >=?)';
        }
        //Info: why collection ids is not in bindParams
        //binding multiple values to single param is not posible with $adapter->query . For more info see PDOStatement::execute
        $termSql = "SELECT
                    t.termEntryId as 'term-termEntryId',
                    t.definition as 'term-definition',
                    l.langName as 'term-language',
                    t.id as 'term-Id',
                    t.term as 'term-term',
                    t.processStatus as 'term-processStatus',
                    t.userName as 'term-lastEditor',
                    t.updated as 'term-lastEditedDate',
                    tp.id as 'termproposal-id',
                    tp.term as 'termproposal-term',
                    tp.created as 'termproposal-lastEditedDate',
                    tp.userName as 'termproposal-lastEditor',
                    null as 'attribute-id',
                    null as 'attribute-name',
                    null as 'attribute-value',
                    null as 'attribute-lastEditedDate',
                    null as 'attribute-lastEditor',
                    null as 'attributeproposal-id',
                    null as 'attributeproposal-value',
                    null as 'attributeproposal-lastEditedDate',
                    null as 'attributeproposal-lastEditor'
                    FROM terms_term t
                    LEFT OUTER JOIN terms_proposal tp ON tp.termId = t.id
                    INNER JOIN LEK_languages l ON t.language = l.id
                WHERE ".$adapter->quoteInto('t.collectionId IN(?)',$collectionIds)
            .$termYoungerSql."
                AND (tp.term is not null or t.processStatus = 'unprocessed')
                ORDER BY t.termEntryTbxId, t.term";

        $termResult = $adapter->query($termSql,$bindParams)->fetchAll();

        $attributeSql = "SELECT
                        ta.id as 'attribute-id',
                        ta.termId as 'term-Id',
                        ta.termEntryId as 'attribute-termEntryId',
                        ta.elementName as 'attribute-name',
                        ta.value as 'attribute-value',
                        ta.updated as 'attribute-lastEditedDate',
                        ta.userName as 'attribute-lastEditor',
                        ta.processStatus as 'attribute-processStatus',
                        l.langName as 'term-language',
                        tap.id as 'attributeproposal-id',
                        tap.value as 'attributeproposal-value',
                        tap.created as 'attributeproposal-lastEditedDate',
                        tap.userName as 'attributeproposal-lastEditor',
                        t.termEntryId as 'term-termEntryId',
                        t.definition as 'term-definition',
                        t.id as 'term-Id',
                        t.term as 'term-term',
                        t.processStatus as 'term-processStatus',
                        t.userName as 'term-lastEditor',
                        t.updated as 'term-lastEditedDate',
                        tp.id as 'termproposal-id',
                        tp.term as 'termproposal-term',
                        tp.created as 'termproposal-lastEditedDate',
                        tp.userName as 'termproposal-lastEditor'
                    FROM terms_attributes ta
                        LEFT OUTER JOIN terms_attributes_proposal tap ON tap.attributeId = ta.id
                        LEFT OUTER JOIN terms_term t on ta.termId = t.id
                        LEFT OUTER JOIN terms_proposal tp on tp.termId = t.id
                        LEFT OUTER JOIN LEK_languages l ON t.language = l.id
                    WHERE ".$adapter->quoteInto('ta.collectionId IN(?)', $collectionIds).
            $attrYoungerSql."
                    AND (tap.value is not null or ta.processStatus = 'unprocessed')
                    ORDER BY ta.termEntryId, ta.termId";

        $attributeResult = $adapter->query($attributeSql,$bindParams)->fetchAll();

        //merge term proposals with term attributes and term entry attributes proposals
        $resultArray = array_merge($termResult, $attributeResult);

        if (empty($resultArray)) {
            return [];
        }

        return $this->groupProposalExportData($resultArray);
    }
    /***
     * Group the term and attribute proposal data for the export
     * @param array $data
     * @return array
     */
    protected function groupProposalExportData(array $data): array
    {
        usort($data, function($a, $b) {
            $retval = $a['term-Id'] <=> $b['term-Id'];
            if ($retval == 0) {
                $retval = $b['term-term'] <=> $a['term-term'];
            }

            return $retval;
        });

        $returnResult = [];
        $tmpTerm = [];

        //clange cell color by value on the excel export callback
        $changeMyCollorTag = '<changemycolortag>';
        foreach ($data as $row) {
            $tmpTerm['termEntryId'] = $row['term-termEntryId'];
            //if it is empty it is termEntryAttribute
            if(empty($tmpTerm['termEntryId']) && !empty($row['attribute-termEntryId'])){
                $tmpTerm['termEntryId'] = $row['attribute-termEntryId'];
            }
            $tmpTerm['definition'] = $row['term-definition'];
            $tmpTerm['language'] = $row['term-language'];
            $tmpTerm['termId'] = $row['term-Id'];
            $tmpTerm['term'] = $changeMyCollorTag.$row['term-term'];
            $tmpTerm['termProposal'] = '';
            $tmpTerm['processStatus'] = $row['term-processStatus'];
            $tmpTerm['attributeName'] = $row['attribute-name'];
            $tmpTerm['attribute'] = $row['attribute-value'];
            $tmpTerm['attributeProposal'] = '';
            $tmpTerm['lastEditor'] = $changeMyCollorTag.$row['term-lastEditor'];
            $tmpTerm['lastEditedDate'] = $changeMyCollorTag.$row['term-lastEditedDate'];

            //if the proposal exist, set the change color and last editor for the proposal
            if(!empty($row['termproposal-term'])){
                $tmpTerm['term'] = str_replace($changeMyCollorTag,'',$row['term-term']);
                $tmpTerm['termProposal'] = $changeMyCollorTag.$row['termproposal-term'];
                $tmpTerm['lastEditor'] = $changeMyCollorTag.$row['termproposal-lastEditor'];
                $tmpTerm['lastEditedDate'] = $changeMyCollorTag.$row['termproposal-lastEditedDate'];
            }

            if(isset($row['attribute-processStatus']) && $row['attribute-processStatus']==self::PROCESS_STATUS_UNPROCESSED){
                $tmpTerm['attribute'] = $changeMyCollorTag.$row['attribute-value'];
                $tmpTerm['lastEditor'] = $changeMyCollorTag.$row['attribute-lastEditor'];
                $tmpTerm['lastEditedDate'] = $changeMyCollorTag.$row['attribute-lastEditedDate'];
                $tmpTerm['term'] = str_replace($changeMyCollorTag,'',$row['term-term']);
                $tmpTerm['termProposal'] = str_replace($changeMyCollorTag,'',$row['termproposal-term']);
            }

            //if the attribute proposal is set, set the change color and last editor for the attribute proposal
            if(!empty($row['attributeproposal-value'])){
                $tmpTerm['term'] = str_replace($changeMyCollorTag,'',$row['term-term']);
                $tmpTerm['termProposal'] = str_replace($changeMyCollorTag,'',$row['termproposal-term']);
                $tmpTerm['attribute'] = $row['attribute-value'];
                $tmpTerm['attributeProposal'] = $changeMyCollorTag.$row['attributeproposal-value'];
                $tmpTerm['lastEditor'] = $changeMyCollorTag.$row['attributeproposal-lastEditor'];
                $tmpTerm['lastEditedDate'] = $changeMyCollorTag.$row['attributeproposal-lastEditedDate'];
            }
            $returnResult[] = $tmpTerm;
            $tmpTerm = [];
        }

        return $returnResult;
    }

    /***
     * Get all definitions in the given entryIds. The end results will be grouped by $entryIds as a key.
     * @param array $entryIds
     * @return array
     */
    public function getDeffinitionsByEntryIds(array $entryIds): array
    {
        if (empty($entryIds)) {
            return [];
        }
        $s = $this->db->select()
            ->where('termEntryId IN(?)', $entryIds);
        $return = $this->db->fetchAll($s)->toArray();

        if(empty($return)){
            return [];
        }

        //group the definitions by termEntryId as a key
        $result = [];
        foreach ($return as $r) {
            if (!isset($result[$r['termEntryId']])) {
                $result[$r['termEntryId']] = [];
            }

            if (!in_array($r['definition'], $result[$r['termEntryId']]) && !empty($r['definition'])) {
                $result[$r['termEntryId']][] = $r['definition'];
            }
        }

        return $result;
    }

    /**
     * Is the term a "preferred" term according to the given status?
     * @param string $termStatus
     * @return boolean
     */
    static public function isPreferredTerm(string $termStatus): bool
    {
        $termStatusMap = self::getTermStatusMap();
        if (!array_key_exists($termStatus, $termStatusMap)) {
            return false;
        }
        return $termStatusMap[$termStatus] == 'preferred';
    }
    /**
     * Is the term a "permitted" term according to the given status?
     * @param string $termStatus
     * @return boolean
     */
    static public function isPermittedTerm(string $termStatus): bool
    {
        $termStatusMap = self::getTermStatusMap();
        if (!array_key_exists($termStatus, $termStatusMap)) {
            return false;
        }

        return $termStatusMap[$termStatus] == 'permitted';
    }

    /**
     *
     * @param string $termId
     * @param array $collectionIds
     * @return Zend_Db_Table_Row_Abstract | null
     */
    public function loadByMid(string $termId, array $collectionIds): ?Zend_Db_Table_Row_Abstract
    {
        $s = $this->db->select(false);
        $s->from($this->db);
        $s->where('collectionId IN(?)', $collectionIds)->where('termTbxId = ?', $termId);

        $this->row = $this->db->fetchRow($s);
        if (empty($this->row)) {
            $this->notFound('#select', $s->assemble());
        }

        return $this->row;
    }

    /**
     * returns all term mids from given segment content (allows and returns also duplicated mids)
     * @param string $seg
     * @return array values are the mids of the terms in the string
     */
    public function getTermMidsFromSegment(string $seg): array
    {
        return array_map(function($item) {
            return $item['termId'];
        }, $this->getTermInfosFromSegment($seg));
    }

    /**
     * Returns term-informations for a given group id
     *
     * @param array $collectionIds
     * @param string $termEntryTbxId
     * @param array $languageIds 1-dim array with languageIds|default empty array;
     *          if passed only terms with the passed languageIds are returned
     * @return array  2-dim array (get term of first row like return[0]['term'])
     */
    public function getAllTermsOfGroup(array $collectionIds, string $termEntryTbxId, $languageIds = []): array
    {
        $db = $this->db;
        $s = $db->select()
            ->where('collectionId IN(?)', $collectionIds)
            ->where('termEntryTbxId = ?', $termEntryTbxId);

        if (!empty($languageIds)) {
            $s->where('languageId in (?)', $languageIds);
        }

        return $db->fetchAll($s)->toArray();
    }
    /**
     * returns mids and term flags (css classes) found in a string
     * @param string $seg
     * @return array 2D Array, first level are found terms, second level has key mid and key classes
     */
    public function getTermInfosFromSegment(string $seg): array
    {
        return $this->tagHelper->getInfos($seg);
    }

    public function updateAttributeAndTransacTermIdAfterImport($collectionId)
    {
        $sqlAttribute = 'UPDATE terms_attributes termsAtt
                            JOIN terms_term tt on termsAtt.termGuid = tt.guid
                        SET termsAtt.termId = tt.id
                        WHERE termsAtt.termGuid = tt.guid
                        AND termsAtt.collectionId = :collectionId';

        $sqlTransacGrp = 'UPDATE terms_transacgrp termsTrg
                            JOIN terms_term tt on termsTrg.termGuid = tt.guid
                        SET termsTrg.termId = tt.id
                        WHERE termsTrg.termGuid = tt.guid
                        AND termsTrg.collectionId = :collectionId';

        $this->db->getAdapter()->query($sqlAttribute, ['collectionId' => $collectionId]);
        $this->db->getAdapter()->query($sqlTransacGrp, ['collectionId' => $collectionId]);
    }

}
