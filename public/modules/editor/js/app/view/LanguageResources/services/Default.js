
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
 * @class Editor.plugins.pluginFeasibilityTest.view.EditorPanel
 * @extends Ext.panel.Panel
 *
 * Currently default for the following types:
 * 'editor_Services_OpenTM2',
 * 'editor_Services_Moses',
 * 'editor_Services_LucyLT',
 * 'editor_Services_SDLLanguageCloud',
 * 'editor_Services_Google',
 * 'editor_Plugins_GroupShare',
 * 'editor_Plugins_DeepL',
 */
Ext.define('Editor.view.LanguageResources.services.Default', {
    id: 'Default',

    exportTooltip:'#UT#Exportieren',
    log:'#UT#Log',
    
    /**
     * returns the row css class for the associated service in the tm overview panel
     * @param {Editor.model.LanguageResources.LanguageResource} rec
     * @return {Array}
     */
    getTmOverviewRowCls: function(record) {
        var result = [];
        if(record.get('filebased')) {
            result.push('language-ressource-import');
            result.push('language-ressource-export');
        }
        result.push('languageResource-status-'+record.get('status'));
        return result;
    },

    /**
     * Add/import new resoucres button default tooltip. This is rendered in the language resources grid.
     */
    getAddTooltip: function(){
        return false;
    },
    
    /**
     * Export button default tooltip
     */
    getExportTooltip: function(){
    	return this.exportTooltip;
    },
    
    /**
     * Get export language resources action icon class
     */
    getExportIconClass: function(){
    	return 'x-hidden-display';
    },
    
    /**
     * Get import language resources action icon class
     */
    getImportIconClass: function(){
    	return 'x-hidden-display';
    },

    /**
     * Get the import window for handleImportTm()
     */
    getImportWindow: function(){
        return 'importTmWindow';
    },
    
    /**
     * Get download language resources action icon class
     */
    getDownloadIconClass: function(){
    	return 'x-hidden-display';
    },
    
    /**
     * Get download language resources action icon tooltip
     */
    getDownloadTooltip: function(){
        return false;
    },
    
    /**
     * Get log language resources action icon class
     */
    getLogIconClass: function(record){
        if(record.get('eventsCount') > 0){
            return 'ico-tm-log';
        }
        return 'x-hidden-display';
    },
    
    /**
     * Get log language resources action icon tooltip
     */
    getLogTooltip: function(record){
        if(record.get('eventsCount') > 0){
            return this.log;
        }
        return false;
    },

    /**
     * Get edit specific data action icon class
     */
    getEditSpecificIconClass: function(){
        return 'x-hidden-display';
    },

    /**
     * Get edit specific data action icon tooltip
     */
    getEditSpecificTooltip: function(){
        return false;
    },

    /**
     * Retrieves the window to edit the specific data of a language resource.
     * Generally must be implemented in concrete implementations
     * @returns {string}
     */
    getEditSpecificWindow: function(){
        return '';
    },

    /**
     * If edit specific data action disabled
     */
    isEditSpecificDisabled: function(){
        return true;
    },
    
    /**
     * Get valid file-types for download.
     * @return array
     */
    getValidFiletypes: function(){
        return ['tm','tmx'];
    },

    /**
     * Must return the renderer as function to keep scope correct
     * @returns {function(*, *, *): *}
     */
    getNameRenderer: function() {
        return function(v, meta, rec) {
            return v;
        };
    }
});