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
 */
class editor_Models_Terminology_Import_TbxBinaryDataImport
{
    /**
     * imports the binary data refObject as images
     * @param int $collectionId
     * @param SimpleXMLElement $refObjectList
     * @throws editor_Models_Terminology_Import_Exception
     */
    public function import(int $collectionId, SimpleXMLElement $refObjectList)
    {
        /** @var $imagesModel editor_Models_Terminology_Models_ImagesModel */
        $imagesModel = ZfExtended_Factory::get('editor_Models_Terminology_Models_ImagesModel');
        $imagesModel->checkImageTermCollectionFolder($collectionId);

        $tbxImagesCollection = $imagesModel->getAllImagesByCollectionId($collectionId);

        /** @var SimpleXMLElement $refObject */
        foreach ($refObjectList as $refObject) {
            $image = $this->makeImageData($collectionId, $refObject);

            //ON INSERT and UPDATE ONLY, on UPDATE delete the existing one
            $image['uniqueName'] = $imagesModel->createUniqueName($image['name']);

            $hashInDB = $tbxImagesCollection[$image['targetId']]['contentMd5hash'] ?? null;
            if($hashInDB === $image['contentMd5hash']) {
                //if the image with the same target id and content hash is already in DB, do not save it
                continue;
            }

            $imagesModel->saveImageToDisk($collectionId, $image['uniqueName'], $image['data']);
            unset($image['data']);

            if(empty($image['id'])) {
                $image['id'] = $imagesModel->db->insert($image);
                //collect the inserted images for later file clean up
                $tbxImagesCollection[$image['targetId']] = $image;
            }
            else {
                $id = $image['id'];
                unset($image['id']);
                $imagesModel->db->update($image, ['id = ?' => $id]);
            }
        }
        $missingFiles = $imagesModel->purgeImageFiles($collectionId, array_column($tbxImagesCollection, 'uniqueName'));
        if(!empty($missingFiles)) {
            $this->logger->warn('E1028', 'TBX Import: there are image files in the database which are missing on the disk', [
                'termCollectionId' => $collectionId,
                'missingFiles' => $missingFiles,
            ]);
        }
    }

    /**
     * makes a terms_image data array out of the imported XML node
     * @param int $collectionId
     * @param SimpleXMLElement $refObject
     * @return array
     */
    protected function makeImageData(int $collectionId, SimpleXMLElement $refObject): array {
        $targetId = (string)$refObject->attributes()->{'id'};

        $image = [
            'id' => null, //null to insert/with id to update
            'targetId' => $targetId,
            'collectionId' => $collectionId,
            'name' => null,
            'uniqueName' => null,
            'format' => null,
            'contentMd5hash' => null,
        ];

        //the image data is stored in multiple item tags with different types, read them out:
        $items = [];
        foreach ($refObject->item as $item) {
            $items[(string)$item->attributes()->{'type'}] = (string)$item;
        }

        if (isset($items['encoding'])) {
            $image['name'] = $items['name'];
        } else {
            $image['name'] = (string)$targetId.'.'.$items['format'];
        }
        $image['format'] = $items['format'];

        $hexOrXbaseWithoutSpace = str_replace(' ', '', $items['data']);

        if (isset($image['encoding']) && $image['encoding'] === 'hex') {
            // convert the hex string to binary
            $image['data'] = hex2bin($hexOrXbaseWithoutSpace);
        } else {
            // convert the base64 string to binary
            $image['data'] = base64_decode($hexOrXbaseWithoutSpace);
        }

        $image['contentMd5hash'] = md5($image['data']);

        return $image;
    }
}
