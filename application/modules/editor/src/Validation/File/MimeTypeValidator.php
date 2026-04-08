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

namespace MittagQI\Translate5\Validation\File;

class MimeTypeValidator extends \Zend_Validate_File_MimeType
{
    public function isValid($value, $file = null): bool
    {
        $isValid = parent::isValid($value, $file);

        if ($isValid) {
            return true;
        }

        $isXmlLike = $this->sniffXmlLike($file['tmp_name'] ?? $value);
        $mimetypes = $this->getMimeType(true);

        foreach ($mimetypes as $mimetype) {
            if ($isXmlLike && str_contains($mimetype, 'xml')) {
                return true;
            }
        }

        return false;
    }

    public function sniffXmlLike(string $path): bool
    {
        $fh = fopen($path, 'rb');
        if (! $fh) {
            return false;
        }

        $head = fread($fh, 65536);
        fclose($fh);

        if ($head === false || $head === '') {
            return false;
        }

        // Strip UTF-8 BOM if present
        $head = preg_replace('/^\xEF\xBB\xBF/', '', $head);

        // Trim leading whitespace/control-ish
        $trimmed = ltrim($head);

        return str_starts_with($trimmed, '<?xml') || str_starts_with($trimmed, '<');
    }
}
