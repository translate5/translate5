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

namespace MittagQI\Translate5\T5Memory;

use Psr\Http\Message\StreamInterface;

class Psr7StreamWrapper
{
    private static bool $protocolDefined = false;

    private static StreamInterface $stream;

    private $position = 0;

    public static function register(StreamInterface $stream)
    {
        // @phpstan-ignore-next-line
        static::$stream = $stream;

        if (! self::$protocolDefined) {
            stream_wrapper_register('psr7', self::class);
            self::$protocolDefined = true;
        }

        return 'psr7://stream';
    }

    public function stream_open($path, $mode, $options, &$opened_path)
    {
        return true;
    }

    public function stream_read($count)
    {
        $data = self::$stream->read($count);
        $this->position += strlen($data);

        return $data;
    }

    public function stream_eof()
    {
        return self::$stream->eof();
    }

    public function stream_stat()
    {
        return [];
    }

    public function stream_seek($offset, $whence = SEEK_SET)
    {
        if ($whence === SEEK_SET) {
            self::$stream->seek($offset);
        } elseif ($whence === SEEK_CUR) {
            self::$stream->seek($this->position + $offset);
        } elseif ($whence === SEEK_END) {
            self::$stream->seek(self::$stream->getSize() + $offset);
        } else {
            return false;
        }

        $this->position = self::$stream->tell();

        return true;
    }

    public function stream_tell()
    {
        return $this->position;
    }

    public function stream_metadata($path, $option, $var)
    {
        return false;
    }

    public function url_stat(string $path, int $flags)
    {
        return array_fill(0, 12, 0);
    }
}
