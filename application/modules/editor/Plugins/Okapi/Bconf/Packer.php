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
 * Packs/Assembles a bconf
 * Algorithmically a copy of the original JAVA implementation
 */
final class editor_Plugins_Okapi_Bconf_Packer {

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
     * @param bool $isOutdatedRepack
     * @throws ZfExtended_Exception
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Plugins_Okapi_Exception
     */
    public function process(bool $isOutdatedRepack): void {

        // DEBUG
        if($this->doDebug){ error_log('PACK BCONF: '.$this->bconf->getName()); }

        // so we can access all files in the bconf's data-dir with file name only
        chdir($this->folder);
        $fileName = basename($this->bconf->getPath());
        $this->raf = new editor_Plugins_Okapi_Bconf_RandomAccessFile($fileName, 'wb');

        $this->raf->writeUTF(editor_Plugins_Okapi_Bconf_Entity::SIGNATURE, false);
        $this->raf->writeInt(editor_Plugins_Okapi_Bconf_Entity::VERSION);
        // TODO BCONF: currently plugins are not supported
        $this->raf->writeInt(editor_Plugins_Okapi_Bconf_Entity::NUM_PLUGINS);

        $content = $this->bconf->getContent();
        $pipeline = $this->bconf->getPipeline();
        $this->harvestReferencedFile(1, $content->getSrxFile('source'), $isOutdatedRepack);
        $this->harvestReferencedFile(2, $content->getSrxFile('target'), $isOutdatedRepack);
        // Last ID=-1 to mark no more references
        $this->raf->writeInt(-1);
        $this->raf->writeInt(1);
        $this->raf->writeUTF($pipeline->getContent(), false);
        // process filters & extension mapping
        $customIdentifiers = [];
        foreach($this->bconf->getCustomFilterData() as $filterData){
            $customIdentifiers[] = editor_Plugins_Okapi_Bconf_Filters::createIdentifier($filterData['okapiType'], $filterData['okapiId']);
        }
        // DEBUG
        if($this->doDebug) { error_log('PACKED CUSTOM FILTERS: '."\n".implode(', ', $customIdentifiers)); }

        // instantiate the extension mapping and evaluate the additional default okapi and translate5 filter files (this needs to know the "real" custom filters
        $extensionMapping = $this->bconf->getExtensionMapping();
        $extensionMapData = $extensionMapping->getMapForPacking($customIdentifiers);
        $defaultFilterFiles = $extensionMapping->getOkapiDefaultFprmsForPacking($customIdentifiers); // retrieves an array of pathes !

        // DEBUG
        if($this->doDebug){
            error_log('PACKED DEFAULT FILTERS: '."\n".print_r($defaultFilterFiles, 1));
            error_log('PACKED EXTENSION MAPPING: '."\n".print_r($extensionMapData, 1));
        }
        $numAllEmbeddedFilters = count($customIdentifiers) + count($defaultFilterFiles);
        // write number of embedded filters
        $this->raf->writeInt($numAllEmbeddedFilters);
        foreach($customIdentifiers as $identifier){
            // we are already in the bconf's dir, so we can reference custom filters by filename only
            $this->writeFprm($identifier, $identifier.'.'.editor_Plugins_Okapi_Bconf_Filter_Entity::EXTENSION);
        }
        foreach($defaultFilterFiles as $identifier => $path){
            // the static default filters will be added with explicit settings, These are either OKAPI defaults or translate5 adjusted defaults
            $this->writeFprm($identifier, $path);
        }
        // write the adjuated extension map
        $countLines = count($extensionMapData);
        $extMapBinary = ''; // we'll build up the binary format in memory instead of wirting every line itself to file
        foreach($extensionMapData as $lineData){
            $extMapBinary .= $this->raf::toUTF($lineData[0]);
            $extMapBinary .= $this->raf::toUTF($lineData[1]);
        }
        $this->raf->writeInt($countLines);
        $this->raf->fwrite($extMapBinary);
    }

    /**
     * @param string $identifier
     * @param string $path
     */
    private function writeFprm(string $identifier, string $path){
        $this->raf->writeUTF($identifier, false);
        $this->raf->writeUTF(file_get_contents($path), false); // QUIRK: Need additional null byte. Where does it come from in Java?
    }

    /**
     * @param int $id
     * @param string $fileName
     * @param bool $isOutdatedRepack
     * @throws editor_Plugins_Okapi_Bconf_InvalidException
     */
    private function harvestReferencedFile(int $id, string $fileName, bool $isOutdatedRepack){
        $this->raf->writeInt($id);
        $this->raf->writeUTF($fileName, false);

        if($fileName == ''){
            // QUIRK: this value is encoded as BIG ENDIAN long long in the bconf. En/Decoding of 64 byte values creates Exceptions on 32bit OS, so we write 2 32bit Ints here
            $this->raf->writeInt(0);
            $this->raf->writeInt(0);
            return;
        }
        // check, if a SRX is a T5 default SRX and exchange it with the current version if we are in the process of repacking
        // this will be a permanent change since the physical file is overwritten
        // this happens without knowing/caring if this is the source/target bconf
        if($isOutdatedRepack && pathinfo($fileName, PATHINFO_EXTENSION) === editor_Plugins_Okapi_Bconf_Segmentation_Srx::EXTENSION){
            editor_Plugins_Okapi_Bconf_Segmentation::instance()->onRepack($this->folder.'/'.$fileName);
        }
        //Open the file and read the content
        $resource = fopen($fileName, 'rb');
        // can not really happen in normal operation but who knows
        if($resource === false){
            throw new editor_Plugins_Okapi_Bconf_InvalidException('Unable to open file '.$fileName);
        }
        $fileSize = filesize($fileName);
        $fileContent = fread($resource, $fileSize);
        // QUIRK: this value is encoded as BIG ENDIAN long long in the bconf. En/Decoding of 64 byte values creates Exceptions on 32bit OS, so we write 2 32bit Ints here (limiting the encodable size to 4GB...)
        $this->raf->writeInt(0);
        $this->raf->writeInt($fileSize);
        if($fileSize > 0){
            $this->raf->fwrite($fileContent);
        }
        fclose($resource);
    }
}