
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
 * Shows a short overview of the quality status of the task
 */
Ext.define('Editor.view.quality.admin.TaskQualities', {
    extend:'Ext.panel.Panel',
    alias: 'widget.taskQualities',
    itemId:'taskQualities',
    extraParams: [], // Extra params property used for store proxy binding
    title: '#UT#Qualitätssicherung',
    //card type, used for card display order
    importType: 'postimport',
    store: null,
    // autoScroll: true,
    requires: [
        'Editor.store.quality.Task',
        'Editor.view.quality.admin.TaskQualitiesViewModel',
        'Editor.view.quality.admin.TaskQualitiesController'
    ],
    controller: 'taskQualities',
    viewModel: {
        type: 'taskQualities'
    },
    strings: {
        category: '#UT#Kategorie',
        total: '#UT#Anzahl',
        errors: '#UT#Fehler',
        falsePositives: '#UT#Falsch erkannte Fehler',
        status: '#UT#Status',
        completeTipCaption: '#UT#Alle Segmente wurden seit der letzten Änderung geprüft',
        incompleteTipCaption: '#UT#Segmente unvollständig geprüft',
        incompleteTipText: '#UT#Diese Kategorie wurde nicht oder nur unvollständig geprüft',
        startAnalysisHint: '#UT#Bitte stoßen Sie unten eine neue Prüfung an um das Problem zu beheben',
        faultyTipCaption: '#UT#Interne Tag Fehler',
        faultyTipText: '#UT#Es gibt Interne Tag Fehler die einen fehlerfreien Export der Aufgabe verhindern',
        newAnalysis: '#UT#Neu überprüfen',
    },
    publishes: {
        //publish this field so it is bindable
        extraParams: true
    },
    bind: {
        loading: '{isAnalysisRunning}',
        disabled: '{!enablePanel}'
    },
    /**
     * allow the store extra params to be configurable on grid level. This will enable flexible loads via binding
     * This function only expects and handles extraParams with valid taskGuid
     */
    setExtraParams: function(extraParams){
        this.store.getProxy().abort(); // abort running requests to avoid contextless requests
        if(extraParams && extraParams.taskGuid && extraParams.taskId){
            this.store.taskGuid = extraParams.taskGuid;
            this.store.taskId = extraParams.taskId;
            this.store.reload();
        } else {
            this.store.removeAll(false);
        }
    },
    /**
     * refreshes a loaded store (checks for matching taskGuid)
     */
    refreshStore: function(taskGuid){
        if(taskGuid && this.store.taskGuid && this.store.taskGuid == taskGuid){
            this.store.getProxy().abort();
            this.store.reload();
        }
    },
    initConfig: function(instanceConfig) {
        this.store = Ext.create('Editor.store.quality.Task');
        var me = this,
            config = {
                title: this.title,
                cls: 'taskQualities',
                items:[{
                    xtype: 'treepanel',
                    itemId: 'taskQualitiesTree',
                    store: me.store,
                    rootVisible: false,
                    useArrows: true,
                    reserveScrollbar: true,
                    viewConfig: {
                        getRowClass: function(record){
                            return (record.isFaulty() || record.hasFaultyChildren()) ? 'x-tree-faulty' : '';
                        }
                    },
                    columns: [{
                        xtype: 'treecolumn',
                        iconCls: 'x-tree-noicon',
                        text: me.strings.category,
                        dataIndex : 'text',
                        sortable: true,
                        flex: 4
                    },{
                        xtype: 'gridcolumn',
                        text: me.strings.total,
                        dataIndex : 'qcount',
                        sortable: true,
                        flex: 2
                    },{
                        xtype: 'gridcolumn',
                        text: me.strings.errors,
                        renderer: function (total, meta, record){
                            return total - record.get('qcountfp');
                        },
                        dataIndex : 'qcount',
                        sortable: true,
                        flex: 2
                    },{
                        xtype: 'gridcolumn',
                        text: me.strings.falsePositives,
                        dataIndex : 'qcountfp',
                        sortable: true,
                        flex: 3
                    },{
                        xtype: 'gridcolumn',
                        text: me.strings.status,
                        renderer: function (isComplete, meta, record){
                            // only rubrics will have an icon
                            if(!record.isRubric()){
                                // mark faulty category, no tooltip needed
                                if(record.isFaulty()){
                                    return '<span class="x-grid-symbol t5-quality-faulty">' + Ext.String.fromCodePoint(parseInt('0xf057', 16)) + '</span>';
                                }
                                return '';
                            }
                            var html = '';
                            // type is incompletely analysed
                            if(record.isIncomplete()){
                                html = '<span class="x-grid-symbol t5-quality-incomplete" data-qtip="'
                                    + '<b>' + me.strings.incompleteTipCaption +'</b><br/>'
                                    + me.strings.incompleteTipText + '. ' + me.strings.startAnalysisHint
                                    + '">'+ Ext.String.fromCodePoint(parseInt('0xf071', 16)) + '</span>';
                            } else {
                                html = '<span class="x-grid-symbol t5-quality-complete" data-qtip="'
                                    + '<b>' + me.strings.completeTipCaption +'</b>">'
                                    + Ext.String.fromCodePoint(parseInt('0xf00c', 16)) + '</span>';
                            }
                            // type blocks exporting of task
                            if(record.hasFaultyChildren()){
                                html += ' <span class="x-grid-symbol t5-quality-faulty" data-qtip="'
                                    + '<b>' + me.strings.faultyTipCaption +'</b><br/>' + me.strings.faultyTipText + '">'
                                    + Ext.String.fromCodePoint(parseInt('0xf057', 16)) + '</span>';
                            }
                            return html;
                        },
                        dataIndex : 'qcomplete',
                        sortable: false,
                        flex: 3
                    }]
                }],
                dockedItems: [{
                    xtype: 'toolbar',
                    dock: 'bottom',
                    ui: 'footer',
                    itemId: 'analysisToolbar',
                    items: [{
                        xtype: 'button',
                        text: me.strings.newAnalysis,
                        width: 150,
                        glyph: 'xf200@FontAwesome5FreeSolid',
                        listeners: {
                            click: function(btn){
                                me.onAnalysisButtonClick(btn);
                            }
                        }
                    }]
                }]
            };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([ config ]);
    },
    /**
     * TODO AUTOQA: add dialog to modify the QA configuration
     * Starts a re-analysis
     */
    onAnalysisButtonClick: function(btn){
    	if(!this.store || !this.store.taskGuid || !this.store.taskId){
    		return;
    	}
        var me = this,
        	taskGuid = this.store.taskGuid,
        	taskId = this.store.taskId,
        	projectTaskGrid = Ext.ComponentQuery.query('#projectTaskGrid').at(0),
	        setAnalysisRecordState = function(store, taskId){
	            var record = store ? store.getById(taskId) : null;
	            if(!record){
	                return;
	            }
	            record.set('state', 'opautoqa');
	        };
	    // before the analysis is started, set the task state to 'autoqa'
	    // the matchanalysis and languageresourcesassoc panel loading masks are binded 
	    // to the task status. Changing the status to autoqa will automaticly apply the loading masks for those panels
	    setAnalysisRecordState(Ext.StoreManager.get('admin.Tasks'), taskId);
	    if(projectTaskGrid){
	    	setAnalysisRecordState(projectTaskGrid.getStore(), taskId);
	    }
        Ext.Ajax.request({
            url: Editor.data.restpath+'task/'+taskId+'/autoqa/operation',
            method: "PUT",
            params: { "taskGuid": taskGuid, "taskId": taskId },
            scope: this,
            failure: function(response){
                Editor.app.getController('ServerException').handleException(response);
            }
        });
    }
});

