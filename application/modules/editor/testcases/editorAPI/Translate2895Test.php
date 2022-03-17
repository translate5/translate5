<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

/**
 * Testcase for TRANSLATE-2895 tests the boundary / framing tag removing in the XLF import
 * For details see the issue.
 */
class Translate2895Test extends ZfExtended_Test_Testcase {

    private static editor_Models_Import_FileParser_XmlParser $xmlParser;

    public static function setUpBeforeClass(): void
    {
        require_once 'editor/Models/Import/FileParser/Tag.php';
        require_once 'editor/Models/Import/FileParser/XmlParser.php';
        require_once 'editor/Models/Import/FileParser/Xlf/SurroundingTagRemover/None.php';
        require_once 'editor/Models/Import/FileParser/Xlf/SurroundingTagRemover/Paired.php';
        require_once 'editor/Models/Import/FileParser/Xlf/SurroundingTagRemover/All.php';
        self::$xmlParser = new editor_Models_Import_FileParser_XmlParser();
    }

    public function testNoneRemover() {
        $remover = new editor_Models_Import_FileParser_Xlf_SurroundingTagRemover_None();
        $remover->calculate(false,
            $sourceChunks = $this->prepareContent('<1>Das ist ein Test</1>'),
            $this->prepareContent('<1>This ist a Test</1>'),
            new editor_Models_Import_FileParser_XmlParser());

        $this->assertEmpty($remover->getLeading(), 'leading removed content should be empty');
        $this->assertEmpty($remover->getTrailing(), 'trailing removed content should be empty');

        $this->assertEquals($sourceChunks, $remover->sliceTags($sourceChunks), 'nothing should be sliced here');
    }

    public function testPairedRemover() {
        $remover = new editor_Models_Import_FileParser_Xlf_SurroundingTagRemover_Paired();
        //simple paired tags removed
        $this->_testContent($remover, '<1>', 'Ein Test', '</1>');

        //simple paired tags removed, keeping a single tag in the middle
        $this->_testContent($remover, '<1>', 'Ein Test<2/>', '</1>');
        $this->_testContent($remover, '<1>', '<2/>Ein Test', '</1>');

        //simple paired tags removed, removing also an isolated single tag
        $this->_testContent($remover, '<1>', 'Ein Test', '<10/></1>');
        $this->_testContent($remover, '<1><10/>', 'Ein Test', '</1>');
        $this->_testContent($remover, '<1>', 'Ein Test', '</1><10/>');
        $this->_testContent($remover, '<10/><1>', 'Ein Test', '</1>');

        //just a single tag at the start / end
        $this->_testContent($remover, '<10/>', 'Ein Test', '');
        $this->_testContent($remover, '', 'Ein Test', '<10/>');
        $this->_testContent($remover, '', '<1/>Ein Test', '');
        $this->_testContent($remover, '', 'Ein Test<1/>', '');

        //keep tags if not only paired tags
        $this->_testContent($remover, '', '<1>Ein Test</1><2><3/></2>', '');
        $this->_testContent($remover, '', '<1><2/></1><3>Ein Test</3>', '');
        $this->_testContent($remover, '', '<1>Ein Test</1></2>', '');
        $this->_testContent($remover, '', '<1/><2>Ein Test</2>', '');

        //keep tags if partner is inside the text
        $this->_testContent($remover, '', '<1>Ein</1> Test', '');
        $this->_testContent($remover, '', 'Ein <1>Test</1>', '');

        //testing with a given target
        $this->_testContent($remover, '<1>', 'Ein Test', '</1>', 'A test');
        $this->_testContent($remover, '<1>', 'Ein Test</2>', '</1>', 'A test</2>');
        //testing with a given target with different structure
        $this->_testContent($remover, '', '<1>Ein Test<2/></1>', '', '<1>A test</1><2/>');
        $this->_testContent($remover, '', '<1>Ein Test<10/></1>', '', '<1>A test</1><10/>');
    }

