<?php

use Doctrine\DBAL\Exception;

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
 * @method string getTermId() getTermId()
 * @method string setTermId() setTermId(string $termId)
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
 * @method string getDescrip() getDescrip()
 * @method string setDescrip() setDescrip(string $descrip)
 * @method string getDescripType() getDescripType()
 * @method string setDescripType() setDescripType(string $descripType)
 * @method string getDescripTarget() getDescripTarget()
 * @method string setDescripTarget() setDescripTarget(string $descripTarget)
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


    /**
     * The above constants are needed in the application as list, since reflection usage is expensive we cache them here:
     * @var array
     */
    protected static array $statusCache = [];

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
    }

    /**
     * $fullResult[$term['mid'].'-'.$term['groupId'].'-'.$term['collectionId']]
     * $fullResult['termId-termEntryId-collectionId'] = TERM
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
    public function getRestTermsOfTermEntry($groupId, $mid, $collectionId)
    {
        $s = $this->db->select()
            ->where('termEntryId = ?', $groupId)
            ->where('termId != ?', $mid)
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
            $s->where('language in (?)', $langs);
        }

        $s->order('termEntryTbxId ASC')
            ->order('language ASC')
            ->order('id ASC');
        $data = $this->db->fetchAll($s);

        if ($data->count() == 0) {
            return null;
        }

        return $data;
    }


    /**
     * Search terms in the term collection with the given search string and languages.
     * @param string $queryString
     * @param string $languages
     * @param array $collectionIds
     * @param mixed $limit
     * @param array $processStats
     * @return array
     */
    public function searchTermByLanguage(string $queryString, string $languages, array $collectionIds, $limit = null, array $processStats): array
    {
        $termObject = ZfExtended_Factory::get('editor_Models_Terminology_TbxObjects_Term');

        //if wildcards are used, adopt them to the mysql needs
        $queryString = str_replace("*","%",$queryString);
        $queryString = str_replace("?","_",$queryString);

        //when limit is provided -> autocomplete search
        if($limit){
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
            ->from($tableTerm, ['term as label', 'id as value', 'term as desc', 'definition', 'termEntryTbxId', 'collectionId', 'termEntryId', 'languageId'])
            ->where('lower(`'.$tableTerm.'`.term) like lower(?) COLLATE utf8mb4_bin',$queryString)
            ->where('`'.$tableTerm.'`.languageId IN(?)', explode(',', $languages))
            ->where('`'.$tableTerm.'`.collectionId IN(?)',$collectionIds)
            ->where('`'.$tableTerm.'`.processStatus IN(?)',$processStats);
        $s->order($tableTerm.'.term asc');

        if ($limit) {
            $s->limit($limit);
        }

        if (!$isProposalAllowed || !in_array(self::PROCESS_STATUS_UNPROCESSED, $processStats)) {
            return $this->db->fetchAll($s)->toArray();
        }

        //if proposal is allowed, search also in the proposal table for results
        $tableProposal = (new editor_Models_Db_Term_Proposal())->info($this->db::NAME);
        $sp = $this->db->select()
            ->setIntegrityCheck(false)
            ->from($tableProposal, ['term as label', 'termId as value', 'term as desc'])
            ->joinInner($tableTerm, '`'.$tableTerm.'`.`id` = `'.$tableProposal.'`.`termId`', ['definition', 'termEntryTbxId', 'collectionId', 'termEntryId', 'languageId'])
            ->where('lower(`'.$tableProposal.'`.term) like lower(?) COLLATE utf8mb4_bin', $queryString)
            ->where('`'.$tableTerm.'`.languageId IN(?)', explode(',', $languages))
            ->where('`'.$tableTerm.'`.collectionId IN(?)', $collectionIds)
            ->order($tableTerm.'.term asc');
        if ($limit) {
            $sp->limit($limit);
        }

        $sql = '('.$s->assemble().') UNION ('.$sp->assemble().')';

        return $this->db->getAdapter()->query($sql)->fetchAll();
    }

    /***
     * It is proposal when the user is allowed for term proposal operation
     * @return boolean
     */
    public function isProposableAllowed(): bool
    {
        $user=ZfExtended_Factory::get('ZfExtended_Models_User');
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
            'termId',
            'termEntryId',
            'collectionId',
            'languageId',
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
            $atr['proposable'] = $isAttributeProposalAllowed && $attribute->isProposable($atr['name'],$atr['attrType']);
            if ($isAttributeProposalAllowed) {
                $atr['proposal'] = !empty($attProposal['id']) ? $attProposal : null;
                $attProposal = [];
            }

            array_push($map[$termKey]['attributes'], $atr);
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
                $map[$oldKey]['attributes'] = $attribute->updateModificationGroupDate($map[$oldKey]['attributes'],isset($map[$oldKey]['proposal'])?$map[$oldKey]['proposal']:[]);
            }
        }

        if (empty($map)) {
            return null;
        }

        return $map;
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
            ->where('language = ?', $languageId ?? $this->getLanguage())
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
        $s->where('terms_term.id=?', $termId)
            ->order('LEK_languages.rfc5646')
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
            'terms_attributes.labelId as labelId',
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
//            'terms_attributes.attrDataType AS attrDataType',
//            'terms_attributes.processStatus AS attrProcessStatus',
            new Zend_Db_Expr('"termAttribute" as attributeOriginType')//this is needed as fixed value
        ];

        $cols = [
            'definition',
            'termEntryTbxId',
            'term as label',
            'term as term',//for consistency
            'id as value',
            'term as desc',
            'status as termStatus',
            'processStatus as processStatus',
            'id as termId',
            'termEntryId',
            'collectionId',
            'language as languageId'
        ];

        $s = $this->db->select()
            ->setIntegrityCheck(false)
            ->from($this->db, $cols)
            ->joinLeft('terms_attributes', 'terms_attributes.termId = terms_term.termId', $attCols)
            ->joinLeft('LEK_term_attributes_label', 'LEK_term_attributes_label.id = terms_attributes.labelId',['LEK_term_attributes_label.labelText as headerText'])
            ->join('LEK_languages', 'terms_term.languageId = LEK_languages.id', ['LEK_languages.rfc5646 AS language']);

        if($this->isProposableAllowed()){
            $s->joinLeft('LEK_term_proposal', 'LEK_term_proposal.termId = terms_term.id',[
                'LEK_term_proposal.term as proposalTerm',
                'LEK_term_proposal.id as proposalId',
                'LEK_term_proposal.created as proposalCreated',
                'LEK_term_proposal.userName as proposalUserName'
            ]);
        }

        $attribute = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeModel');
        /* @var $attribute editor_Models_Terminology_Models_AttributeModel */

        if($attribute->isProposableAllowed()){
            $s->joinLeft('LEK_term_attribute_proposal', 'LEK_term_attribute_proposal.attributeId = terms_attributes.id',[
                'LEK_term_attribute_proposal.value as proposalAttributeValue',
                'LEK_term_attribute_proposal.id as proposalAttributelId',
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
        $s->where('terms_term.termEntryId = ?', $termEntryId)
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
                ->where('language IN (?)',$language);
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
}
