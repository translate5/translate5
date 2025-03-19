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

namespace MittagQI\Translate5\Plugins\Okapi\Bconf;

use SplFileObject;
use Throwable;
use ZfExtended_Exception;

/**
 * Common util class for bconf export and import
 * Java reads/writes/represents in BigEndian
 * Algorithmically a copy of the original JAVA implementation
 */
class RandomAccessFile extends SplFileObject
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
        string $mode = 'r',
        bool $useIncludePath = false,
        ?object $context = null
    ) {
        parent::__construct($filename, $mode, $useIncludePath, $context);
    }

    /**
     * Read next UTF-8 value
     * @throws ZfExtended_Exception
     */
    public function readUTF()
    {
        try {
            $length = $this->fread(2);
            if ($length === false) {
                $this->throwOnReadUTF('fread: could not read length of following UTF-8 string', error_get_last());
            }
            $unpacked = unpack('n', $length);
            if ($unpacked === false) {
                $this->throwOnReadUTF('unpack: Type a: not enough input', error_get_last());
            }
            $utflen = $unpacked[1]; // n -> Big Endian unsigned short (Java)
            if ($utflen > 0) {
                $length = $this->fread($utflen);
                if ($length === false) {
                    $this->throwOnReadUTF('fread: could not read length of following UTF-8 string', error_get_last());
                }
                $unpacked = unpack('a' . $utflen, $length);
                if ($unpacked === false) {
                    $this->throwOnReadUTF('unpack: invalid format-string "a' . $utflen . '"', error_get_last());
                }

                return $unpacked[1];
            } else {
                return '';
            }
        } catch (Throwable $e) {
            throw new ZfExtended_Exception($e->getMessage());
        }
    }

    /**
     * @throws ZfExtended_Exception
     */
    private function throwOnReadUTF(string $msg, ?array $lastError): void
    {
        $message = (is_array($lastError) && array_key_exists('message', $lastError)) ? $lastError['message'] : $msg;

        throw new ZfExtended_Exception($message);
    }

    /**
     * Write the UTF-8 value in bconf
     */
    public function writeUTF($string, bool $withNullByte = true): void
    {
        $length = strlen($string);
        $this->fwrite(pack('n', $length));
        $this->fwrite($string . ($withNullByte ? "\0" : ''));
    }

    /**
     * Convert $string to binary as it would be written
     */
    public static function toUTF($string): string
    {
        $length = strlen($string);

        return pack('n', $length) . $string;
    }

    /**
     * Read the Integer value in bconf
     * QUIRK: PHP unpack has no option for signed 32bit Integer, so we have to convert after reading
     * @return int|mixed
     * @throws ZfExtended_Exception
     */
    public function readInt(): mixed
    {
        try {
            $uint32 = unpack('N', $this->fread(4))[1]; // N -> UInt32.BE but we want Int32

            return $uint32 <= self::PHP_INT32_MAX ? $uint32 : $uint32 - self::OVERFLOW_SUB;
        } catch (Throwable $e) {
            throw new ZfExtended_Exception($e->getMessage());
        }
    }

    /**
     * Write the Integer value in bconf
     */
    public function writeInt($intValue): void
    {
        $this->fwrite(pack('N', $intValue));
    }
}
