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

/**
 * Unpacks/Disassembles a bconf
 * Algorithmically a copy of the original JAVA implementation
 */
final class editor_Plugins_Okapi_Bconf_Unpacker {

    // currently unused but extracted from the RAINBOW Java code, so we keep it for info
    public const STEP_REFERENCES = [
        'SegmentationStep'   => ['SourceSrxPath', 'TargetSrxPath'],
        'TermExtractionStep' => ['StopWordsPath', 'NotStartWordsPath', 'NotEndWordsPath'],
        'XMLValidationStep'  => ['SchemaPath'],
        'XSLTransformStep'   => ['XsltPath'],
    ];

    /**
     * @var editor_Plugins_Okapi_Bconf_Entity
     */
    private editor_Plugins_Okapi_Bconf_Entity $bconf;
    /**
     * @var string
     */
    private string $folder;
    /**
     * @var editor_Plugins_Okapi_Bconf_RandomAccessFile
     */
    private editor_Plugins_Okapi_Bconf_RandomAccessFile $raf;
    /**
     * @var bool
     */
    private bool $doDebug;
    
    public function __construct(editor_Plugins_Okapi_Bconf_Entity $bconf){
        $this->bconf = $bconf;
        $this->folder = $this->bconf->getDataDirectory();
        $this->doDebug = ZfExtended_Debug::hasLevel('plugin', 'OkapiBconfPackUnpack');
    }

