
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
 * Encapsulates all logic for global quality management
 * Currently that is only the opening of the statistics window and the persistance of the filter mode of the qualities filter panel
 */
Ext.define('Editor.controller.Quality', {
    extend : 'Ext.app.Controller',
    models: ['Editor.model.quality.Filter'],
    stores: ['Editor.store.quality.Filter'],
    filterPanelMode: 'all', // the initial filter mode of the filter panel. Can be 'all' | 'error' | 'falsepositive'
    listen: {
        component: {
            '#qualityFilterPanel #modeSelector': {
                change:'onFilterModeChanged'
            }
        },
        controller: {
            '#Editor.$application': {
                editorConfigLoaded:'onEditorConfigLoaded'
            }
        },
    },
    /**
     * After task config load event handler.
     */
    onEditorConfigLoaded:function(app){
        app.getController('Editor.controller.QualityMqm').setActive(
            app.getTaskConfig('autoQA.enableMqmTags')
        );
    },
    /**
     * Changes the globally managed filter mode to ensure persistence
     */
    onFilterModeChanged: function(comp, newVal, oldVal) {
        this.filterPanelMode = newVal;
    },
    /**
     * Accessor for the filter mode
     */
    getFilterMode: function(){
        return this.filterPanelMode;
    }
});
