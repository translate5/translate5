<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
use MittagQI\Translate5\Plugins\SpellCheck\Segment\Check;

/**
 * Numbers check
 */
class editor_Segment_Numbers_Check
{
    public const NUM1 = 'num1';

    public const NUM2 = 'num2';

    public const NUM3 = 'num3';

    public const NUM4 = 'num4';

    public const NUM5 = 'num5';

    public const NUM6 = 'num6';

    public const NUM7 = 'num7';

    public const NUM8 = 'num8';

    public const NUM9 = 'num9';

    public const NUM10 = 'num10';

    public const NUM11 = 'num11';

    public const NUM12 = 'num12';

    public const NUM13 = 'num13';

    public const NUM14 = 'num14';

    private array $states = [];

    /**
     * Languages [id => rfc5646] pairs
     */
    public static ?array $lang = null;

    /**
     * editor_Segment_Numbers_Check constructor.
     * @throws ReflectionException
     */
    public function __construct(
        editor_Models_Task $task,
        editor_Models_Segment $segment,
        editor_Segment_FieldTags $source,
        editor_Segment_FieldTags $target
    ) {
        // Get source text, and replace whitespace-placeholder-characters for non-breaking space, linebreak, tab and ordinary space with themselves
        // Note: the non-commented space (1st item in 2nd arg of str_replace call) is the non-breaking space with code 160
        //       and the commented one is an ordinary space with code 32
        $sourceText = Check::prepareTarget($segment, $source);

        // Do same for target text. Here we need same preparation both for source and target
        $targetText = Check::prepareTarget($segment, $target);

        // Load langs [id => rfc5646] pairs if not yet loaded
        self::$lang ??= ZfExtended_Factory
            ::get('editor_Models_Languages')
                ->loadAllKeyValueCustom('id', 'sublanguage');

        // Run check
        $this->states = numbers_check(
            $sourceText,
            $targetText,
            self::$lang[$task->getSourceLang()],
            self::$lang[$task->getTargetLang()],
            $task
        );
    }

    /**
     * Retrieves the evaluated states
     * @return string[]
     */
    public function getStates()
    {
        return $this->states;
    }

    /**
     * @return boolean
     */
    public function hasStates()
    {
        return count($this->states) > 0;
    }
}
