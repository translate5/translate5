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

use MittagQI\ZfExtended\FileWriteException;
use ZfExtended_Exception;

/**
 * Base code for all ZXLIFF parsing & processing
 * This is no full-fledged XLIFF parser but a faster regex-based implementation
 * Will only deal with source, target, ID and RESNAME attributes
 */
abstract class AbstractXliffProcessor
{
    public const TRANSUNIT_PATTERN = '~<trans-unit([^>]+)>\s*<source>(.*?)</source>\s*<target>(.*?)</target>\s*</trans-unit>~';

    public const RESNAME_PATTERN = '~<trans-unit (id="[^"]+")>\s*<source>(.*?)</source>\s*<target>.*?</target>\s*</trans-unit>~';

    public const ID_PATTERN = '~\s+id\s*=\s*["\']{1}([^"\']+)["\']{1}~';

    public const HEADER_TPL =
        '<?xml version="1.0" ?>' . "\n" .
        '<xliff xmlns="urn:oasis:names:tc:xliff:document:1.1" version="1.1">' . "\n" .
        '    <file original="php-sourcecode" source-language="{sourcelocale}" target-language="{targetlocale}" datatype="php">' . "\n" .
        '        <body>';

    public const FOOTER_TPL = "\n" .
        '        </body>' . "\n" .
        '    </file>' . "\n" .
        '</xliff>';

    public const TRANSUNIT_TPL = "\n" .
        '            <trans-unit id="{id}">' . "\n" .
        '                <source>{source}</source>' . "\n" .
        '                <target>{target}</target>' . "\n" .
        '            </trans-unit>';

    protected string $header;

    protected string $body = '';

    protected string $footer;

    protected string $existingBody = '';

    protected array $existingStrings = [];

    protected int $numUntranslated = 0;

    protected int $numStrings = 0;

    protected bool $wasSaved = false;

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
            if (preg_match(self::ID_PATTERN, $matches[1], $idMatch) === 1) {
                $idSource = $this->parseId($idMatch[1]);
                if ($source !== $idSource) {
                    error_log('Found TRANS-UNIT with differring SOURCE and ID: ' . $matches[0]);
                }
                $this->existingStrings[] = $idSource;
            } else {
                error_log('Found TRANS-UNIT without ID: ' . $matches[0]);
            }

