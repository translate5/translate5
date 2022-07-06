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
 * Generate new bconf file
 * @see editor_Plugins_Okapi_Bconf_File
 * @var editor_Plugins_Okapi_Bconf_Entity $entity
 * @var bool $doDebug
 */
trait editor_Plugins_Okapi_Bconf_PackerTrait {

    /**
     * @throws editor_Plugins_Okapi_Exception
     */
    private function doPack(): void {

        // so we can access all files in the bconf's data-dir with file name only
        chdir($this->entity->getDataDirectory());

        $fileName = basename($this->entity->getPath());
        $raf = new editor_Plugins_Okapi_Bconf_RandomAccessFile($fileName, 'wb');
        $raf->writeUTF(editor_Plugins_Okapi_Bconf_Entity::SIGNATURE, false);
        $raf->writeInt(editor_Plugins_Okapi_Bconf_Entity::VERSION);
        // TODO BCONF: currently plugins are not supported
        $raf->writeInt(editor_Plugins_Okapi_Bconf_Entity::NUM_PLUGINS);

        // Read the pipeline and extract steps
        $this->processPipeline($raf);

        // process filters & extension mapping
        $customIdentifiers = [];
        foreach($this->entity->getCustomFilterData() as $filterData){
            $customIdentifiers[] = editor_Plugins_Okapi_Bconf_Filters::createIdentifier($filterData['okapiType'], $filterData['okapiId']);
        }
        // DEBUG
        if($this->doDebug) { error_log('PACKED CUSTOM FILTERS: '."\n".implode(', ', $customIdentifiers)); }

        // instantiate the extension mapping and evaluate the additional default okapi and translate5 filter files (this needs to know the "real" custom filters
        $extensionMapping = $this->entity->getExtensionMapping();
        $extensionMapData = $extensionMapping->getMapForPacking($customIdentifiers);
        $defaultFilterFiles = $extensionMapping->getOkapiDefaultFprmsForPacking($customIdentifiers); // retrieves an array of pathes !

        // DEBUG
        if($this->doDebug){
            error_log('PACKED DEFAULT FILTERS: '."\n".print_r($defaultFilterFiles, 1));
            error_log('PACKED EXTENSION MAPPING: '."\n".print_r($extensionMapData, 1));
        }
        $numAllEmbeddedFilters = count($customIdentifiers) + count($defaultFilterFiles);
        // write number of embedded filters
        $raf->writeInt($numAllEmbeddedFilters);
        foreach($customIdentifiers as $identifier){
            // we are already in the bconf's dir, so we can reference custom filters by filename only
            $this->writeFprm($raf, $identifier, $identifier.'.'.editor_Plugins_Okapi_Bconf_Filter_Entity::EXTENSION);
        }
        foreach($defaultFilterFiles as $identifier => $path){
            // the static default filters will be added with explicit settings, These are either OKAPI defaults or translate5 adjusted defaults
            $this->writeFprm($raf, $identifier, $path);
        }
        // write the adjuated extension map
        $countLines = count($extensionMapData);
        $extMapBinary = ''; // we'll build up the binary format in memory instead of wirting every line itself to file
        foreach($extensionMapData as $lineData){
            $extMapBinary .= $raf::toUTF($lineData[0]);
            $extMapBinary .= $raf::toUTF($lineData[1]);
        }
        $raf->writeInt($countLines);
        $raf->fwrite($extMapBinary);
    }

    private function writeFprm(editor_Plugins_Okapi_Bconf_RandomAccessFile $raf, string $identifier, string $path){
        $raf->writeUTF($identifier, false);
        $raf->writeUTF(file_get_contents($path), false); // QUIRK: Need additional null byte. Where does it come from in Java?
    }

    private function processPipeline($raf): void {
        $pipelineFile = 'pipeline.pln';
        $pipelineSize = filesize($pipelineFile);
        $resource = fopen($pipelineFile, 'rb');
        $xml = fread($resource, $pipelineSize);
        fclose($resource);

        $xmlData = new SimpleXMLElement($xml);
        $id = 0;
        foreach($xmlData as $key){
            $methods = explode("\n", $key);
            foreach($methods as $method){
                if(str_contains($method, 'Path')){

                    $path = explode("=", $method)[1];
                    $path = str_replace('\\\\', '/', $path);
                    $path = str_replace('\\', '/', $path);

                    $this::harvestReferencedFile($raf, ++$id, basename($path));
                }
            }
        }
        // Last ID=-1 to mark no more references
        $raf->writeInt(-1);

        $raf->writeInt(1);
        $raf->writeUTF($xml, false);
    }

    private function harvestReferencedFile($raf, $id, $fileName): void {
        $raf->writeInt($id);
        $raf->writeUTF($fileName, false);

        if($fileName == ''){
            // QUIRK: this value is encoded as BIG ENDIAN long long in the bconf. En/Decoding of 64 byte values creates Exceptions on 32bit OS, so we write 2 32bit Ints here
            $raf->writeInt(0);
            $raf->writeInt(0);
            return;
        }
        //Open the file and read the content
        $file = fopen($fileName, "rb") or die("Unable to open file!");

        $fileSize = filesize($fileName);
        $fileContent = fread($file, $fileSize);
        // QUIRK: this value is encoded as BIG ENDIAN long long in the bconf. En/Decoding of 64 byte values creates Exceptions on 32bit OS, so we write 2 32bit Ints here (limiting the encodable size to 4GB...)
        $raf->writeInt(0);
        $raf->writeInt($fileSize);
        if($fileSize > 0){
            $raf->fwrite($fileContent);
        }
        fclose($file);
    }
}