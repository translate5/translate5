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

declare(strict_types=1);

namespace MittagQI\Translate5\Tools\Tmx;

use editor_Models_Import_FileParser_XmlParser;
use MittagQI\Translate5\Tools\Tmx\ConvertFromAraya\PlaceholderDTO;
use ReflectionException;
use ZfExtended_Factory;

/**
 * See MITTAGQI-364 Convert tags in Araya-based TMX
 */
class ConvertFromAraya
{
    protected editor_Models_Import_FileParser_XmlParser $fileParser;

    /**
     * the number of translation-units the file contains
     */
    protected int $countTus = 0;

    /**
     * The number of translation-units <tu>s without an entry for lead-language German
     */
    protected int $countHasNoLeadLanguage = 0;

    /**
     * for each translation-unit which contains one or more invalid placeholder,
     * an entry is made into this list containing the number of invalid placeholder in the unit.
     *
     * key is the offset of the <tu>, if that is of any interest.
     *
     * @var PlaceholderDTO[]
     */
    protected array $invalidPlaceholder = [];

    /**
     * @throws ReflectionException
     */
    public function __construct()
    {
        $this->fileParser = ZfExtended_Factory::get(editor_Models_Import_FileParser_XmlParser::class);

        // search for all <tu> entries and send them to the FixArayaTmxTuParser
        $this->fileParser->registerElement('tu', null, function ($tag, $idx, $opener) {
            $this->countTus++;
            $idOffset = $opener['openerKey'] - 1;
            $tuParser = new ConvertFromAraya\TuParser($this->fileParser, $idOffset);
            $tagContent = $this->fileParser->join($this->fileParser->getRange($opener['openerKey'], $idx));
            $tuParser->parse($tagContent);

            if ($tuParser->hasNoLeadLanguage()) {
                $this->countHasNoLeadLanguage++;
            }
            if ($tuParser->hasInvalidPlaceholderTags()) {
                $this->invalidPlaceholder[$idOffset] = $tuParser->getCountOfInvalidPlaceholderTags();
            }
        });
    }

    public function parse(string $tmxData): string
    {
        $data = $this->fileParser->parse($tmxData);

        echo 'file contains ' . $this->countTus . ' translation-units <tu>s' . "\n"
            . $this->countHasNoLeadLanguage . ' <tu>s do not have an entry for lead-language' . "\n"
            . count($this->invalidPlaceholder) . ' <tu>s containing overall ' . array_sum(
                $this->invalidPlaceholder
            ) . ' invalid placeholder ' . "\n";

        return $data;
    }
}
