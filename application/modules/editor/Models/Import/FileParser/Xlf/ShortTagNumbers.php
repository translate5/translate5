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

use editor_Models_Import_FileParser_Tag as Tag;

/**
 * handles the generation of the tag shorttagnumbers for internal tags (source / target and tag pairs only,
 *  whitespace numbering is done in the whitespace tag helper
 *
 * TRANSLATE-2658
 * with memoQ XLF we had the following problem - the id is correct to match between source and target,
 * the rid indeed not.
 * The rid is only consistent inside either source or target to find bpt ept pairs, and is required to
 * be used here, since the bpt / ept id is not matching.
 * Example:
 * source: <bpt id="1" rid="1">&lt;uf&gt;</bpt>TEXT<ept id="2" rid="1">&lt;uf&gt;</ept>
 * target: <bpt id="1" rid="2">&lt;uf&gt;</bpt>text<ept id="2" rid="2">&lt;uf&gt;</ept>
 * Here the rid is used to match between bpt and ept, but not between source and target, therefore the ID is used.
 * We assume this is valid for all XLF formats.
 */
class editor_Models_Import_FileParser_Xlf_ShortTagNumbers
{
    /**
     * map of content tag to tagNr, to get the correct tagNr for tag pairs and between source and target column
     */
    protected array $shortTagNumbers = [];

    /**
     * counter for internal tags - public since may be edited from outside
     */
    public int $shortTagIdent = 1;

    /**
     * Contains all tags of the current call(source or target), paired or not
     * @var editor_Models_Import_FileParser_Tag[]
     */
    protected array $allTags = [];

    protected bool $source = true;

    /**
     * resets the shortTags - if source then reset completly,
     * if not source (target then) keep the shorTags for finding existing ones
     */
    public function init(bool $source)
    {
        $this->source = $source;
        $this->allTags = []; //allTags references to the tags of either source or target

        //this assumes that source tags are coming always before target tags
        if ($source) {
            $this->shortTagIdent = 1;
            $this->shortTagNumbers = [];
        } else {
            //if we parse the target, we have to reuse the tagNrs found in source, so we do not reset shortTagNumbers
            // but reset shortTagIdent for potential new tags to the highest previously found shortTagNumber + 1
            $this->shortTagIdent = empty($this->shortTagNumbers) ?
                1 :
                (max(array_merge($this->shortTagNumbers['single'] ?? [], $this->shortTagNumbers['pair'] ?? [])) + 1);
        }
    }

    /**
     * calculates the short tag numbers and renders the tag
     * - partners are tried in each group (source or target) first by rid, if no rid set, lookup by id in the same group
     * - if we are in source collect all used short tag numbers by id
     * - if we are in target use the short tag numbers used in source for tags with the same id, if no match,
     * create a new shortTagNr
     */
    public function calculatePartnerAndType()
    {
        //first loop to find the partners by rid, or if not given by id
        $tagsById = [];
        foreach ($this->allTags as $tag) {
            if (! is_null($tag->rid) && array_key_exists('rid-' . $tag->rid, $tagsById)) {
                // tag has an rid and there is already a tag with rid
                $this->setAsPartners($tag, $tagsById['rid-' . $tag->rid]);
            } elseif (is_null($tag->rid) && ! $tag->isSingle() && array_key_exists('id-' . $tag->id, $tagsById)) {
                // we may apply id based matching only if no rid based partner was found (and the tag has no rid)
                // and on open/close tags, since the id of single tags may be duplicated
                $this->setAsPartners($tag, $tagsById['id-' . $tag->id]);
            } elseif (! is_null($tag->rid)) {
                $tagsById['rid-' . $tag->rid] = $tag;
            } else {
                $tagsById['id-' . $tag->id] = $tag;
            }
        }

        //second loop: find shorttag numbers, either of the partner (source)
        // or of the shortTagnumbers of the source (target)
        foreach ($this->allTags as $tag) {
            if (empty($tag->partner)) {
                // if a previous open/close tag has no partner, we render it as single tag...
                $tag->setSingle();
            }

            //tagnr aldready set, (perhaps via partner link), then just render
            if (! is_null($tag->tagNr)) {
                $tag->renderTag();

                continue;
            }

            $shortTagNumberKey = $this->getShortTagNumberKey($tag);

            //if we are in source or no shortTagNumber was used for that tag ID yet, create one
            if ($this->source || empty($this->shortTagNumbers[$shortTagNumberKey][$tag->getId()])) {
                $tag->tagNr = $this->shortTagIdent++;
                $this->shortTagNumbers[$shortTagNumberKey][$tag->getId()] = $tag->tagNr;
            } else {
                //only in target and if the same tag was already found in source, then reuse
                $tag->tagNr = $this->shortTagNumbers[$shortTagNumberKey][$tag->getId()];
            }

            //if the tag has a partner, set the partners number too
            if (! empty($tag->partner)) {
                $tag->partner->tagNr = $tag->tagNr;
                //track the shortTagNumber of the partner if not already set
                if (empty($this->shortTagNumbers[$shortTagNumberKey][$tag->partner->getId()])) {
                    $this->shortTagNumbers[$shortTagNumberKey][$tag->partner->getId()]
                        = $tag->partner->tagNr;
                }
            }

            $tag->renderTag();
        }
    }

    /**
     * Small helper to join partners
     */
    private function setAsPartners(editor_Models_Import_FileParser_Tag $tag, editor_Models_Import_FileParser_Tag $partner)
    {
        $tag->partner = $partner;
        $partner->partner = $tag;
    }

    /**
     * collects the created tag
     */
    public function addTag(editor_Models_Import_FileParser_Tag $tagObj)
    {
        $idsFrequency = array_count_values(array_map(static fn (Tag $tag) => $tag->getIdentifier(), $this->allTags));

        if (array_key_exists($tagObj->getIdentifier(), $idsFrequency)) {
            $tagObj->changeId($tagObj->getId() . '-duplicate-' . $idsFrequency[$tagObj->getIdentifier()]);
        }

        $this->allTags[] = $tagObj;
    }

    private function getShortTagNumberKey(editor_Models_Import_FileParser_Tag $tag): string
    {
        return $tag->isSingle() ? 'single' : 'pair';
    }
}
