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

namespace MittagQI\Translate5\Plugins\Okapi\Bconf\Filter;

use MittagQI\Translate5\Plugins\Okapi\Bconf\Filters;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Parser\PropertiesParser;
use MittagQI\Translate5\Plugins\Okapi\Bconf\ResourceFile;
use Throwable;
use ZfExtended_Exception;

/**
 * Class validating a fprm file in the X-Properties format
 * This validates the given X-Properties file against the passed okapiType as reference
 * Reference-file are the default-fprms out of the okapi default inventory
 *
 * #v1
 * extractIsolatedStrings.b=false
 * codeFinderRules.rule0=</?([A-Z0-9a-z]*)\b[^>]*>
 * extractAllPairs.b=true
 * genericMetaRules=
 * codeFinderRules.count.i=1
 */
final class PropertiesValidation extends ResourceFile
{
    /**
     * UGLY: there are "volitile" variables that mimic a list (the properties-format has no support for arrays/lists)
     * They are included as properties but are not mandatory and represent the state of other properties
     * The concrete naming is like "zzz0", "hlt1", ...
     * @var array
     */
    public const VOLATILE_VARS = ['ccc', 'cfd', 'hlt', 'sln', 'sss', 'yyy', 'zzz']; // are from the openxml filter

    protected string $mime = 'text/x-properties';

    private bool $needsRepair = false;

    private PropertiesParser $props;

    private PropertiesParser $referenceProps;

    private bool $strict = false;

    private array $volatiles = [];

    /**
     * @throws ZfExtended_Exception
     */
    public function __construct(string $path, string $content = null)
    {
        parent::__construct($path, $content);
        $identifier = Filters::createIdentifierFromPath($path);
        $idata = Filters::parseIdentifier($identifier);
        // find volatile-props - if already defined
        $volatiles = VolatileProperties::instance()->getPropertyNames($idata->type);
        if ($volatiles !== null) {
            $this->strict = true;
            $this->volatiles = $volatiles;
        }
        // try to get the default validation file
        $validationFile = Filters::instance()->getOkapiDefaultFilterPathById($idata->type);
        if (empty($validationFile)) {
            $filters = OkapiFilterInventory::instance()->findFilter($idata->type);
            if (count($filters) > 0) {
                $validationFile = OkapiFilterInventory::instance()->createFprmPath($filters[0]);
            }
        }
        if (empty($validationFile)) {
            // DEBUG
            if ($this->doDebug) {
                error_log('PROPERTIES VALIDATION ERROR: "' . $idata->type . '" seems no valid okapi-type');
            }
            $this->validationError = '"' . $idata->type . '" seems no valid okapi-type';
        } else {
            $this->validationError = ''; // to avoid errors due to accessing unitialized vars ...
            $this->referenceProps = new PropertiesParser(file_get_contents($validationFile));
            if (! $this->referenceProps->isValid()) {
                // DEBUG
                if ($this->doDebug) {
                    error_log('PROPERTIES VALIDATION ERROR: Invalid reference file "' . $validationFile
                        . '": (' . $this->referenceProps->getErrorString(', ') . ')');
                }

                throw new ZfExtended_Exception(
                    'Invalid reference file "' . $validationFile . '" ('
                    . $this->referenceProps->getErrorString(', ') . ')'
                );
            }
            $this->props = new PropertiesParser($this->content);
            if (! $this->props->isValid()) {
                // DEBUG
                if ($this->doDebug) {
                    error_log('PROPERTIES VALIDATION ERROR: Invalid fprm "' . $path
                        . '": (' . $this->props->getErrorString(', ') . ')');
                }
                $this->validationError = trim($this->validationError . ' ' . $this->props->getErrorString());
            }
        }
    }

    /**
     * Retrieves if we know all dynamic/volatile properties a FPRM can have and
     * are able to remove obsolete ones therefore
     */
    public function isStrict(): bool
    {
        return $this->strict;
    }

