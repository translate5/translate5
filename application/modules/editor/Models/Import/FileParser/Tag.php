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

    /**
     * @var array|editor_ImageTag[]
     */
    static array $renderer = [
        self::TYPE_OPEN => 'editor_ImageTag_Left',
        self::TYPE_CLOSE => 'editor_ImageTag_Right',
        self::TYPE_SINGLE => 'editor_ImageTag_Single',
    ];

    protected int $type;

    /**
     * flag if the tags set as originalContent are XMLish (so starting and ending with < and >)
     * @var bool
     */
    protected bool $xmlTags = true;

    /**
     * The originating tag (x|g|it|ph|bx|ex|bpt|ept)
     */
    public string $tag;

    /**
     * @var string mandatory, the id to identity the same tag in source and target
     */
    public string $id;

    /**
     * @var string|null optional, the rid to match opening and closing tag, if given
     */
    public ?string $rid = null;

    /**
     * The short tag number used in the GUI
     * @var mixed
     */
    public mixed $tagNr = null;

    /**
     * The original raw (un-encoded) content contained in the tag
     * @var string
     */
    public string $originalContent;

    /**
     * the text to be rendered as full text content of the tag in the GUI, defaults mostly to the encoded version of originalcontent, but finally depends on the XLF dialect
     * @var string|null
     */
    public ?string $text = null;

    /**
     * the rendered tag to be used in the GUI and saved in the DB
     * @var string
     */
    public string $renderedTag;

    /**
     * The partner of a paired tag, null for single tags.
     * @var editor_Models_Import_FileParser_Tag|null
     */
    public ?editor_Models_Import_FileParser_Tag $partner = null;
    
    protected static string $mode = self::RETURN_MODE_INTERNAL;

    
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
     * @param int $type open,close,single see local constants
     * @param bool $xmlTags defines if the given tags as originalContent are XMLish (so starting and ending with < and >)
     */
    public function __construct(int $type = self::TYPE_SINGLE, bool $xmlTags = true) {
        $this->type = $type;
        $this->xmlTags = $xmlTags;
    }

    public function setSingle() {
        $this->type = self::TYPE_SINGLE;
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
        return match (self::$mode) {
            self::RETURN_MODE_REMOVED => '',
            self::RETURN_MODE_ORIGINAL => $this->getOriginalModeContent(),
            default => $this->renderedTag,
        };
    }

    /**
     * returns the content to be used for RETURN_ORIGINAL_MODE rendering
     * @return string
     */
    protected function getOriginalModeContent(): string {
        return $this->originalContent;
    }

    /**
     * renders this tag instance as internal tag
     */
    public function renderTag(int $length = -1, string $title = null, string $cls = null): string {
        //lazy loading of the image tag renderers
        if(is_string(self::$renderer[$this->type])) {
            self::$renderer[$this->type] = ZfExtended_Factory::get(self::$renderer[$this->type]);
        }

        return $this->renderedTag = self::$renderer[$this->type]->getHtmlTag([
            'class' => $this->parseSegmentGetStorageClass($this->originalContent, $this->xmlTags) . ($cls ?? ''),
            'text' => $this->text ?? htmlentities($this->originalContent, ENT_COMPAT), //PHP 8.1 fix - default changed!
            'shortTag' => $this->tagNr,
            'id' => $this->id, //mostly original tag id
            'length' => $length,
            'title' => $title,
        ]);
    }

    /**
     * helper for parseSegment: encode the tag content without leading and trailing <>
     * checks if $tag starts with < and ends with >
     *
     * @param string $tag contains the tag
     * @param boolean $xmlTags true if the tags are XMLish (so starting and ending with < and >)
     * @return string encoded tag content
     */
    private function parseSegmentGetStorageClass(string $tag, bool $xmlTags): string {
        if($xmlTags) {
            if(!str_starts_with($tag, '<') || !str_ends_with($tag, '>')){
                trigger_error('The Tag ' . $tag . ' has not the structure of a tag.', E_USER_ERROR);
            }
            //we store the tag content without leading < and trailing >
            //since we expect to cut of just two ascii characters no mb_ function is needed, the UTF8 content inbetween is untouched
            $tag = substr($tag, 1, -1);
        }
        return implode('', unpack('H*', $tag));
    }
}