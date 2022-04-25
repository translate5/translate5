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
    public const NUMPLUGINS = 0;
    public const DESCRIPTION_FILE = "content.json";
    public const PIPELINE_FILE = "pipeline.pln";
    public const EXTENSIONMAP_FILE = "extensions-mapping.txt";
    public const STEP_REFERENCES = [
        "SegmentationStep"   => ["SourceSrxPath", "TargetSrxPath"],
        "TermExtractionStep" => ["StopWordsPath", "NotStartWordsPath", "NotEndWordsPath"],
        "XMLValidationStep"  => ["SchemaPath"],
        "XSLTransformStep"   => ["XsltPath"],
    ];

    protected editor_Plugins_Okapi_Models_Bconf $entity;

    public function __construct(editor_Plugins_Okapi_Models_Bconf $entity) {
        $this->entity = $entity;
    }

    public function pack(): string {
        return editor_Plugins_Okapi_Bconf_Composer::doPack($this->entity);
    }

    public function unpack(string $filePath): mixed {
        return editor_Plugins_Okapi_Bconf_Parser::doUnpack($filePath, $this->entity);
    }

}