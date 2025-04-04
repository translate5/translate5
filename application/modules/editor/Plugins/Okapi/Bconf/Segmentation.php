<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Plugins\Okapi\Bconf;

use editor_Models_ConfigException;
use editor_Utils;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Segmentation\Srx;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Segmentation\T5SrxInventory;
use MittagQI\Translate5\Plugins\Okapi\OkapiException;
use MittagQI\ZfExtended\MismatchException;
use stdClass;
use ZfExtended_Debug;
use ZfExtended_Exception;

/**
 * Class processing SRX files on import and repacking outdated bconfs,
 * also the upload/processing of SRX files is covered here
 *
 * Updating / Identifying translate5 adjusted SRX files:
 * translate 5 holds a set of adjusted SRX files, they are stored in .../Plugins/Okapi/data/srx/translate5/
 * When a bconf is packed, it will be checked, if the referenced SRX is a translate5 default SRX, in this case,
 * the most recent version is taken/updated.
 * This comparision is done by md5 hashing of the SRX file, there is no database-based data as with FPRMs
 *
 * Validating SRX files:
 * Since we currently cannot validate the rules in a SRX file we validate a SRX by using the packed BCONF against a testfile.
 * This is done with Bconf\Validation
 */
final class Segmentation
{
    public const NAME_MAXLENGTH = 25;

    private static ?Segmentation $_instance = null;

    /**
     * Classic Singleton
     */
    public static function instance(): Segmentation
    {
        if (self::$_instance == null) {
            self::$_instance = new Segmentation();
        }

        return self::$_instance;
    }

    private bool $doDebug;

    private T5SrxInventory $t5inventory;

    private function __construct()
    {
        $this->doDebug = ZfExtended_Debug::hasLevel('plugin', 'OkapiBconfProcessing');
        $this->t5inventory = T5SrxInventory::instance();
    }

    /**
     * Updates the SRX files to the current version in an unpacking action
     * Triggered by unpacking a bconf
     * @throws ZfExtended_Exception
     * @throws editor_Models_ConfigException
     * @throws OkapiException
     */
    public function onUnpack(Pipeline $pipeline, string $folderPath): void
    {
        $this->updateSrx($folderPath . '/' . $pipeline->getSrxFile('source'), 'unpack');
        // we only need to process the target if it is not identical to the source (what is the default structure9
        if (! $pipeline->hasIdenticalSourceAndTargetSrx()) {
            $this->updateSrx($folderPath . '/' . $pipeline->getSrxFile('target'), 'unpack');
        }
    }

    /**
     * Updates a SRX to the current version in an re-packing action
     * Triggered by re-packing an outdated bconf
     * @throws ZfExtended_Exception
     * @throws editor_Models_ConfigException
     * @throws OkapiException
     */
    public function onRepack(string $path): void
    {
        $this->updateSrx($path, 'repack');
    }

