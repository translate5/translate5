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

/* * #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 */

/**
 * Just an empty class since default XLF export can be used for Zend XLF
 */
class editor_Models_Export_FileParser_XlfZend extends editor_Models_Export_FileParser_Xlf {

    /**
     * restores the original HTML tags from the ph and bpt ept tags
     * {@inheritDoc}
     * @see editor_Models_Export_FileParser_Xlf::getSegmentContent()
     */
    protected function getSegmentContent($segmentId, $field) {
        $content = parent::getSegmentContent($segmentId, $field);
        $parser = ZfExtended_Factory::get('editor_Models_Import_FileParser_XmlParser');
        /* @var $parser editor_Models_Import_FileParser_XmlParser */
        $parser->registerElement('bpt,ept,ph', null, function($tag, $key, $opener) use ($parser){
            $textContent = $parser->getRange($opener['openerKey'] + 1, $key - 1, true);
            $textContent = htmlspecialchars_decode($textContent);
            $parser->replaceChunk($opener['openerKey'], $textContent);
            $parser->replaceChunk($opener['openerKey'] + 1, '', $key - $opener['openerKey']);
        });
        return $parser->parse($content);
    }
    
}
