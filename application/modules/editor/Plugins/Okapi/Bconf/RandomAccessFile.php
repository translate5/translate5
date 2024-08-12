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

/**
 * Common util class for bconf export and import
 * Java reads/writes/represents in BigEndian
 * Algorithmically a copy of the original JAVA implementation
 */
class editor_Plugins_Okapi_Bconf_RandomAccessFile extends SplFileObject
{
    /**
     * @var int
     */
    public const PHP_INT32_MAX = 0x7FFFFFFF;

    /**
     * @var int
     */
    public const PHP_UINT32_MAX = 0xFFFFFFFF;

    /**
     * @var int
     */
    public const OVERFLOW_SUB = 0x100000000; // == PHP_UINT32_MAX +1

    public function __construct(
        string $filename,
        string $mode = "r",
        bool $useIncludePath = false,
        ?object $context = null
    ) {
        parent::__construct($filename, $mode, $useIncludePath, $context);
    }

    /**
     * Read next UTF-8 value
     * @throws ZfExtended_UnprocessableEntity
     */
    public function readUTF()
    {
        try {
            $utflen = unpack("n", $this->fread(2))[1]; // n -> Big Endian unsigned short (Java)

            // unpack("A"...) strips whitespace!
            return $utflen > 0 ? unpack("a" . $utflen, $this->fread($utflen))[1] : '';
        } catch (Throwable $e) {
            throw new ZfExtended_UnprocessableEntity(errorCode: 'E1026', previous: $e);
        }
    }

    /** Write the UTF-8 value in bconf
     */
    public function writeUTF($string, bool $withNullByte = true): void
    {
        $length = strlen($string);
        $this->fwrite(pack("n", $length));
        $this->fwrite($string . ($withNullByte ? "\0" : ''));
    }

    /**
     * Convert $string to binary as it would be written
     */
    public static function toUTF($string): string
    {
        $length = strlen($string);

        return pack("n", $length) . $string;
    }

    /**
     * Read the Integer value in bconf
     * QUIRK: PHP unpack has no option for signed 32bit Integer, so we have to convert after reading
     * @return int|mixed
     * @throws ZfExtended_UnprocessableEntity
     */
    public function readInt(): mixed
    {
        try {
            $uint32 = unpack("N", $this->fread(4))[1]; // N -> UInt32.BE but we want Int32

            return $uint32 <= self::PHP_INT32_MAX ? $uint32 : $uint32 - self::OVERFLOW_SUB;
        } catch (Throwable $e) {
            throw new ZfExtended_UnprocessableEntity(errorCode: 'E1026', previous: $e);
        }
    }

    /**
     * Write the Integer value in bconf
     */
    public function writeInt($intValue): void
    {
        $this->fwrite(pack("N", $intValue));
    }
}
