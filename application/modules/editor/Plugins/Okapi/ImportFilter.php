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

namespace MittagQI\Translate5\Plugins\Okapi;

use editor_Plugins_Okapi_Bconf_Analysis;
use editor_Plugins_Okapi_Bconf_Entity;
use ZfExtended_Exception;

/**
 * Helper to handle the file-types handled by OKAPI in the import
 * These Extensions depend on the selected bconf OR, if provided, an embedded bconf
 */
final class ImportFilter
{
    private $supportedExtensions = [];

    public function __construct(
        private ?editor_Plugins_Okapi_Bconf_Entity $bconf = null,
        private ?string $bconfInZip = null
    ) {
        if ($bconfInZip !== null) {
            $analysis = new editor_Plugins_Okapi_Bconf_Analysis($bconfInZip);
            $this->supportedExtensions = $analysis->getExtensionMapping()->getAllExtensions();
            $this->bconf = null;
        } elseif ($bconf !== null) {
            $this->supportedExtensions = $bconf->getSupportedExtensions();
        } else {
            throw new ZfExtended_Exception('an OKAPI ImportFilter must be instantiated either with a bconf-entity or the absolute path to an embedded bconf');
        }
    }

    /**
     * Retrieves, which file-extensions are supported by the selected or maybe embedded bconf
     */
    public function getSupportedExtensions(): array
    {
        return $this->supportedExtensions;
    }

    /**
     * Retrieves if the given extension is supported
     */
    public function isExtensionSupported(string $extension): bool
    {
        return in_array(strtolower($extension), $this->supportedExtensions);
    }

    /**
     * Retrieves as the import bconf supports any extensions
     */
    public function hasSupportedExtensions(): bool
    {
        return count($this->supportedExtensions) > 0;
    }

    /**
     * Retrieves, if the filter was created with an embedded bconf in the import zip
     */
    public function hasEmbeddedBconf(): bool
    {
        return ($this->bconfInZip !== null);
    }

    /**
     * @return string|null
     */
    public function getBconfPath(): string
    {
        if ($this->bconf !== null) {
            return $this->bconf->getPath();
        } else {
            return $this->bconfInZip;
        }
    }

    public function getBconfName(): string
    {
        if ($this->bconf !== null) {
            return $this->bconf->getName();
        } else {
            return basename($this->bconfInZip);
        }
    }

    /**
     * Retrieves the name of the given bconf as it should appear in the event-log
     */
    public function getBconfDisplayName(): string
    {
        if ($this->bconf !== null) {
            return $this->bconf->getName() . ' (id: ' . $this->bconf->getId() . ')';
        } else {
            return basename($this->bconfInZip) . ' (from Import-ZIP)';
        }
    }
}
