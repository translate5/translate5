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

/**
 * converts single paired XML tags into paired tags where possible.
 *  <bx rid="1">foo<ex rid="1"> OR <bpt rid="1">foo<ept rid="1">
 * to
 *  <g id="1">foo</g>
 * 
 * More examples and algorithm explanation at the end of the file!
 * 
 * Currently this class is made to pair <bpt>/<bx> and <ept>/<ex> XLIFF tags, 
 *  but it should be easily possible to refactor this class in a more general way to use it also for other single tag pairs
 */
class editor_Models_Converter_XmlPairer {
    /**
     * @var editor_Models_Converter_XmlPairerNode[]
     */
    protected $openersById = [];
    
    /**
     * @var array
     */
    protected $nodeList;
    
    /**
     * @var editor_Models_Converter_XmlPairerNode
     */
    protected $lastNode = null;
    
    /**
     * Contains a map with the replaced data key: bpt/bx / ept/ex => value: g tag
     * @var array
     */
    protected $replaceList = [];
    
    /**
     * Contains a map between opener tag and closer tag (is needed for restoring a whole tagmap)
     * @var array
     */
    protected $pairMap = [];
    
    /**
     * @var array
     */
    protected $validOpenTags = ['bx', 'bpt'];
    
    /**
     * @var array
     */
    protected $validCloseTags = ['ex', 'ept'];
    
    /**
     * Only pairs in the same container can be replaced. 
     * Fixes TRANSLATE-1841 where immutable mrk tag pairs mess up the nested output.
     * @var integer
     */
    protected $containerId = 0;
    
    /**
     * container stack, see containerId
     * @var array
     */
    protected $containerStack = [];
    
