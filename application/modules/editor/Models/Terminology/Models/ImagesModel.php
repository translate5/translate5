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
 * Class editor_Models_Terms_Images
 * TermsImage Instance
 *
 * @method integer getId() getId()
 * @method void setId() setId(integer $id)
 * @method string getTargetId() getTargetId()
 * @method string setTargetId() setTargetId(string $targetId)
 * @method string getName() getName()
 * @method string setName() setName(string $name)
 * @method string getUniqueName() getUniqueName()
 * @method string setUniqueName() setUniqueName(string $uniqueName)
 * @method string getEncoding() getEncoding()
 * @method string setEncoding() setEncoding(string $encoding)
 * @method string getFormat() getFormat()
 * @method string setFormat() setFormat(string $format)
 * @method integer getCollectionId() getCollectionId()
 * @method integer setCollectionId() setCollectionId(integer $collectionId)
 */
class editor_Models_Terminology_Models_ImagesModel extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_Terminology_Images';

    /**
     * returns the image paths to a collection ID and a list of targets
     * @param int $collectionId
     * @param array $targetIds
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function getImagePathsByTargetIds(int $collectionId, array $targetIds): array {
        $sql = $this->db->select()
            ->from($this->db, ['targetId', 'uniqueName'])
            ->where('targetId IN (?)', $targetIds);
        $images = $this->db->fetchAll($sql)->toArray();

        //generate the paths
        foreach($images as $image) {
            $uniqueNames[$image['targetId']] = '/term-images-public/tc_'.$collectionId.'/'.$image['uniqueName'];
        }
        error_log("ID: ".$collectionId);
        error_log(print_r($targetIds,1));
        error_log(print_r($uniqueNames,1));
        return $uniqueNames;
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
    public function getAllImagesByCollectionId(int $collectionId): array
    {
        $fullResult = [];

        $query = "SELECT * FROM terms_images WHERE collectionId = :collectionId";
        $queryResults = $this->db->getAdapter()->query($query, ['collectionId' => $collectionId]);

        foreach ($queryResults as $image) {
            $fullResult[$image['collectionId'].'-'.$image['targetId']] = $image;
        }

        return $fullResult;
    }

    public function createImportTbx(string $sqlParam, string $sqlFields, array $sqlValue)
    {
        $this->init();
        $insertValues = rtrim($sqlParam, ',');

        $query = "INSERT INTO terms_images ($sqlFields) VALUES $insertValues";

        return $this->db->getAdapter()->query($query, $sqlValue);
    }


    public function loadByTargetId(string $targetId)
    {
        return $this->row = $this->db->fetchRow('`targetId` = "' . $targetId . '"');
    }

    public function delete() {
        // If file exists delete it
        if (is_file($src = 'term-images-public/tc_' . $this->getCollectionId() . '/' . $this->getUniqueName())) {
            unlink($src);
        }

        return parent::delete();
    }
}
