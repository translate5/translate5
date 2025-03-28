<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

use MittagQI\Translate5\Plugins\Okapi\Bconf\Parser\PropertiesParser;

class StepValidation
{
    protected string $errMsg = '';

    protected ?PropertiesParser $props = null;

    /* Format: 'propertyName'=>'forcedValue' where forcedValue can be null to keep the existing value */
    protected array $requiredProperties = [];

    protected bool $canBeEmpty = true;

    private bool $wasRepaired = false;

    /**
     * @throws \ZfExtended_Exception
     * @throws \ZfExtended_NotFoundException
     */
    public function __construct(string $contents, array $requiredProperties = [])
    {
        $this->requiredProperties = $requiredProperties;
        if (! empty($this->requiredProperties)) {
            $this->canBeEmpty = false;
        }
        $this->errMsg = $this->validate($contents);
    }

    public function getProperties(): ?PropertiesParser
    {
        return $this->props;
    }

    public function isValid(): bool
    {
        return $this->errMsg === '';
    }

    public function wasRepaired(): bool
    {
        return $this->wasRepaired;
    }

    public function getErrMsg(): string
    {
        return $this->errMsg;
    }

    /**
     * @throws \ZfExtended_Exception
     * @throws \ZfExtended_NotFoundException
     */
    protected function validate(string $contents): string
    {
        if (empty($contents) && $this->canBeEmpty) {
            return '';
        }
        $this->props = new PropertiesParser($contents);
        if (! $this->props->isValid()) {
            return 'invalid ' . $this->getCurrentStep() . ' (' . $this->props->getErrorString(', ') . ')';
        }

        foreach ($this->requiredProperties as $propertyName => $forcedValue) {
            if (! $this->props->has($propertyName)) {
                if ($forcedValue === null) {
                    return 'missing required property in ' . $this->getCurrentStep() . ': ' . $propertyName;
                }
                $this->props->add($propertyName, $forcedValue);
                $this->wasRepaired = true;
            } elseif ($forcedValue === null) {
                continue;
            } elseif ($this->props->get($propertyName) !== $forcedValue) {
                $this->props->set($propertyName, $forcedValue);
                $this->wasRepaired = true;
            }
        }

        return '';
    }

    private function getCurrentStep(): string
    {
        return substr(strrchr(get_class($this), '\\'), 1);
    }
}
