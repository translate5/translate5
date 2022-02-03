
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
/**
 * @class Editor.view.LanguageResources.services.TermCollection
 * @extends Editor.view.LanguageResources.services.Default
 */
Ext.define('Editor.view.LanguageResources.services.TermCollection', {
    requires: ['Editor.view.LanguageResources.services.Default'],
    extend: 'Editor.view.LanguageResources.services.Default',
    id: 'TermCollection',

    addTooltip: '#UT#Weitere Term-Collection Daten in Form einer TBX Datei importieren und dem Term-Collection hinzufügen',
    openTermPortal: '#UT#Term-Collection im TermPortal öffnen',

    /**
     * returns the row css class for the associated service in the tm overview panel
     * @param {Editor.model.LanguageResources.LanguageResource} rec
     * @return {Array}
     */
    getTmOverviewRowCls: function(record) {
        var result = [];
        result.push('language-ressource-import');
        result.push('languageResource-status-'+record.get('status'));
        return result;
    },

    /***
     * Term collection tooltip for add/import term collection button. This is rendered in the language resources grid.
     */
    getAddTooltip:function(record){
    	if(record.get('status') == 'novalidlicense'){
    		return false;
    	}
        return this.addTooltip;
    },
    
    /***
     * Get export language reources action icon class
     */
    getExportIconClass:function(){
    	return 'ico-tm-export';
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
     * Get the import window for handleImportTm()
     */
    getImportWindow:function(){
        return 'importCollectionWindow';
    },

    /**
     * @returns {(function(*, *, *): (string|*))|*}
     */
    getNameRenderer: function() {
        var ttip = this.openTermPortal;
        return function(v, meta, rec) {
            var ctrl = Editor.app.getController('Termportal'),
                //since we are in a termcollection (so term specific) we do that call not via event, but directly on termportal controller:
                url = ctrl && ctrl.urlToTermPortal(rec);
            if(url) {
                meta.tdAttr = 'data-qtip="'+ttip+'"';
                return '<a href="'+url+'" target="termportalandinstanttranslate">' + v + '</a>';
            }
            return v;
        }
    }
});