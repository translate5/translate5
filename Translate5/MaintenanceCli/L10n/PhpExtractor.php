<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
declare(strict_types=1);

namespace Translate5\MaintenanceCli\L10n;

use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;
use Zend_Registry;

class PhpExtractor
{
    /**
     * see https://stackoverflow.com/questions/5695240/php-regex-to-ignore-escaped-quotes-within-quotes
     */
    private string $regexSingleQuoted = "'[^'\\\\]*(?:\\\\.[^'\\\\]*)*'";

    /**
     * see https://stackoverflow.com/questions/5695240/php-regex-to-ignore-escaped-quotes-within-quotes
     */
    private string $regexDoubleQuoted = '"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"';

    private array $brokenMatches = [];

    private Zend_Db_Adapter_Abstract $db;

    public function __construct(
        private readonly string $absoluteFilePath
    ) {
        $this->db = Zend_Db_Table::getDefaultAdapter();
    }

    public function extract(): array
    {
        $strings = [];
        $content = file_get_contents($this->absoluteFilePath);

        if ($content === false) {
            throw new \ZfExtended_Exception('Could not read file ' . $this->absoluteFilePath);
        }

        // find calls like $translate->~_('something'); (note: "~" prevents extraction ;-)
        $content = preg_replace_callback(
            '~->\s*_\s*\(\s*(' . $this->regexSingleQuoted . ')\s*\)~s',
            function ($matches) use (&$strings) {
                if (count($matches) > 1) {
                    $string = $this->prepareMatch($matches[1], "'");
                    if ($string !== null) {
                        $strings[] = $string;
                    }
                }

                return '->test();';
            },
            $content
        );
        // find calls like $translate->~_("something"); (note: "~" prevents extraction ;-)
        $content = preg_replace_callback(
            '~->\s*_\s*\(\s*(' . $this->regexDoubleQuoted . ')\s*\)~s',
            function ($matches) use (&$strings) {
                if (count($matches) > 1) {
                    $string = $this->prepareMatch($matches[1], '"');
                    if ($string !== null) {
                        $strings[] = $string;
                    }
                }

                return '->test();';
            },
            $content
        );

        // find calls like $view->template~Apply('something'); (note: "~" prevents extraction ;-)
        $content = preg_replace_callback(
            '~->\s*templateApply\s*\(\s*(' . $this->regexSingleQuoted . ')\s*[,)]{1}~s',
            function ($matches) use (&$strings) {
                if (count($matches) > 1) {
                    $string = $this->prepareMatch($matches[1], "'");
                    if ($string !== null) {
                        $strings[] = $string;
                    }
                }

                return '->testApply(\'test\'' . substr($matches[0], -1);
            },
            $content
        );
        // find calls like $view->template~Apply("something"); (note: "~" prevents extraction ;-)
        $content = preg_replace_callback(
            '~->\s*templateApply\s*\(\s*(' . $this->regexDoubleQuoted . ')\s*[,)]{1}~s',
            function ($matches) use (&$strings) {
                if (count($matches) > 1) {
                    $string = $this->prepareMatch($matches[1], '"');
                    if ($string !== null) {
                        $strings[] = $string;
                    }
                }

                return '->testApply(\'test\'' . substr($matches[0], -1);
            },
            $content
        );

        // parse artificial programmatic includes of table columns
        // $translateTable->~__('LEK_languages', 'langName');  (note: "~" prevents extraction ;-)
        $content = preg_replace_callback(
            '~translateTable\s*->\s*__\s*\(\s*["\']{1}([^"\']+)["\']{1}\s*,\s*["\']{1}([^"\']+)["\']{1}\s*\)~',
            function ($matches) use (&$strings) {
                if (count($matches) > 2) { // @phpstan-ignore-line
                    $this->addTableMatches($matches[1], $matches[2], $strings);
                }

                return '->testTable();';
            },
            $content
        );

        // parse artificial programmatic includes of configs
        // $translateConfig->~___('runtimeOptions.segments.qualityFlags', 'default'); (note: "~" prevents extraction ;-)
        $content = preg_replace_callback(
            '~translateConfig\s*->\s*___\s*\(\s*["\']{1}([^"\']+)["\']{1}\s*,\s*["\']{1}([^"\']+)["\']{1}\s*\)~',
            function ($matches) use (&$strings) {
                if (count($matches) > 2) { // @phpstan-ignore-line
                    $this->addConfigMatches($matches[1], $matches[2], $strings);
                }

                return '->testConfig();';
            },
            $content
        );

        // find misconfigured calls on ->_
        preg_replace_callback(
            '~->\s*_\s*\(\s*(.*?)\s*\)~s',
            function ($matches) {
                $this->brokenMatches[] = $matches[0] . ' in file ' . $this->absoluteFilePath;

                return '->test();';
            },
            $content
        );
        // find misconfigured calls on ->templateApply
        preg_replace_callback(
            '~->\s*templateApply\s*\(\s*(.*?)\s*[,)]{1}~s',
            function ($matches) {
                $this->brokenMatches[] = str_replace(["\n", "\r", "\t"], ['\n', '\r', '\t'], $matches[0]) .
                    ' in file ' . $this->absoluteFilePath;

                return '->testApply(\'test\'' . substr($matches[0], -1);
            },
            $content
        );

        return array_values(array_unique($strings));
    }

