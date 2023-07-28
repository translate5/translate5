<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Installer\SystemRequirement\Modules;

use DOMDocument;
use Zend_Exception;
use ZfExtended_Models_SystemRequirement_Modules_Abstract;
use ZfExtended_Models_SystemRequirement_Result;
use ZfExtended_Zendoverwrites_Translate;

/**
 * Contains checks for fundamental configuration values
 * @used-by \ZfExtended_Models_SystemRequirement_Validator::__construct
 */
class Locales extends ZfExtended_Models_SystemRequirement_Modules_Abstract
{

    /**
     * {@inheritDoc}
     * @throws Zend_Exception
     * @see ZfExtended_Models_SystemRequirement_Modules_Abstract::validate()
     */
    public function validate(): ZfExtended_Models_SystemRequirement_Result
    {
        $this->result->id = 'locales';
        $this->result->name = 'Locales XML syntax check';

        $checksDone = 0;
        $paths = ZfExtended_Zendoverwrites_Translate::getInstance()->getTranslationDirectories();
        foreach ($paths as $path) {
            $xlfFiles = glob($path . '*.xliff');
            foreach ($xlfFiles as $xlfFile) {
                $checksDone++;
                $this->parseXliff($xlfFile);
            }
        }

        if ($checksDone === 0) {
            $this->result->error[] = 'No locale XLF files found for checking...';
        }

        return $this->result;
    }

    protected function parseXliff($fileName)
    {
        libxml_clear_errors(); //empty the error collector first!
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        if (!$doc->load($fileName)) {
            $errors = libxml_get_errors();

            if (!empty($errors)) {
                $errors = join(
                    '',
                    array_map(function ($error) {
                        return "Error at line {$error->line}, column {$error->column}: {$error->message}";
                    }, $errors)
                );
                $this->result->error[] = 'XML errors in ' . $fileName . $errors;
            }
            //important for subsequent calls!
            libxml_clear_errors();
        }
    }
}