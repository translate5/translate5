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
     * @string
     */
    protected $tbxImportDirectoryPath = APPLICATION_PATH.'/../data/tbx-import/';

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
        $uniqueNames = [];
        foreach($images as $image) {
            $uniqueNames[$image['targetId']] = APPLICATION_RUNDIR.'/editor/plugins/termimage/TermPortal/tc_'.$collectionId.'/'.$image['uniqueName'];
        }

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
        $src = $this->getImagePath($this->getCollectionId(), $this->getUniqueName());
        is_file($src) && unlink($src);

        return parent::delete();
    }

    /**
     * returns false if the termcollection folder is not usable
     * @throws editor_Models_Terminology_Import_Exception
     */
    public function checkImageTermCollectionFolder(int $collectionId) {
        if(!is_dir($this->tbxImportDirectoryPath) || !is_writable($this->tbxImportDirectoryPath)) {
            //TBX Import: Folder to save images does not exist or is not writable!
            throw new editor_Models_Terminology_Import_Exception('E1353', [
                'path' => $this->tbxImportDirectoryPath
            ]);
        }

        $imagePath = $this->getImagePath($collectionId);

        try {
            if (file_exists($imagePath) || @mkdir($imagePath, 0777, true)) {
                return; //in case of success return here
            }
        } catch (Throwable $e) {
        }
        //if we reach here the folder could not be created
        //TBX Import: Folder to save termcollection images could not be created!
        throw new editor_Models_Terminology_Import_Exception('E1354', [
            'path' => $imagePath
        ]);
    }

    /**
     * Returns the absolute path to term collection folder for images
     * @param int $collectionId
     * @param string $file if given append the file to the path
     * @return string
     */
    public function getImagePath(int $collectionId, string $file = null): string {
        return $this->tbxImportDirectoryPath.'term-images-public/tc_'.$collectionId.($file ? ('/'.$file) : '');
    }

    /**
     * tests if the given unique id is valid
     * @param boolean $uniqueId
     * @return boolean
     */
    public function isValidUniqueId($uniqueId): bool {
        return (bool) preg_match('/[a-z0-9-]{36}\.[a-zA-Z0-9]{3,4}/', $uniqueId);
    }


    /**
     * converts the term collection string to a valid integer
     */
    public function readCollectionIdFromUrlPart(string $tcIdURL): int {
        return (int) str_replace('tc_', '', $tcIdURL);
    }

    /**
     * Save the given image to the term collection images folder. This function will check and create the required folder structure
     * @param int $collectionId
     * @param string $imageName
     * @param string $imageContent
     */
    public function saveImageToDisk(int $collectionId, string $imageName, string $imageContent)
    {
        file_put_contents($this->getImagePath($collectionId, $imageName), $imageContent);
    }
}