    public function getBrokenMatches(): array
    {
        return $this->brokenMatches;
    }

    private function prepareMatch(string $match, string $quote): ?string
    {
        $string = trim(trim($match), $quote);
        // important: in double-quoted strings, we need to expand them programmatically
        if ($quote === '"') {
            $string = str_replace(['\n', '\r', '\t'], ["\n", "\r", "\t"], $string);
        }
        // in case the string only contains markup & placeholders, it must not be translated ...
        if (trim(strip_tags(preg_replace('~{[a-zA-Z0-9.]+}~', '', $string))) === '') {
            return null;
        }

        return $string;
    }

    private function addTableMatches(string $tableName, string $columnName, array &$strings): void
    {
        $sql = 'SELECT DISTINCT `' . $columnName . '` FROM `' . $tableName . '`';
        $result = $this->db->fetchAll($sql);
        foreach ($result as $row) {
            $strings[] = $row[$columnName];
        }
    }

    private function addConfigMatches(string $configName, string $columnName, array &$strings): void
    {
        if ($columnName !== 'value' && $columnName !== 'default' && $columnName !== 'defaults') {
            throw new \ZfExtended_Exception(
                'Wrong translation annotation for $translateConfig->___()' .
                ', allowed are only "value" and "default" but got " ' . $columnName . '" in ' . $this->absoluteFilePath
            );
        }

        $sql = 'SELECT `' . $columnName . '`, `type`, `typeClass` FROM `Zf_configuration` WHERE `name` = \'' . $configName . '\'';
        $row = $this->db->fetchRow($sql);

        if (! empty($row)) {
            $typeManager = Zend_Registry::get('configTypeManager');
            /* @var $typeManager \ZfExtended_DbConfig_Type_Manager */
            $type = $typeManager->getType($row['typeClass']);

            if ($columnName === 'defaults') {
                $list = $type->getDefaultList($row['defaults']);
                $value = empty($list) ? [] : explode(',', $list);
            } else {
                $value = $type->convertValue($row['type'], $row[$columnName]);
            }
            if (! empty($value)) {
                if (is_array($value) || is_object($value)) {
                    if (is_object($value)) {
                        $value = get_object_vars($value);
                    }
                    foreach ($value as $item) {
                        $strings[] = $item;
                    }
                } else {
                    $strings[] = (string) $value;
                }
            }
        } else {
            error_log('Outdated translation annotation for $translateConfig->___(): ' . $configName);
        }
    }
}
