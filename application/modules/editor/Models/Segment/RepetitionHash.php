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
 */
class editor_Models_Segment_RepetitionHash {
    /**
     * @var editor_Models_Segment_UtilityBroker
     */
    protected $util;
    
    /**
     * @var boolean
     */
    protected $isSourceEditing;

    protected ?editor_Models_Segment $segment = null;
    protected ?editor_Models_Import_FileParser_SegmentAttributes $attributes = null;

    /**
     * A list of meta fields where the values should be added to the repetition calculation of each segment
     * the used fields must exist as segment meta and as SegmentAttributes itself or as SegmentAttribute custom attribute.
     * @var array
     */
    protected $metaFieldsToAdd = [];

    public function __construct(editor_Models_Task $task) {
        $this->isSourceEditing = (bool) $task->getEnableSourceEditing();
        $this->util = ZfExtended_Factory::get('editor_Models_Segment_UtilityBroker');
        $this->metaFieldsToAdd = $task->getConfig()->runtimeOptions->alike->segmentMetaFields->toArray() ?? [];
    }

    public function setSegmentAttributes(editor_Models_Import_FileParser_SegmentAttributes $attributes) {
        $this->attributes = $attributes;
    }

    /**
     * rehashes the target of the given segment (assuming the segments targetEdit is already up-to-date) alternatively a new target can be given as string
     * @param editor_Models_Segment $segment
     * @param string|null $newTarget
     * @return string
     */
    public function rehashTarget(editor_Models_Segment $segment, ?string $newTarget = null): string {
        $this->segment = $segment;
        return $this->hashTarget($newTarget ?? $segment->getTargetEdit(), $segment->getSource());
    }

    /**
     * rehashes the relais of the given segment (assuming the segments relais is already up-to-date) alternatively a new relais value can be given as string
     * @param editor_Models_Segment $segment
     * @param string|null $newValue
     * @return string
     */
    public function rehashRelais(editor_Models_Segment $segment, ?string $newValue = null): string {
        $this->segment = $segment;
        return $this->hashRelais($newValue ?? $segment->getRelais());
    }


    /**
     * Creates the hash out of the target string, adds source tag count if source editing is enabled
     * @param string $target
     * @param string $source
     * @return string
     */
    public function hashTarget($target, $source) {
        //since target may contain also track changes, we have to sanitize them first
        $target = $this->util->trackChangeTag->removeTrackChanges($target);
        return $this->generateHash($target, $this->getCountSourceEditing($source, $target));
    }
    
    /**
     * Creates the hash out of the source string, adds target tag count in the tag calculation
     * @param string $source
     * @param string $target
     * @return string
     */
    public function hashSource($source, $target) {
        //only the real tag count goes into the hash (whitespace tags are ignored, since they are editable)
        $stat = $this->util->internalTag->statistic($target);
        return $this->generateHash($source, $stat['tag']);
    }
    
    /**
     * Creates the hash for a relais column
     * @param string $relais
     * @return string
     */
    public function hashRelais($relais) {
        return $this->generateHash($relais, '');
    }
    
    /**
     * Creates the hash for a alternative targets
     * @param string $alternative
     * @return string
     */
    public function hashAlternateTarget($alternative) {
        return $this->generateHash($alternative, '');
    }

    /**
     * Creates the segment value hash for the repetition editor
     *
     * @param string $value
     * @param string $additionalValue
     * @return string
     * @throws Zend_Exception
     */
    protected function generateHash(string $value, string $additionalValue): string {
        if(is_null($this->attributes) && is_null($this->segment)) {
            // crucial: both fields are needed in some class overwrites in private plugins, but not in the general code.
            // On import attributes must be set before calling the hash functions
            // On rehashTarget the needed information is given via the segment itself
            throw new LogicException('No segment or segment attributes are given!');
        }

        $value = $this->util->internalTag->replace($value, function($match) {
            //whitespace tags and real tags can not replaced by each other, so they must be different in the hash,
            // so: "Das<x>Haus" may not be a repetition anymore of "DasTABHaus", even TAB and <x> are both replaced with an internal tag
            if(in_array($match[3], editor_Models_Segment_Whitespace::WHITESPACE_TAGS)) {
                return '<internal-ws-tag>';
            }
            return '<internal-tag>';
        });
        
        if(!empty($additionalValue)) {
            $value .= '#'.$additionalValue;
        }

        //we may only add custom fields to the hash calculation if the value is not empty.
        // Reason: empty content is per definition not repeatable, and we check that via a constant containing the md5 hash of the empty string
        // if we now add additional stuff here, the empty string segment gets repeatable since the hash does not match anymore the empty string hash
        // therefore we may not add additional stuff if the value was previously empty
        if($value != '' && !empty($this->metaFieldsToAdd)) {
            // loop through the configured additional data and add them the hash calculation
            foreach($this->metaFieldsToAdd as $field) {
                if(empty($this->segment)) {
                    //TODO check customAttributes too if no native found!
                    $toAdd = $this->attributes->$field;
                }
                else {
                    $toAdd = $this->segment->meta()->__call('get'.ucfirst($field), []);
                }
                $value .= '#'.$toAdd;
            }
        }
        return md5($value);
    }
    
    /**
     * returns the tag count in the given source segment data
     * returns an empty string when source editing is false
     * @param string $segmentValue
     * @param string $targetValue
     * @return mixed
     */
    protected function getCountSourceEditing($segmentValue, $targetValue) {
        //if target is empty, the source count must be ignored, to generate equal hashes for empty targets
        // otherwise the translation task recognition is not working properly
        if(!$this->isSourceEditing || empty($targetValue)) {
            return '';
        }
        //only the real tag count goes into the hash (whitespace tags are ignored, since they are editable)
        $stat = $this->util->internalTag->statistic($segmentValue);
        return $stat['tag'];
    }
}