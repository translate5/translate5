<?php

use Doctrine\DBAL\Exception;

/**
 * Class editor_Models_Terms_Attributes
 * Attributes Instance
 *
 * @method integer getId() getId()
 * @method void setId() setId(integer $id)
 * @method string getType() getType()
 * @method string setType() setType(string $type)
 * @method string getDataType() getDataType()
 * @method string setDataType() setDataType(string $dataType)
 * @method string getLanguage() getLanguage()
 * @method string setLanguage() setLanguage(string $language)
 * @method string getElementName() getElementName()
 * @method string setElementName() setElementName(string $elementName)
 * @method string getTarget() getTarget()
 * @method string setTarget() setTarget(string $target)
 * @method string getTermId() getTermId()
 * @method string setTermId() setTermId(string $termId)
 * @method string getValue() getValue()
 * @method string setValue() setValue(string $value)
 * @method integer getCollectionId() getCollectionId()
 * @method integer setCollectionId() setCollectionId(integer $collectionId)
 * @method string getTermEntryId() getTermEntryId()
 * @method string setTermEntryId() setTermEntryId(string $termEntryId)
 * @method string getLabelId() getLabelId()
 * @method string setLabelId() setLabelId(string $labelId)
 * @method string getTermEntryGuid() getTermEntryGuid()
 * @method string setTermEntryGuid() setTermEntryGuid(string $termEntryGuid)
 * @method string getLangSetGuid() getLangSetGuid()
 * @method string setLangSetGuid() setLangSetGuid(string $langSetGuid)
 * @method string getTermGuid() getTermGuid()
 * @method string setTermGuid() setTermGuid(string $termGuid)
 * @method string getUserName() getUserName()
 * @method string setUserName() setUserName(string $userName)
 * @method string getUserGuid() getUserGuid()
 * @method string setUserGuid() setUserGuid(string $userGuid)
 * @method string getGuid() getGuid()
 * @method string setGuid() setGuid(string $guid)
 * @method string getCreated() getCreated()
 * @method void setCreated() setCreated(string $created)
 * @method string getUpdated() getUpdated()
 * @method void setUpdated() setUpdated(string $updated)
 * @method string getProcessStatus() getProcessStatus()
 * @method void setProcessStatus() setProcessStatus(string $processStatus)
 */
class editor_Models_Terminology_Models_AttributeModel extends ZfExtended_Models_Entity_Abstract
{
    const ATTR_LEVEL_ENTRY = 'termEntry';
    const ATTR_LEVEL_LANGSET = 'langSet';
    const ATTR_LEVEL_TERM = 'term';
    protected $dbInstanceClass = 'editor_Models_Db_Terminology_Attribute';
    protected $validatorInstanceClass = 'editor_Models_Validator_Term_Attribute';

    /**
     * editor_Models_Terms_Attribute constructor.
     */
    public function __construct() {
        parent::__construct();
    }

    public function getAttributeCollectionByEntryId($collectionId, $termEntryId): array
    {
        $attributeByKey = [];

        $query = "SELECT * FROM terms_attributes WHERE collectionId = :collectionId AND termEntryId = :termEntryId";
        $queryResults = $this->db->getAdapter()->query($query, ['collectionId' => $collectionId, 'termEntryId' => $termEntryId]);

        foreach ($queryResults as $key => $attribute) {
            $attributeByKey[$attribute['elementName'].'-'.$attribute['language'].'-'.$attribute['termId']] = $attribute;
        }

        return $attributeByKey;
    }

    public function getAttributeByCollectionId(int $collectionId): array
    {
        $attributeByKey = [];

        $query = "SELECT * FROM terms_attributes WHERE collectionId = :collectionId";
        $queryResults = $this->db->getAdapter()->query($query, ['collectionId' => $collectionId]);

        foreach ($queryResults as $key => $attribute) {
            $attributeByKey[$attribute['elementName'].'-'.$attribute['language'].'-'.$attribute['termId']] = $attribute;
        }

        return $attributeByKey;
    }

