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
     * returns the image data arrays for a given list of targetIds
     * @param int $collectionId
     * @param array $targetIds
     * @return array
     */
    public function loadByTargetIdList(int $collectionId, array $targetIds): array
    {
        $sql = $this->db->select()
            ->where('targetId IN (?)', $targetIds)
            ->where('collectionId = ?', $collectionId);
        return $this->db->fetchAll($sql)->toArray();
    }

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
            ->where('targetId IN (?)', $targetIds)
            ->where('collectionId = ?', $collectionId);
        $images = $this->db->fetchAll($sql)->toArray();

        //generate the paths
        $uniqueNames = [];
        foreach($images as $image) {
            $uniqueNames[$image['targetId']] = $this->getPublicPath($collectionId, $image['uniqueName']);
        }

        return $uniqueNames;
    }

    /**
     * return the public webpath to an image
     * @param int|null $collectionId if omitted use the internal collectionId
     * @param string|null $imageName if omitted use the internal unique name
     * @return string
     */
    public function getPublicPath(int $collectionId = null, string $imageName = null): string {
        return APPLICATION_RUNDIR.'/editor/plugins/termimage/TermPortal/tc_'.($collectionId ?? $this->getCollectionId()).'/'.($imageName ?? $this->getUniqueName());
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
        $this->purgeImageTable($collectionId);
        $fullResult = [];

        $query = "SELECT * FROM terms_images WHERE collectionId = :collectionId";
        $queryResults = $this->db->getAdapter()->query($query, ['collectionId' => $collectionId]);

        foreach ($queryResults as $image) {
            $fullResult[$image['targetId']] = $image;
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


    /**
     * loads a image by given collection and target internally
     * @param int $collectionId
     * @param string $targetId
     * @return mixed
     */
    public function loadByTargetId(int $collectionId, string $targetId)
    {
        return $this->row = $this->db->fetchRow([
            'collectionId = ?' => $collectionId,
            'targetId = ?' => $targetId,
        ]);
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

    /**
     * renames / moves a given file to the unique file name / given filename
     * @param string $source
     * @param int $collectionId
     * @param string|null $targetFile if omitted use internal unique ID
     * @return bool
     */
    public function moveImage(string $source, int $collectionId, string $targetFile = null): bool {
        $this->checkImageTermCollectionFolder($collectionId);
        return rename($source, $this->getImagePath($collectionId, $targetFile ?? $this->getUniqueName()));
    }

    /**
     * creates a unique name out of the given one
     * @param string $name
     * @return string
     */
    public function createUniqueName(string $name): string {
        $d = strrpos($name,".");
        $extension = ($d===false) ? "" : ('.'.substr($name,$d+1));
        return ZfExtended_Utils::uuid().$extension;
    }

    /**
     * the images files on the disk, which are not in the images table, and returns the unique names in the DB where the file is missing
     * @param int $collectionId
     * @param array $filesInDb
     * @return array file table entries where the file on the disk is missing
     */
    public function purgeImageFiles(int $collectionId, array $filesInDb): array {
        $found = scandir($this->getImagePath($collectionId));
        $filesInDb[] = '.';
        $filesInDb[] = '..';
        $toBeDeleted = array_diff($found, $filesInDb);
        $missingOnDisk = array_diff($filesInDb, $found);
        foreach($toBeDeleted as $file) {
            $file = $this->getImagePath($collectionId, $file);
            if(file_exists($file)) {
                unlink($file);
            }
        }
        return $missingOnDisk;
    }

    /**
     * deletes terms_images entries which are not referenced by any attribute
     * @param int $collectionId
     */
    protected function purgeImageTable(int $collectionId) {
        $this->db->getAdapter()->query('DELETE i FROM `terms_images` i
                LEFT JOIN (
                    SELECT id, target FROM `terms_attributes` WHERE collectionId = ?
                ) a ON i.targetId = a.target  
                WHERE a.id IS NULL AND i.collectionId = ?', [$collectionId, $collectionId]);
    }

    public function getQtyByCollectionId($collectionId) {
        return $this->db->getAdapter()->query('
            SELECT COUNT(`id`) FROM `terms_images` WHERE `collectionId` = ?'
        , $collectionId)->fetchColumn();
    }
}
