<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2025 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Plugins\Okapi\Worker;

use MittagQI\Translate5\Plugins\Okapi\Bconf\BconfEntity;
use SplFileInfo;

final class XmlEntitiesPatcher
{
    private const XML_IDENTIFIER_START = 'okf_xml';

    // limit scan of the initial section of the XML for better performance with large files
    private const XML_HEAD_SCAN_LENGTH = 4096;

    private const T5_SIGN = 't5doctype';

    private const T5_CLOSE = 'T5';

    public static function patchBeforeImport(?int $bconfId, SplFileInfo $file): void
    {
        $patchedEntities = self::getPatchedEntities($bconfId, $file->getExtension());
        $entitiesMap = [];
        foreach ($patchedEntities as $entity) {
            $char = html_entity_decode('&' . $entity . ';', ENT_HTML5, 'UTF-8');
            if (mb_strlen($char) === 1) {
                $entitiesMap[$entity] = $char;
            }
        }
        if (empty($entitiesMap)) {
            return;
        }
        $filePath = $file->getRealPath();
        $fileHead = self::readFileHead($filePath);
        if (stripos($fileHead, '<!DOCTYPE') !== false) {
            // extract the existing DOCTYPE and patch it, being aware of possible INLINE declarations
            if (preg_match('/<!DOCTYPE([^]]+)]>/i', $fileHead, $docType)) {
                $entitiesData = '';
                foreach ($entitiesMap as $entity => $char) {
                    if (! preg_match('/<!ENTITY\s+' . $entity . '\s/', $docType[1])) {
                        $entitiesData .= '<!ENTITY ' . $entity . ' "&#' . mb_ord($char) . ';">' . "\n";
                    }
                }
                if (empty($entitiesData)) {
                    return;
                }
                $newDocType = str_replace(']', ' ' . $entitiesData . ']', $docType[0]);
            } elseif (preg_match('/<!DOCTYPE([^>]+)>/i', $fileHead, $docType)) {
                $newDocType = substr($docType[0], 0, -1) . ' [' . self::entitiesToXml($entitiesMap) . ']>';
            } else {
                return;
            }

            $xmlData = str_replace(
                $docType[0],
                $newDocType . "\n" .
                '<!--' . self::T5_SIGN . ' ' . htmlentities($docType[0], ENT_NOQUOTES, 'UTF-8') . ' ' . self::T5_SIGN . '-->',
                file_get_contents($filePath)
            );
        } elseif (preg_match('/<\?xml[^>]*>/i', $fileHead, $xmlTag)) {
            $xmlData = str_replace($xmlTag[0], $xmlTag[0] . '<!DOCTYPE root [' . self::entitiesToXml($entitiesMap) . ']>' . "\n" .
                '<!--' . self::T5_SIGN . '  ' . self::T5_SIGN . '-->', file_get_contents($filePath));
        } else {
            return;
        }
        // TRANSLATE-4778: if a non-declared named entity exists directly before the closing root tag of the document
        $xmlData = preg_replace('/<[^>]+>[^<>]*$/', '<!-- ' . self::T5_CLOSE . ' -->\\0', $xmlData);
        file_put_contents($filePath, $xmlData);
    }

    public static function patchAfterExport(SplFileInfo $file): void
    {
        $filePath = $file->getRealPath();
        $fileHead = self::readFileHead($filePath);
        if (str_contains($fileHead, '<!--' . self::T5_SIGN) && preg_match(
            '/<!--' . self::T5_SIGN . ' (.*?) ' . self::T5_SIGN . '-->/s',
            $fileHead,
            $m
        )) {
            $xmlData = str_replace($m[0], '', file_get_contents($filePath));
            $xmlData = preg_replace(
                ['/<!DOCTYPE[^]]+]>/i', '/<!-- ' . self::T5_CLOSE . ' -->(<[^>]+>[^<>]*)$/'],
                [html_entity_decode($m[1], ENT_NOQUOTES, 'UTF-8'), '\\1'],
                $xmlData
            );
            file_put_contents($filePath, $xmlData);
        }
    }

    private static function getPatchedEntities(?int $bconfId, string $fileExtension): array
    {
        if (empty($bconfId)) {
            return [];
        }
        $bconf = new BconfEntity();
        $bconf->load($bconfId);
        $patchedEntities = trim($bconf->getPatchedEntities());
        $identifiers = $bconf->getExtensionMapping()->findCustomIdentifiers();
        if (empty($identifiers) || empty($patchedEntities)) {
            return [];
        }
        $okfxmlExtensions = [];
        foreach ($identifiers as $identifier) {
            if (str_starts_with($identifier, self::XML_IDENTIFIER_START)) {
                $okfxmlExtensions = array_merge($okfxmlExtensions, $bconf->getExtensionMapping()->findExtensionsForFilter($identifier));
            }
        }
        if (empty($okfxmlExtensions) || ! in_array(strtolower($fileExtension), $okfxmlExtensions)) {
            return [];
        }

        return explode(',', $patchedEntities);
    }

    private static function entitiesToXml(array $entitiesMap): string
    {
        $s = '';
        foreach ($entitiesMap as $entity => $char) {
            $s .= '<!ENTITY ' . $entity . ' "&#' . mb_ord($char) . ';">' . "\n";
        }

        return $s;
    }

    private static function readFileHead(string $filePath): string
    {
        $fp = fopen($filePath, "rb");
        $buf = fread($fp, self::XML_HEAD_SCAN_LENGTH);
        fclose($fp);

        return $buf;
    }
}
