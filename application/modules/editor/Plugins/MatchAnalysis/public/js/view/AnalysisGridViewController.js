/*
 START LICENSE AND COPYRIGHT

  This file is part of translate5

  Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

Ext.define('Editor.plugins.MatchAnalysis.view.AnalysisGridViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.matchAnalysisGrid',

    listen: {
        store: {
            '#Editor.plugins.MatchAnalysis.store.MatchAnalysis': {
                load : 'onAnalysisRecordLoad',
                metachange: 'onMetaChange',
                beforeload: 'onAnalysisBeforeLoad'
            }
        }
    },

    init : function() {
        var me=this;
        Ext.on({
            projectTaskSelectionChange:'onProjectTaskSelectionChange',
            scope:me
        });
    },

    /***
     * Event handler for project task change event.
     * On each task change, analysis store will be reloaded for the new task
     * @param newTask
     */
    onProjectTaskSelectionChange: function (newTask){
        if(!newTask){
            return;
        }
        var view = this.getView();
        view && view.setTask(newTask);
    },

    /***
     * Export analysis by given type
     * @param type
     */
    exportAction:function (type){
        var me= this,
            params = {},
            task = me.getView().task;

        params.taskGuid = task.get('taskGuid');
        params.type = type.itemId;
        params.unitType = me.getView().down('#unitType').getValue();
        window.open(Editor.data.restpath+'plugins_matchanalysis_matchanalysis/export?'+Ext.urlEncode(params));
    },

    /***
     * On match analysis record is loaded in the store
     */
    onAnalysisRecordLoad:function(store) {
        var me=this,
        	view=me.getView(),
            vm = view.getViewModel(),
            task = view.task,
        	record=store.getAt(0),
            hasData = !!record;

        vm.set('hasAnalysisData', hasData);

        view.down('#infoPanel').update({
            hasAnalysisData: hasData,
            created: record && record.get('created'),
            internalFuzzy: record && record.get('internalFuzzy'),
            editFullMatch: task && task.get('edit100PercentMatch'),
            strings: view.strings
        });
    },

    /***
     * Before analysis store is loaded, add additional parameters to the proxy
     * @param store
     */
    onAnalysisBeforeLoad:function( store){
        var me = this,
            view = me.getView(),
            proxy=store.getProxy(),
            unitTypeField = view && view.down('#unitType'),
            unitTypeValue = Editor.data.plugins.MatchAnalysis.calculateBasedOn;

        try {
            // in some cases, when the grid is clicked to fast, the field dom element is not set (it can be because of
            // extjs cache) and getting the value using getValue method produces js error
            if(unitTypeField){
                unitTypeValue = unitTypeField.getValue();
            }
        }catch (e){
            if( !Ext.isEmpty(unitTypeField.value)){
                unitTypeValue = unitTypeField.value;
            }
        }
        var merged = Ext.merge({}, proxy.getExtraParams(), {
                unitType:unitTypeValue
            });
        proxy.setExtraParams(merged);
    },

    /**
     * reconfigure the grid to use the configured match rate fields
     * @param {Editor.plugins.MatchAnalysis.store.MatchAnalysis} store
     * @param {Object} meta
     */
    onMetaChange: function(store, meta) {
        var view = this.getView();

        view && view.reconfigure(store, view.getColumnConfig(meta.fields));
    },

    /***
     * Unit type combo box select - event handler
     */
    onUnitTypeSelect: function (){
        var me = this,
            view = me.getView(),
            store = view && view.getStore();

        store && store.load();
    }
});