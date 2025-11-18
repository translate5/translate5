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

namespace MittagQI\Translate5\Segment\Operation\Factory;

use editor_Models_Segment as Segment;
use JsonException;
use MittagQI\Translate5\Segment\Operation\DTO\DurationsDto;
use MittagQI\Translate5\Segment\Operation\DTO\UpdateSegmentDto;
use MittagQI\Translate5\Segment\SegmentMarkupValidation;
use REST_Controller_Request_Http as Request;
use ZfExtended_BadRequest;
use ZfExtended_Sanitized_HttpRequest;

class UpdateSegmentDtoFactory
{
    public function __construct(
        private readonly SegmentMarkupValidation $markupValidation,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            new SegmentMarkupValidation()
        );
    }

    public function fromRequest(Segment $segment, Request $request): UpdateSegmentDto
    {
        $data = $this->getRequestData($segment, $request);
        $textData = $this->getTextData($segment, $data);

        return new UpdateSegmentDto(
            $textData,
            new DurationsDto(
                (object) ($data['durations'] ?? []),
            ),
            isset($data['stateId']) ? (int) $data['stateId'] : null,
            isset($data['autoStateId']) ? (int) $data['autoStateId'] : null,
            isset($data['matchRate']) ? (int) $data['matchRate'] : null,
            $data['matchRateType'] ?? null,
        );
    }

    private function getRequestData(Segment $segment, Request $request): array
    {
        if ($request instanceof ZfExtended_Sanitized_HttpRequest) {
            $editableData = $segment->getEditableDataIndexList(true);
            $sanitizationMap = array_combine(
                $editableData,
                array_fill(0, count($editableData), \ZfExtended_Sanitizer::MARKUP)
            );

            return $request->getData(true, $sanitizationMap);
        }

        $data = $request->getParam('data');

        if (null === $data) {
            // if the request does not contain the data field, we assume JSON in raw body if content type is appropriate
            if ($request->getHeader('content-type') !== 'application/json') {
                throw new ZfExtended_BadRequest('E1560', [
                    'error' => 'Invalid content type',
                ]);
            }

            $data = $request->getRawBody();
        }

        try {
            return json_decode($data, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ZfExtended_BadRequest('E1560', [
                'error' => $e->getMessage(),
            ], $e);
        }
    }

    /**
     * performing basic cleanup for the textual data (= Segment Markup) that was sent
     * @return array<string, string>
     */
    public function getTextData(Segment $segment, array $data): array
    {
        //        $error = [];
        $textData = [];
        $allowedAlternatesToChange = $segment->getEditableDataIndexList(true);

        foreach ($data as $key => $value) {
            //consider only changeable datafields:
            if (! in_array($key, $allowedAlternatesToChange)) {
                continue;
            }
            // Cleanup: May a duplicate savecheck was not removed by the frontend
            $regex = '#<img[^>]+class="duplicatesavecheck"[^>]+data-segmentid="([0-9]+)" data-fieldname="([^"]+)"[^>]*>#';
            $match = [];

            if (preg_match($regex, $value, $match)) {
                $value = str_replace($match[0], '', $value);
            }

            $value = $this->markupValidation->prepare(
                $value,
                (int) $segment->getId(),
                (int) $segment->getSegmentNrInTask(),
                $key
            );

            $textData[$key] = $value;
            //            //if segmentId and fieldname from content differ to the segment to be saved, throw the error!
            //            if ($match[2] != $key || $match[1] != $segment->getId()) {
            //                $error['real fieldname: ' . $key] = [
            //                    'segmentId' => $match[1],
            //                    'fieldName' => $match[2],
            //                ];
            //            }
        }

        //        if (empty($error)) {
        return $textData;
        //        }

        //        $logText = 'Error on saving a segment!!! Parts of the content in the PUT request ';
        //        $logText .= 'delivered the following segmentId(s) and fieldName(s):' . PHP_EOL;
        //        $logText .= print_r($error, true) . PHP_EOL;
        //        $logText .= 'but the request was fofromRequestr segmentId ' . $segment->getId();
        //        $logText .= ' (compare also the above fieldnames!).' . PHP_EOL;
        //        $logText .= 'Therefore the segment has not been saved!' . PHP_EOL;
        //        $logText .= 'Actually saved Segment PUT data and data to be saved in DB:' . PHP_EOL;
        //        $logText .= print_r($data, true)
        //            . PHP_EOL . print_r($segment->getDataObject(), true)
        //            . PHP_EOL . PHP_EOL;
        //        $logText .= 'Content of $_SERVER had been: ' . print_r($_SERVER, true);
        //
        //        $this->logger->logError('Possible Error on saving a segment!', $logText);
        //
        //        $e = new \ZfExtended_Exception();
        //        $e->setMessage(
        //            'Aufgrund der langsamen Verarbeitung von Javascript im Internet Explorer konnte das Segment nicht korrekt gespeichert werden.'
        //            . ' Bitte öffnen Sie das Segment nochmals und speichern Sie es erneut.'
        //            . ' Sollte das Problem bestehen bleiben, drücken Sie bitte F5 und bearbeiten dann das Segment erneut.'
        //            . ' Vielen Dank!',
        //            true
        //        );
        //
        //        throw $e;
    }
}
