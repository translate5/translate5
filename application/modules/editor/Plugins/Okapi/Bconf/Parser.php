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


class editor_Plugins_Okapi_Bconf_Parser extends editor_Plugins_Okapi_Bconf_File {
    /**
     * Import bconf
     * Extracts the parts of a given bconf file in the order
     * 1) plug-ins
     * 2) references data
     * 3) pipeline
     * 4) filter configurations
     * 5) extensions -> filter configuration id mapping
     *
     * @param string $pathToParse
     * @param editor_Plugins_Okapi_Models_Bconf $entity
     * @throws Zend_Exception
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Plugins_Okapi_Exception
     */
    public static function doUnpack(string $pathToParse, editor_Plugins_Okapi_Models_Bconf $entity) {
        chdir($entity->getDataDirectory());

        $content = [
            'refs' => [],
            'fprm' => [],
        ];

        $raf = new editor_Plugins_Okapi_Bconf_RandomAccessFile($pathToParse, "rb");
        $sig = $raf->readUTF(); // signature
        if(!$raf::SIGNATURE === $sig){
            throw new ZfExtended_UnprocessableEntity("Invalid file format.");
        }
        $version = $raf->readInt(); // Version info
        if(!($version >= 1 && $version <= $raf::VERSION)){
            throw new ZfExtended_UnprocessableEntity("Invalid version.");
        }

        //=== Section 1: plug-ins
        if($version > 1){ // Remain compatible with v.1 bconf files

            $numPlugins = $raf->readInt();
            for($i = 0; $i < $numPlugins; $i++){
                $relPath = $raf->readUTF();
                $raf->readInt(); // Skip ID
                $raf->readUTF(); // Skip original full filename
                $size = $raf->readLong();
                self::createReferencedFile($raf, $size, $relPath);
            }

        }

        //=== Section 2: references data

        // Build a lookup table to the references
        $refNames = [];
        $id = $raf->readInt(); // First ID or end of section marker
        while($id != -1) {
            $filename = $refNames[] = $raf->readUTF(); // Skip filename
            // Skip over the data to move to the next reference
            $size = $raf->readLong();
            if($size > 0){
                self::createReferencedFile($raf, $size, $filename);
            }
            // Then get the information for next entry
            $id = $raf->readInt(); // ID
        }

        //=== Section 3 : the pipeline itself
        $xmlWordCount = $raf->readInt();
        $pipelineXml = '';
        for($i = 0; $i < $xmlWordCount; $i++){
            $pipelineXml .= $raf->readUTF();
        }

        $startFilterConfigs = $raf->ftell();
        // Read the pipeline and instantiate the steps
        $pipeline = [
            'xml' => $pipelineXml,
        ];
        // reads in the given path
        $content['refs'] = $refNames;

        self::parsePipeline($pipeline, $refNames);

        file_put_contents(self::PIPELINE_FILE, $pipeline['xml']);

        //=== Section 4 : the filter configurations
        $raf->fseek($startFilterConfigs);
        // Get the number of filter configurations
        $count = $raf->readInt();

        // Read each one
        for($i = 0; $i < $count; $i++){
            $okapiId = $content['fprm'][] = $raf->readUTF();
            $data = $raf->readUTF();
            // And create the parameters file
            file_put_contents("$okapiId.fprm", $data);
        }

        //=== Section 5: the extensions -> filter configuration id mapping
        $write = '';
        $extMap = self::readExtensionMap($raf);
        foreach($extMap as $ext => $okapiId){
            $write .= $ext . "\t" . $okapiId . PHP_EOL;
        }
        file_put_contents(self::EXTENSIONMAP_FILE, $write);
        file_put_contents(self::DESCRIPTION_FILE, json_encode($content, JSON_PRETTY_PRINT));
    }

    private static function readExtensionMap($raf): array {
        $map = [];
        $count = $raf->readInt();
        for($i = 0; $i < $count; $i++){
            $ext = $raf->readUTF();
            $okapiId = $raf->readUTF();
            $map[$ext] = $okapiId;
        }
        return $map;
    }

    /**
     * Writes out part of an opened file as separate file
     * @param SplFileObject $raf
     * @param int $size
     * @param string $path
     * @throws editor_Plugins_Okapi_Exception
     */
    private static function createReferencedFile(SplFileObject $raf, int $size, string $path) {
        /** @var resource $fos file output stream */
        $fos = fopen($path, 'wb');
        if(!$fos){
            throw new editor_Plugins_Okapi_Exception('E1057', ['okapiDataDir' => "Could not create '$path'"]);
        }
        // FIXME: when stream_copy_to_stream supports SplFileObjects use that
        // $written = stream_copy_to_stream($raf->getFp(), $fos, $size);
        $written = 0;
        $toWrite = $size;
        $buffer = min(65536, $toWrite); // 16 pages à 4K
        while($toWrite > $buffer && $written !== false) {
            $written += fwrite($fos, $raf->fread($buffer));
            $toWrite -= $buffer;
        }
        $written += fwrite($fos, $raf->fread($toWrite));
        fclose($fos);
        if($written !== $size){
            throw new editor_Plugins_Okapi_Exception('E1057', [
                'okapiDataDir' => "Could " . ($written ? "only write $written bytes of " : "not write") . "'$path'",
            ]);
        }
    }

    private static function parsePipeline(&$pipeline, $refMap) {
        $doc = new DOMDocument();
        $doc->loadXML($pipeline['xml']);
        /** @var DOMNodeList nodes */
        $nodes = $doc->getElementsByTagName("step");

        foreach($nodes as $elem){
            $class = $elem->getAttribute("class");
            if($class == null){
                throw new ZfExtended_UnprocessableEntity("The attribute 'class' is missing.");
            }
            $classParts = explode('.', $class);
            $stepName = end($classParts);
            $pathParams = self::STEP_REFERENCES[$stepName] ?? [];
            foreach($pathParams as $param){
                $param = lcfirst($param);
                $path = array_shift($refMap); // QUIRK: Original code includes absolute path in place of filename
                $pipeline['xml'] = preg_replace("/^($param=).*$/m", "$1$path", $pipeline['xml']);
            }
            $pipeline['steps'][] = $class;
        }

        return $pipeline;
    }
}
