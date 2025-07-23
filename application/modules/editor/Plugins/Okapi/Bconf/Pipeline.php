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

declare(strict_types=1);

namespace MittagQI\Translate5\Plugins\Okapi\Bconf;

use editor_Plugins_Okapi_Init;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Segmentation\Srx;
use MittagQI\ZfExtended\MismatchException;
use SimpleXMLElement;
use ZfExtended_Exception;
use ZfExtended_NotFoundException;
use ZfExtended_SecurityException;

/**
 * Class representing the pipeline of a BCONF
 * This generally is a xml file with x-properties Sections
 * For validation, we analyse the content as XML and properties
 * To manipulate the SRX-paths, we replace the strings as it is too complicated to implement a XML/Properties bridge
 * A Pipeline will have the Translate5 OKAPI Version Index as Attribute from version 10 on
 */
final class Pipeline extends ResourceFile
{
    /*
     * A typical pipeline file looks like this:
    </rainbowPipeline>
     */

    public const FILE = 'pipeline.pln';

    public const BCONF_VERSION_ATTR = 't5bconfVersion';

    // a Pipeline is generally a XML variant
    protected string $mime = 'text/xml';

    private ?string $sourceSrxPath = null;

    private ?string $targetSrxPath = null;

    private ?string $xsltPath = null;

    private int $bconfVersion = -1;

    /**
     * @var string[]
     */
    private array $steps = [];

    /**
     * @var string[]
     */
    private array $errors = [];

    /**
     * Marker for the bconf-entity for proper caching
     */
    private int $bconfId;

    private bool $needsRepair = false;

    private string $repairedContent;

    private const IMPORT_STEPS = [
        'XSLTransformStep' => [
            'required' => false,
            'javaClass' => 'net.sf.okapi.steps.xsltransform.XSLTransformStep',
            'requiredProperties' => [
                // Format: propertyName => forcedValue (null means any value is ok)
                'xsltPath' => null,
            ],
        ],
        'RawDocumentToFilterEventsStep' => [
            'required' => true,
            'javaClass' => 'net.sf.okapi.steps.common.RawDocumentToFilterEventsStep',
            'requiredProperties' => [],
        ],
        'SegmentationStep' => [
            'required' => true,
            'javaClass' => 'net.sf.okapi.steps.segmentation.SegmentationStep',
            'requiredProperties' => [
                'doNotSegmentIfHasTarget.b' => false,
            ],
        ],
        'ExtractionStep' => [
            'required' => true,
            'javaClass' => 'net.sf.okapi.steps.rainbowkit.creation.ExtractionStep',
            'requiredProperties' => [
                'writerOptions.includeNoTranslate.b' => true,
                'writerOptions.includeIts.b' => true,
                'writerOptions.includeAltTrans.b' => true,
                'writerOptions.includeCodeAttrs.b' => true,
                'writerOptions.escapeGT.b' => true,
            ],
        ],
    ];

    /**
     * @throws ZfExtended_Exception
     */
    public function __construct(string $path, string $content = null, int $bconfId = -1)
    {
        parent::__construct($path, $content);
        $this->bconfId = $bconfId;
        $this->parse($this->content);
    }

    public function getBconfId(): int
    {
        return $this->bconfId;
    }

    /**
     * Retrieves the BCONF Version-index, that is saved in the Pipeline XML from version 10 on
     * For BCONFs without version, this returns -1
     */
    public function getBconfVersion(): int
    {
        return $this->bconfVersion;
    }

    /**
     * A readonly-prop of the encoded steps of the pipeline that is evaluated when parsing
     * @return string[]
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    /**
     * @return mixed
     * @throws ZfExtended_Exception
     */
    public function getSrxFile(string $field): string
    {
        if ($field === 'source') {
            return $this->sourceSrxPath;
        } elseif ($field === 'target') {
            return $this->targetSrxPath;
        } else {
            throw new ZfExtended_Exception('Invalid field "' . $field . '", must be "source" or "target"');
        }
    }

