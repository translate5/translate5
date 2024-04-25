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

namespace MittagQI\Translate5\Logging;

use Cesargb\Log\Exceptions\RotationFailed;

class Rotation
{
    /**
     * Rotation failed exception handler
     */
    public static function fail(RotationFailed $exception)
    {
        echo 'Log rotation failed: ' . $exception->getMessage(); /* print_r([
            $exception->getMessage(),
            $exception->getCode(),
            $exception->getFile(),
            $exception->getFilename(),
        ]);*/
    }

    /**
     * Handler called when rotation is done
     *
     * @param string $message For example: 'successful'
     * @param string $filename Absolute path to original log file
     */
    public static function done(string $message, string $filename)
    {
    }

    /**
     * Get filename target and original filename. Called after done-handler
     *
     * @param string $filenameTarget   Absolute path to compressed file, e.g with '.1.gz'-postfix to original log file
     * @param string $filenameRotated  Absolute path to original log file
     */
    public static function then(string $filenameTarget, string $filenameRotated)
    {
    }

    public static function rotate(string $logFileName)
    {
        // Get instance
        $rotation = new \Cesargb\Log\Rotation([
            // Optional, files are rotated 10 time before being removed. Default 366
            'files' => 10,

            // Set level compression or true to default level. Default false
            'compress' => true,

            // Optional, are rotated when they grow bigger than 1024 bytes. Default 0
            'min-size' => 1024 * 1024 * 100,

            // must be false, since copy and truncate might lock the log file to long
            'truncate' => false,

            // Optional, to catch a exception in rotating
            'catch' => function (RotationFailed $exception) {
                self::fail($exception);
            },

            // Optional, this method will be called when the process has finished
            'finally' => function ($message, $filename) {
                self::done($message, $filename);
            },

            // Optional, to get filename target and original filename
            'then' => function ($filenameTarget, $filenameRotated) {
                self::then($filenameTarget, $filenameRotated);
            },
        ]);

        // Rotate
        $rotation->rotate(APPLICATION_DATA . "/logs/$logFileName");
    }
}
