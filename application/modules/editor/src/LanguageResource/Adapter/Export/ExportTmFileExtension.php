<?php

namespace MittagQI\Translate5\LanguageResource\Adapter\Export;

enum ExportTmFileExtension: string
{
    case TM = 'tm';
    case TMX = 'tmx';
    case ZIP = 'zip';

    public static function fromMimeType(string $mime, bool $preferZip = true): self
    {
        if (! in_array($mime, self::getValidExportTypes(), true)) {
            throw new \InvalidArgumentException("Invalid mime type: $mime");
        }

        if ($mime === "application/xml") {
            return self::TMX;
        }

        return $preferZip ? self::ZIP : self::TM;
    }

    /**
     * @return array<string, string>
     */
    public static function getValidExportTypes(): array
    {
        return [
            'TM' => 'application/zip',
            'TMX' => 'application/xml',
        ];
    }
}
