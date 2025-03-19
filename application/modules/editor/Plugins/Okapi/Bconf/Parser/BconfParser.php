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

namespace MittagQI\Translate5\Plugins\Okapi\Bconf\Parser;

use editor_Plugins_Okapi_Init;
use MittagQI\Translate5\Plugins\Okapi\Bconf\BconfEntity;
use MittagQI\Translate5\Plugins\Okapi\Bconf\BconfInvalidException;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Content;
use MittagQI\Translate5\Plugins\Okapi\Bconf\ExtensionMapping;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Filter\Fprm;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Filter\PropertiesValidation;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Pipeline;
use MittagQI\Translate5\Plugins\Okapi\Bconf\RandomAccessFile;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Segmentation;
use Throwable;
use Zend_Exception;
use ZfExtended_Exception;

/**
 * Unpacks/Disassembles a bconf
 * Algorithmically a copy of the original JAVA implementation
 * By default, no files are written, just analyzed
 * If a concrete bconf is available, the neccessary parts are extracted as files to the bconf-data-dir
 */
abstract class BconfParser
{
    // currently unused but extracted from the RAINBOW Java code, so we keep it for info
    // TODO FIXME: Use in Pipeline & Implement as Step-Classes
    public const STEP_REFERENCES = [
        'SegmentationStep' => ['SourceSrxPath', 'TargetSrxPath'],
        'TermExtractionStep' => ['StopWordsPath', 'NotStartWordsPath', 'NotEndWordsPath'],
        'XMLValidationStep' => ['SchemaPath'],
        'XSLTransformStep' => ['XsltPath'],
    ];

    protected string $folder;

    protected ?RandomAccessFile $raf;

    protected BconfEntity $bconf;

    protected string $bconfName;

    protected bool $hasBconf;

    /**
     * Holdes the list of embedded filters / fprms
     */
    protected array $embeddedFilters;

    protected ExtensionMappingParser $mapping;

    protected bool $doDebug;

