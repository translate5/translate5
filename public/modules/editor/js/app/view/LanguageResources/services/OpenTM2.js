
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

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
Ext.define('Editor.view.LanguageResources.services.OpenTM2', {
    requires: ['Editor.view.LanguageResources.services.Default'],
    extend: 'Editor.view.LanguageResources.services.Default',
    id: 'OpenTM2',
    
    addTooltip: '#UT#Weitere TM Daten in Form einer TMX Datei importieren und dem TM hinzufügen',
    download: '#UT#Dateibasiertes TM herunterladen und lokal speichern',
    
    /***
     * Add/import new resoucres button default tooltip. This is rendered in the language resources grid.
     */
    getAddTooltip:function(record){
    	if(record.get('status') == 'novalidlicense'){
    		return false;
    	}
        return this.addTooltip;
    },
    
    /***
     * Get import language reources action icon class
     */
    getImportIconClass:function(record){
    	if(record.get('status') == 'novalidlicense'){
    		return 'x-hidden-display';
    	}
    	return 'ico-tm-import';
    },
    
    /***
     * Get download language reources action icon class
     */
    getDownloadIconClass:function(record){
    	if(record.get('status') == 'novalidlicense'){
    		return 'x-hidden-display';
    	}
    	return 'ico-tm-download';
    },
    
    /***
     * Get download language reources action icon tooltip
     */
    getDownloadTooltip:function(record){
    	if(record.get('status') == 'novalidlicense'){
    		return false;
    	}
    	return this.download;
    }
});