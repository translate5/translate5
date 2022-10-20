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

namespace MittagQI\Translate5\Test\Import;

/**
 * Helper that represents a task-config.ini file
 */
final class TaskConfigIni
{
    private array $map = [];

    /**
     * @param string|null $content: if given, the contents of an existing file
     * @param array $configs: if given, configs as a map. will overwrite configs from $content
     */
    public function __construct(string $content = null, array $configs = [])
    {
        if ($content !== null) {
            $lines = explode("\n", trim(str_replace("\r", '', $content)));
            foreach($lines as $line){
                if(str_contains($line, '=')){
                    $index = mb_strpos($line, '=');
                    $name = trim(mb_substr($line, 0, $index));
                    $value = trim(mb_substr($line, $index + 1));
                    $this->map[$name] = $value;
                }
            }
        }
        foreach($configs as $name => $value){
            $this->map[$name] = $value;
        }
    }

    /**
     * Creates the contents of the ini-file
     * @return string
     */
    public function getContents(): string
    {
        $content = '';
        foreach($this->map as $name => $value){
            $content .= $name.' = '.$value."\n";
        }
        return $content;
    }
}
