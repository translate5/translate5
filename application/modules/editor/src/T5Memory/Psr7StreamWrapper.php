<?php

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