    /**
     * Import bconf
     * Extracts the parts of a given bconf file in the order
     * 1) plug-ins
     * 2) references data
     * 3) pipeline
     * 4) filter configurations
     * 5) extensions -> filter configuration id mapping
     *
     * @throws ZfExtended_Exception
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Plugins_Okapi_Exception
     */
    public function process($bconfPath): void {

        // DEBUG
        if($this->doDebug){ error_log('UNPACK BCONF: '.$this->bconf->getName()); }

        $this->raf = new editor_Plugins_Okapi_Bconf_RandomAccessFile($bconfPath, 'rb');
        $sig = $this->raf->readUTF();
        if($sig !== editor_Plugins_Okapi_Bconf_Entity::SIGNATURE){
            throw new editor_Plugins_Okapi_Bconf_InvalidException("Invalid signature '".htmlspecialchars($sig)."' in file header before byte ".$this->raf->ftell().". Must be '".editor_Plugins_Okapi_Bconf_Entity::SIGNATURE."'");
        }
        $version = $this->raf->readInt();
        if(!($version >= 1 && $version <= editor_Plugins_Okapi_Bconf_Entity::VERSION)){
            throw new editor_Plugins_Okapi_Bconf_InvalidException("Invalid version '$version' in file header before byte ".$this->raf->ftell().'. Must be in range 1-'.editor_Plugins_Okapi_Bconf_Entity::VERSION);
        }

        $referencedFiles = []; // stores the referenced files we write to disk

        //=== Section 1: plug-ins: Currently not further processed
        if($version > 1){ // Remain compatible with v.1 bconf files

            $numPlugins = $this->raf->readInt();
            for($i = 0; $i < $numPlugins; $i++){
                $file = $this->raf->readUTF();
                $this->raf->readInt(); // Skip ID
                $this->raf->readUTF(); // Skip original full filename
                // QUIRK: this value is encoded as BIG ENDIAN long long in the bconf. En/Decoding of 64 byte values creates Exceptions on 32bit OS, so we read 2 32bit Ints here (limiting the decodable size to 4GB...)
                $this->raf->readInt();
                $size = $this->raf->readInt();
                $this->createReferencedFile($size, $file);
                $referencedFiles[] = basename($file); // can the read filename contain pathes ?
            }
        }

        //=== Section 2: Read contained reference files

        $refMap = []; // numeric indices are read from the bconf, starting with 1

        while(($refIndex = $this->raf->readInt()) != -1 && !is_null($refIndex)) {
            $file = $refMap[$refIndex] = $this->raf->readUTF();
            // Skip over the data to move to the next reference
            // QUIRK: this value is encoded as BIG ENDIAN long long in the bconf. En/Decoding of 64 byte values creates Exceptions on 32bit OS, so we read 2 32bit Ints here (limiting the decodable size to 4GB...)
            $this->raf->readInt();
            $size = $this->raf->readInt();
            if($size > 0){
                // this is a bit of unneccessary, we save the srx first and then exchange it if it is a standard T5 srx
                $this->createReferencedFile($size, $file);
                $referencedFiles[] = basename($file); // can the read filename contain pathes ?
            }
        }

        if($refIndex === NULL){
            throw new editor_Plugins_Okapi_Bconf_InvalidException('Malformed references list. Read NULL instead of integer before byte ' . $this->raf->ftell());
        }
        if(($refCount = count($refMap)) < 2){
            throw new editor_Plugins_Okapi_Bconf_InvalidException("Only $refCount references included. Need sourceSRX and targetSRX.");
        }

        //=== Section 3 : the pipeline itself
        $xmlWordCount = $this->raf->readInt();
        $pipelineXml = '';
        for($i = 0; $i < $xmlWordCount; $i++){
            $pipelineXml .= $this->raf->readUTF();
        }
        // create & validate the pipeline
        $pipeline = new editor_Plugins_Okapi_Bconf_Pipeline($this->bconf->getPipelinePath(), trim($pipelineXml), $this->bconf->getId());
        if(!$pipeline->validate(true)){
            throw new editor_Plugins_Okapi_Bconf_InvalidException('Invalid Pipeline: '.$pipeline->getValidationError());
        } else {
            // the piplene is only valid if the contained SRX files have been saved to disk
            if(!in_array($pipeline->getSrxFile('source'), $referencedFiles)){
                throw new editor_Plugins_Okapi_Bconf_InvalidException('Invalid Pipeline: the given source-srx file was not embedded in the bconf.');
            }
            if(!in_array($pipeline->getSrxFile('target'), $referencedFiles)){
                throw new editor_Plugins_Okapi_Bconf_InvalidException('Invalid Pipeline: the given target-srx file was not embedded in the bconf.');
            }
        }
        // if the embedded SRX files are outdated T5 default SRX files this will trigger updating them to current revisions
        editor_Plugins_Okapi_Bconf_Segmentation::instance()->onUnpack($pipeline, $this->folder);
        // save the pipeline to disk
        $pipeline->flush();
        // transfer parsed props/references
        $content = new editor_Plugins_Okapi_Bconf_Content($this->bconf->getContentPath(), NULL, $this->bconf->getId(), true);
        $content->setSteps($pipeline->getSteps());
        $content->setSrxFile('source', $pipeline->getSrxFile('source'));
        $content->setSrxFile('target', $pipeline->getSrxFile('target'));

        $startFilterConfigs = $this->raf->ftell();
        //=== Section 4 : the filter configurations
        $this->raf->fseek($startFilterConfigs);
        // Get the number of filter configurations
        $count = $this->raf->readInt();

        // needed for data-exchange in the processing API in editor_Plugins_Okapi_Bconf_ExtensionMapping
        $replacementMap = [];
        $customFilters = [];

        // Read each one
        for($i = 0; $i < $count; $i++){
            $identifier = $this->raf->readUTF();
            $data = $this->raf->readUTF();
            // save the fprm if it points to a valid custom identifier/filter
            try {
                if(editor_Plugins_Okapi_Bconf_ExtensionMapping::processUnpackedFilter($this->bconf, $identifier, $data, $replacementMap, $customFilters)){
                    $content->addFilter($identifier);
                }
            } catch (Exception $e){
                throw new editor_Plugins_Okapi_Bconf_InvalidException($e->getMessage());
            }
        }
        // DEBUG
        if($this->doDebug){
            error_log('UNPACK CUSTOM FILTERS: '.print_r($customFilters, 1));
            error_log('UNPACK REPLACEMENT folder MAP: '.print_r($replacementMap, 1));
        }

        //=== Section 5: the extensions -> filter configuration id mapping
        $count = $this->raf->readInt();
        if(!$count){
            throw new editor_Plugins_Okapi_Bconf_InvalidException('No extensions-mapping present in bconf.');
        }
        $rawMap = [];
        for($i = 0; $i < $count; $i++){
            $rawMap[] = [ $this->raf->readUTF(), $this->raf->readUTF() ];
        }
        // the extension-mapping will validate the raw data and applies any adjustments cached in the replacement-map
        $extensionMapping = new editor_Plugins_Okapi_Bconf_ExtensionMapping($this->bconf, $rawMap, $replacementMap);
        // this saves the custom-filters as entries to the DB and writes the mapping-file
        $extensionMapping->flushUnpacked($customFilters);

        // last thing to do: save our inventory/TOC
        $content->flush();

        // DEBUG
        if($this->doDebug) { error_log('UNPACKED MAP: '."\n".print_r($extensionMapping->getMap(), 1)); }
    }

    /**
     * @param int $size
     * @param string $file
     * @throws editor_Plugins_Okapi_Bconf_InvalidException
     */
    private function createReferencedFile(int $size, string $file): void {
        /** @var resource $fos file output stream */
        $fos = fopen($this->folder.'/'.$file, 'wb');
        if($fos === false){
            throw new editor_Plugins_Okapi_Bconf_InvalidException('Unable to open file '.$file);
        }
        // TODO FIXME: when stream_copy_to_stream supports SplFileObjects use that
        // $written = stream_copy_to_stream($this->raf->getFp(), $fos, $size);

        /** @var int|bool $written */
        $written = 0;
        $toWrite = $size;
        $buffer = min(65536, $toWrite); // 16 pages à 4K
        while($toWrite > $buffer && $written !== false) {
            $written += fwrite($fos, $this->raf->fread($buffer));
            $toWrite -= $buffer;
        }
        $written += fwrite($fos, $this->raf->fread($toWrite));
        fclose($fos);
        if($written !== $size){
            throw new editor_Plugins_Okapi_Bconf_InvalidException('Could ' . ($written !== false ? "only write $written bytes of " : 'not write').' '.$file);
        }
    }
}