    public function createImportTbx(string $sqlParam, string $sqlFields, array $sqlValue)
    {
        $this->init();
        $insertValues = rtrim($sqlParam, ',');

        $query = "INSERT INTO terms_attributes ($sqlFields) VALUES $insertValues";

        return $this->db->getAdapter()->query($query, $sqlValue);
    }

    /**
     * @param array $attributes
     * @return bool
     */
    public function updateImportTbx(array $attributes): bool
    {
        foreach ($attributes as $attribute) {
            $this->db->update($attribute, ['id=?'=> $attribute['id']]);
        }

        return true;
    }

    /***
     * Is the user allowed for attribute proposal
     * @return boolean
     */
    public function isProposableAllowed(): bool
    {
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */

        return $user->hasRole('termProposer');
    }

    /***
     * Update term modification attribute group with the term proposal values.
     * The modification group date and editor will be set as term proposal date an term proposal editor.
     *
     * @param array $attributes
     * @param array $termProposal
     * @return array
     */
    public function updateModificationGroupDate(array $attributes, array $termProposal): array
    {
        if (empty($attributes) || empty($termProposal) || empty($termProposal['created']) || empty($termProposal['userName'])) {
            return $attributes;
        }

        //foreach term attribute check, find the transac modification attribute
        foreach ($attributes as &$attribute) {

            if (empty($attribute['children'])) {
                continue;
            }

            //ignore non modification tags
            if ($attribute['name'] != 'transac' || $attribute['attrType'] != 'modification') {
                continue;
            }

            foreach ($attribute['children'] as &$child) {
                if ($child['name'] == 'date') {
                    //convert the date to unix timestamp
                    $child['attrValue'] = strtotime($termProposal['created']);
                }
                if ($child['name'] == 'transacNote' && $this->isResponsablePersonAttribute($child['attrType'])) {
                    $child['attrValue'] = $termProposal['userName'];
                }
            }
        }

        return $attributes;
    }

    /**
    * Check if given AttrType is for responsable person
    * Info: the responsable person type is saved in with different values in some tbx files
    * @param string|null $type
    * @return boolean
    */
    public function isResponsablePersonAttribute(string $type = null): bool
    {
        if (!empty($type)) {
            return $type == 'responsiblePerson' || $type == 'responsibility';
        }
        if ($this->getType() != null) {
            return $this->getType() == 'responsiblePerson' || $this->getType() == 'responsibility';
        }

        return false;
    }

