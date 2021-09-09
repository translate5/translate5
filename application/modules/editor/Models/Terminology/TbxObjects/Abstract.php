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
abstract class editor_Models_Terminology_TbxObjects_Abstract
{
    const TABLE_FIELDS = [];
    const GUID_FIELD = 'guid';

    protected static array $updatableTableFields = [];

    public ?editor_Models_Terminology_TbxObjects_TermEntry $parentEntry = null;
    public ?editor_Models_Terminology_TbxObjects_Langset $parentLangset = null;
    public ?editor_Models_Terminology_TbxObjects_Term $parentTerm = null;

    public function __construct(array $data = null)
    {
        if(!is_array($data)) {
            return;
        }
        foreach ($data as $key => $val) {
            if(property_exists($this, $key)) {
                $this->{$key} = $val;
            }
        }
    }

    /**
     * Sets all needed parent nodes by just setting the latest available
     * @param editor_Models_Terminology_TbxObjects_Abstract $parentNode
     */
    public function setParent(editor_Models_Terminology_TbxObjects_Abstract $parentNode) {
        //init polymorphic parent nodes:
        $this->parentEntry = null;
        $this->parentLangset = null;
        $this->parentTerm = null;

        if($parentNode instanceof editor_Models_Terminology_TbxObjects_Term) {
            $this->parentTerm = $parentNode;
            $this->parentLangset = $parentNode->parentLangset;
            $this->parentEntry = $this->parentLangset->parentEntry;
        }elseif($parentNode instanceof editor_Models_Terminology_TbxObjects_Langset) {
            $this->parentLangset = $parentNode;
            $this->parentEntry = $parentNode->parentEntry;
        }elseif($parentNode instanceof editor_Models_Terminology_TbxObjects_TermEntry) {
            //can be only the entry then.
            $this->parentEntry = $parentNode;
        }
    }

    /**
     * @return string
     */
    abstract public function getCollectionKey(): string;

    /**
     * returns the field names which are updateable
     * @return string[]
     */
    public function getUpdateableFields(): array {
        $cls = get_class($this);
        if(empty(static::$updatableTableFields[$cls])) {
            $fields = array_filter(static::TABLE_FIELDS);
            //the GUID field of the element is never updateable!
            if(isset($fields[self::GUID_FIELD])) {
                unset($fields[self::GUID_FIELD]);
            }
            static::$updatableTableFields[$cls] = $fields;
        }
        return array_keys(static::$updatableTableFields[$cls]);
    }

    /**
     * returns a hash of the updateable data of this ImportObject
     * @return string
     */
    public function getDataHash(): string {
        if($this instanceof editor_Models_Terminology_TbxObjects_Term) {
            error_log(print_r(array_intersect_key(get_object_vars($this), static::$updatableTableFields[get_class($this)]),1));
        }
        return md5(print_r(array_intersect_key(get_object_vars($this), static::$updatableTableFields[get_class($this)]),1));
    }
}