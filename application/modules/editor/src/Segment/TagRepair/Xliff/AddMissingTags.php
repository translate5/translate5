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

declare(strict_types=1);

namespace MittagQI\Translate5\Segment\TagRepair\Xliff;

/**
 * In case tags are missing in translated text, they will be added ad the beginning
 */
class AddMissingTags implements RepairInterface
{
    /**
     * Add tags that exist in source but are missing in translated text
     *
     * @param string $text Text to repair
     * @param array<TagInterface> $sourceTags Tags from source text
     * @param array<TagInterface> $translatedTags Tags from translated text
     * @return string Modified text
     */
    public function apply(string $text, array $sourceTags, array $translatedTags): string
    {
        $modifiedText = $text;

        // create maps of tags by ID
        $sourceTagsById = [];
        $sourceTagsByRid = [];
        foreach ($sourceTags as $tag) {
            $sourceTagsById[$tag->getId()] = $tag;

            // group paired tags by rid for easier access
            if ($tag->isPaired() && $tag->getRid() !== null) {
                if (! isset($sourceTagsByRid[$tag->getRid()])) {
                    $sourceTagsByRid[$tag->getRid()] = [];
                }
                $sourceTagsByRid[$tag->getRid()][] = $tag;
            }
        }

        $translatedTagsById = [];
        $translatedTagsByRid = [];
        foreach ($translatedTags as $tag) {
            $translatedTagsById[$tag->getId()] = $tag;

            // group paired tags by rid for easier access
            if ($tag->isPaired() && $tag->getRid() !== null) {
                if (! isset($translatedTagsByRid[$tag->getRid()])) {
                    $translatedTagsByRid[$tag->getRid()] = [];
                }
                $translatedTagsByRid[$tag->getRid()][] = $tag;
            }
        }

        $missingTags = [];
        $missingPairedTags = [];

        foreach ($sourceTagsById as $id => $tag) {
            if (! isset($translatedTagsById[$id])) {
                if ($tag->isSingle()) {
                    $missingTags[] = $tag;
                } else {
                    if (! isset($missingPairedTags[$tag->getRid()])) {
                        $missingPairedTags[$tag->getRid()] = [
                            'bx' => null,
                            'ex' => null,
                        ];
                    }
                    $missingPairedTags[$tag->getRid()][$tag->getType()] = $tag;
                }
            }
        }

        // process missing paired tags first
        foreach ($missingPairedTags as $rid => $missingPair) {
            // only process if at least one tag in the pair is missing
            if ($missingPair['bx'] === null && $missingPair['ex'] === null) {
                continue;
            }

            // do we have the partner tag in the translated text ?
            $translatedPartners = $translatedTagsByRid[$rid] ?? [];

            if (! empty($translatedPartners)) {
                // we have at least one tag from the pair in the translated text
                // -> place it accordingly to the existing pair

                foreach ($translatedPartners as $partnerTag) {
                    $partnerPos = strpos($modifiedText, $partnerTag->getFullTag());

                    if ($partnerPos !== false) {
                        // position of the partner tag match

                        if ($partnerTag->isOpening() && $missingPair['ex'] !== null) {
                            // partner is opening and we are missing the closing tag
                            // place closing tag immediately after the opening tag
                            $insertPos = $partnerPos + strlen($partnerTag->getFullTag());

                            $newTag = $missingPair['ex']->recreate();
                            $modifiedText = substr_replace($modifiedText, $newTag, $insertPos, 0);
                        } elseif ($partnerTag->isClosing() && $missingPair['bx'] !== null) {
                            // partner is closing and we are missing the opening tag
                            // place opening tag immediately before the closing tag
                            $insertPos = $partnerPos;

                            $newTag = $missingPair['bx']->recreate();
                            $modifiedText = substr_replace($modifiedText, $newTag, $insertPos, 0);
                        }

                        break;
                    }
                }
            } else {
                // no partner tags exist in translated text:We will need to add both missing tags

                if ($missingPair['bx'] !== null && $missingPair['ex'] !== null) {
                    // if both tags are missing, add them at the beginning
                    $modifiedText = $missingPair['bx']->recreate() . $missingPair['ex']->recreate() . $modifiedText;
                } elseif ($missingPair['bx'] !== null) {
                    // just add opening at the beginning
                    $modifiedText = $missingPair['bx']->recreate() . $modifiedText;
                } elseif ($missingPair['ex'] !== null) {
                    // just add closing at the end
                    $modifiedText .= $missingPair['ex']->recreate();
                }
            }
        }

        // add missing single tags at the beginning
        foreach ($missingTags as $tag) {
            $modifiedText = $tag->recreate() . $modifiedText;
        }

        return $modifiedText;
    }
}