    /**
     * returns true if the attribute is proposable according to its values.
     * Additional ACL checks must be done outside (in the controllers)
     * @param string $name optional, if both parameters are empty the values from $this are used
     * @param string $type optional, if both parameters are empty the values from $this are used
     */
    public function isProposable($name = null, $type = null) {
        if(empty($name) && empty($type)) {
            $name = $this->getElementName();
            $type = $this->getType();
        }
        return !($name == 'date'
            || $name=='termNote' && $type=='processStatus'
            || $name=='transacNote' && ($this->isResponsablePersonAttribute($type))
            || $name=='transac' && ($type=='creation' || $type=='origination')
            || $name=='transac' && $type=='modification');
    }
    /***
     * Get all attributes for the given term entry (groupId)
     * @param string $termEntryId
     * @param array $collectionIds
     * @return array|NULL
     */
    public function getAttributesForTermEntry(string $termEntryId, array $collectionIds): ?array
    {
        $cols = [
            'terms_attributes.id AS attributeId',
            'terms_attributes.labelId as labelId',
            'terms_attributes.termEntryId AS termEntryId',
//            'terms_attributes.parentId AS parentId',
            'terms_attributes.internalCount AS internalCount',
            'terms_attributes.language AS language',
            'terms_attributes.elementName AS name',
            'terms_attributes.type AS attrType',
            'terms_attributes.dataType AS attrDataType',
            'terms_attributes.target AS attrTarget',
            'terms_attributes.guid AS attrId',
//            'terms_attributes.attrLang AS attrLang',
            'terms_attributes.value AS attrValue',
            'terms_attributes.created AS attrCreated',
            'terms_attributes.updated AS attrUpdated',
            'terms_term_entry.collectionId AS collectionId',
            new Zend_Db_Expr('"termEntryAttribute" as attributeOriginType')//this is needed as fixed value
        ];

        $s = $this->db->select()
            ->from($this->db,[])
            ->join('terms_term_entry', 'terms_term_entry.id = terms_attributes.termEntryId', $cols)
            ->joinLeft('LEK_term_attributes_label', 'LEK_term_attributes_label.id = terms_attributes.labelId', ['LEK_term_attributes_label.labelText as headerText']);

        // todo: Sinisa, add transacGrp to attributes array
        $s->join('terms_transacgrp', 'terms_transacgrp.termEntryId = terms_term_entry.id',[
            'terms_transacgrp.transac as transac',
            'terms_transacgrp.transacNote as transacNote',
            'terms_transacgrp.transacType as transacType',
        ]);

        if ($this->isProposableAllowed()) {
            $s->joinLeft('LEK_term_attribute_proposal', 'LEK_term_attribute_proposal.attributeId = terms_attributes.id',[
                'LEK_term_attribute_proposal.value as proposalAttributeValue',
                'LEK_term_attribute_proposal.id as proposalAttributelId',
            ]);
        }

        $s->where('terms_attributes.termId is null OR terms_attributes.termId = ""')
            ->where('terms_term_entry.id = ?', $termEntryId)
            ->where('terms_term_entry.collectionId IN(?)', $collectionIds)
            ->group('terms_term_entry.id');
        $s->setIntegrityCheck(false);

        $rows = $this->db->fetchAll($s)->toArray();

        if (empty($rows)) {
            return null;
        }

        $mapProposal = function($item) {
            $item['proposable'] = $this->isProposable($item['name'],$item['attrType']);
            $item['proposal'] = null;

            if (!isset($item['proposalAttributelId'])) {
                unset($item['proposalAttributelId']);

                if (!isset($item['proposalAttributeValue'])) {
                    unset($item['proposalAttributeValue']);
                }
                return $item;
            }
            $obj = new stdClass();
            $obj->id = $item['proposalAttributelId'];
            $obj->value = $item['proposalAttributeValue'];
            $item['proposal'] = $obj;

            return $item;
        };
        $rows = array_map($mapProposal, $rows);

        return $rows;
//        return $this->createChildTree($rows);
    }

    /***
     * Check if for the current term there is a processStatus attribute. When there is no one, create it.
     * @param int $termId
     * @return NULL|mixed|array
     */
    public function checkOrCreateProcessStatus(int $termId): ?array
    {
        $s=$this->db->select()
            ->where('termId=?',$termId)
            ->where('elementName="termNote"')
            ->where('attrType="processStatus"');

        $result=$this->db->fetchAll($s)->toArray();
        if(count($result)>0){
            return null;
        }

        $term=ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        /* @var $term editor_Models_Term */
        $term->load($termId);

        $language=ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $language editor_Models_Languages */

        $language->loadById($term->getLanguage());

        $this->setCollectionId($term->getCollectionId());
        $this->setTermId($term->getId());
        $this->setLanguage($language->getRfc5646());
        $this->setElementName('termNote');
        $this->setType('processStatus');

        $label = ZfExtended_Factory::get('editor_Models_TermCollection_TermAttributesLabel');
        /* @var $label editor_Models_TermCollection_TermAttributesLabel */
        $label->loadOrCreate('termNote', 'processStatus');
        $this->setLabelId($label->getId());

//        $this->setAttrLang($language->getRfc5646());

        $this->setUserGuid($term->getUserGuid());
        $this->setUserName($term->getUserName());
        $this->setProcessStatus($term->getProcessStatus());
        $this->setValue($term->getProcessStatus());

        return $this->save();
    }

