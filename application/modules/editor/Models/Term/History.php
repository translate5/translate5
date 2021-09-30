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
 * @method integer getId() getId()
 * @method void setId() setId(integer $id)
 * @method integer getTermId() getTermId()
 * @method void setTermId() setTermId(integer $termId)
 * @method integer getCollectionId() getCollectionId()
 * @method void setCollectionId() setCollectionId(integer $collectionId)
 * @method integer getTermEntryId() getTermEntryId()
 * @method void setTermEntryId() setTermEntryId(integer $termEntryId)
 * @method integer getLanguageId() getLanguageId()
 * @method void setLanguageId() setLanguageId(integer $languageId)
 * @method string getLanguage() getLanguage()
 * @method void setLanguage() setLanguage(string $language)
 * @method string getTerm() getTerm()
 * @method void setTerm() setTerm(string $term)
 * @method string getProposal() getProposal()
 * @method void setProposal() setProposal(string $proposal)
 * @method string getStatus() getStatus()
 * @method void setStatus() setStatus(string $status)
 * @method string getProcessStatus() getProcessStatus()
 * @method void setProcessStatus() setProcessStatus(string $processStatus)
 * @method integer getUpdatedBy() getUpdatedBy()
 * @method void setUpdatedBy() setUpdatedBy(integer $updatedBy)
 * @method string getUpdatedAt() getUpdatedAt()
 * @method void setUpdatedAt() setUpdatedAt(string $updatedAt)
 * @method string getDefinition() getDefinition()
 * @method void setDefinition() setDefinition(string $term)
 * @method string getTermEntryTbxId() getTermEntryTbxId()
 * @method void setTermEntryTbxId() setTermEntryTbxId(string $termEntryTbxId)
 * @method string getTermTbxId() getTermTbxId()
 * @method void setTermTbxId() setTermTbxId(string $termTbxId)
 * @method string getTermEntryGuid() getTermEntryGuid()
 * @method void setTermEntryGuid() setTermEntryGuid(string $termEntryGuid)
 * @method string getLangSetGuid() getLangSetGuid()
 * @method void setLangSetGuid() setLangSetGuid(string $langSetGuid)
 * @method string getGuid() getGuid()
 * @method void setGuid() setGuid(string $guid)
 */
class editor_Models_Term_History extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_Term_History';
}
