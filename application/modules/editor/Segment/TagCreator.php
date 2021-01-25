<?php
/*
 START LICENSE AND COPYRIGHT
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
 
 This file is part of a plug-in for translate5.
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 For the license of this plug-in, please see below.
 
 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html
 
 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5.
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
 http://www.gnu.org/licenses/gpl.html
 http://www.translate5.net/plugin-exception.txt
 
 END LICENSE AND COPYRIGHT
 */

/**
 * This API creates the types of Internal tags either fromn a DOMElement or JSON serialization
 * WORK IN PROGRESS
 */
class editor_Segment_TagCreator {
    
    /**
     * @var editor_Segment_TagCreator
     */
    private static $_instance = null;
    
    /**
     * 
     * @return editor_Segment_TagCreator
     */
    public static function instance(){
        if(self::$_instance == null){
            self::$_instance = new editor_Segment_TagCreator();
        }
        return self::$_instance;
    }
        
    private function __construct(){
        
    }
    /**
     * Tries to evaluate an Internal tag out of given JSON Data
     * To make this happen all available Internal Tag Identifiers must be registered with this class
     * The default is a 'editor_Segment_AnyInternalTag' representing an uncategorized internal tag
     * NOTE: This API does not care about the children contained in the tag nor the text-length
     * @param stdClass $data
     * @throws Exception
     * @return editor_Segment_InternalTag
     */
    public function fromJsonData(stdClass $data){
        try {
            $tag = $this->evaluate($data->type, $data->name, $data->classes, $data->attribs, $data->startIndex, $data->endIndex);
            $tag->jsonUnserialize($data);
            return $tag;
        } catch (Exception $e) {
            throw new Exception('Could not deserialize editor_Segment_InternalTag from JSON Data '.json_encode($data));
        }        
    }
    /**
     * Tries to evaluate an Internal tag out of a given DOM Element
     * To make this happen all availaable Internal Tag Identifiers must be registered with this class
     * The default is a 'editor_Segment_AnyInternalTag' representing an uncategorized internal tag
     * NOTE: This API does not care about the children contained in the tag nor the text-length
     * @param DOMElement $element
     * @param int $startIndex
     * @param int $endIndex
     * @return editor_Segment_InternalTag
     */
    public function fromDomElement(DOMElement $element, int $startIndex=0, int $endIndex=0){
        $classNames = [];
        $attributes = [];
        if($element->hasAttributes()){
            foreach ($element->attributes as $attr) {
                if($attr->nodeName == 'class'){
                    $classNames = explode(' ', trim($attr->nodeValue));
                } else {
                    $attributes[$attr->nodeName] = $attr->nodeValue;
                }
            }
        }
        $tag = $this->evaluate('', $element->nodeName, $classNames, $attributes, $startIndex, $endIndex);
        if(count($classNames) > 0){
            foreach($classNames as $cname){
                $tag->addClass($cname);
            }
        }
        if(count($attributes) > 0){
            foreach($attributes as $name => $val){
                $tag->addAttribute($name, $val);
            }
        }
        return $tag;
    }
    /**
     * The central API to identify the needed Tag class by classnames and attributes
     * @param string $type
     * @param string $nodeName
     * @param string[] $classNames
     * @param string[] $attributes
     * @param int $startIndex
     * @param int $endIndex
     * @return editor_Segment_InternalTag
     */
    private function evaluate(string $type, string $nodeName, array $classNames, array $attributes, int $startIndex, int $endIndex){
        // try to let the quality manager find a tag
        $tag = editor_Segment_Quality_Manager::instance()->evaluateInternalTag($type, $nodeName, $classNames, $attributes, $startIndex, $endIndex);
        if($tag != null){
            return $tag;
        }
        // the default is the "any" tag
        return new editor_Segment_AnyInternalTag($startIndex, $endIndex, '', $nodeName);
    }
}
