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

namespace Translate5\MaintenanceCli\L10n;

use ZfExtended_Exception;

abstract class AbstractXliffProcessor
{
    protected string $header;

    protected string $body = '';

    protected string $footer;

    protected string $existingBody = '';

    protected array $existing = [];

    /**
     * @throws ZfExtended_Exception
     */
    public function __construct(
        protected string $absoluteFilePath,
        protected bool $prefillUntranslated = false,
        protected bool $markUntranslated = false
    ) {
        $content = file_get_contents($this->absoluteFilePath);
        if ($content === false) {
            throw new ZfExtended_Exception('Could not read XLIFF file ' . $this->absoluteFilePath);
        }
        preg_replace_callback('~<trans-unit[^>]+>~', function ($matches) {
            if (preg_match('~\s+id\s*=\s*["\']{1}([^"\']+)["\']{1}~', $matches[0], $idMatch) === 1) {
                $this->existing[] = base64_decode($idMatch[1]);
            }

            return '';
        }, $content);

        $parts = explode('<body>', $content);
        if (count($parts) !== 2) {
            throw new ZfExtended_Exception('XLIFF file ' . $this->absoluteFilePath . ' has an invalid structure');
        }
        $this->header = $parts[0] . '<body>';

        $parts = explode('</body>', $parts[1]);
        if (count($parts) !== 2) {
            throw new ZfExtended_Exception('XLIFF file ' . $this->absoluteFilePath . ' has an invalid structure');
        }
        $this->existingBody = rtrim($parts[0]);
        $this->footer = "\n" . '        </body>' . $parts[1];
    }

    public function saveAs(string $absolutePath): void
    {
        $content = $this->header . $this->body . $this->footer;
        file_put_contents($absolutePath, $content);
    }

    protected function addTransUnit(string $source, string $target): void
    {
        if ($target === '' && ($this->prefillUntranslated || $this->markUntranslated)) {
            $target = $this->markUntranslated ? L10nConfiguration::UNTRANSLATED : $source;
        }
        $this->body .= "\n" .
            '            <trans-unit id="' . base64_encode($source) . '">' . "\n" .
            '                <source>' . $source . '</source>' . "\n" .
            '                <target>' . $target . '</target>' . "\n" .
            '            </trans-unit>';
    }

    /**
     * @param array $strings flat array of strings to translate
     * @param array $translations associative array in the format source => target
     * @return int number of untranslated strings after assembly
     */
    protected function assemble(array $strings, array $translations): int
    {
        $untranslated = [];
        sort($strings, SORT_NATURAL);
        // add all translations
        foreach ($strings as $string) {
            if (array_key_exists($string, $translations)) {
                $this->addTransUnit($string, $translations[$string]);
            } elseif ($this->prefillUntranslated || $this->markUntranslated) {
                // when marking, we add an own section for untranslated strings
                $untranslated[] = $string;
            } else {
                $this->addTransUnit($string, '');
            }
        }
        if (count($untranslated) > 0) {
            $this->body .= "\n" . '            ' . L10nConfiguration::UNTRANSLATED_SECTION;

            foreach ($untranslated as $string) {
                $this->addTransUnit($string, '');
            }
        }

        return count($untranslated);
    }

    protected function flush(): void
    {
        $content = $this->header . $this->body . $this->footer;
        file_put_contents($this->absoluteFilePath, $content);
    }
}