    public function pairTags($xmlAllUnpaired) {
        //split up tags and text in nodes
        $this->nodeList = preg_split('/(<[^>]*>)/i', $xmlAllUnpaired, flags: PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
        
        $this->lastNode = null;
        
        //walk over all nodes (tags and text) and get meta data
        foreach($this->nodeList as $idx => $node) {
            $this->parseNode($idx, $node);
        }
        
        //if there are openers without an closer it remains as bx and we can just ignore it in the further code
        foreach($this->openersById as $rid => $node) {
            if(empty($node->partner)) {
                unset($this->openersById[$rid]);
            }
        }
        
        foreach($this->openersById as $rid => $node) {
            if(! $node->isReplaceable()) {
                continue;
            }
            $opener = $node->isOpener() ? $node : $node->partner;
            $closer = $opener->partner;

            //we cannot use rid here, since its not sure that it is unique in one segment
            // the id instead is generated on bx / ex generation and is unique
            $newOpener = $this->getOpener($opener);
            $newCloser = $this->getCloser($closer);
            
            $this->pairMap[$this->nodeList[$opener->idx]] = $this->nodeList[$closer->idx];
            $this->replaceList[$this->nodeList[$opener->idx]] = $newOpener;
            $this->replaceList[$this->nodeList[$closer->idx]] = $newCloser;
            
            $this->nodeList[$opener->idx] = $newOpener;
            $this->nodeList[$closer->idx] = $newCloser;
            
        }
        
        return join('', $this->nodeList);
        //walking over all found pairs, and replace them to g tags where there are no misplaced tags in between
        // misplaced means, where nesting level is lower as the nesting level of the pair itself
    }

    protected function getOpener(editor_Models_Converter_XmlPairerNode $node) {
        return '<g id="'.$node->id.'">';
    }
    
    protected function getCloser(editor_Models_Converter_XmlPairerNode $node) {
        return '</g>';
    }
    
    /**
     * returns a list of replaced single tags with g tags
     * @return array
     */
    public function getReplaceList() {
        return $this->replaceList;
    }
    
    /**
     * returns a map between bpt/ept paired tags
     * @return array
     */
    public function getPairMap() {
        return $this->pairMap;
    }
    
    /**
     * parses one given node
     * @param int $idx
     * @param string $node
     */
    protected function parseNode($idx, $node) {
        $m = $this->createNode($idx, $node);
        if($m === false) {
            $this->adjustContainerLevel($node);
            return;
        }
        $m->containerId = end($this->containerStack);
        
        if($m->isOpener()) {
            //take care of the opener reference
            $this->openersById[$m->rid] = $m;
        }
        //TAG_CLOSE:
        else {
            //if no open tag found before the close tag, we ignore the close tag. 
            if(empty($this->openersById[$m->rid])) {
                return; 
            }
            //setting both tags as partner on each other
            $m->partner = $this->openersById[$m->rid];
            $m->partner->partner = $m;
        }
        
        $this->changeLevel($m);
        $this->addAsChild($m);
        
        $this->lastNode = $m;
    }
    
    /**
     * creates a XmlPairerNode by the given nod
     * @param int $idx
     * @param string $node
     * @return boolean|editor_Models_Converter_XmlPairerNode
     */
    protected function createNode($idx, $node) {
        $matches = null;
        //if no tag or a tag without rid information, ignore
        if(!preg_match('#<([^\s>]+)[^>]+id="([^"]+)"[\s]+rid="([^"]+)"[^>]*>#', $node, $matches)) {
            return false;
        }
        
        $tag = $matches[1];
        $id = $matches[2];
        $rid = $matches[3];
        
        //ignore non paired tags
        if(!in_array($tag, $this->validOpenTags) && !in_array($tag, $this->validCloseTags)) {
            return false;
        }
        
        //init node data, $open = ($tag == 'bx' || $tag == 'bpt')
        $opener = (in_array($tag, $this->validOpenTags));
        return new editor_Models_Converter_XmlPairerNode($idx, $opener, $rid, $id);
    }
    
    /**
     * only single tags in the same container (like <mrk></mrk>) should be paired
     * @param string $node
     * @return editor_Models_Converter_XmlPairerNode|boolean
     */
    protected function adjustContainerLevel(string $node) {
        $matches = [];
        //check if the node is a opener, closer or single tag
        if(preg_match('#<(/)?[^>/]*(/>|>)#', $node, $matches)) {
            // if the "/" is found at the start, then it is a closer
            if($matches[1] === '/') {
                array_pop($this->containerStack);
            }
            // if the closing bracket has no preceding "/" it is the closing tag
            elseif($matches[2] === '>') {
                $this->containerStack[] = ++$this->containerId;
            }
            // else means $matches[2] === '/>': if the "/" is found at the end, then it is a self closing single tag, which is to be ignored
        }
    }
    
    /**
     * calculates the nesting level
     * @param editor_Models_Converter_XmlPairerNode $currentNode
     */
    protected function changeLevel (editor_Models_Converter_XmlPairerNode $currentNode) {
        if($this->lastNode) {
            $nestingLevel = $this->lastNode->level;
        }
        else {
            $nestingLevel = 0;
        }
        
        //recalculate the nesting level, depending on the last tag type
        //bx > bx → nesting++
        //bx > ex → nesting unchanged
        //ex > bx → nesting unchanged
        //ex > ex → nesting--
        if($this->lastNode && ($this->lastNode->type === $currentNode->type)) {
            $nestingLevel += $this->lastNode->type;
        }
        
        //only for openers the calculated nestinglevel must be set
        if($currentNode->isOpener()) {
            $currentNode->level = $nestingLevel;
        }
        else {
            //in wellformed XML the level of the close tag is 
            // always the same as of its paired open tag:
            // since currentNode is a closer here, partner must be defined or we would not got here
            $currentNode->level = $currentNode->partner->level;
        }
    }
    
    /**
     * Adds the given node (opener and closer) as childNode to all open openers
     *  open opener means here: no closer found yet 
     * @param editor_Models_Converter_XmlPairerNode $currentNode
     */
    protected function addAsChild(editor_Models_Converter_XmlPairerNode $currentNode) {
        foreach($this->openersById as $rid => $node) {
            if(!is_null($node->rid) && $node->rid === $currentNode->rid) {
                //don't add the partner as child
                continue; 
            }
            //this opener node is still open, that means it is a parent if currentNode
            if(empty($node->partner)) {
                $node->children[] = $currentNode;
            }
        }
    }
}

/**
 * Only internally needed class to wrap XML nodes 
 */
class editor_Models_Converter_XmlPairerNode {
    const TAG_OPEN = 1;
    const TAG_CLOSE = -1;
    /**
     * @var integer 1 open tag; -1 close tag 
     */
    public $type;
    
    /**
     * @var integer The node index in the original node array
     */
    public $idx;
    
    /**
     * @var integer The node ID in the original node
     */
    public $id;
    
    /**
     * @var mixed The reference ID to match open and close tag
     */
    public $rid;
    
