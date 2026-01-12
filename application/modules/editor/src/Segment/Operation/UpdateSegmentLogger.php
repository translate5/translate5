<?php
/*
START LICENSE AND COPYRIGHT
 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a paid plug-in for translate5.

 The translate5 core software and its freely downloadable plug-ins are licensed under an AGPLv3 open-source license
 (https://www.gnu.org/licenses/agpl-3.0.en.html).
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 Paid translate5 plugins can deviate from standard AGPLv3 licensing and therefore constitute an
 exception. As such, translate5 plug-ins can be licensed under either AGPLv3 or GPLv3 (see below for details).

 Briefly summarized, a GPLv3 license dictates the same conditions as its AGPLv3 variant, except that it
 does not require the program (plug-in, in this case) to direct users toward its download location if it is
 only being used via the web in a browser.
 This enables developers to write custom plug-ins for translate5 and keep them private, granted they
 meet the GPLv3 licensing conditions stated above.
 As the source code of this paid plug-in is under open source GPLv3 license, everyone who did obtain
 the source code could pass it on for free or paid to other companies or even put it on the web for
 free download for everyone.

 As this would undermine completely the financial base of translate5s development and the translate5
 community, we at MittagQI would not longer support a company or supply it with updates for translate5,
 that would pass on the source code to third parties.

 Of course as long as the code stays within the company who obtained it, you are free to do
 everything you want with the source code (within the GPLv3 boundaries), like extending it or installing
 it multiple times.

 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html

 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5. This plug-in exception allows using GPLv3 for translate5 plug-ins,
 although translate5 core is licensed under AGPLv3.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/gpl.html
             http://www.translate5.net/plugin-exception.txt
END LICENSE AND COPYRIGHT
*/
declare(strict_types=1);

namespace MittagQI\Translate5\Segment\Operation;

use editor_Models_Segment;
use MittagQI\ZfExtended\Logger\SimpleFileLogger;
use MittagQI\ZfExtended\Sanitizer\HttpRequest;
use ZfExtended_Debug;

final class UpdateSegmentLogger
{
    public static function fromPutRequest(
        string $process,
        editor_Models_Segment $segment,
        HttpRequest $request,
    ): self {
        $data = $request->getData(true);
        foreach (self::CAPTURED_FIELDS as $field) {
            if (array_key_exists($field, $data)) {
                return new self(
                    $process,
                    (int) $segment->getId(),
                    $data[$field],
                    $segment->get($field),
                    $field
                );
            }
        }
        // when the segment text was saved unchanged, no field-data will be sent and the field (may) be encoded
        // in "durations". In this case we take the actual segments field as request-sent data
        foreach (self::CAPTURED_FIELDS as $field) {
            if (array_key_exists('durations', $data) && array_key_exists($field, $data['durations'])) {
                return new self(
                    $process,
                    (int) $segment->getId(),
                    $segment->get($field),
                    $segment->get($field),
                    $field
                );
            }
        }

        // this will create a "dummy" logger that logs anything ...
        return new self($process, (int) $segment->getId(), '', '', 'invalid');
    }

    private const CAPTURED_FIELDS = [
        'sourceEdit',
        'target',
        'targetEdit',
    ];

    private const LOG_ALWAYS = false;

    private bool $doLog;

    private array $log = [];

    private bool $isSuspicious = false;

    public function __construct(
        private readonly string $process,
        private readonly int $segmentId,
        private readonly string $requestSentText,
        private readonly string $textBeforeUpdate,
        private readonly string $capturedField
    ) {
        $this->doLog = in_array($capturedField, self::CAPTURED_FIELDS)
            && (self::LOG_ALWAYS || ZfExtended_Debug::hasLevel('editor', 'segmentSave')); // @phpstan-ignore-line
    }

    /**
     * Captures a change based on textual data
     */
    public function captureChange(string $text, string $field, string $origin): void
    {
        if ($this->doLog && strtolower($field) === strtolower($this->capturedField)) {
            $this->log[] = [
                'text' => $text,
                'field' => $field,
                'origin' => $origin,
            ];
            $this->checkSuspicious($text);
        }
    }

    /**
     * Captures a change based on the segment-entity
     */
    public function captureSegmentChange(editor_Models_Segment $segment, string $origin): void
    {
        if ($this->doLog) {
            $text = $segment->get($this->capturedField);
            $this->log[] = [
                'text' => $text,
                'field' => $this->capturedField,
                'origin' => $origin,
            ];
            $this->checkSuspicious($text);
        }
    }

    /**
     * Finishes the logging of the update & writes the entry when suspicious changes have been detected
     */
    public function finish(): void
    {
        if ($this->doLog && $this->isSuspicious) {
            // create logger with upper limit of 20MB
            $sfl = new SimpleFileLogger('segmentSave.log', 20971520);
            $entry =
                '#ID: ' . $this->segmentId .
                '#PROCESS:' . $this->process .
                '#SENT:' . $this->requestSentText . '#ENDSENT' .
                '#BEFORE:' . $this->textBeforeUpdate . '#ENDBEFORE';
            foreach ($this->log as $logEntry) {
                $orig = strtoupper($logEntry['origin']);
                $field = ($logEntry['field'] !== $this->capturedField) ? '|' . strtolower($logEntry['field']) : '';
                $entry .= '#' . $orig . $field . ':' . $logEntry['text'] . '#END' . $orig;
            }

            $sfl->log($entry);
        }
    }

    private function checkSuspicious(string $text): void
    {
        if ($this->condenseText($this->requestSentText) !== $this->condenseText($text)) {
            $this->isSuspicious = true;
        }
    }

    private function condenseText(string $text): string
    {
        $text = preg_replace('~\s+~', '', strip_tags($text));
        $text = str_replace([json_decode('"\u00a0"'), json_decode('"\u202f"'), '&#160;', '&#8239;'], '', $text);

        return html_entity_decode($text, ENT_HTML5 | ENT_QUOTES, 'UTF-8');
    }
}