            return '';
        }, $this->existingBody);
    }

    public function getNumStrings(): int
    {
        return $this->numStrings;
    }

    public function getNumUntranslated(): int
    {
        return $this->numUntranslated;
    }

    public function fileChanged(): bool
    {
        return $this->wasSaved;
    }

    /**
     * Saves the file under a different name to import it to translate5 for review
     * Optionally, a resname can be given that will be added to all transunits (adding a counter: resname-N)
     * @throws FileWriteException
     */
    public function saveAsImport(string $absolutePath): void
    {
        // resname will represent location of ZXLIFF in file-system
        $baseResname = $this->createBaseResname();
        // inject resnames
        $body = preg_replace_callback(self::RESNAME_PATTERN, function ($matches) use ($baseResname) {
            $idAttr = $matches[1];
            $resnameAttr = 'resname="' . $baseResname . md5($matches[2]) . '"';

            return str_replace(
                '<trans-unit ' . $idAttr . '>',
                '<trans-unit ' . $idAttr . ' ' . $resnameAttr . '>',
                $matches[0]
            );
        }, $this->body);
        $content = $this->header . $body . $this->footer;
        if (file_put_contents($absolutePath, $content) === false) {
            throw new FileWriteException($absolutePath);
        }
    }

    protected function addTransUnit(string $source, string $target): void
    {
        if ($target === '' && ($this->prefillUntranslated || $this->markUntranslated)) {
            $target = $this->markUntranslated ? L10nConfiguration::UNTRANSLATED : $source;
        }
        $this->body .= $this->createTransUnit($this->createId($source), $source, $target);
    }

    protected function createTransUnit(string $id, string $source, string $target): string
    {
        // we cannot simply use str_replace as this would replace "{source}" and "{target}"
        // also in the replaced source/target !
        $parts = explode('{source}', self::TRANSUNIT_TPL);

        return str_replace('{id}', $id, $parts[0]) .
            $this->prepareText($source) .
            str_replace('{target}', $this->prepareText($target), $parts[1]);
    }

    /**
     * @param array $strings flat array of strings to translate
     * @param array $translations associative array in the format source => target
     * @return int number of untranslated strings after assembly
     */
    protected function assemble(array $strings, array $translations): int
    {
        $this->numUntranslated = 0;
        $this->numStrings = 0;
        $untranslated = [];
        sort($strings, SORT_NATURAL);
        // add all translations
        foreach ($strings as $string) {
            if (! is_string($string)) {
                throw new \Exception(
                    'AbstarctXliffProcessor::assemble: no string provided but ' .
                    gettype($string) . '(' . print_r($string, true) . ')'
                );
            }
            if (array_key_exists($string, $translations)) {
                $this->addTransUnit($string, $translations[$string]);
                if (empty($translations[$string])) {
                    $this->numUntranslated++;
                }
            } elseif ($this->prefillUntranslated || $this->markUntranslated) {
                // when marking, we add an own section for untranslated strings
                $untranslated[] = $string;
                $this->numUntranslated++;
            } else {
                $this->addTransUnit($string, '');
                $this->numUntranslated++;
            }
            $this->numStrings++;
        }
        if (count($untranslated) > 0) {
            $this->body .= "\n" . '            ' . L10nConfiguration::UNTRANSLATED_SECTION;

            foreach ($untranslated as $string) {
                $this->addTransUnit($string, '');
            }
        }

        return $this->numUntranslated;
    }

    /**
     * @throws FileWriteException
     */
    protected function flush(): void
    {
        if ($this->body !== $this->existingBody) {
            $content = $this->header . $this->body . $this->footer;
            if (file_put_contents($this->absoluteFilePath, $content) === false) {
                throw new FileWriteException($this->absoluteFilePath);
            }
            $this->wasSaved = true;
        }
    }

    protected function createId(string $source): string
    {
        return base64_encode($source);
    }

    protected function parseId(string $id): string
    {
        return base64_decode($id);
    }

    /**
     * The relative path of a ZXLIFF file in the code will be used as base resname
     */
    protected function createBaseResname(): string
    {
        // relative path from app root
        $resname = dirname($this->absoluteFilePath);
        $resname = substr($resname, strpos($resname, '/translate5') + 11);
        // remove trailing /locales
        if (str_ends_with($resname, '/locales')) {
            $resname = substr($resname, 0, -8);
        }

        return $resname . '/';
    }

    /**
     * TODO FIXME:
     * There normally should be no parsing neccessary for imported localizations but it turns out, frequently
     * <a>-tags are not converted back from XLIFF-tags. How could that be ?
     * <bpt id="1" rid="1">&lt;a href=&quot;https://confluence.translate5.net/display/visualReview/Upload+rules&quot; target=&quot;_blank&quot;&gt;</bpt>
     */
    protected function prepareText(string $text): string
    {
        if (str_contains($text, '<bpt') || str_contains($text, '<ept') || str_contains($text, '<ph')) {
            return preg_replace_callback('~<(bpt|ept|ph)[^>]+>(.*?)</(bpt|ept|ph)>~', function ($matches) {
                return htmlspecialchars_decode($matches[2], ENT_QUOTES);
            }, $text);
        }

        return $text;
    }

    protected function createHeader(string $locale): string
    {
        return str_replace(
            '{sourcelocale}',
            L10nHelper::createSourceLocale($locale),
            str_replace(
                '{targetlocale}',
                $locale,
                self::HEADER_TPL
            )
        );
    }
}