    /**
     * Processes an SRX upload. This action has a lot of challenges:
     * - The sent SRX is a (maybe outdated) T5 default SRX and needs to be updated. The naming then will be the default
     * - the sent name is a real custom srx and we need to validate it & change the name in the related files
     * @throws MismatchException
     * @throws ZfExtended_Exception
     * @throws \Zend_Exception
     * @throws \ZfExtended_UnprocessableEntity
     * @throws OkapiException
     */
    public function processUpload(BconfEntity $bconf, string $field, string $uploadPath, string $uploadName): void
    {
        if ($field !== 'source' && $field !== 'target') {
            throw new MismatchException('E2004', [$field, 'field']);
        }
        // evaluate what's the other field
        $otherField = ($field == 'source') ? 'target' : 'source';
        // we need both SRXs, the pipeline & the content object
        $srx = $bconf->getSrx($field);
        $otherSrx = $bconf->getSrx($otherField);
        $pipeline = $bconf->getPipeline();
        $content = $bconf->getContent();
        // set the srx-content from the upload and validate it
        $srx->setContent(file_get_contents($uploadPath));
        if ($srx->validate()) {
            // if the SRX is a translate5 default SRX, we need no further validation
            if ($this->isDefaultSrx($srx)) {
                // the isDefaultSrx call will update the content to the current revision if it is a default SRX
                // DEBUG
                if ($this->doDebug) {
                    error_log('SRX UPLOAD: The sent ' . $field . '-SRX ' . $uploadName
                        . ' is a translate5 default SRX');
                }
                // if both srx's are identical, we copy the name/path over
                if ($srx->getHash() === $otherSrx->getHash()) {
                    $srx->setPath($otherSrx->getPath());
                    // DEBUG
                    if ($this->doDebug) {
                        error_log('SRX UPLOAD: The sent default ' . $field
                            . '-SRX matches the other SRX and thus the files will be identical');
                    }
                } else {
                    $fileName = 'languages-' . $field . '.' . Srx::EXTENSION;
                    $srx->setPath($bconf->createPath($fileName));
                    if ($otherSrx->getPath() === $srx->getPath()) {
                        // the almost impossible case: the target srx is called "languages-source" (or vice versa)
                        $fileName = 'languages-' . $otherField . '.' . Srx::EXTENSION;
                        $otherSrx->setPath($bconf->createPath($fileName));
                        $otherSrx->flush();
                    }
                    // DEBUG
                    if ($this->doDebug) {
                        error_log('SRX UPLOAD: The sent default SRX is unique in the bconf.'
                            . 'The new names are as follows: ' . $field . ': ' . $srx->getFile() . ', '
                            . $otherField . ': ' . $otherSrx->getFile());
                    }
                }
                $srx->flush();
                $this->updateSrxInFiles($pipeline, $content, $field, $srx, $otherField, $otherSrx);
                $bconf->pack();
            } else {
                // real custom SRX uploads must be validated with OKAPI
                $srxOriginalPath = $srx->getPath();
                $otherSrxOriginalPath = $otherSrx->getPath();
                $customFile = $this->createCustomFile($uploadName, $field, $otherField);
                $srx->setPath($bconf->createPath($customFile));
                // DEBUG
                if ($this->doDebug) {
                    error_log('SRX UPLOAD: The sent ' . $field . '-SRX ' . $uploadName
                        . ' is a customized SRX and the filename will be ' . $customFile);
                }

                if ($otherSrx->getPath() === $srx->getPath()) {
                    // another almost impossible case: custom name equals the other srx
                    $customFile = pathinfo($customFile, PATHINFO_FILENAME) . '-' . $field . '.' . Srx::EXTENSION;
                    $srx->setPath($bconf->createPath($customFile));
                    // DEBUG
                    if ($this->doDebug) {
                        error_log('SRX UPLOAD: The ' . $field . '-SRX new filename needs to be adjusted to '
                            . $customFile . ' because it matches the ' . $otherField . '-SRX');
                    }
                }
                // create backups
                if ($srxOriginalPath !== $otherSrxOriginalPath) {
                    // when pathes are identical the other SRX is our backup
                    rename($srxOriginalPath, $srxOriginalPath . '.bu');
                }
                rename($pipeline->getPath(), $pipeline->getPath() . '.bu');
                rename($content->getPath(), $content->getPath() . '.bu');
                // write the uploaded srx to disk
                $srx->flush();
                // update the dependencies to disk
                $this->updateSrxInFiles($pipeline, $content, $field, $srx, $otherField, $otherSrx);
                // pack the bconf
                $bconf->pack();
                // validate the bconf by testing it with okapi
                $bconfValidationError = $bconf->validate();
                if ($bconfValidationError !== null) {
                    // DEBUG
                    if ($this->doDebug) {
                        error_log('SRX UPLOAD FAILED: Validation of ' . $field
                            . '-SRX unsuccessful. Validation-error: ' . $bconfValidationError);
                    }
                    // restore backups
                    unlink($srx->getPath());
                    if ($srxOriginalPath !== $otherSrxOriginalPath) {
                        // when pathes are identical the other SRX is our backup
                        rename($srxOriginalPath . '.bu', $srxOriginalPath);
                    }
                    unlink($pipeline->getPath());
                    rename($pipeline->getPath() . '.bu', $pipeline->getPath());
                    unlink($content->getPath());
                    rename($content->getPath() . '.bu', $content->getPath());
                    $bconf->pack();
                    $bconf->invalidateCaches(); // invalidate the cached files, we changed the underlying files ...

                    throw new OkapiException('E1390', [
                        'filename' => $uploadName,
                        'details' => $bconfValidationError,
                    ]);
                } else {
                    // DEBUG
                    if ($this->doDebug) {
                        error_log('SRX UPLOAD: Validation of ' . $field . '-SRX successful');
                    }
                    // cleanup: remove backup files
                    if ($srxOriginalPath !== $otherSrxOriginalPath) {
                        // when pathes are identical the other SRX is our backup
                        unlink($srxOriginalPath . '.bu');
                    }
                    unlink($pipeline->getPath() . '.bu');
                    unlink($content->getPath() . '.bu');
                }
            }
        } else {
            throw new OkapiException('E1390', [
                'filename' => $uploadName,
                'details' => $srx->getValidationError(),
            ]);
        }
    }