    public function hasToBeRepaired(): bool
    {
        return $this->needsRepair;
    }

    /**
     * Validates a FPRM based on it's type
     * We will ignore extra-values that may be in the FPRM compared to the reference
     * We will add missing values in comparision to the original file
     */
    public function validate(bool $forImport = false): bool
    {
        if (! $this->props->isValid()) {
            return false;
        }
        $valid = true;
        $missingProps = [];
        foreach ($this->referenceProps->getPropertyNames() as $varName) {
            if (! $this->props->has($varName)) {
                $missingProps[] = $varName;
            } else {
                try {
                    $this->referenceProps->set($varName, $this->props->get($varName));
                } catch (Throwable $e) {
                    // highly improbable but who knows ...
                    // DEBUG
                    if ($this->doDebug) {
                        error_log('PROPERTIES VALIDATION PROBLEM: The file has an invalid value "'
                            . $varName . '": ' . $e->getMessage());
                    }
                    $this->validationError = trim($this->validationError . "\n"
                        . ' The file has an invalid value: ' . $varName);
                    $valid = false;
                }
            }
        }
        // remove missing volatile props
        if (count($missingProps) > 0) {
            $props = [];
            foreach ($missingProps as $prop) {
                if ($this->isVolatileProperty($prop)) {
                    $this->referenceProps->remove($prop);
                } else {
                    $props[] = $prop;
                }
            }
            $missingProps = $props;
        }
        // if there are additional vars we add them to our reference props
        $additionalProps = array_diff($this->props->getPropertyNames(), $this->referenceProps->getPropertyNames());
        if (count($additionalProps) > 0) {
            if ($this->doDebug) {
                error_log('PROPERTIES VALIDATION PROBLEM: The file has additional values compared to the reference:'
                    . ' (' . implode(', ', $additionalProps) . ')');
            }
            foreach ($additionalProps as $propName) {
                $this->referenceProps->add($propName, $this->props->get($propName));
            }
            $this->needsRepair = true;
        }
        if (count($missingProps) > 0) {
            // DEBUG
            if ($this->doDebug) {
                error_log('PROPERTIES VALIDATION PROBLEM: The file has missing values compared to the reference:'
                    . ' (' . implode(', ', $missingProps) . ')');
            }
            $this->validationError = trim($this->validationError . "\n" . 'The file has missing values ('
                . implode(', ', $missingProps) . ')');
            // SPECIAL: when importing, we silently ignore missing props, when validating edited FPRMs,
            // we need to be more picky as this hints to an incomplete GUI (maybe due to rainbow updates)
            if (! $forImport) {
                $valid = false;
            }
            $this->needsRepair = true;
        }
        if ($this->needsRepair) {
            $this->content = $this->referenceProps->unparse();
        }

        return $valid;
    }

    /**
     * Evaluates, if a variable-name represents a volatile/dynamic property
     * A volatile property results from serialized array or collection data and can have certain forms:
     *  cfd0=HYPERLINK // simple list
     *  codeFinderRules0=[A-Z]+ // simple list
     *  rule0.codeFinderRules.rule1=a-z0-9]+// double nested collection
     *  fontMappings.1.sourceLocalePattern=[a-z0-9]+ // another type of nested collection
     */
    private function isVolatileProperty($name): bool
    {
        // we only regard the first part of a nested variable or dismiss the type
        if (str_contains($name, '.')) {
            $parts = explode('.', $name);
            $name = $parts[0];
        }
        $name = $this->removeIntegerSuffix($name);

        return in_array($name, $this->volatiles);
    }

    private function removeIntegerSuffix(string $name): string
    {
        if (preg_match('/^(.*?)(\d+)$/', $name, $matches)) {
            // we do not regard e.g. "777" as integer suffixed
            if ($matches[1] !== '') {
                return $matches[1];
            }
        }

        return $name;
    }
}
