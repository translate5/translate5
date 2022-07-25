<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

class editor_Models_Import_FileParser_Xlf_OtherContent_Data {
    public string $mid;
    public int $startMrkIdx;
    public int $endMrkIdx;

    /**
     * Contains the content as string with internal tags,
     *  with or without preserved whitespace, depending on the same name flag,
     * @var string
     */
    public string $content = '';

    /**
     * Contains the above content as chunks - so no reparse is needed
     * @var array
     */
    public array $contentChunks = [];

    /**
     * Contains the above content as original chunks - so no reparse is needed
     *  original means: internal tags are converted back - but all after the multiple whitespaces were condensed
     * @var array
     */
    public array $contentChunksOriginal = [];

    /**
     * Contains the above content as original content in one string
     * @var string
     */
    public string $contentOriginal = '';

    /**
     * Flag if current element should be imported
     * @var bool
     */
    public bool $toBeImported = false;

    /**
     * @param string $mid
     * @param int $startIdx
     * @param int $endIdx
     */
    public function __construct(string $mid, int $startIdx, int $endIdx) {
        $this->mid = $mid;
        $this->startMrkIdx = $startIdx;
        $this->endMrkIdx = $endIdx;
    }
}