    /**
     * Loads an attribute for the given term
     * @param editor_Models_Terminology_Models_TermModel $term
     * @param string $name
     * @param string $level
     */
    public function loadByTermAndName(editor_Models_Terminology_Models_TermModel $term, string $name, string $level = self::ATTR_LEVEL_TERM) {
        $s = $this->db->select()->where('collectionId = ?', $term->getCollectionId());
        $s->where('termEntryId = ?', $term->getTermEntryId());
        if ($level == self::ATTR_LEVEL_LANGSET || $level == self::ATTR_LEVEL_TERM) {
            $lang = ZfExtended_Factory::get('editor_Models_Languages');
            /* @var $lang editor_Models_Languages */
            $lang->loadById($term->getLanguageId());
            $s->where('language = ?', strtolower($lang->getRfc5646()));
        } else {
            $s->where('language is null OR language = "none"');
        }

        if ($level === self::ATTR_LEVEL_TERM) {
            $s->where('termId = ?', $term->getTermId());
        } else {
            $s->where('termId is null OR termId = ""');
        }
        $s->where('elementName = ?', $name);

        $row = $this->db->fetchRow($s);
        if (!$row) {
            $this->notFound(__CLASS__ . '#termAndName', $term->getId().'; '.$name);
        }
        //load implies loading one Row, so use only the first row
        $this->row = $row;
    }
    /***
     * Add comment attribute for given term
     * @param int $termId
     * @param string $termText
     * @return editor_Models_Terminology_Models_AttributeModel
     */
    public function addTermComment(int $termId,string $termText): self
    {
        $term = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        /* @var $term editor_Models_Terminology_Models_TermModel */
        $term->load($termId);

        $lang = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $lang editor_Models_Languages */
        $lang->loadById($term->getLanguageId());

        $label = ZfExtended_Factory::get('editor_Models_TermCollection_TermAttributesLabel');
        /* @var $label editor_Models_TermCollection_TermAttributesLabel */
        $label->loadOrCreate('note');

        $this->init([
            'elementName' => 'note',
            'created' => NOW_ISO,
            'internalCount' => 1,
            'collectionId' => $term->getCollectionId(),
            'termId' => $term->getTermId(),
            'termEntryId' => $term->getTermEntryId(),
            'termEntryGuid' => $term->getTermEntryGuid(),
            'langSetGuid' => $term->getLangSetGuid(),
            'guid' => ZfExtended_Utils::guid(),
            'language' => strtolower($lang->getRfc5646()),
            'labelId' => $label->getId(),
            'processStatus' => editor_Models_Term::PROCESS_STATUS_UNPROCESSED
        ]);
        $this->setValue(trim($termText));
        $sessionUser = new Zend_Session_Namespace('user');
        $this->setUserGuid($sessionUser->data->userGuid);
        $this->setUserName($sessionUser->data->userName);
        $this->hasField('updated') && $this->setUpdated(NOW_ISO);
        $this->save();

        return $this;
    }

    /**
     * creates a new, unsaved term attribute history entity
     * @return editor_Models_Term_AttributeHistory
     */
    public function getNewHistoryEntity(): editor_Models_Term_AttributeHistory
    {
        $history = ZfExtended_Factory::get('editor_Models_Term_AttributeHistory');
        /* @var $history editor_Models_Term_AttributeHistory */
        $history->setAttributeId($this->getId());
        $history->setHistoryCreated(NOW_ISO);

        $fields = $history->getFieldsToUpdate();
        foreach ($fields as $field) {
            $history->__call('set' . ucfirst($field), array($this->get($field)));
        }

        return $history;
    }
}
