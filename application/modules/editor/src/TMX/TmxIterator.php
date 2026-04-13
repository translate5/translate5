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

namespace MittagQI\Translate5\TMX;

use Generator;
use MittagQI\Translate5\ContentProtection\T5memory\ConvertT5MemoryTagService;
use MittagQI\Translate5\ContentProtection\T5memory\ConvertT5MemoryTagServiceInterface;
use MittagQI\Translate5\T5Memory\Psr7StreamWrapper;
use MittagQI\Translate5\T5Memory\TMX\CharacterReplacer;
use Psr\Http\Message\StreamInterface;
use Throwable;
use XMLReader;
use Zend_Registry;
use ZfExtended_Logger;

class TmxIterator
{
    public function __construct(
        private readonly ZfExtended_Logger $logger,
        private readonly ConvertT5MemoryTagServiceInterface $conversionService,
        private readonly CharacterReplacer $characterReplacer,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            Zend_Registry::get('logger')->cloneMe('editor.t5memory.tmx-iterator'),
            ConvertT5MemoryTagService::create(),
            CharacterReplacer::create(),
        );
    }

    /**
     * @return Generator<string>|null
     */
    public function iterateTmx(
        StreamInterface $stream,
        bool $returnHeader,
        int &$foundTuCount,
        bool $unprotect
    ): ?Generator {
        try {
            $stream->rewind();
            $reader = XMLReader::open(Psr7StreamWrapper::register($stream));

            if (false === $reader) {
                throw new \LogicException('Could not open XMLReader on stream');
            }
        } catch (Throwable $e) {
            $this->logger->exception($e);

            return null;
        }

        // suppress: namespace error : Namespace prefix t5 on n is not defined
        $errorLevel = error_reporting();
        error_reporting($errorLevel & ~E_WARNING);

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'tu') {
                $foundTuCount++;

                $tu = $reader->readOuterXML();

                if ($unprotect) {
                    $tu = $this->characterReplacer->revertToInvalidXmlCharacters($tu);
                    $tu = $this->conversionService->convertT5MemoryTagToContent($tu);
                }

                yield $tu . PHP_EOL;
            }

            if (! $returnHeader) {
                continue;
            }

            if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'header') {
                yield '<tmx version="1.4">' . PHP_EOL;
                yield preg_replace('/gitCommit=".+"/U', '', $reader->readOuterXML()) . PHP_EOL;
                yield '<body>' . PHP_EOL;
            }
        }

        error_reporting($errorLevel);

        $reader->close();
    }
}
