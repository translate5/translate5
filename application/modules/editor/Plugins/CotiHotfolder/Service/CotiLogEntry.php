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

declare(strict_types=1);

namespace MittagQI\Translate5\Plugins\CotiHotfolder\Service;

/**
 * COTI specification can be found here: https://www.dercom.de/en/projekte
 */
enum CotiLogEntry: int
{
    case UnknownType = 1001;
    case UnknownStatus = 1002;
    case ProjectNotFound = 1003;
    case DocumentNotFound = 1004;
    case UnknownMetaPropertyKey = 1005;

    case UnknownReportType = 1006;
    case UnknownUserOrPassword = 1007;
    case UnknownAction = 1008;
    case UnknownLanguageFatal = 1009;
    case ReportedDataInvalid = 1010;
    case PackageInvalid = 1011;
    case CotiFileInvalid = 1012;
    case CotiSessionExpired = 1013;
    case InvalidAuthenticationHeader = 1014;
    case TmsNotFound = 2001;
    case PackageWriteError = 2002;
    case PackageImportError = 2003;
    case PackageFilesMissing = 2004;
    case UnknownLanguageError = 2005;
    case InternalError = 2006;
    case NotAuthenticated = 2007;
    case PackageGenerated = 4001;
    case PackageRead = 4002;
    case PackageMoved = 4003;
    case PackageTranslated = 4004;
    case Completed = 5001;

    public function description(): string
    {
        return match ($this) {
            self::UnknownType => 'Unknown type',
            self::UnknownStatus => 'Unknown status',
            self::UnknownMetaPropertyKey => 'Unknown meta property key',
            self::UnknownReportType => 'Unknown report type',
            self::UnknownUserOrPassword => 'Unknown User or Password',
            self::UnknownAction => 'Unknown Action',
            self::ProjectNotFound => 'Project not found: %s',
            self::DocumentNotFound => 'Document not found: %s',
            self::UnknownLanguageFatal, self::UnknownLanguageError => 'Unknown Language: %s',
            self::ReportedDataInvalid => 'Reported data invalid',
            self::PackageInvalid => 'Package invalid',
            self::CotiFileInvalid => 'COTI File invalid: %s',
            self::CotiSessionExpired => 'COTI Session expired',
            self::InvalidAuthenticationHeader => 'Invalid Authentication Header',
            self::TmsNotFound => 'TMS not found',
            self::PackageWriteError => 'Package can\’t be written',
            self::PackageImportError => 'Package can\’t be imported',
            self::PackageFilesMissing => 'Package Files missing: %s',
            self::InternalError => 'Internal Error',
            self::NotAuthenticated => 'Not Authenticated',
            self::PackageGenerated => 'Package generated',
            self::PackageRead => 'Package read',
            self::PackageMoved => 'Package moved',
            self::PackageTranslated => 'Package translated',
            self::Completed => 'Completed',
        };
    }
}
