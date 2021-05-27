
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
 * @class Editor.plugins.MatchAnalysis.controller.MatchAnalysis
 * @extends Ext.app.Controller
 */
Ext.define('Editor.plugins.MatchAnalysis.controller.MatchAnalysis', {
    extend: 'Ext.app.Controller',
    
    requires: [
        'Editor.plugins.MatchAnalysis.view.AnalysisPanel',
        'Editor.plugins.MatchAnalysis.view.LanguageResources'
    ],
    
    models: ['Editor.plugins.MatchAnalysis.model.MatchAnalysis'],
    stores:['Editor.plugins.MatchAnalysis.store.MatchAnalysis'],
    
    refs:[{
        ref: 'adminTaskPreferencesWindow',
        selector: 'adminTaskPreferencesWindow'
    },{
        ref: 'preferencesTabpanal',
        selector: 'adminTaskPreferencesWindow > tabpanel'
    },{
        ref:'matchAnalysisPanel',
        selector: 'matchAnalysisPanel'
    },{
        ref:'languageResourceTaskAssocPanel',
        selector:'languageResourceTaskAssocPanel'
    },{
        ref: 'taskAssocGrid',
        selector: '#languageResourcesTaskAssocGrid'
    },{
      ref:'projectPanel',
      selector:'#projectPanel'
    },{
        ref:'projectTaskGrid',
        selector:'#projectTaskGrid'
    }],
    TASK_STATE_ANALYSIS: 'matchanalysis',
    strings:{
        taskGridIconTooltip:'#UT#Match-Analyse',
        finishTask:'#UT#Beenden',
        analysis:'#UT#Analyse Starten',
        preTranslation:'#UT#Analyse &amp; Vorübersetzungen starten',
        preTranslationTooltip:'#UT#Die Vorübersetzung löst auch eine neue Analyse aus',
        startAnalysisMsg:'#UT#Match-Analyse und Vorübersetzungen werden ausgeführt.',
        finishAnalysisMsg:'#UT#Die Match-Analyse und die Vorübersetzung sind abgeschlossen.',
        internalFuzzy:'#UT#Zähle interne Fuzzy',
        pretranslateMatchRate:'#UT#Vorübersetzungs Match-Rate',
        pretranslateMatchRateTooltip:'#UT#Vorübersetzung mit TM-Match, die größer oder gleich dem ausgewählten Wert ist',
        pretranslateTmAndTerm:'#UT#Vorübersetzen (TM &amp; Terme)',
        pretranslateTmAndTermTooltip:'#UT#Treffer aus der Terminologie werden bevorzugt vorübersetzt.',
        pretranslateMt:'#UT#Vorübersetzen (MT)',
        pretranslateMtTooltip:'#UT#Treffer aus dem TM werden bevorzugt vorübersetzt',
        analysisLoadingMsg:'#UT#Analyse läuft'
    },
    
    listeners:{
        beforeStartAnalysis:'onBeforeStartAnalysis'  
    },
    listen:{
        component:{
            '#adminTaskPreferencesWindow tabpanel':{
                render:'onTaskPreferencesWindowPanelRender'
            },
            '#adminTaskAddWindow': {
                beforerender:'onAdminTaskWindowBeforeRender'
            },
            '#languageResourceTaskAssocPanel':{
                render:'onLanguageResourcesPanelRender'
            },
            '#languageResourcesWizardPanel':{
                startMatchAnalysis:'onStartMatchAnalysis'
            },
            'taskActionMenu': {
                itemsinitialized: 'onTaskActionColumnItemsInitialized'
            }
        },
        controller:{
            '#admin.TaskOverview':{
                taskCreated:'onTaskCreated'
            },
            '#LanguageResourcesTaskassoc':{
                taskAssocSavingFinished:'onTaskAssocSavingFinished'
            }
        },
        store:{
            '#languageResourcesTaskAssoc':{
                load:'onLanguageResourcesTaskAssocStoreLoad'
            }
        }
    },

    /***
     * Before analysis event handler
     */
    onBeforeStartAnalysis:function(taskId,operation){
        var me = this,
            setAnalysisRecordState=function(store,taskId){
                var record = store ? store.getById(taskId) : null;
                if(!record){
                    return;
                }
                record.set('state','matchanalysis');
            };
        //before the analysis is started, set the task state to 'matchanalysis'
        //the matchanalysis and languageresourcesassoc panel loading masks are binded 
        //to the task status. Changing the status to analysis will automaticly apply the loading masks for those panels
        setAnalysisRecordState(Ext.StoreManager.get('admin.Tasks'),taskId);
        setAnalysisRecordState(me.getProjectTaskGrid().getStore(),taskId);
    },
    
    /**
     * Task action column items initialized event handler.
     */
    onTaskActionColumnItemsInitialized: function(items) {
        var me=this;
        items.push({
            text:this.strings.taskGridIconTooltip,
            glyph: 'f200@FontAwesome5FreeSolid',
            action: 'editorAnalysisTask',
            hidden:true,
            bind:{
                hidden:'{!isNotErrorImportPendingCustom}'
            },
            scope:me,
            handler:me.onMatchAnalysisMenuClick,
            sortIndex:8
        });
    },
    /**
     * Inserts the language resource card into the task import wizard
     */
    onAdminTaskWindowBeforeRender:function(window,eOpts){
        var me=this;
        window.insertCard({
            xtype:'languageResourcesWizardPanel',
            //index where the card should appear in the group
            groupIndex:1,
            listeners:{
                activate:{
                    fn:me.onLanguageResourcesWizardPanelActivate,
                    scope:me
                }
            }
        },'postimport');
    },

    /***
     * On task preferences window tabpanel render
     */
    onTaskPreferencesWindowPanelRender:function(panel){
        //add the matchanalysis panel in the tabpanel
        panel.insert(2,{
           xtype:'matchAnalysisPanel'
        });
    },

    onLanguageResourcesPanelRender:function(panel){
        var me=this,
            storeData=[];
        
        //init the pretranslate matchrate options (from 0-103)
        for(var i=0;i<=103;i++){
            storeData.push({
                id:i,
                value:i+"%"
            });
        }
        
        panel.addDocked([{
            xtype : 'toolbar',
            dock : 'bottom',
            ui: 'footer',
            layout: {
                type: 'hbox',
                pack: 'start'
            },
            items: [{
                xtype:'button',
                text:this.strings.analysis,
                itemId:'btnAnalysis',
                width:150,
                dock:'bottom',
                glyph: 'f200@FontAwesome5FreeSolid',
                bind:{
                    disabled:'{!enableDockedToolbar || !hasLanguageResourcesAssoc}',
                    hidden:'{isAnalysisButtonHidden}'
                },
                listeners:{
                    click:{
                        fn:this.matchAnalysisButtonHandler,
                        scope:this
                    }
                }
            }]
        },{
            xtype : 'toolbar',
            dock : 'bottom',
            ui: 'footer',
            bind:{
                disabled:'{!enableDockedToolbar || !hasLanguageResourcesAssoc}'
            },
            layout: {
                type: 'vbox',
                align: 'left'
            },
            items : [{
                xtype:'checkbox',
                value: 1,
                boxLabel:this.strings.internalFuzzy,
                itemId:'cbInternalFuzzy',
                dock:'bottom'
            },{
                dock:'bottom',
                xtype:'container',
                layout: {
                    type: 'hbox',
                    align: 'left'
                },
                items:[
                    {
                        xtype:'checkbox',
                        bind:{
                            disabled:'{!hasTmOrCollection}'
                        },
                        value: 1,
                        cls:'lableInfoIcon',
                        boxLabel:me.strings.pretranslateTmAndTerm,
                        autoEl: {
                            tag: 'div',
                            'data-qtip': me.strings.pretranslateTmAndTermTooltip
                        },
                        itemId:'pretranslateTmAndTerm',
                        padding: '0 20 0 0'
                    },{
                        xtype:'combobox',
                        stretch: false,
                        align: 'left',
                        itemId:'cbMinMatchrate',
                        fieldLabel: me.strings.pretranslateMatchRate,
                        tooltip:me.strings.pretranslateMatchRateTooltip,
                        store: Ext.create('Ext.data.Store', {
                            fields: ['id', 'value'],
                            data : storeData
                        }),
                        value:100,
                        displayField: 'value',
                        valueField: 'id',
                        queryMode: 'local',
                    }
                ]
            },{
                xtype:'checkbox',
                bind:{
                    disabled:'{!hasMt}'
                },
                value: 1,
                cls:'lableInfoIcon',
                boxLabel:me.strings.pretranslateMt,
                autoEl: {
                    tag: 'div',
                    'data-qtip': me.strings.pretranslateMtTooltip
                },
                itemId:'pretranslateMt',
            }]
        }]);
    },
    
    /***
     * Event handler after a task was successfully created
     */
    onTaskCreated:function(task){
        this.loadTaskAssoc(task);
    },
    
    onLanguageResourcesWizardPanelActivate:function(panel){
        if(!panel.task){
            return;
        }
        var me=this;
        me.loadTaskAssoc(panel.task);
    },
    
    onMatchAnalysisMenuClick:function(item){
        var me=this,
            task=item.lookupViewModel(true).get('task');
        me.getProjectPanel().getController().redirectFocus(task,true);
        me.getAdminTaskPreferencesWindow().down('tabpanel').setActiveTab('matchAnalysisPanel');
    },
    
    /***
     * Load match resources task assoc store
     */
    loadTaskAssoc:function(task){
        var taskAssoc=Editor.app.getController('Editor.controller.LanguageResourcesTaskassoc');
        //load the task assoc store
        taskAssoc.handleLoadPreferences(taskAssoc,task);
        
    },
    
    /***
     * On language resource task assoc store load event handler
     */
    onLanguageResourcesTaskAssocStoreLoad:function(store){
        this.updateTaskAssocPanelViewModel(store);
    },

    onStartMatchAnalysis:function(taskId,operation){
        this.startAnalysis(taskId,operation);
    },
    
    matchAnalysisButtonHandler:function(button){
        var me=this,
            win=button.up('#adminTaskPreferencesWindow'),
            tmAndTermChecked=me.isCheckboxChecked('pretranslateTmAndTerm'),
            mTChecked=me.isCheckboxChecked('pretranslateMt'),
            task=win.getCurrentTask(),
            operation=(mTChecked || tmAndTermChecked) ? "pretranslation" : "analysis";
        
        me.startAnalysis(task.get('id'),operation);
    },
    
    /***
     * Start the match analysis or pretranslation for the taskId.
     * Operation can contains: 
     *    'analysis' -> runs only match analysis
     *    'pretranslation' -> runs match analysis with pretranslation
     */
    startAnalysis:function(taskId,operation){
        //'editor/:entity/:id/operation/:operation',
        var me = this;
        
        me.fireEvent('beforeStartAnalysis',taskId,operation);

        Ext.Ajax.request({
            url: Editor.data.restpath+'task/'+taskId+'/'+operation+'/operation',
            method: "PUT",
            params: {
                internalFuzzy: me.isCheckboxChecked('cbInternalFuzzy'),
                pretranslateMatchrate: me.getComponentByItemId('cbMinMatchrate').getValue(),
                pretranslateTmAndTerm: me.isCheckboxChecked('pretranslateTmAndTerm'),
                pretranslateMt: me.isCheckboxChecked('pretranslateMt'),
                isTaskImport:me.getComponentByItemId('adminTaskAddWindow') ? 1 : 0,
                batchQuery:me.isCheckboxChecked('batchQuery')
            },
            scope: this,
            failure: function(response){
                Editor.app.getController('ServerException').handleException(response);
            }
        })
    },
    
    /***
     * Language resource to task assoc after save event handler
     */
    onTaskAssocSavingFinished:function(record,store){
        this.updateTaskAssocPanelViewModel(store);
    },

    /***
     * Update the language resources task assoc panel view model
     */
    updateTaskAssocPanelViewModel:function(assocStore){
        var me=this,
            panels=Ext.ComponentQuery.query('languageResourceTaskAssocPanel'),
            store=assocStore ? assocStore : (me.getTaskAssocGrid() ? me.getTaskAssocGrid() : null);
        if(!panels || panels.length<1 || !store){
            return;
        }
        for(var i=0;i<panels.length;i++){
            var pnl=panels[i],
                vm=pnl.getViewModel();
            //set the view model items variable
            vm && vm.set('items',(store.getData().getSource() || store.getData()).getRange());
        }
    },
    
    /***
     * Get component by itemId
     */
    getComponentByItemId:function(itemId){
        var cmp=Ext.ComponentQuery.query('#'+itemId);
        if(cmp.length<1){
            return null;
        }
        return cmp[0];
    },

    /***
     * Check if the checbox component is checked
     */
    isCheckboxChecked:function(itemId){
        var component=this.getComponentByItemId(itemId);
        if(!component || component.isDisabled()){
            return 0;
        }
        return component.checked ? 1 : 0;
    }

});