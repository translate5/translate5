
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

/**
 * Encapsulates all logic for global quality management
 */
Ext.define('Editor.controller.Quality', {
    extend : 'Ext.app.Controller',
    views: ['quality.statistics.Window'],
    requires: ['Editor.store.quality.Statistics'], // Statistics Store in "requires" instead "stores" to prevent automatic instantiation
    models: ['Editor.model.quality.Filter'],
    stores: ['Editor.store.quality.Filter'],
    refs:[{
        ref : 'statisticsWindow',
        selector : '#qualityStatisticsWindow',
        autoCreate: true,
        xtype: 'qualityStatisticsWindow'
    }],
    listen: {
        component: {
            '#segmentgrid #qualityStatisticsBtn': {
                click:'showStatistics'
            }
        },
        controller: {
            '#Editor.$application': {
                editorViewportClosed: 'onEditorViewportClosed'
            }
        }
    },
    /**
     * displays the Statistics Window
     */
    showStatistics: function() {
        this.getStatisticsWindow().show();
    },
    /**
     * unloads the Quality Filter Store. This can't be done in the view because the view get's destroyed before the editorViewportClosed event is thrown
     */
    onEditorViewportClosed: function(){
        var store = Ext.getStore('FilterQualities');
        store.getRootNode().removeAll(false);
    }
});
