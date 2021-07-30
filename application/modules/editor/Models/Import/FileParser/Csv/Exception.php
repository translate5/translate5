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

/**
 * Should be used for errors in the context of CSV import processing
 */
class editor_Models_Import_FileParser_Csv_Exception extends editor_Models_Import_FileParser_Exception {
    /**
     * @var string
     */
    protected $domain = 'editor.import.fileparser.csv';
    
    static protected $localErrorCodes = [
        'E1017' => 'The regex {regex} matches the placeholderCSV string {placeholder} that is used in the editor_Models_Import_FileParser_Csv class to manage the protection loop. This is not allowed. Please find another solution to protect what you need to protect in your CSV via Regular Expression.',
        'E1018' => 'The string $this->placeholderCSV ({placeholder}) had been present in the segment before parsing it. This is not allowed.',
        'E1075' => 'Error on parsing a line of CSV. Current line is: "{line}". Error could also be in previous line!',
        'E1076' => 'In the line "{line}" there is no third column.',
        'E1077' => 'No linebreak found in CSV: "{file}"',
        'E1078' => 'No header column found in CSV: "{file}"',
        'E1079' => 'In application.ini configured column-header(s) "{headers}" not found in CSV: "{file}"',
        'E1080' => 'Source and mid given but no more data columns found in CSV: "{file}"',
    ];
}