    /**
     * Import bconf
     * Extracts the parts of a given bconf file in the order
     * 1) plug-ins
     * 2) references data
     * 3) pipeline
     * 4) filter configurations
     * 5) extensions -> filter configuration id mapping
     * If $doUpgrade is set, the Property-based FPRMS are automatically upgraded
     *
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws BconfInvalidException
     */
    public function process(string $bconfPath, bool $doUpgrade = false): void
    {
        // we must catch all exceptions of the RandomAccessFile to be able to release the file-pointer properly!
        try {
            // DEBUG
            if ($this->doDebug) {
                error_log('UNPACK BCONF: ' . $this->bconfName);
            }

            $this->raf = new RandomAccessFile($bconfPath, 'rb');
            $sig = $this->raf->readUTF();
            if ($sig !== BconfEntity::SIGNATURE) {
                throw new BconfInvalidException(
                    "Invalid signature '" . htmlspecialchars($sig) . "' in file header before byte "
                    . $this->raf->ftell() . ". Must be '" . BconfEntity::SIGNATURE . "'"
                );
            }
            $version = $this->raf->readInt();
            if (! ($version >= 1 && $version <= BconfEntity::VERSION)) {
                throw new BconfInvalidException(
                    "Invalid version '$version' in file header before byte " . $this->raf->ftell()
                    . '. Must be in range 1-' . BconfEntity::VERSION
                );
            }

            $referencedFiles = []; // stores the referenced files we write to disk

            //=== Section 1: plug-ins: Currently not further processed

            if ($version > 1) { // Remain compatible with v.1 bconf files
                $numPlugins = $this->raf->readInt();
                for ($i = 0; $i < $numPlugins; $i++) {
                    $file = $this->raf->readUTF();
                    $this->raf->readInt(); // Skip ID
                    $this->raf->readUTF(); // Skip original full filename
                    // QUIRK: this value is encoded as BIG ENDIAN long long in the bconf.
                    // En/Decoding of 64 byte values creates Exceptions on 32bit OS,
                    // so we read 2 32bit Ints here (limiting the decodable size to 4GB...)
                    $this->raf->readInt();
                    $size = $this->raf->readInt();
                    // extracts the harvested file to disk - if we have a concrete bconf
                    $this->createReferencedFile($size, $file);
                    $referencedFiles[] = basename($file); // can the read filename contain pathes ?
                }
            }

            //=== Section 2: Read contained reference files

            $refMap = []; // numeric indices are read from the bconf, starting with 1

            while (($refIndex = $this->raf->readInt()) != -1 && ! is_null($refIndex)) {
                $file = $refMap[$refIndex] = $this->raf->readUTF();
                // Skip over the data to move to the next reference
                // QUIRK: this value is encoded as BIG ENDIAN long long in the bconf.
                // En/Decoding of 64 byte values creates Exceptions on 32bit OS,
                // so we read 2 32bit Ints here (limiting the decodable size to 4GB...)
                $this->raf->readInt();
                $size = $this->raf->readInt();
                if ($size > 0) {
                    // extracts the harvested file to disk - if we have a concrete bconf
                    // this is a bit of unneccessary, we save the srx first and then exchange it if it is a standard T5 srx
                    $this->createReferencedFile($size, $file);
                    $referencedFiles[] = basename($file); // can the read filename contain pathes ?
                }
            }

            if ($refIndex === null) {
                throw new BconfInvalidException(
                    'Malformed references list. Read null instead of integer before byte ' . $this->raf->ftell()
                );
            }
            if (($refCount = count($refMap)) < 2) {
                throw new BconfInvalidException(
                    "Only $refCount references included. Need sourceSRX and targetSRX."
                );
            }

            //=== Section 3 : the pipeline itself
            $xmlWordCount = $this->raf->readInt();
            $pipelineXml = '';
            for ($i = 0; $i < $xmlWordCount; $i++) {
                $pipelineXml .= $this->raf->readUTF();
            }

            // writing pipeline & content - if we have a concrete bconf
            if ($this->hasBconf) {
                // create & validate the pipeline
                $pipeline = new Pipeline($this->bconf->getPipelinePath(), trim($pipelineXml), (int) $this->bconf->getId());
                if (! $pipeline->validate(true)) {
                    throw new BconfInvalidException('Invalid Pipeline: ' . $pipeline->getValidationError());
                } else {
                    // the piplene is only valid if the contained SRX files have been saved to disk
                    if (! in_array($pipeline->getSrxFile('source'), $referencedFiles)) {
                        throw new BconfInvalidException(
                            'Invalid Pipeline: The given source-srx file was not embedded in the bconf.'
                        );
                    }
                    if (! in_array($pipeline->getSrxFile('target'), $referencedFiles)) {
                        throw new BconfInvalidException(
                            'Invalid Pipeline: The given target-srx file was not embedded in the bconf.'
                        );
                    }
                }
                // if the embedded SRX files are outdated T5 default SRX files
                // this will trigger updating them to current revisions
                Segmentation::instance()->onUnpack($pipeline, $this->folder);
                // save the pipeline to disk
                $pipeline->flush();
                // transfer parsed props/references
                $content = new Content($this->bconf->getContentPath(), null, (int) $this->bconf->getId(), true);
                $content->setSteps($pipeline->getSteps());
                $content->setSrxFile('source', $pipeline->getSrxFile('source'));
                $content->setSrxFile('target', $pipeline->getSrxFile('target'));

                $pipelineSrc = editor_Plugins_Okapi_Init::getDataDir() . 'pipeline/translate5-merging.pln';
                $pipelineDest = $this->bconf->getDataDirectory() . '/export-pipeline.pln';
                copy($pipelineSrc, $pipelineDest);
            }

            $startFilterConfigs = $this->raf->ftell();
            //=== Section 4 : the filter configurations
            $this->raf->fseek($startFilterConfigs);
            // Get the number of filter configurations
            $count = $this->raf->readInt();

            // needed for data-exchange in the processing API in ExtensionMapping
            $replacementMap = [];
            $customFilters = [];
            $this->embeddedFilters = [];

            // Read each one
            for ($i = 0; $i < $count; $i++) {
                $identifier = $this->raf->readUTF();
                $data = $this->raf->readUTF();

                $this->embeddedFilters[] = $identifier;
                if ($this->hasBconf) {
                    // save the fprm if it points to a valid custom identifier/filter
                    try {
                        if (ExtensionMapping::processUnpackedFilter($this->bconf, $identifier, $data, $replacementMap, $customFilters)) {
                            $content->addFilter($identifier);
                            // if wanted, we upgrade the the FPRMs (currently only properties-based ones)
                            if ($doUpgrade) {
                                $fprmFile = $this->folder . '/' . $identifier . '.fprm';
                                $fprm = new Fprm($fprmFile);
                                if ($fprm->getType() === Fprm::TYPE_XPROPERTIES) {
                                    $validation = new PropertiesValidation($fprmFile, $fprm->getContent());
                                    $validation->upgrade();
                                    if ($validation->validate()) {
                                        $validation->flush();
                                        if ($this->doDebug) {
                                            error_log('UPGRADED FPRM: ' . basename($fprmFile));
                                        }
                                    } else {
                                        throw new BconfInvalidException(
                                            'Invalid FPRM: The embedded FPRM "' . basename($fprmFile) . '" seems invalid.'
                                        );
                                    }
                                }
                            }
                        }
                    } catch (Throwable $e) {
                        throw new BconfInvalidException($e->getMessage());
                    }
                }
            }
            // DEBUG
            if ($this->doDebug) {
                if ($this->hasBconf) {
                    error_log('UNPACK CUSTOM FILTERS: ' . print_r($customFilters, true));
                    error_log('UNPACK REPLACEMENT folder MAP: ' . print_r($replacementMap, true));
                } else {
                    error_log('EMBEDDED FILTERS: ' . implode(', ', $this->embeddedFilters));
                }
            }

            //=== Section 5: the extensions -> filter configuration id mapping
            $count = $this->raf->readInt();
            if (! $count) {
                throw new BconfInvalidException('No extensions-mapping present in bconf.');
            }
            $rawMap = [];
            for ($i = 0; $i < $count; $i++) {
                $rawMap[] = [$this->raf->readUTF(), $this->raf->readUTF()];
            }

            if ($this->hasBconf) {
                // the extension-mapping will validate the raw data and applies any adjustments
                // cached in the replacement-map
                $this->mapping = new ExtensionMapping($this->bconf, $rawMap, $replacementMap);
                // this saves the custom-filters as entries to the DB and writes the mapping-file
                $this->mapping->flushUnpacked($customFilters);

                // last thing to do: save our inventory/TOC
                $content->flush();
            } else {
                $this->mapping = new ExtensionMappingParser($rawMap);
            }

            // explicitly close file-pointer
            $this->raf = null;

            // DEBUG
            if ($this->doDebug) {
                error_log('UNPACKED MAP: ' . "\n" . print_r($this->mapping->getMap(), true));
            }
        } catch (Throwable $e) {
            $this->raf = null;

            throw $e;
        }
    }