    /**
     * Updates a SRX. Does not flush the content
     * @throws ZfExtended_Exception
     */
    public function setSrxFile(string $field, string $file): void
    {
        if (pathinfo($file, PATHINFO_EXTENSION) !== Srx::EXTENSION) {
            throw new ZfExtended_Exception('A SRX file must have the file-extension "srx": ' . $file);
        }
        if ($field === 'source') {
            $this->content = preg_replace_callback('~sourceSrxPath\s*=([^\r\n]*)~', function ($m) use ($file) {
                return 'sourceSrxPath=' . pathinfo($m[1], PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . $file;
            }, $this->content);
            $this->sourceSrxPath = $file;
        } elseif ($field === 'target') {
            $this->content = preg_replace_callback('~targetSrxPath\s*=([^\r\n]*)~', function ($m) use ($file) {
                return 'targetSrxPath=' . pathinfo($m[1], PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . $file;
            }, $this->content);
            $this->targetSrxPath = $file;
        } else {
            throw new MismatchException('E2004', [$field, 'field']);
        }
    }

    public function getXsltFile(): ?string
    {
        return $this->xsltPath;
    }

    /**
     * @throws ZfExtended_Exception
     */
    public function setXsltFile(string $file): void
    {
        if (pathinfo($file, PATHINFO_EXTENSION) !== 'xslt') {
            throw new ZfExtended_Exception('A XSLT file must have the file-extension "xslt": ' . $file);
        }
        $this->content =
            preg_replace('~xsltPath\s*=\s*' . pathinfo($this->xsltPath, PATHINFO_FILENAME)
                . '~', 'xsltPath=' . pathinfo($file, PATHINFO_FILENAME), $this->content);
        $this->xsltPath = $file;
    }

    public function hasIdenticalSourceAndTargetSrx(): bool
    {
        return ($this->sourceSrxPath === $this->targetSrxPath);
    }

    public function validate(bool $forImport = false): bool
    {
        if (count($this->errors) > 0) {
            $this->validationError = ucfirst(implode(', ', $this->errors));
            // DEBUG
            if ($this->doDebug) {
                error_log('PIPELINE IS INVALID: ' . $this->validationError);
            }

            return false;
        }
        // DEBUG
        if ($this->doDebug) {
            error_log('PIPELINE ' . $this->path . ' IS VALID');
        }

        return true;
    }

    /**
     * @throws ZfExtended_Exception
     * @throws ZfExtended_NotFoundException
     */
    private function parse(string $content): void
    {
        $this->needsRepair = false;
        $this->errors = [];
        $xml = simplexml_load_string($content);
        if (! $xml) {
            $this->errors[] = 'invalid XML content in ' . self::FILE;

            return;
        }
        $version = isset($xml['version']) ? (int) $xml['version'] : -1;
        if ($version !== 1) {
            $this->errors[] = 'invalid Version of pipeline "' . $version . '"';

            return;
        }
        // T5 bconf version index
        if (isset($xml[self::BCONF_VERSION_ATTR])) {
            $this->bconfVersion = (int) $xml[self::BCONF_VERSION_ATTR];
            if ($this->bconfVersion !== editor_Plugins_Okapi_Init::BCONF_VERSION_INDEX) {
                $xml[self::BCONF_VERSION_ATTR] = (string) editor_Plugins_Okapi_Init::BCONF_VERSION_INDEX;
                $this->needsRepair = true;
            }
        } else {
            $xml->addAttribute(self::BCONF_VERSION_ATTR, (string) editor_Plugins_Okapi_Init::BCONF_VERSION_INDEX);
            $this->needsRepair = true;
        }
        // Used for error message for missing required steps
        $requiredSteps = array_filter(self::IMPORT_STEPS, function ($v) {
            return $v['required'];
        });

        foreach ($xml->step as $stepXml) { /** @var SimpleXMLElement $stepXml */
            $javaClass = (string) $stepXml['class'];
            if (empty($javaClass)) {
                continue;
            }
            if (stripos($javaClass, 'externalcommand') !== false) {
                throw new ZfExtended_SecurityException('Step not allowed: ' . $javaClass);
            }
            $this->steps[] = $javaClass;
            $stepClass = substr(strrchr($javaClass, '.'), 1);
            if (isset(self::IMPORT_STEPS[$stepClass])) {
                if ($javaClass !== self::IMPORT_STEPS[$stepClass]['javaClass']) {
                    $this->errors[] = 'invalid javaClass for step "' . $stepClass . '" : ' . $javaClass;

                    continue;
                }
                if (isset($requiredSteps[$stepClass])) {
                    unset($requiredSteps[$stepClass]);
                }
                $step = new StepValidation((string) $stepXml, self::IMPORT_STEPS[$stepClass]['requiredProperties']);
                if (! $step->isValid()) {
                    $this->errors[] = $step->getErrMsg();
                } else {
                    if ($step->wasRepaired()) {
                        $this->needsRepair = true;
                        // @phpstan-ignore-next-line
                        $stepXml[0] = $step->getProperties()->unparse(); // https://github.com/phpstan/phpstan/issues/8236
                        // dom_import_simplexml($stepXml)->nodeValue = $step->getProperties()->unparse();
                    }
                    if ($stepClass === 'SegmentationStep') {
                        $props = $step->getProperties();
                        $this->sourceSrxPath = $props->has('sourceSrxPath') ?
                            self::basename($props->get('sourceSrxPath')) : null;
                        $this->targetSrxPath = $props->has('targetSrxPath') ?
                            self::basename($props->get('targetSrxPath')) : null;
                    } elseif ($stepClass === 'XSLTransformStep') {
                        $props = $step->getProperties();
                        $this->xsltPath = $props->has('xsltPath') ?
                            self::basename($props->get('xsltPath')) : null;
                    }
                }
            }
        }
        if (count($requiredSteps) > 0) {
            $this->errors[] = 'the pipeline has missing steps: ' . implode(', ', array_keys($requiredSteps));
        }
        if (empty($this->sourceSrxPath) ||
            empty($this->targetSrxPath) ||
            pathinfo($this->sourceSrxPath, PATHINFO_EXTENSION) != Srx::EXTENSION ||
            pathinfo($this->targetSrxPath, PATHINFO_EXTENSION) != Srx::EXTENSION
        ) {
            $this->errors[] = 'the pipeline had no or invalid entries for the source or target segmentation srx file';
        } else {
            // we will remove any path from the SRX-Files to normalize the value (it usually contains the rainbow workspace path)
            // this also is a security-related necessity since an attack with paths on the server's file-system could be attempted
            if (self::basename($this->sourceSrxPath) != $this->sourceSrxPath) {
                $this->setSrxFile('source', self::basename($this->sourceSrxPath));
            }
            if (basename($this->targetSrxPath) != $this->targetSrxPath) {
                $this->setSrxFile('target', self::basename($this->targetSrxPath));
            }
            if ($this->xsltPath !== null && self::basename($this->xsltPath) != $this->xsltPath) {
                $this->setXsltFile(self::basename($this->xsltPath));
            }
        }
        if ($this->needsRepair) {
            if ($xml->asXml()) {
                $this->repairedContent = $xml->asXml();
            } else {
                $this->errors[] = 'Error parsing the XML - probably invalid';
                $this->needsRepair = false;
            }
        }
        // we do not want to debug the default-merging pipeline / the export-bconf
        if ($this->doDebug && $this->needsRepair && $this->getFile() !== 'translate5-merging.pln') {
            error_log('PIPELINE for bconf ' . $this->bconfId . ' needs repair!');
        }
    }

    public function hasToBeRepaired(): bool
    {
        return $this->needsRepair;
    }

    public function repair(): void
    {
        if ($this->needsRepair) {
            $this->content = $this->repairedContent;
            $this->repairedContent = '';
            $this->needsRepair = false;

            if ($this->doDebug) {
                error_log('PIPELINE: Repaired for bconf ' . $this->bconfId . ', version ' . $this->bconfVersion);
            }

            $this->bconfVersion = editor_Plugins_Okapi_Init::BCONF_VERSION_INDEX;
        }
    }

    /**
     * Handles correctly Windows-based paths
     */
    private static function basename(string $filename): string
    {
        $filename = str_replace('\\', '/', $filename);

        return basename($filename);
    }
}