    public function testPairedAll() {
        $remover = new editor_Models_Import_FileParser_Xlf_SurroundingTagRemover_All();
        //removing all tags, paired, single
        $this->_testContent($remover, '<1>', 'Ein Test', '</1>');
        $this->_testContent($remover, '<1>', 'Ein Test', '<2/></1>');
        $this->_testContent($remover, '<1><2/>', 'Ein Test', '</1>');
        $this->_testContent($remover, '<1>', 'Ein Test', '<10/></1>');
        $this->_testContent($remover, '<1><10/>', 'Ein Test', '</1>');
        $this->_testContent($remover, '<1>', 'Ein Test', '</1><10/>');
        $this->_testContent($remover, '<10/><1>', 'Ein Test', '</1>');
        $this->_testContent($remover, '<1>', 'Ein Test', '</1><2><3/></2>');
        $this->_testContent($remover, '<1><2/></1><3>', 'Ein Test', '</3>');
        $this->_testContent($remover, '<1>', 'Ein Test', '</1></2>');
        $this->_testContent($remover, '<1/><2>', 'Ein Test', '</2>');

        //just a single tag at the start / end
        $this->_testContent($remover, '<1/>', 'Ein Test', '');
        $this->_testContent($remover, '', 'Ein Test', '<1/>');

        //keeping the tags if a partner is inside text
        $this->_testContent($remover, '', '<1>Ein</1> Test', '');
        $this->_testContent($remover, '', 'Ein <1>Test</1>', '');

        //test with target given
        $this->_testContent($remover, '<1>', 'Ein Test', '</1>', 'A test');
        $this->_testContent($remover, '<1>', 'Ein Test', '</2></1>', 'A test');

        //test with target given, keep tags if target is different
        $this->_testContent($remover, '', '<1>Ein Test<2/></1>', '', '<1>A test</1><2/>');
        $this->_testContent($remover, '', '<1>Ein Test<10/></1>', '', '<1>A test</1><10/>');
    }

    /**
     * This methods concats the given start middle and end parts, gives them to the remover, and the remover should return the start as removed start, the end as removed end.
     * @param editor_Models_Import_FileParser_Xlf_SurroundingTagRemover_Abstract $remover
     * @param string $start
     * @param string $middleSource
     * @param string $end
     * @param string|null $middleTarget defaults to middleSource but can be given to test different tags between source and target
     */
    protected function _testContent(
        editor_Models_Import_FileParser_Xlf_SurroundingTagRemover_Abstract $remover,
        string $start,
        string $middleSource,
        string $end,
        ?string $middleTarget = null)
    {
        $source = $start.$middleSource.$end;
        $remover->calculate(false,
            $sourceChunks = $this->prepareContent($source),
            is_null($middleTarget) ? [] : $this->prepareContent($start.$middleTarget.$end),
            self::$xmlParser);

        $this->assertEquals($start, $remover->getLeading(), 'removed leading content is not as expected: '.$source);
        $this->assertEquals($end, $remover->getTrailing(), 'removed trailing content is not as expected: '.$source);

        $this->assertEquals($middleSource, self::$xmlParser->join(array_map(function($item){
            //we convert the internal tag objects back to string for comparsion
            return (string) $item;
        }, $remover->sliceTags($sourceChunks))), 'trimmed content is not as expected: '.$source);
    }

    protected function prepareContent(string $text): array {
        $chunks = preg_split('#(</?[^>]+>)#i', $text, flags: PREG_SPLIT_DELIM_CAPTURE);
        $partner = [];
        //loop over all chunks and convert tags to tag objects as needed in the Removers
        foreach($chunks as $idx => $item) {
            if(!str_starts_with($item, '<') && !str_ends_with($item, '>')) {
                continue;
            }
            $tag = $item;
            if(str_starts_with($tag, '</')) {
                $type = editor_Models_Import_FileParser_Tag::TYPE_CLOSE;
            }
            elseif(str_ends_with($tag, '/>')) {
                $type = editor_Models_Import_FileParser_Tag::TYPE_SINGLE;
            }
            else {
                $type = editor_Models_Import_FileParser_Tag::TYPE_OPEN;
            }
            $item = new editor_Models_Import_FileParser_Tag($type);
            $item->originalContent = $item->renderedTag = $tag;
            $item->tagNr = trim($tag, '</>');
            $item->tag = 'x';

            if($item->isSingle()) {
                //single tags with a nr bigger as 9 are tested as it tags
                if((int) $item->tagNr > 9) {
                    $item->tag = 'it'; //to test isolated stuff
                }
            }
            //openers and closers need their partners:
            else {
                $item->tag = 'g';
                if(empty($partner[$item->tagNr])) {
                    $partner[$item->tagNr] = $item;
                }
                else {
                    $item->partner = $partner[$item->tagNr];
                    $item->partner->partner = $item;
                }
            }
            $chunks[$idx] = $item;
        }
        return $chunks;
    }
}
