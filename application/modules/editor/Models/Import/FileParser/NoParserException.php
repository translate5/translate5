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

class editor_Models_Import_FileParser_NoParserException extends editor_Models_Import_Exception
{
    /**
     * @var string
     */
    protected $domain = 'editor.import.fileparser';

    protected static $localErrorCodes = [
        'E1060' => 'For the fileextension "{extension}" no parser is registered. For available parsers see log details.',
        'E1135' => 'There are no importable files in the Task. The following file extensions can be imported with the selected/embedded file-format-settings: {extensions}',
        'E1166' => 'Although there were importable files in the task, no files were imported. Investigate the log for preceeding errors.',
        'E1433' => 'The stored fileparser class {fileparserCls} is no valid fileparser - stored for file {fileId} {file}',
    ];
}
