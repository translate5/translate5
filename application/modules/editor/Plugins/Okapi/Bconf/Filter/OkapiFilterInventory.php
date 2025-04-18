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
use ZfExtended_BadMethodCallException;

/**
 * Class representing the static data for all okapi default filters a bconf can have
 */
final class OkapiFilterInventory extends FilterInventory
{
    /*
     * A filter-entry has the following structure:
     {
        "id": "okf_markdown",
        "type": "okf_markdown",
        "name": "Markdown",
        "description": "Markdown files",
        "mime": "text/markdown",
        "extensions": ["md"],
        "settings": true
    },
     */

    private static ?OkapiFilterInventory $_instance = null;

    /**
     * Validates a default-identifier (a mapping-identifier pointing to an okapi-default and not a fprm-file)
     * @throws ZfExtended_BadMethodCallException
     */
    public static function isValidDefaultIdentifier(string $identifier): bool
    {
        // avoid nonsense
        if (str_contains($identifier, Filters::IDENTIFIER_SEPERATOR)) {
            throw new ZfExtended_BadMethodCallException(
                'OkapiFilterInventory::isValidDefaultIdentifier can only check Okapi default filters that do not point to a fprm file'
            );
        }
        if (count(self::instance()->findFilter(null, $identifier)) > 0) {
            return true;
        }

        // as a fallback, we lookup by type. Currently, all types will have an file $type@$type as well but who knows ...
        return self::isValidType($identifier);
    }

    /**
     * validates an okapi filter type (if it generally exists)
     */
    public static function isValidType($okapiType): bool
    {
        return (count(self::instance()->findFilter($okapiType)) > 0);
    }

    /**
     * Retrieves the MimeType for a OKAPI filter type (or id)
     */
    public static function findMimeType($okapiType): string
    {
        // first, try to find filter by ID (which are the un-customized types in our inventory!)
        $result = self::instance()->findFilter(null, $okapiType);
        if (count($result) > 0) {
            return $result[0]->mime;
        }
        $result = self::instance()->findFilter($okapiType);
        if (count($result) > 0) {
            return $result[0]->mime;
        }

        // the mime type has only informative character,
        // therefore we use a generic default in case of not being able to evaluate it
        return 'text/plain';
    }

    /**
     * Classic Singleton
     */
    public static function instance(): OkapiFilterInventory
    {
        if (self::$_instance == null) {
            self::$_instance = new OkapiFilterInventory();
        }

        return self::$_instance;
    }

    /**
     * Relative to the static data-dir
     */
    protected string $inventoryFile = 'fprm/okapi-filters.json';

    /**
     * Relative to the static data-dir
     */
    protected string $inventoryFolder = 'fprm/okapi';
}
