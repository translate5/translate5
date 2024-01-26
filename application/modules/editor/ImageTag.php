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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */

/**
 * base class for the creation of internal tags
 */
abstract class editor_ImageTag {
    protected string $htmlTagTpl = '<div class="{type} {class} internal-tag ownttip"><span title="{title}" class="short">{shortTag}</span><span data-originalid="{id}" data-length="{length}" class="full">{text}</span></div>';
    
    /**
     * returns the Html Tag used in the editor for this tag type.
     * The Tag template can contain "{varnames}" in curly braces (like in ExtJS)
     * These {varnames} are replaced by the content of the given assoc array.
     * @param array $parameters an assoc array; keys => varnames WITHOUT curly braces, value => value to replace the varname
     * @return string
     */
    public function getHtmlTag(array $parameters) {
        // CRUCIAL: Newlines in internal tags will break frontend-functionality. Therefore we turn all newlines in the content & title to "↵"
        // the export/back-conversion will be done with the base64-encoded data thus this replacing otherwise will do no harm
        if(array_key_exists('text', $parameters)){
            $parameters['text'] = editor_Utils::visualizeNewlines($parameters['text']);
        } else {
            $parameters['text'] = '';
        }
        if(array_key_exists('title', $parameters) && $parameters['title'] !== null){
            $parameters['title'] = editor_Utils::visualizeNewlines($parameters['title']);
        } else {
            $parameters['title'] = htmlspecialchars($parameters['text'], ENT_COMPAT, null, false);
        }
        if(!array_key_exists('length', $parameters)) {
            $parameters['length'] = -1;
        }
        $keys = array_map(function($k){
            return '{'.$k.'}';
        }, array_keys($parameters));
        return str_replace($keys, $parameters, $this->htmlTagTpl);
    }
}