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
 * Class representing the pipeline of a BCONF
 * This generally is a xml file with x-properties Sections
 * For validation, we analyse the content as XML and properties
 * To manipulate the SRX-pathes, we replace the strings as it is to complicated to imlement a XML/Properties bridge
 *
 */
final class editor_Plugins_Okapi_Bconf_Pipeline extends editor_Plugins_Okapi_Bconf_ResourceFile {

    /*
     * A typical pipline file looks like this:

    <?xml version="1.0" encoding="UTF-8"?>
    <rainbowPipeline version="1">
        <step class="net.sf.okapi.steps.common.RawDocumentToFilterEventsStep"></step>
        <step class="net.sf.okapi.steps.segmentation.SegmentationStep">#v1
segmentSource.b=true
segmentTarget.b=true
renumberCodes.b=false
sourceSrxPath=languages.srx
targetSrxPath=languages.srx
...
copySource.b=false</step>
        <step class="net.sf.okapi.steps.rainbowkit.creation.ExtractionStep">#v1
writerClass=net.sf.okapi.steps.rainbowkit.xliff.XLIFFPackageWriter
packageName=pack1
...
writerOptions.escapeGT.b=false
        </step>
    </rainbowPipeline>

    */

    const FILE = 'pipeline.pln';

    // a Pipeline is generally a XML variant
    protected string $mime = 'text/xml';
    /**
     * @var string|null
     */
    private ?string $sourceSrxPath = NULL;
    /**
     * @var string|null
     */
    private ?string $targetSrxPath = NULL;
    /**
     * @var string[]
     */
    private array $steps = [];
    /**
     * @var string[]
     */
    private array $errors = [];
    /**
     * Marker for the bconf-entity for proper caching
     * @var int
     */
    private int $bconfId;

    /**
     * @param string $path
     * @param string|null $content
     * @param int $bconfId
     * @throws ZfExtended_Exception
     */
    public function __construct(string $path, string $content=NULL, int $bconfId=-1){
        parent::__construct($path, $content);
        $this->bconfId = $bconfId;
        $this->parse($this->content);
    }

    /**
     * @return int
     */
    public function getBconfId() : int {
        return $this->bconfId;
    }

    /**
     * A readonly-prop of the encoded steps of the pipeline that is evaluated when parsing
     * @return string[]
     */
    public function getSteps() : array {
        return $this->steps;
    }

    /**
     * @param string $field
     * @return mixed
     * @throws ZfExtended_Exception
     */
    public function getSrxFile(string $field) : string {
        if($field === 'source'){
            return $this->sourceSrxPath;
        } else if($field === 'target'){
            return $this->targetSrxPath;
        } else {
            throw new ZfExtended_Exception('Invalid field "'.$field.'", must be "source" or "target"');
        }
    }

    /**
     * Updates a SRX. Does not flush the content
     * @param string $field
     * @param string $file
     * @throws ZfExtended_Exception
     */
    public function setSrxFile(string $field, string $file){
        if(pathinfo($file, PATHINFO_EXTENSION) !== editor_Plugins_Okapi_Bconf_Segmentation_Srx::EXTENSION){
            throw new ZfExtended_Exception('A SRX file must have the file-extension "srx": '.$file);
        }
        if($field === 'source'){
            $this->content = preg_replace('~sourceSrxPath\s*=\s*'.pathinfo($this->sourceSrxPath, PATHINFO_FILENAME).'~', 'sourceSrxPath='.pathinfo($file, PATHINFO_FILENAME), $this->content);
            $this->sourceSrxPath = $file;
        } else if($field === 'target'){
            $this->content = preg_replace('~targetSrxPath\s*=\s*'.pathinfo($this->targetSrxPath, PATHINFO_FILENAME).'~', 'targetSrxPath='.pathinfo($file, PATHINFO_FILENAME), $this->content);
            $this->targetSrxPath = $file;
        } else {
            throw new ZfExtended_Mismatch('E2004', [ $field, 'field' ]);
        }
    }

    /**
     * @return bool
     */
    public function hasIdenticalSourceAndTargetSrx() : bool {
        return ($this->sourceSrxPath === $this->targetSrxPath);
    }

    /**
     * Validates a SRX
     * @return bool
     */
    public function validate(bool $forImport=false) : bool {
        if(count($this->errors) > 0){
            $this->validationError = ucfirst(implode(', ', $this->errors));
            // DEBUG
            if($this->doDebug) { error_log('PIPELINE IS INVALID: '.$this->validationError); }
            return false;
        }
        // DEBUG
        if($this->doDebug) { error_log('PIPELINE '.$this->path.' IS VALID'); }
        return true;
    }

    /**
     * @param string $content
     */
    private function parse(string $content){
        $pipeline = simplexml_load_string($content);
        if(!$pipeline){
            $this->errors[] = 'invalid XML content in '.self::FILE;
            return;
        }
        $version = (int) $pipeline['version'];
        if($version !== 1){
            $this->errors[] = 'invalid Version of pipeline "'.$version.'"';
            return;
        }
        $hasSegmentationStep = false;
        foreach($pipeline->step as $step){ /* @var SimpleXMLElement $step */
            $class = (string) $step['class'];
            if(!empty($class)){
                $this->steps[] = $class;
                $parts = explode('.', $class);
                $lastPart = array_pop($parts);
                if($lastPart === 'SegmentationStep'){
                    $hasSegmentationStep = true;
                    $propsContent = $step->__toString();
                    $props = new editor_Plugins_Okapi_Bconf_Parser_Properties($propsContent);
                    if(!$props->isValid()){
                        $this->errors[] = 'invalid Segmentation step ('.$props->getErrorString(', ').')';
                    } else {
                        $this->sourceSrxPath = ($props->has('sourceSrxPath')) ? basename($props->get('sourceSrxPath')) : NULL;
                        $this->targetSrxPath = ($props->has('targetSrxPath')) ? basename($props->get('targetSrxPath')) : NULL;
                    }
                }
            }
        }
        if(count($this->steps) === 0){
            $this->errors[] = 'the pipeline has no steps';
        }
        if(!$hasSegmentationStep){
            $this->errors[] = 'the pipeline has no segmentation step';
        }
        if(empty($this->sourceSrxPath) || empty($this->targetSrxPath) || pathinfo($this->sourceSrxPath, PATHINFO_EXTENSION) != editor_Plugins_Okapi_Bconf_Segmentation_Srx::EXTENSION || pathinfo($this->targetSrxPath, PATHINFO_EXTENSION) != editor_Plugins_Okapi_Bconf_Segmentation_Srx::EXTENSION){
            $this->errors[] = 'the pipeline had no or invalid entries for the source or target segmentation srx file';
        } else {
            // we will remove any path from the SRX-Files to normalize the value (it usually contains the rainbow workspace path)
            if(basename($this->sourceSrxPath) != $this->sourceSrxPath){
                $this->setSrxFile('source', basename($this->sourceSrxPath));
            }
            if(basename($this->targetSrxPath) != $this->targetSrxPath){
                $this->setSrxFile('target', basename($this->targetSrxPath));
            }
        }
    }
}