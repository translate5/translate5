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

class editor_Models_Terminology_Import_TbxBinaryDataImport
{
    /**
     * Flag indicating whether binary data should be additionally base64_decode()-ed prior writing as files on disk
     *
     * @var bool
     */
    public $doubleDecode = false;

    /**
     * [someTarget => true] pairs for each value of id-attr mentioned in '<refObject id="someTarget">'-nodes
     * It will be further used as a dictionary which makes it handy to check whether the image definition exists
     * in back-matter for any value of target-attr in '<descrip type="figure" target="someTarget">'-nodes
     */
    public array $figureExists = [];

    /**
     * Temporary directory where imported zip archive was extracted into
     */
    protected string $extractedZipDir;

    /***
     * @var ZfExtended_Logger
     */
    protected ZfExtended_Logger $logger;

    /**
     * Images quantities
     */
    public array $imageQty = [

        /**
         * Newly created images on disk
         */
        'created' => 0,

        /**
         * Recreated as having equal contentMd5Hash but missing on disk
         */
        'recreated' => 0,

        /**
         * Unchanged as having equal contentMd5Hash and existing on disk
         */
        'unchanged' => 0,

        /**
         * Images whose paths are mentioned in back-matter or directly in
         * <xref type="xGraphic" target="some/path.jpg>-nodes, but no images found under that paths
         */
        'missing' => 0,
    ];

    /**
     * Info about missing images
     *
     * @var array|array[]
     */
    public array $missingImages = [

        // [targetId => true] pairs for each figure's targetId that have no definition in back-matter. See E1540
        'definitions' => [],

        // ['path/to/image.png' => true] pairs for each back-matter definition's path or xGraphic's target
        // (which is path as well) that have no real files in the zip-archive under that path. See E1544
        'files' => [],
    ];

    protected editor_Models_Terminology_Models_ImagesModel $imagesModel;

    /**
     * [targetId => [..data..]] pairs for all records in terms_images-table for current term collection
     *
     * @var array|array[]
     */
    protected array $knownImageA = [];

    /**
     * Current term collection instance
     */
    protected editor_Models_TermCollection_TermCollection $collection;

    /**
     * editor_Models_Terminology_Import_TbxBinaryDataImport constructor.
     *
     * @throws Zend_Exception
     * @throws editor_Models_Terminology_Import_Exception
     */
    public function __construct(string $tbxFilePath, editor_Models_TermCollection_TermCollection $collection)
    {
        // Get dir where imported zip-archive was extracted
        $this->extractedZipDir = pathinfo($tbxFilePath, PATHINFO_DIRNAME);

        // Setup doubleDecode flag
        $this->doubleDecode = preg_match(
            '~<sourceDesc><p>Termflow</p></sourceDesc>~',
            file_get_contents($tbxFilePath, false, null, 0, 5 * 1024)
        );

        // Setup logger instance
        $this->logger = Zend_Registry::get('logger');

        // Setup collection instance
        $this->collection = $collection;

        // Setup images model instance
        $this->imagesModel = ZfExtended_Factory::get(editor_Models_Terminology_Models_ImagesModel::class);

        // Check current termcollection's images directory is writable, and if no - exception is thrown here
        $this->imagesModel->checkImageTermCollectionFolder($this->collection->getId());

        // Get [targetId => [..data..]] pairs records in terms_images table that are known for current collectionId
        $this->knownImageA = $this->imagesModel->getAllImagesByCollectionId($this->collection->getId());
    }

    /**
     * Imports the binary data refObject as images
     */
    public function import(SimpleXMLElement $refObjectList): void
    {
        // Foreach coming image
        /** @var SimpleXMLElement $refObject */
        foreach ($refObjectList as $refObject) {
            $this->importSingleImage($refObject);
        }

        // Get missing files
        $missingFiles = $this->imagesModel->purgeImageFiles(
            $this->collection->getId(),
            array_column($this->knownImageA, 'uniqueName')
        );

        // If there are images mentioned in db but missing on disk - log that as warning
        if (! empty($missingFiles)) {
            $this->logger->warn('E1028', 'TBX Import: there are image files in the database which are missing on the disk', [
                'termCollectionId' => $this->collection->getId(),
                'languageResource' => $this->collection,
                'missingFiles' => $missingFiles,
            ]);
        }
    }

