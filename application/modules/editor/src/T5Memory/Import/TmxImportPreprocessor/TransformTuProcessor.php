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

declare(strict_types=1);

namespace MittagQI\Translate5\T5Memory\Import\TmxImportPreprocessor;

use DOMDocument;
use DOMXPath;
use editor_Models_Languages as Language;
use MittagQI\Translate5\Repository\CustomerRepository;
use MittagQI\Translate5\T5Memory\DTO\ImportOptions;
use MittagQI\Translate5\T5Memory\Import\TmxImportPreprocessor\ValueObject\TuFieldsMapping;
use MittagQI\Translate5\TMX\BrokenTranslationUnitLogger\Contract\BrokenTranslationUnitLoggerInterface;
use MittagQI\Translate5\TMX\BrokenTranslationUnitLogger\TranslationUnitCollector\NotMappedFieldsCollector;

class TransformTuProcessor extends Processor
{
    private const MAPPING_CONFIG_NAME = 'runtimeOptions.tmxImportProcessor.transformTusMapping';

    public function __construct(
        private readonly \Zend_Config $config,
        private readonly CustomerRepository $customerRepository,
    ) {
    }

    public static function create(): self
    {
        return new self(
            \Zend_Registry::get('config'),
            CustomerRepository::create(),
        );
    }

    public function supports(Language $sourceLang, Language $targetLang, ImportOptions $importOptions): bool
    {
        return true;
    }

    public function order(): int
    {
        return 100;
    }

