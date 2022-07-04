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

    use editor_Plugins_Okapi_Bconf_ParserTrait;
    use editor_Plugins_Okapi_Bconf_ComposerTrait;

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

    protected editor_Plugins_Okapi_Models_Bconf $entity;

    public function __construct(editor_Plugins_Okapi_Models_Bconf $entity) {
        $this->entity = $entity;
    }

    /**
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Plugins_Okapi_Exception
     */
    public function pack(): void {
        try {
            $this->doPack();
        } catch(editor_Plugins_Okapi_Exception|ZfExtended_UnprocessableEntity){
        } catch(Exception $e){
            $this->invalidate($e->__toString(), 'EXCEP');
        }
    }

    /**
     * @param string $pathToParse
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Plugins_Okapi_Exception
     */
    public function unpack(string $pathToParse): void {
        try {
            $this->doUnpack($pathToParse);
        } catch(editor_Plugins_Okapi_Exception|ZfExtended_UnprocessableEntity){
        } catch(Exception $e){
            $this->invalidate($e->__toString(), 'EXCEP');
        }
    }

    /**
     * Handles occurred errors by deleting new records and throwing appropriate Exceptions
     * @param string $msg Message or exception to throw
     * @param string $errorCode Defines which Exeption will be thrown
     * @return never // TODO Add as return type on PHP8.1
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Plugins_Okapi_Exception
     */
    protected function  invalidate(string $msg = '', string $errorCode = 'E1026'): void {
        $errors = [[$msg]];
        if($this->entity->isNewRecord()){
            try {
                $this->entity->delete();
            } catch(Exception $e){
                if($errorCode === 'E1026'){
                    $errors[] = [strval($e)];
                } else {
                    $msg .= PHP_EOL . strval($e);
                }
            }
        }
        throw match ($errorCode) {
            'E1057' => new editor_Plugins_Okapi_Exception($errorCode, ['okapiDataDir' => $msg]),
            'E1026' => new ZfExtended_UnprocessableEntity($errorCode, ['errors' => $errors]),
            default => new Exception($msg),
        };
    }

}