    /**
     * Evaluates, if the passed SRX is a default SRX. If so, the SRXs content is updated to the new version
     * @throws editor_Models_ConfigException
     * @throws OkapiException
     */
    public function isDefaultSrx(Srx $srx): bool
    {
        $data = $this->findCurrentReplacementData($srx->getHash());
        if ($data->path == null || ! $data->match) {
            return false;
        }
        if ($data->update) {
            // DEBUG
            if ($this->doDebug) {
                error_log('SRX SEGMENTATION: updated default SRX ' . $srx->getFile()
                    . ' to the current version ' . basename($data->path));
            }
            $srx->setContent(file_get_contents($data->path));
        }

        return true;
    }

    /**
     * @throws ZfExtended_Exception
     * @throws editor_Models_ConfigException
     * @throws OkapiException
     */
    private function updateSrx(string $path, string $action): void
    {
        $content = file_get_contents($path);
        if ($content === false) {
            throw new ZfExtended_Exception('Can not read SRX file from path ' . $path);
        }
        $hash = ResourceFile::createHash($content);
        $data = $this->findCurrentReplacementData($hash);
        if ($data->path != null && $data->match && $data->update) {
            // simple overwrite in the file-system
            unlink($path);
            copy($data->path, $path);
            // DEBUG
            if ($this->doDebug) {
                error_log('SRX SEGMENTATION: ' . $action . ': updated ' . basename($path)
                    . ' to the current version ' . basename($data->path));
            }
        } else {
            // DEBUG
            if ($this->doDebug) {
                error_log('SRX SEGMENTATION: ' . $action . ': no need to update ' . basename($path)
                    . ', its either custom or current');
            }
        }
    }

    /**
     * Finds the current SRX to replace the one with the passed hash
     * Returns no path either if the passed hash is actual or if it was not found
     * @throws editor_Models_ConfigException
     * @throws OkapiException
     */
    private function findCurrentReplacementData(string $hash): stdClass
    {
        $data = new stdClass();
        $data->match = false;
        $data->update = false;
        $data->path = null;
        $matchingItem = $this->t5inventory->findByHash($hash);
        if ($matchingItem != null) {
            $data->match = true;
            $currentItem = $this->t5inventory->findCurrent();
            if ($currentItem->version > $matchingItem->version) {
                $data->update = true;
                $data->field = ($matchingItem->sourceHash === $hash) ? 'source' : 'target';
                $data->path = $this->t5inventory->createSrxPath($currentItem, $data->field);
            }
        }

        return $data;
    }

    /**
     * Creates the name to be used in case a srx was upladed and both SRX's have the same name - what is the normal case
     * The sent name may is a download-filename generated by T% which looks like $bconfName.'-translate-'.$variant;
     */
    private function createCustomFile(string $sentName, string $field, string $otherField): string
    {
        $newName = pathinfo($sentName, PATHINFO_FILENAME);
        $newName = editor_Utils::filenameFromUploadName($newName);
        if (str_contains($newName, 'languages')) {
            $parts = explode('languages', $newName);
            $newName = trim('languages-' . trim($parts[1], '-_'), '-');
            if (strlen($newName) > self::NAME_MAXLENGTH) {
                $newName = trim(substr($newName, 0, self::NAME_MAXLENGTH), '-_');
            }
        } else {
            $newName = trim(substr('languages-' . trim($newName, '-_'), 0, self::NAME_MAXLENGTH), '-_');
        }
        if ($newName === 'languages-' . $otherField || $newName === 'languages') {
            $newName = 'languages-' . $field;
        }

        return $newName . '.' . Srx::EXTENSION;
    }

    /**
     * @throws ZfExtended_Exception
     */
    private function updateSrxInFiles(
        Pipeline $pipeline,
        Content $content,
        string $field,
        Srx $srx,
        string $otherField,
        Srx $otherSrx
    ): void {
        $pipeline->setSrxFile($field, $srx->getFile());
        $pipeline->setSrxFile($otherField, $otherSrx->getFile());
        $pipeline->flush();
        $content->setSrxFile($field, $srx->getFile());
        $content->setSrxFile($otherField, $otherSrx->getFile());
        $content->flush();
    }
}
