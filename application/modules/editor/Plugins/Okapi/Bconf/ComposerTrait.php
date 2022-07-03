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
 * @var editor_Plugins_Okapi_Models_Bconf $entity
 */
trait editor_Plugins_Okapi_Bconf_ComposerTrait {

    /**
     * @throws editor_Plugins_Okapi_Exception
     */
    private function doPack(): void {
        chdir($this->entity->getDataDirectory()); // so we can access with file name only

        $content = ['refs' => null, 'fprm' => null];

        if(file_exists(self::DESCRIPTION_FILE)){
            $content = json_decode(file_get_contents(self::DESCRIPTION_FILE), associative: true);
        }
        $fileName = basename($this->entity->getPath());
        $raf = new editor_Plugins_Okapi_Bconf_RandomAccessFile($fileName, 'wb');

        $raf->writeUTF($raf::SIGNATURE, false);
        $raf->writeInt($raf::VERSION);
        //TODO check the Plugins currentlly not in use
        $raf->writeInt(self::NUMPLUGINS);

        //Read the pipeline and extract steps
        self::processPipeline($raf);
        self::filterConfiguration($raf, $content);
        self::extensionsMapping($raf);
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
    //=== Section 4: The filter configurations

    /**
     * @param editor_Plugins_Okapi_Bconf_RandomAccessFile $raf
     * @param array $content Ordered bconf contents
     */
    private function filterConfiguration(editor_Plugins_Okapi_Bconf_RandomAccessFile $raf, array $content): void {
        $fprms = $content['fprm'] ?? glob("*.fprm");
        $raf->writeInt(count($fprms));
        foreach($fprms as $filterParam){
            $filename = $filterParam . (str_ends_with($filterParam, '.fprm') ? '' : '.fprm');
            $raf->writeUTF($filterParam, false);
            $raf->writeUTF(file_get_contents($filename), false); //QUIRK: Need additional null byte. Where does it come from in Java?
        }
    }

    /**
     * Section 5: Mapping extensions -> filter configuration id
     * @param editor_Plugins_Okapi_Bconf_RandomAccessFile $raf
     */
    private function extensionsMapping(editor_Plugins_Okapi_Bconf_RandomAccessFile $raf): void {
        if(!file_exists(self::EXTENSIONMAP_FILE)){
            return;
        }
        $extMap = file(self::EXTENSIONMAP_FILE, FILE_IGNORE_NEW_LINES);
        $amount = count($extMap);
        $extMapBinary = ''; // we'll build up the binary format in memory instead of wirting every line itself to file
        foreach($extMap as $line){
            $extAndConf = explode("\t", $line);
            $extMapBinary .= $raf::toUTF($extAndConf[0]);
            $extMapBinary .= $raf::toUTF($extAndConf[1]);
        }
        $raf->writeInt($amount);
        $raf->fwrite($extMapBinary);
    }

}