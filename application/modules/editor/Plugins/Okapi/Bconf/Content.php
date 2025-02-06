<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Plugins\Okapi\Bconf;

use MittagQI\Translate5\Plugins\Okapi\Bconf\Segmentation\Srx;
use stdClass;
use ZfExtended_Debug;
use ZfExtended_Exception;

/**
 * Class representing the Content/TOC of a bconf
 * This generally is a JSON file that will not be embedded in the bconf
 * but just acts as an inventory for all extracted files of the bconf
 */
final class Content extends ResourceFile
{
    /*
    fixed structure of our contents
    {
        "refs": {
            "sourceSrxPath": "languages.srx",
            "targetSrxPath": "languages.srx"
        },
        "step": [
            "net.sf.okapi.steps.common.RawDocumentToFilterEventsStep",
            "net.sf.okapi.steps.segmentation.SegmentationStep",
            "net.sf.okapi.steps.rainbowkit.creation.ExtractionStep"
        ],
        "fprm": [
            "okf_plaintext@translate5local-plaintext_customized",
            "okf_wiki@translate5local-wiki_markup_customized"
        ]
    }
     */

    /**
     * @var string
     */
    public const FILE = 'content.json';

    /**
     * we're dealing with a JSON file
     */
    protected string $mime = 'application/json';

    private stdClass $refs;

    private array $step = [];

    private array $fprm = [];

    /**
     * Marker for the bconf-entity for proper caching
     */
    private int $bconfId;

    /**
     * @throws ZfExtended_Exception
     */
    public function __construct(string $path, string $content = null, int $bconfId = -1, bool $doCreateEmpty = false)
    {
        $this->path = $path;
        $this->bconfId = $bconfId;
        $this->doDebug = ZfExtended_Debug::hasLevel('plugin', 'OkapiBconfValidation');
        if (! $doCreateEmpty) {
            if ($content === null) {
                $this->parse(file_get_contents($path));
            } else {
                $this->parse($content);
            }
        } else {
            $this->refs = new stdClass();
        }
    }

    public function getBconfId(): int
    {
        return $this->bconfId;
    }

    /**
     * Adds a filter-identifier and updates the content. Does not flush the related file !
     */
    public function addFilter(string $identifier)
    {
        $this->fprm[] = $identifier;
    }

    /**
     * Removes a filter-identifier and updates the content. Does not flush the related file !
     */
    public function removeFilter(string $identifier)
    {
        $this->fprm = array_diff($this->fprm, [$identifier]);
    }

    /**
     * Sets the filter-identifiers and updates the content. Does not flush the related file !
     */
    public function setFilters(array $identifiers)
    {
        $this->fprm = $identifiers;
    }

    /**
     * @return mixed
     * @throws ZfExtended_Exception
     */
    public function getSrxFile(string $field): string
    {
        if ($field === 'source') {
            return $this->refs->sourceSrxPath;
        } elseif ($field === 'target') {
            return $this->refs->targetSrxPath;
        } else {
            throw new ZfExtended_Exception('Invalid field "' . $field . '", must be "source" or "target"');
        }
    }

    /**
     * Sets the SRX-path and updates the content. Does not flush the related file !
     * @throws ZfExtended_Exception
     */
    public function setSrxFile(string $field, string $file)
    {
        if (pathinfo($file, PATHINFO_EXTENSION) !== Srx::EXTENSION) {
            throw new ZfExtended_Exception('A SRX file must have the file-extension "srx": ' . $file);
        }
        if ($field === 'source') {
            $this->refs->sourceSrxPath = $file;
        } elseif ($field === 'target') {
            $this->refs->targetSrxPath = $file;
        } else {
            throw new ZfExtended_Exception('Invalid field "' . $field . '", must be "source" or "target"');
        }
    }

    /**
     * Adds our steps and updates the content. Does not flush the related file !
     */
    public function setSteps(array $steps): void
    {
        $this->step = $steps;
    }

    public function hasIdenticalSourceAndTargetSrx(): bool
    {
        return ($this->refs->sourceSrxPath === $this->refs->targetSrxPath);
    }

    /**
     * Validates a content.json
     */
    public function validate(bool $forImport = false): bool
    {
        $errors = [];
        if (! property_exists($this->refs, 'sourceSrxPath') || empty($this->refs->sourceSrxPath)) {
            $errors[] = 'no source SRX set';
        }
        if (! property_exists($this->refs, 'targetSrxPath') || empty($this->refs->targetSrxPath)) {
            $errors[] = 'no target SRX set';
        }
        if (count($this->step) < 1) {
            $errors[] = 'no step found';
        }
        if (count($errors) > 0) {
            $this->validationError = ucfirst(implode(', ', $errors)) . '.';

            return false;
        }

        return true;
    }

    /**
     * @throws ZfExtended_Exception
     */
    private function parse(string $content): void
    {
        if (empty($content)) {
            throw new ZfExtended_Exception('Invalid JSON content of ' . self::FILE . ': Empty content');
        }
        $json = json_decode($content);
        if (empty($json) ||
            ! is_object($json) ||
            ! property_exists($json, 'refs') ||
            ! property_exists($json, 'fprm')
        ) {
            throw new ZfExtended_Exception('Invalid JSON content of ' . self::FILE . ': Invalid structure');
        }
        if (property_exists($json, 'step')) {
            $this->step = $json->step;
        } else {
            // LEGACY FIX:
            // in the first revisions of the bconf-management, the steps have not been extracted ...
            // we repair that by extracting them from the pipeline
            $this->step = $this->getLegacySteps();
        }
        if (is_array($json->refs)) {
            // LEGACY FIX:
            // in the first revisions of the bconf-management, the refs have been an array
            if (count($json->refs) < 1) {
                throw new ZfExtended_Exception(
                    'Invalid JSON content of ' . self::FILE . ': Invalid structure, references missing'
                );
            }
            $this->refs = new stdClass();
            $this->refs->sourceSrxPath = $json->refs[0];
            $this->refs->targetSrxPath = (count($json->refs) > 1) ? $json->refs[1] : $json->refs[0];
        } else {
            $this->refs = $json->refs;
        }
        $this->fprm = $json->fprm;
    }

    public function getContent(): string
    {
        $data = new stdClass();
        $data->refs = $this->refs;
        $data->step = $this->step;
        $data->fprm = $this->fprm;

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * Very outdated installations may have no steps parsed into the bconf, we add them here
     * @return string[]
     * @throws ZfExtended_Exception
     */
    private function getLegacySteps(): array
    {
        $pipelinePath = dirname($this->getPath()) . '/' . Pipeline::FILE;
        $pipeline = new Pipeline($pipelinePath, null, $this->getBconfId());

        return $pipeline->getSteps();
    }
}