    /**
     * @var integer The nesting level of this node
     */
    public $level = null;
    
    /**
     * @var editor_Models_Converter_XmlPairerNode The partner node to this node
     */
    public $partner = null;
    
    /**
     * @var editor_Models_Converter_XmlPairerNode[] The nodes which are between this node and its partner
     */
    public $children = [];
    
    /**
     * level for the real tag pair container of this node (existing mrk pairs for example)
     * @var integer
     */
    public $containerId;
    
    /**
     * @param int $idx The node index in the original node array
     * @param bool $open true when open tag, false when closing tag
     * @param mixed $rid The reference ID to match open and close tag
     * @param int $id The reference ID to match open and close tag
     */
    public function __construct($idx, $open, $rid, $id) {
        $this->idx = $idx;
        $this->id = $id;
        $this->rid = $rid; //referenceId;
        $this->type = $open ? self::TAG_OPEN : self::TAG_CLOSE;
    }
    
    /**
     * returns true if this node is an opener, false otherwise
     * @return boolean
     */
    public function isOpener() {
        return $this->type === self::TAG_OPEN;
    }
    
    /**
     * returns true if this node and its partner can be currently converted to a g tag pair
     *  This is done by comparing the children nestinglevel to the local nesting level 
     * @return boolean
     */
    public function isReplaceable() {
        if($this->containerId !== $this->partner->containerId) {
            return false;
        }
        foreach($this->children as $child) {
            //if one child has the same or a lesser level, this node is not replacable
            if($child->level <= $this->level) {
                return false;
            }
        }
        //all children are wellformed XML
        return true;
    }
}

/*

1. The algorithm splits the string up into XML tags and text nodes
2. Then the nesting level of each tag is calculated,
   the end tag is forced to have the same level as its beginning tag
3. Each Tag pair which contains children with a lesser nesting level
   cannot be converted to a real tag pair, since the XML would not be 
   wellformed
4. The recognition of valid tag pairs is first come / first server, 
   see the latest example. 

An easy HTML example leading to invalid XML could look like this:
<p>
    <b>
        <i>
    </b>
        </i>
</p>

In single paired tags this would be:
<bx rid="1" type="p">
    <bx rid="1" type="b">
        <bx rid="1" type="i">
    <ex rid="2" type="b">
        <ex rid="1" type="i">
<ex rid="3" type="p">

The nesting levels would be (in HTML again, since easier to read:)
<p>             1
    <b>         2
        <i>     3
    </b>        2 → The i tag pair contains this child with a lower nesting level
        </i>    3
</p>            1

In the above example the <i> tag pair has an invalid child, 
so the i tag pair cannot be converted into a tag pair.

The final result would then after conversion:
<g id="1" type="p">
    <g id="1" type="b">
        <bx rid="1" type="i">
    </g>
        <ex rid="1" type="i">
</g>



More examples of the algorithm:

<d>                     1
    <p2>                2
        <b>             3
            <i>         4
                <p1>    5
            </i>        4
            <i>         4
            </i>        4
        </b>            3
    </p2>               2
                </p1>   5 <p1></p1> cannot be resolved
    <p2>                2
        <b>             3
            <i>         4
                <p1>    5
            </i>        4
        </b>            3
    </p2>               2
                </p1>   5 <p1></p1> again cannot be resolved
</d>                    1



<p2>                1
    <b>             2
        <i>         3
            <p1>    4 → is ignored and remains <bx> because of missing paired tag
        </i>        3
    </x>              → remains <ex> because of missing paired opener tag
    </b>            3
</p2>               2


This examples shows that the algorithm could be improved in detecting which nodes are causing the error:
<x>                     1 → recognized as g
    <p2>                2 → remains bx
        <b>             3 → remains bx
            <i>         4 → recognized as g 
            </i>        4 → recognized as g
</x>                    1 → recognized as g
        </b>            3 → remains ex
    </p2>               2 → remains ex
    
    
Would be the <x> pair recognized as bad pair instead, we could have 
<x>                     1 → remains bx
    <p2>                2 → recognized as g
        <b>             3 → recognized as g
            <i>         4 → recognized as g 
            </i>        4 → recognized as g
</x>                    1 → remains ex
        </b>            3 → recognized as g
    </p2>               2 → recognized as g
    
This would be the better example since lesser single tag pairs. 
But effort to improve the algorithm in that way, would be to high as we need it here.

*/