    /**
     * Import single image from either SimpleXMLElement instance, or from string local path within extracted zip-archive
     */
    public function importSingleImage(SimpleXMLElement|string $from): bool
    {
        // Prepare record-data for coming image
        $coming = $this->makeImageData($from);
        $coming['uniqueName'] = $this->imagesModel->createUniqueName($coming['name']);

        // If image have no data
        if ($coming['data'] === false) {
            // Increment missing images counter
            $this->imageQty['missing']++;

            // Return
            return false;
        }

        // Add [targetId => true] pair to the list of pairs. This will be used in further step
        // (tbx-attributes processing step) to log warnings about missing image definitions
        $this->figureExists[$coming['targetId']] = true;

        // Get known image-record having such targetId, if possible
        $known = $this->knownImageA[$coming['targetId']] ?? null;

        // If coming image-record is found among known ones
        if ($known) {
            // If values of contentMd5hash-prop are as well equal for known and coming image
            // the only thing we need to do here is to check whether image file does really
            // exist on disk and if no - recreate it
            if ($known['contentMd5hash'] === $coming['contentMd5hash']) {
                // Get the absolute path to image
                $abs = $this->imagesModel->getImagePath($this->collection->getId(), $known['uniqueName']);

                // If image does not really exists on disk
                if (! file_exists($abs)) {
                    // Recreate on disk
                    $this->imagesModel->saveImageToDisk($this->collection->getId(), $known['uniqueName'], $coming['data']);

                    // Count that
                    $this->imageQty['recreated']++;

                    // Else increment unchanged-counter
                } else {
                    $this->imageQty['unchanged']++;
                }

                // Else if coming image-record is found among known, but we got new file
            } else {
                // Replace existing image-file with the new one
                $this->imagesModel->saveImageToDisk($this->collection->getId(), $coming['uniqueName'], $coming['data']);

                // Update known image record with values from coming image data
                $this->imagesModel->db->update([
                    'uniqueName' => $coming['uniqueName'],
                    'format' => $coming['format'],
                    'contentMd5hash' => $coming['contentMd5hash'],
                ], [
                    'id = ?' => $known['id'],
                ]);

                // Count that
                $this->imageQty['updated']++;
            }

            // Else if coming image was NOT found among known ones
        } else {
            // Save coming image file on disk
            $this->imagesModel->saveImageToDisk($this->collection->getId(), $coming['uniqueName'], $coming['data']);

            // Unset temporary 'data'-prop as it was needed for above call only but will produce sql-error if kept further
            unset($coming['data']);

            // Insert new terms_images-record
            $coming['id'] = $this->imagesModel->db->insert($coming);

            // Append coming image to the list of known ones
            $this->knownImageA[$coming['targetId']] = $coming;

            // Increment qty of images newly created on disk
            $this->imageQty['created']++;
        }

        //
        return true;
    }

    /**
     * Makes an array with props for terms_image-record out of the XML node or value of target-attr
     */
    protected function makeImageData(SimpleXMLElement|string $from): array
    {
        // Get target
        $targetId = $from instanceof SimpleXMLElement
            ? (string) $from->attributes()->id
            : $from;

        // Prepare data
        $image = [
            'id' => null,
            'targetId' => $targetId,
            'name' => null,
            'uniqueName' => null,
            'format' => null,
            'collectionId' => $this->collection->getId(),
            'contentMd5hash' => null,
        ];

        // If $from arg is string - assume it's a string value of target-attr
        // of <xref type="xGraphic" target="some/file.jpg"></xref>
        if (is_string($from)) {
            // Set props
            $image['name'] = pathinfo($from, PATHINFO_BASENAME);
            $path = $this->extractedZipDir . '/' . $from;
            $image['data'] = file_exists($path) ? file_get_contents($path) : false;
            $image['format'] = $image['data'] ? mime_content_type($path) : '';

            /**
             * Else if $from is a SimpleXMLElement and has itemSet-node, assume it looks like specified here
             * https://www.ttt.org/oscarStandards/tbx/tbx_oscar.pdf, point 11.3 (page 26), e.g:
             *
             * <itemSet>
             *   <itemGrp>
             *     <item>image.jpg</item>
             *     <xref target="some/path/inside/zip/image.jpg"/>
             *   </itemGrp>
             * </itemSet>
             */
        } elseif ($from->itemSet) {
            // Set props
            $image['name'] = (string) $from->itemSet->itemGrp->item;
            $path = $this->extractedZipDir . '/' . $from->itemSet->itemGrp->xref->attributes()->target;
            $image['data'] = file_exists($path) ? file_get_contents($path) : false;
            $image['format'] = $image['data'] ? mime_content_type($path) : '';

            /**
             * Else assume it looks like specified here:
             *
             * <item type="codePage">base64</item>
             * <item type="format">jpg</item>
             * <item type="data">base64-encoded data</item>
             */
        } else {
            // The image data is stored in multiple item tags with different types, read them out:
            $items = [];
            foreach ($from->item as $item) {
                $items[(string) $item->attributes()->type] = (string) $item;
            }

            // Setup name and encoding
            if (isset($items['encoding'])) {
                $image['name'] = $items['name'];
                $image['encoding'] = $items['encoding'];
            } else {
                $image['name'] = (string) $targetId . '.' . $items['format'];
                $image['encoding'] = $items['codePage']; //codepage never tested, overtaken from original code
            }

            // Pick format
            $image['format'] = $items['format'];

            // Drop spaces, if any
            $hexOrXbaseWithoutSpace = str_replace(' ', '', $items['data']);

            // If hex
            if ($image['encoding'] === 'hex') {
                // Convert the hex string to binary
                $image['data'] = hex2bin($hexOrXbaseWithoutSpace);

                // If doubleDecode flag is true - do one more decode
                if ($this->doubleDecode) {
                    $image['data'] = base64_decode($image['data']);
                }

                // Else
            } else {
                // Convert the base64 string to binary
                $image['data'] = base64_decode($hexOrXbaseWithoutSpace);
            }
        }

        // Get md5 hash from binary data
        $image['contentMd5hash'] = md5($image['data']);

        // Remove the encoding from the images array, it is used only for internal check
        unset($image['encoding']);

        // Return data for terms_images-record
        return $image;
    }
}
