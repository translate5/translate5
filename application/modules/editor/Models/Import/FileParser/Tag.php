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
 * Internal struct to contain a tag and its data parts for the import
 * TODO move TagTrait functionality into this class too
 */
class editor_Models_Import_FileParser_Tag {
    
    const RETURN_MODE_INTERNAL = 'internal';
    const RETURN_MODE_ORIGINAL = 'original';
    const RETURN_MODE_REMOVED  = 'removed';
    
    const TYPE_OPEN     = 1;
    const TYPE_CLOSE    = 2;
    const TYPE_SINGLE   = 3;
    
    protected $type;
    
    public $tag;
    public $rid;
    public $tagNr;
    public $originalContent;
    public $text;
    public $renderedTag;
    
    protected static $mode = self::RETURN_MODE_INTERNAL;

    
    /**
     * sets the mode what tags should return on converting to string, returns the previous mode
     * @param string $mode
     * @return string
     */
    public static function setMode(string $mode = self::RETURN_MODE_INTERNAL): string {
        $oldMode = self::$mode;
        self::$mode = $mode;
        return $oldMode;
    }
    
    /**
     * returns the current tag mode
     * @return string
     */
    public static function getMode(): string {
        return self::$mode;
    }
    
    /**
     * type of tag must be given on construction
     * @param int $type
     */
    public function __construct(int $type) {
        $this->type = $type;
    }
    
    public function isSingle(): bool {
        return $this->type === self::TYPE_SINGLE;
    }
    
    public function isOpen(): bool {
        return $this->type === self::TYPE_OPEN;
    }
    
    public function isClose(): bool {
        return $this->type === self::TYPE_CLOSE;
    }
    
    /**
     * returns the tag content according to the set mode
     * @return string
     */
    public function __toString(): string {
        switch (self::$mode) {
            case self::RETURN_MODE_REMOVED:
                return '';
            case self::RETURN_MODE_ORIGINAL:
                return $this->originalContent;
            case self::RETURN_MODE_INTERNAL:
            default:
                return $this->renderedTag;
        }
    }
}