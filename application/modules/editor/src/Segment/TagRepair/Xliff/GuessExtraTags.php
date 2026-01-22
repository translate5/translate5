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
 * Repair tags by guessing extra tags based on source tags not present in target and vice versa.
 * Sometimes tags are marked as 'additional-<id>' in the target because those were not recognized properly.
 * however additional tags often correspond to tags in the source that were simply not matched properly.
 * This repair will try to match those additional tags with source tags not present in the target
 * based on type and id/rid.
 */
class GuessExtraTags implements RepairInterface
{
    public function apply(string $text, array $sourceTags, array $translatedTags): string
    {
        $sourceNotInTarget = $this->retrieveTagsNotInTarget($sourceTags, $translatedTags);

        foreach ($translatedTags as $translatedTag) {
            if (! str_contains($translatedTag->getId(), 'additional')) {
                continue;
            }

            $found = false;
            foreach ($sourceNotInTarget as $key => $sourceTag) {
                if (
                    $sourceTag->getType() !== $translatedTag->getType()
                ) {
                    continue;
                }

                if (! in_array(
                    str_replace('additional-', '', $translatedTag->getId()),
                    [$sourceTag->getId(), $sourceTag->getRid()],
                    true
                )) {
                    continue;
                }

                // create corrected tag
                $newTag = $translatedTag->cloneWithChanges([
                    'rid' => $sourceTag->getRid(),
                    'id' => $sourceTag->getId(),
                ]);

                // Replace in text
                $text = str_replace(
                    $translatedTag->getFullTag(),
                    $newTag->recreate(),
                    $text
                );
                $found = true;

                break;
            }

            if ($found && isset($key)) {
                // remove from not in target list
                unset($sourceNotInTarget[$key]);
            }
        }

        return $text;
    }

    /**
     * @param TagInterface[] $sourceTags
     * @param TagInterface[] $translatedTags
     * @return TagInterface[]
     */
    private function retrieveTagsNotInTarget(array $sourceTags, array $translatedTags): array
    {
        $sourceNotInTarget = [];

        foreach ($sourceTags as $sourceTag) {
            $found = false;

            foreach ($translatedTags as $translatedTag) {
                if (
                    $sourceTag->getType() === $translatedTag->getType()
                    && $sourceTag->getId() === $translatedTag->getId()
                ) {
                    $found = true;

                    break;
                }
            }

            if (! $found) {
                $sourceNotInTarget[] = $sourceTag;
            }
        }

        return $sourceNotInTarget;
    }
}
