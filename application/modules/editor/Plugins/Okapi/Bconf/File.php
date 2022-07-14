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
 * Class representing a .bconf file and associated operations
 */
class editor_Plugins_Okapi_Bconf_File {

    use editor_Plugins_Okapi_Bconf_UnpackerTrait;
    use editor_Plugins_Okapi_Bconf_PackerTrait;

    /**
     * @var string
     */
    const DESCRIPTION_FILE = 'content.json';

    /**
     * @var string
     */
    const PIPELINE_FILE = 'pipeline.pln';

    public const STEP_REFERENCES = [
        'SegmentationStep'   => ['SourceSrxPath', 'TargetSrxPath'],
        'TermExtractionStep' => ['StopWordsPath', 'NotStartWordsPath', 'NotEndWordsPath'],
        'XMLValidationStep'  => ['SchemaPath'],
        'XSLTransformStep'   => ['XsltPath'],
    ];

    /**
     * @var editor_Plugins_Okapi_Bconf_Entity
     */
    protected editor_Plugins_Okapi_Bconf_Entity $entity;

    /**
     * @var string
     */
    protected string $bconfName;

    /**
     * @var bool
     */
    protected bool $isNew;

    /**
     * @var bool
     */
    protected bool $doDebug;

    public function __construct(editor_Plugins_Okapi_Bconf_Entity $entity, bool $isNew=false) {
        $this->entity = $entity;
        $this->isNew = $isNew;
        $this->bconfName = $this->entity->getName();
        $this->doDebug = ZfExtended_Debug::hasLevel('plugin', 'OkapiBconfPackUnpack');
    }

    /**
     * Retrieves the id of the related bconf
     * @return int
     */
    public function getBconfId(){
        return $this->entity->getId();
    }

    /**
     * @param string $pathToParse
     * @throws ZfExtended_Exception
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Plugins_Okapi_Exception
     */
    public function unpack(string $pathToParse): void {
        try {
            $this->doUnpack($pathToParse);
        } catch(editor_Plugins_Okapi_Bconf_InvalidException $e){
            // in case of a editor_Plugins_Okapi_Bconf_InvalidException, the exception came from the invalidate-API and the entity is already invalidated
            error_log('UNPACK EXCEPTION: '.$e->getMessage());
            throw new editor_Plugins_Okapi_Exception('E4444', ['bconf' => $this->bconfName, 'details' => $e->getMessage()]);
        } catch(Exception $e){
            // if an other exception than the explicitly thrown via invalidate occur we do a passthrough to be able to identify the origin
            error_log('UNKNOWN UNPACK EXCEPTION: '.$e->__toString());
            $this->invalidate($e->__toString(), false);
            throw $e;
        }
    }

    /**
     * @throws ZfExtended_Exception
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Plugins_Okapi_Exception
     */
    public function pack(): void {
        try {
            $this->doPack();
        } catch(editor_Plugins_Okapi_Bconf_InvalidException $e){
            // in case of a editor_Plugins_Okapi_Bconf_InvalidException, the entity is already invalidated
            error_log('PACK EXCEPTION: '.$e->getMessage());
            throw new editor_Plugins_Okapi_Exception('E4445', ['bconf' => $this->bconfName, 'details' => $e->getMessage()]);
        } catch(Exception $e){
            // if an other exception than the explicitly thrown via invalidate occur we do a passthrough to be able to identify the origin
            error_log('UNKNOWN PACK EXCEPTION: '.$e->__toString());
            $this->invalidate($e->__toString(), false);
            throw $e;
        }
    }

    /**
     * Handles occurred errors by deleting new records and throwing appropriate Exceptions
     * @param string $msg Message or exception to throw
     * @param boolean $throwException If set an Invalid exception will be thrown
     * @throws editor_Plugins_Okapi_Bconf_InvalidException
     */
    protected function  invalidate(string $msg, bool $throwException=true) : void {
        if($this->isNew){
            try {
                $this->entity->delete();
            } catch(Exception $e){
                $msg .= PHP_EOL.strval($e);
            }
        }
        // will be triggered from errors inside the packer/unpacker
        if($throwException){
            throw new editor_Plugins_Okapi_Bconf_InvalidException($msg);
        }
    }
}