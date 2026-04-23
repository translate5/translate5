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

/**
 * Parser for ZXLIFF files tailored for the processing of JSON translations
 */
class JsonXliffParser extends AbstractXliffProcessor
{
    private const string TAG_PATTERN = '~<([^>]+)>~';

    /**
     * @var array<string, string>
     */
    protected array $sourceMap;

    /**
     * @var array<string, string>
     */
    protected array $targetMap;

    /**
     * @throws ZfExtended_Exception
     */
    public function __construct(
        protected string $absoluteFilePath,
        protected string $targetLocale,
    ) {
        if (file_exists($this->absoluteFilePath)) {
            $content = file_get_contents($this->absoluteFilePath);
            if ($content === false) {
                throw new ZfExtended_Exception('Could not read XLIFF file ' . $this->absoluteFilePath);
            }
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
            // find existing strings
            preg_replace_callback(self::TRANSUNIT_PATTERN, function ($matches) {
                $source = $matches[2];
                $target = $matches[3];
                if (preg_match(self::ID_PATTERN, $matches[1], $idMatch) === 1) {
                    $id = $idMatch[1];
                } else {
                    throw new ZfExtended_Exception(
                        'XLIFF file ' . $this->absoluteFilePath . ' has an invalid structure, trans-unit without ID'
                    );
                }
                $this->sourceMap[$id] = $this->unprotectText($source);
                $this->targetMap[$id] = $this->unprotectText($target);

                return '';
            }, $this->existingBody);
        } else {
            $this->header = $this->createHeader($targetLocale);
            $this->existingBody = '';
            $this->footer = self::FOOTER_TPL;
        }
    }

    /**
     * @return array<string, string>
     */
    public function getSourceMap(): array
    {
        return $this->sourceMap;
    }

    /**
     * @return array<string, string>
     */
    public function getTargetMap(): array
    {
        return $this->targetMap;
    }

    /**
     * API usede when exporting JSON based localization to ZXLIFF
     * Expects 2 id => text maps, one for source-locale, one for target-locale with identical keys
     * @throws ZfExtended_Exception
     */
    public function saveFromSourceTargetMaps(array $sourceMap, array $targetMap): void
    {
        $this->body = '';
        foreach ($sourceMap as $id => $source) {
            // Leading/trailing whitespace cannot be saved to a TM so we generate an Exception here
            // so that Problem needs to be solved first
            if (trim($source) !== $source) {
                throw new \ZfExtended_Exception(
                    'Found JSON localization source string that has leading or trailing whitespace.' .
                    ' This must be solved before an export/import can be made: "' . $source . '"'
                );
            }

            if (array_key_exists($id, $targetMap)) {
                $this->body .= $this->createTransUnit(
                    $id . '" resname="' . $id, // HACK: adding the resname-attributes in a dirty way ...
                    $this->protectText($source),
                    $this->protectText($targetMap[$id])
                );
            } else {
                throw new ZfExtended_Exception(
                    'JsonXliffParser::setTranslations: sourceMap and targetMap must have identical keys'
                );
            }
        }
        $this->sourceMap = $sourceMap;
        $this->targetMap = $targetMap;

        $this->flush();
    }

    /**
     * Ugly: there is markup in the JSON-strings without the JSON-strings being proper markup ( holding unescaped stuff)
     * TODO FIXME: is there a better way to escape the stuff properly for XLIFF ? Do we already have such functionality ?
     */
    private function protectText(string $text): string
    {
        // normalize <br/>
        $text = preg_replace('~<br\s+/>~i', '<br/>', $text);
        // allow br, p, a
        $text = preg_replace_callback(self::TAG_PATTERN, function ($matches) {
            $parts = explode(' ', $matches[1]);
            if (in_array($parts[0], ['br/', 'p', '/p', 'a', '/a', 'li', '/li', 'ol', '/ol', 'ul', '/ul'])) {
                return $matches[0];
            }

            return '&lt;' . $matches[1] . '&gt;';
        }, $text);

        // try not to double-encode ...
        return str_replace('& ', '&amp; ', $text);
    }

    /**
     * Ugly: undoing escaping of above
     * TODO FIXME: is there a better way to escape the stuff properly for XLIFF ? Do we already have such functionality ?
     */
    private function unprotectText(string $text): string
    {
        return htmlspecialchars_decode($text, ENT_QUOTES);
    }
}
