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

class Tag implements TagInterface
{
    // Tag types
    // TODO: constants should be used in the repair stratigies. A lot of direct deffinitions of the tags.
    public const TYPE_SINGLE = 'x';

    public const TYPE_OPENING = 'bx';

    public const TYPE_CLOSING = 'ex';

    /**
     * Constructor
     *
     * @param string $type The tag type
     * @param string|null $rid The relation identifier (null for single tags)
     * @param int $position The position in the text
     * @param string $fullTag The full tag text
     */
    public function __construct(
        private string $type,
        private string $id,
        private ?string $rid,
        private int $position,
        private string $fullTag,
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getRid(): ?string
    {
        return $this->rid;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getFullTag(): string
    {
        return $this->fullTag;
    }

    public function isPaired(): bool
    {
        return $this->type === self::TYPE_OPENING || $this->type === self::TYPE_CLOSING;
    }

    public function isOpening(): bool
    {
        return $this->type === self::TYPE_OPENING;
    }

    public function isClosing(): bool
    {
        return $this->type === self::TYPE_CLOSING;
    }

    public function isSingle(): bool
    {
        return $this->type === self::TYPE_SINGLE;
    }

    public function recreate(): string
    {
        $attributes = 'id="' . $this->id . '"';

        if ($this->rid !== null) {
            $attributes .= ' rid="' . $this->rid . '"';
        }

        return '<' . $this->type . ' ' . $attributes . '/>';
    }

    /**
     * Create a clone of this tag with modified properties
     *
     * @param array $changes Key-value pairs of properties to change
     */
    public function cloneWithChanges(array $changes): TagInterface
    {
        $type = $changes['type'] ?? $this->type;
        $id = $changes['id'] ?? $this->id;
        $rid = array_key_exists('rid', $changes) ? $changes['rid'] : $this->rid;
        $position = $changes['position'] ?? $this->position;

        return new Tag($type, $id, $rid, $position, '');
    }

    /**
     * Factory method to create a tag from a text string
     */
    public static function fromString(string $tagString, int $position): ?TagInterface
    {
        $pattern = '/<(bx|ex|x)\s+([^>]+)\/>/';

        if (preg_match($pattern, $tagString, $matches)) {
            $type = $matches[1];
            $attributes = $matches[2];

            $id = null;
            $rid = null;

            if (preg_match('/id="([^"]+)"/', $attributes, $idMatch)) {
                $id = $idMatch[1];
            }

            if (preg_match('/rid="([^"]+)"/', $attributes, $ridMatch)) {
                $rid = $ridMatch[1];
            }

            if ($id !== null) {
                return new Tag($type, $id, $rid, $position, $tagString);
            }
        }

        return null;
    }
}