    /**
     * Retrieves the extension-mapping of the parsed bconf
     */
    public function getExtensionMapping(): ExtensionMappingParser
    {
        return $this->mapping;
    }

    /**
     * Retrieves the filters embedded in the parsed bconf
     */
    public function getEmbeddedFilters(): array
    {
        return $this->embeddedFilters;
    }

    /**
     * Extracts the passed file to disk - if we have a bconf - or just reads it to move the RandomAccessFile pointer
     * @throws BconfInvalidException
     */
    protected function createReferencedFile(int $size, string $file): void
    {
        if ($this->hasBconf) {
            $this->writeReferencedFile($size, $file);
        } else {
            // just move the pointer
            $this->raf->fread($size);
        }
    }

    /**
     * @throws BconfInvalidException
     */
    private function writeReferencedFile(int $size, string $file): void
    {
        $fos = fopen($this->folder . '/' . basename($file), 'wb');
        if (! $fos) {
            throw new BconfInvalidException('Unable to open file ' . $file);
        }
        // TODO FIXME: when stream_copy_to_stream supports SplFileObjects use that
        // $written = stream_copy_to_stream($this->raf->getFp(), $fos, $size);

        $written = 0;
        $toWrite = $size;
        $buffer = min(65536, $toWrite); // 16 pages à 4K
        while ($toWrite > $buffer) {
            $bytes = fwrite($fos, $this->raf->fread($buffer));
            if ($bytes === false) {
                throw new BconfInvalidException('Could not write to ' . $file);
            } else {
                $written += $bytes;
                $toWrite -= $buffer;
            }
        }
        $written += fwrite($fos, $this->raf->fread($toWrite));
        fclose($fos);
        if ($written !== $size) {
            throw new BconfInvalidException('Could only write ' . $written . ' bytes of ' . $size . ' to ' . $file);
        }
    }
}
