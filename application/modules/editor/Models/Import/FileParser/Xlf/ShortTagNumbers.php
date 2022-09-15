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
 * handles the generation of the tag shorttagnumbers for internal tags (source / target and tag pairs only,
 *  whitespace numbering is done in the whitespace tag helper
 *
 * TRANSLATE-2658
 * with memoQ XLF we had the following problem - the id is correct to match between source and target, the rid indeed not.
 * The rid is only consistent inside either source or target to find bpt ept pairs, and is required to be used here, since the bpt / ept id is not matching.
 * Example:
 * source: <bpt id="1" rid="1">&lt;uf&gt;</bpt>TEXT<ept id="2" rid="1">&lt;uf&gt;</ept>
 * target: <bpt id="1" rid="2">&lt;uf&gt;</bpt>text<ept id="2" rid="2">&lt;uf&gt;</ept>
 * Here the rid is used to match between bpt and ept, but not between source and target, therefore the ID is used.
 * We assume this is valid for all XLF formats.
 */
class editor_Models_Import_FileParser_Xlf_ShortTagNumbers {
    /**
     * map of content tag to tagNr, to get the correct tagNr for tag pairs and between source and target column
     * @var array
     */
    protected array $shortTagNumbers = [];

    /**
     * counter for internal tags - public since may be edited from outside
     * @var integer
     */
    public int $shortTagIdent = 1;

    /**
     * Contains all tags of the current call(source or target), paired or not
     * @var editor_Models_Import_FileParser_Tag[]
     */
    protected array $allTags = [];

    /**
     * @var boolean
     */
    protected bool $source = true;

    /**
     * resets the shortTags - if source then reset completly, if not source (target then) keep the shorTags for finding existing ones
     * @param bool $source
     */
    public function init(bool $source) {
        $this->source = $source;
        $this->allTags = []; //allTags references to the tags of either source or target

        //this assumes that source tags are coming always before target tags
        if($source) {
            $this->shortTagIdent = 1;
            $this->shortTagNumbers = [];
        }
        else {
            //if we parse the target, we have to reuse the tagNrs found in source, so we do not reset shortTagNumbers
            // but reset shortTagIdent for potential new tags to the highest previously found shortTagNumber + 1
            $this->shortTagIdent = empty($this->shortTagNumbers) ? 1 : (max($this->shortTagNumbers) + 1);
        }
    }

    /**
     * calculates the short tag numbers and renders the tag
     * - partners are tried in each group (source or target) first by rid, if no rid set, lookup by id in the same group
     * - if we are in source collect all used short tag numbers by id
     * - if we are in target use the short tag numbers used in source for tags with the same id, if no match, create a new shortTagNr
     */
    public function calculatePartnerAndType() {
        $tagsById = [];
        //first loop to find the partners by rid, or if not given by id
        foreach($this->allTags as $tag) {
            $partner = null;
            if(!is_null($tag->rid)) {
                if(empty($tagsById['rid-'.$tag->rid])) {
                    $tagsById['rid-'.$tag->rid] = $tag;
                }
                else {
                    //if we have the same rid already, this is the partner
                    $partner = $tagsById['rid-'.$tag->rid];
                }
            }
            if(empty($tagsById['id-'.$tag->id])) {
                $tagsById['id-'.$tag->id] = $tag;
            }
            elseif(is_null($partner)) {
                //if we have found no partner by rid we use the partner by id
                // TODO possible problem here, may have a <ph> tag and a <ex> tag have the same id? if yes, they would get paired here, although they do not belong together
                $partner = $tagsById['id-'.$tag->id];
            }

            //if we have a partner: link each other
            if(!is_null($partner)) {
                $tag->partner = $partner;
                $partner->partner = $tag; // the enemy of my enemy is my friend ;)
            }
        }


        //second loop: find shorttag numbers, either of the partner (source) or of the shortTagnumbers of the source (target)
        foreach($this->allTags as $tag) {
            if(empty($tag->partner)) {
                $tag->setSingle();
            }

            //tagnr aldready set, (perhaps via partner link), then just render
            if(!is_null($tag->tagNr)) {
                $tag->renderTag();
                continue;
            }

            //if we are in source or no shortTagNumber was used for that tag ID yet, create one
            if($this->source || empty($this->shortTagNumbers[$tag->id])) {
                $tag->tagNr = $this->shortTagIdent++;
                $this->shortTagNumbers[$tag->id] = $tag->tagNr;
            }
            else {
                //only in target and if the same tag was already found in source, then reuse
                $tag->tagNr = $this->shortTagNumbers[$tag->id];
            }

            //if the tag has a partner, set the partners number too
            if(!empty($tag->partner)) {
                $tag->partner->tagNr = $tag->tagNr;
                //track the shortTagNumber of the partner if not already set
                if(empty($this->shortTagNumbers[$tag->partner->id])) {
                    $this->shortTagNumbers[$tag->partner->id] = $tag->partner->tagNr;
                }
            }

            $tag->renderTag();
        }
    }

    /**
     * collects the created tag
     * @param editor_Models_Import_FileParser_Tag $tagObj
     */
    public function addTag(editor_Models_Import_FileParser_Tag $tagObj) {
        $this->allTags[] = $tagObj;
    }
}
