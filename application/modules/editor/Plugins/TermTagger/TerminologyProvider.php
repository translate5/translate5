<?php
/*
START LICENSE AND COPYRIGHT
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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

namespace MittagQI\Translate5\Plugins\TermTagger;

use editor_Models_Segment;
use editor_Models_Task;
use editor_Models_Terminology_Models_TermModel;
use editor_Plugins_TermTagger_Bootstrap;
use editor_Segment_Processing;
use editor_Segment_Tags;
use MittagQI\Translate5\Plugins\TermTagger\Processor\Tagger;
use Throwable;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Exception;

/**
 * Helper that enables to retrieve the terminology for a segment
 * Problem with this class, that it ignores the "normal" Workers load-balancing capabilities
 * This is somewhat "balanced" by using random URLs
 */
final class TerminologyProvider
{
    private Service $service;

    private int $oversizeWordCount;

    /**
     * Instantiates the Provider for the given Plugin (the name is only used for logging)
     * @throws ZfExtended_Exception
     * @throws Zend_Exception
     */
    public function __construct(
        private string $pluginName
    ) {
        $config = Zend_Registry::get('config');
        $this->oversizeWordCount = ($config->runtimeOptions->termTagger->maxSegmentWordCount ?? Configuration::OVERSIZE_WORD_COUNT);
        $this->service = editor_Plugins_TermTagger_Bootstrap::createService(Service::SERVICE_ID);
    }

    /**
     * @return string|null
     * @throws ZfExtended_Exception
     */
    private function createServiceUrl()
    {
        // will retrieve a random service-URL
        $serviceUrl = $this->service->getPooledServiceUrl('import');
        if (empty($serviceUrl)) {
            $serviceUrl = $this->service->getPooledServiceUrl('default');
        }

        return $serviceUrl;
    }

    /**
     * Retrieves terminology in the form source => target
     * @throws ZfExtended_Exception
     * @throws \Zend_Exception
     */
    public function getTerms(editor_Models_Task $task, editor_Models_Segment $segment): array
    {
        // no terms without terminology or similar languages
        if (! $task->getTerminologie() || $task->isSourceAndTargetLanguageSimilar()) {
            return [];
        }
        // if the segment is too long, we reject it
        if ($segment->meta()->getSourceWordCount() >= $this->oversizeWordCount) {
            // there will be an error anyway via AutoQA so we only add this for developers ...
            error_log(
                $this->pluginName . ': For segment ' . $segment->getId()
                . ' / Nr ' . $segment->getSegmentNrInTask() . ' / Task ' . $task->getId()
                . ' the terms could not be evaluated: Segment is too long.'
            );

            return [];
        }

        $serviceUrl = $this->createServiceUrl();
        $segmentTags = editor_Segment_Tags::fromSegment($task, editor_Segment_Processing::IMPORT, $segment);
        $processor = new Tagger($task, $this->service, editor_Segment_Processing::IMPORT, $serviceUrl, false);

        // we use only admitted & preferred terms
        $acceptedStates = [
            editor_Models_Terminology_Models_TermModel::STAT_PREFERRED,
            editor_Models_Terminology_Models_TermModel::STAT_ADMITTED,
        ];

        try {
            $allTerms = $processor->retrieveTermsForSegmentTags($segmentTags);
            $result = [];

            foreach ($allTerms as $termsGroup) {
                if (! empty($termsGroup)) {
                    foreach ($termsGroup as $terms) {
                        $sourceTerm = $terms['source'][0];
                        $targetTerm = null;
                        if (! array_key_exists('target', $terms)) {
                            // there can be terms without target which indicates no translation exists
                            // for the current target-language
                            break;
                        }
                        foreach ($terms['target'] as $target) {
                            if (in_array($target['status'], $acceptedStates, true)) {
                                $targetTerm = $target;

                                break;
                            }
                        }
                        if ($targetTerm !== null) {
                            $result[$sourceTerm['term']] = $targetTerm['term'];
                        }
                    }
                }
            }

            // error_log($this->pluginName . 'Terminology for segment ' . $segment->getId() . ":\n\n"
            //    . print_r($result, true) . "\n");

            return $result;
        } catch (Throwable $e) {
            // probably TBX file failed to load or other exceptions, nothing to do
            error_log(
                $this->pluginName . ': For segment ' . $segment->getId()
                . ' / Nr ' . $segment->getSegmentNrInTask() . ' / Task ' . $task->getId()
                . ' the terms could not be evaluated: ' . $e->getMessage()
            );

            return [];
        }
    }
}
