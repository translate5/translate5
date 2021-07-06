<?php
/*
 START LICENSE AND COPYRIGHT
 
 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 
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

/***
 * Provides tag protection with customizable tag template and regex tag selector
 *
 * @author aleksandar
 *
 */
abstract class editor_Models_Segment_TagAbstract {
    
    /**
     * container for the original tags of the internal tag protection
     * @var array
     */
    protected $originalTags;
    
    /***
     * The default tepmplate for placeholder replacer
     *
     * @var string
     */
    protected $placeholderTemplate;
    
    /***
     * The replacer regex used in the replace function
     * @var string
     */
    protected $replacerRegex;
    
    
    /**
     * protects the tags of one segment
     * @param string $segment
     * @return string
     */
    public function protect(string $segment) {
        $id = 1;
        $this->originalTags = array();
        return $this->replace($segment, function($match) use (&$id) {
            $placeholder = $this->getPlaceholderTemplate($id++);
            $this->originalTags[$placeholder] = $match[0];
            return $placeholder;
        });
    }
    
    /**
     * unprotects / restores the content tags
     * @param string $segment
     * @return string
     */
    public function unprotect(string $segment) {
        return str_replace(array_keys($this->originalTags), array_values($this->originalTags), $segment);
    }
    
    /**
     * replaces tags with either the callback or the given scalar
     * see preg_replace
     * see preg_replace_callback
     * @param string $segment
     * @param Closure|string $replacer
     * @param int $limit optional
     * @param int $count optional, returns the replace count
     * @return mixed
     */
    public function replace($segment, $replacer, $limit = -1, &$count = null) {
        if(!is_string($replacer) && is_callable($replacer)) {
            return preg_replace_callback($this->getReplacerRegex(), $replacer, $segment, $limit, $count);
        }
        return preg_replace($this->getReplacerRegex(), $replacer, $segment, $limit, $count);
    }
    
    /**
     * returns true if given segment content contains text , false if tags only or is empty.
     * White-spaces in tag only segments are not treated as text
     * @param string $segmentContent
     * @return bool
     */
    public function hasText(string $segmentContent): bool {
        $segmentContent = $this->replace($segmentContent, '');
        $segmentContent = trim(strip_tags($segmentContent));
        return !(empty($segmentContent)&&$segmentContent!=="0");
    }
    
    /***
     * Return the placeholder template with id
     *
     * @param int $id
     * @return string
     */
    protected function getPlaceholderTemplate($id){
        //if is not set, set the default placeholder template
        if(!$this->placeholderTemplate){
            throw new Zend_Exception("Placeholder template is not set");
        }
        return sprintf($this->placeholderTemplate, $id);
    }
    
    /***
     * Get the replace regex used in the replace function
     * @return string
     */
    protected function getReplacerRegex(){
        //if it is not set, set the default regex
        if(!$this->replacerRegex){
            throw new Zend_Exception("Replacer regex is not set.");
        }
        return $this->replacerRegex;
    }
    
    /***
     * Return the original tags
     *
     * @return array
     */
    public function getOriginalTags(){
        return $this->originalTags;
    }
    /**
     * Retrieves, if there were original tags, that had to be protected
     * @return boolean
     */
    public function hasOriginalTags() : bool {
        return (count($this->originalTags) > 0);
    }
    /***
     * Update the protected tag value by given key
     * @param mixed $key
     * @param mixed $value
     * @return boolean
     */
    public function updateOriginalTagValue($key,$value){
        if(isset($this->originalTags[$key])){
            $this->originalTags[$key] = $value;
            return true;
        }
        return false;
    }
}
