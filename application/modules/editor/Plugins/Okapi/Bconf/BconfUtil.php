<?php
/*
START LICENSE AND COPYRIGHT

This file is part of translate5

Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

/**
 *
 * Common util class for bconf export and import
 *
 */
class editor_Plugins_Okapi_Bconf_BconfUtil{
    
     
     /** Write the UTF-8 value in bconf
      * @param $string
      * @param $bcongFile
      */
     public static function writeUTF($string, $bcongFile)
     {
          $utfString = utf8_encode($string);
          $length = strlen($utfString);
          fwrite($bcongFile, pack("n", $length));
          fwrite($bcongFile, $utfString);
     }
     
     /** Write the Integer value in bconf
      * @param $intValue
      * @param $bcongFile
      */
     public static function writeInt($intValue, $bcongFile)
     {
          fwrite($bcongFile, pack("N", $intValue));
     }
     
     /** Write the Long  value in bcong
      * @param $pipeLine
      * @param $bcongFile
      */
     public static function writeLong($longValue, $bcongFile)
     {
          fwrite($bcongFile, pack("J", $longValue));
     }
     
}