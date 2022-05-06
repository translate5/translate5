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
 * @see editor_Plugins_Okapi_Bconf_File
 * @var editor_Plugins_Okapi_Models_Bconf $entity
 */
trait editor_Plugins_Okapi_Bconf_ParserTrait {

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
     * @throws Zend_Exception
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Plugins_Okapi_Exception
     */
    public function unpack(string $pathToParse): void {
        chdir($this->entity->getDir());

        $content = [
            'refs' => [],
            'step' => [],
            'fprm' => [],
        ];

        $raf = new editor_Plugins_Okapi_Bconf_RandomAccessFile($pathToParse, "rb");
        $sig = $raf->readUTF();
        $sig !== $raf::SIGNATURE && $this->invalid(
            "Invalid signature '" . htmlspecialchars($sig) . "' in file header before byte " . $raf->ftell() . ". Must be '" . $raf::SIGNATURE . "'");

        $version = $raf->readInt();
        !($version >= 1 && $version <= $raf::VERSION) && $this->invalid(
            "Invalid version '$version' in file header before byte " . $raf->ftell() . ". Must be in range 1-" . $raf::VERSION);

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

        //=== Section 2: Read contained reference files

        $refMap = []; // numeric indices are read from the bconf, starting with 1

        while(($refIndex = $raf->readInt()) != -1 && !is_null($refIndex)) {
            $filename = $refMap[$refIndex] = $raf->readUTF();
            // Skip over the data to move to the next reference
            $size = $raf->readLong();
            if($size > 0){
                self::createReferencedFile($raf, $size, $filename);
            }
        }

        $refIndex === NULL && $this->invalid("Malformed references list. Read NULL instead of integer before byte " . $raf->ftell());
        ($refCount = count($refMap)) < 2 && $this->invalid("Only $refCount reference" . ($refCount ? '' : 's') . " included. Need sourceSRX and targetSRX.");

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

        self::parsePipeline($pipeline, $refMap, $content);

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

    private function readExtensionMap($raf): array {
        $map = [];
        $count = $raf->readInt();
        !$count && $this->invalid("No extensions-mapping present in bconf.");

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
    private function createReferencedFile(SplFileObject $raf, int $size, string $path): void {
        /** @var resource $fos file output stream */
        $fos = fopen($path, 'wb');
        !$fos && $this->invalid("Could not create '$path'", 'E1057');
        // FIXME: when stream_copy_to_stream supports SplFileObjects use that
        // $written = stream_copy_to_stream($raf->getFp(), $fos, $size);

        /** @var int|bool $written */
        $written = 0;
        $toWrite = $size;
        $buffer = min(65536, $toWrite); // 16 pages à 4K
        while($toWrite > $buffer && $written !== false) {
            $written += fwrite($fos, $raf->fread($buffer));
            $toWrite -= $buffer;
        }
        $written += fwrite($fos, $raf->fread($toWrite));
        fclose($fos);
        $written !== $size && $this->invalid(
            "Could " . ($written !== false ? "only write $written bytes of " : "not write") . "'$path'", 'E1057');
    }

    public function parsePipeline(&$pipeline, &$refMap, &$content): array {
        $doc = new editor_Utils_Dom();
        $pipeline['xml'] && $doc->loadXML($pipeline['xml']);
        (!$pipeline['xml'] || !$doc->isValid()) && $this->invalid(
            "Invalid Pipeline inside bconf. " . $doc->getErrorMsg('', true));

        foreach($doc->getElementsByTagName("step") as $i => $step){
            $class = $step->getAttribute("class");
            $class == null && $this->invalid("The attribute 'class' is missing on step #$i.");

            $pipeline['steps'][] = $content['step'][] = $class;
            $classParts = explode('.', $class);
            $stepName = end($classParts);
            $pathParams = self::STEP_REFERENCES[$stepName] ?? [];
            foreach($pathParams as $pathType){
                $pathType = lcfirst($pathType);
                $filename = $content['refs'][$pathType] = array_shift($refMap);
                $pathRegex = "/^($pathType=).*$/m";
                $pipeline['xml'] = preg_replace($pathRegex, "$1$filename", $pipeline['xml']); // QUIRK: Original code includes absolute path in place of filename
            }
        }

        $refs = &$content['refs'];
        !isset($refs['sourceSrxPath'], $refs['targetSrxPath']) && $this->invalid(
            "Reference files missing. Need sourceSRX and targetSRX. Got " . print_r($refs, true));

        return $pipeline;
    }
}