    protected function processTu(
        string $tu,
        Language $sourceLang,
        Language $targetLang,
        ImportOptions $importOptions,
        BrokenTranslationUnitLoggerInterface $brokenTranslationUnitIndicator,
    ): iterable {
        //    <tu tuid="_0000ShBleQXBw1620oZMFEDDm">
        //      <tuv xml:lang="de">
        //        <seg>drei Kinder, Baden-Württemberg</seg>
        //      </tuv>
        //      <tuv xml:lang="en-gb" creationdate="20180821T131628Z" changedate="20180821T131628Z">
        //        <prop type="project">12153555</prop>
        //        <prop type="reviewed">false</prop>
        //        <prop type="aligned">false</prop>
        //        <prop type="created_by">DavidMckeown</prop>
        //        <prop type="modified_by">DavidMckeown</prop>
        //        <prop type="filename">DIE BUNDESTAGSFRAKTION IN DER 19.docx</prop>
        //        <seg>three children, Baden-Württemberg</seg>
        //      </tuv>
        //    </tu>

        $tu = preg_replace_callback(
            '/xml:lang="[a-zA-Z\-]+"/U',
            static fn (array $matches) => strtolower($matches[0]),
            $tu
        );

        $dom = new DOMDocument();
        $dom->loadXML($tu);

        $xpath = new DOMXPath($dom);

        $authorIsSet = null !== $this->getValueByPath('//tu/@creationid', $sourceLang, $targetLang, $xpath);
        $creationDateIsSet = null !== $this->getValueByPath('//tu/@creationdate', $sourceLang, $targetLang, $xpath);
        $documentIsSet = null !== $this->getValueByPath(
            '//tu/prop[@type="tmgr:docname"]',
            $sourceLang,
            $targetLang,
            $xpath
        );

        if ($authorIsSet && $creationDateIsSet && $documentIsSet) {
            return yield $tu;
        }

        $mapping = $this->getMappingConfig($importOptions->customerId);

        if (null === $mapping) {
            $tuProps = $xpath->query('//tu/tuv/prop');

            if ($tuProps->length) {
                $brokenTranslationUnitIndicator->collectProblematicTU(NotMappedFieldsCollector::logCode(), $tu);

                return yield from [];
            }

            return yield $tu;
        }

        $author = $this->getValueByPath($mapping->authorPath, $sourceLang, $targetLang, $xpath);
        $creationDate = $this->getValueByPath($mapping->creationDatePath, $sourceLang, $targetLang, $xpath);
        $document = $this->getValueByPath($mapping->documentPath, $sourceLang, $targetLang, $xpath);

        // Get the <tu> element
        $tuNode = $xpath->query('//tu')->item(0);

        // Set author as creationid attribute on <tu> only if not already set
        if ($author !== null && ! $authorIsSet) {
            $tuNode->setAttribute('creationid', $author);
        }

        // Set creation date as creationdate attribute on <tu> only if not already set
        if ($creationDate !== null && ! $creationDateIsSet) {
            $tuNode->setAttribute('creationdate', $creationDate);
        }

        // Add document as <prop type="tmgr:docname"> child of <tu>
        if ($document !== null && ! $documentIsSet) {
            $propNode = $dom->createElement('prop', htmlspecialchars($document, ENT_XML1, 'UTF-8'));
            $propNode->setAttribute('type', 'tmgr:docname');

            // Insert as first child of <tu> before any <tuv>
            $firstTuv = $xpath->query('//tu/tuv')->item(0);
            if ($firstTuv) {
                $tuNode->insertBefore($propNode, $firstTuv);
                // Add newline after the prop node
                $newlineNode = $dom->createTextNode("\n");
                $tuNode->insertBefore($newlineNode, $firstTuv);
            } else {
                $tuNode->appendChild($propNode);
                $tuNode->appendChild($dom->createTextNode("\n"));
            }
        }

        // Remove all <prop> children from <tuv> nodes
        $tuvProps = $xpath->query('//tu/tuv/prop');
        foreach ($tuvProps as $prop) {
            $parent = $prop->parentNode;

            // Remove whitespace text nodes around the prop element
            $previousSibling = $prop->previousSibling;
            if ($previousSibling && $previousSibling->nodeType === XML_TEXT_NODE && trim($previousSibling->nodeValue) === '') {
                $parent->removeChild($previousSibling);
            }

            $parent->removeChild($prop);
        }

        // Clean up excessive whitespace in tuv nodes
        $tuvNodes = $xpath->query('//tu/tuv');
        foreach ($tuvNodes as $tuvNode) {
            // Normalize whitespace between child nodes
            $tuvNode->normalize();
        }

        // Remove all attributes from <tuv> except xml:lang
        $tuvNodes = $xpath->query('//tu/tuv');
        foreach ($tuvNodes as $tuvNode) {
            $attributesToRemove = [];
            foreach ($tuvNode->attributes as $attr) {
                if ($attr->name !== 'xml:lang') {
                    $attributesToRemove[] = $attr->name;
                }
            }
            foreach ($attributesToRemove as $attrName) {
                $tuvNode->removeAttribute($attrName);
            }
        }

        // Return the transformed TU
        yield $dom->saveXML($tuNode);
    }

    private function getValueByPath(
        ?string $path,
        Language $sourceLang,
        Language $targetLang,
        DOMXPath $xpath
    ): ?string {
        if (null === $path) {
            return null;
        }

        $path = str_replace(
            ['{sourceLang}', '{targetLang}'],
            [strtolower($sourceLang->getRfc5646()), strtolower($targetLang->getRfc5646())],
            $path
        );
        $nodes = $xpath->query($path);

        if ($nodes->length) {
            return $nodes->item(0)->nodeValue;
        }

        return null;
    }

    private function getMappingConfig(?int $customerId): ?TuFieldsMapping
    {
        $mappingConfig = $this->config->runtimeOptions->tmxImportProcessor?->transformTusMapping;
        $mappingConfig = $mappingConfig ? $mappingConfig->toArray() : null;
        $mappingConfig = empty($mappingConfig) ? null : TuFieldsMapping::fromArray($mappingConfig);

        if (null === $customerId) {
            return $mappingConfig;
        }

        $customerConfig = $this->customerRepository->getConfigValue($customerId, self::MAPPING_CONFIG_NAME);

        return empty($customerConfig) ? $mappingConfig : TuFieldsMapping::fromArray(json_decode($customerConfig, true));
    }
}
