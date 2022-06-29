
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
 * @class Editor.view.LanguageResources.pivot.Assoc
 * @extends Ext.panel.Panel
 */
Ext.define('Editor.view.LanguageResources.pivot.Assoc', {
    extend : 'Ext.grid.Panel',
    alias : 'widget.languageResourcePivotAssoc',
    itemId:'languageResourcePivotAssoc',
    controller: 'languageResourcePivotAssoc',
    viewModel: {
        type: 'languageResourcePivotAssoc'
    },
    requires:[
        'Editor.view.LanguageResources.pivot.AssocViewModel',
        'Editor.view.LanguageResources.pivot.AssocViewController',
        'Editor.model.LanguageResources.pivot.Assoc'
    ],
    strings: {
        empty: '#UT#Keine Sprachressource in der Sprachkombination des geöffneten Tasks verfügbar.',
        checked: '#UT#Ressource in Aufgabe verwenden',
        name: '#UT#Name',
        source: '#UT#Quellsprache',
        relaisLang: '#UT#Relaissprache',
        serviceName: '#UT#Ressource',
        taskGuid:'#UT#Aufgabename',
        workerQueued:'#UT#Pivot-Vorübersetzung läuft',
        startPretranslationButtonText:'#UT#Vorübersetzung',
        assocSave: '#UT#Eintrag gespeichert!',
        assocDeleted: '#UT#Eintrag gelöscht!'
    },
    padding: 0,
    layout:'fit',
    store:[],

    task: null,

    bind: {
        loading: '{isPivotPreTranslationRunning}',
        disabled: '{!enablePivotPreTranslationPanel}'
    },

    initConfig : function(instanceConfig) {
        var me = this,
        config = {
            bind:{
                store:'{pivotAssoc}'
            },
            features: [{
                ftype: 'grouping',
                groupHeaderTpl: Ext.create('Ext.XTemplate',
                    '{columnName}: {[this.formatValue(values)]}',
                    {
                        formatValue: function(values) {
                            var ret=values.name;
                            if(values.groupField === 'taskGuid'){
                                // when taskGuid is active as grouping, render the task name as group value
                                var data = values.rows && values.rows[0];
                                return data ? data.get('taskName') : ret;
                            }
                            return ret;
                        }
                    }
                ),
                hideGroupedHeader: false,
                enableGroupingMenu: true,
                groupers:[{property:'serviceName'},{property:'targetLang'}]
            }],
            bbar:[{
                xtype: 'button',
                itemId: 'startPivotPretranslation',
                bind: {
                    disabled: '{!pivotAssocStoreHasRecords}'
                },
                text:me.strings.startPretranslationButtonText
            }],
            columns : [{
                xtype : 'checkcolumn',
                tooltip : me.strings.checked,
                text : me.strings.checked,
                dataIndex : 'checked',
                sortable : true,
                cls: 'taskAssocChecked',
                width:60,
            }, {
                xtype : 'gridcolumn',
                tooltip : me.strings.taskGuid,
                text : me.strings.taskGuid,
                dataIndex : 'taskGuid',
                sortable : true,
                hidden:true,
                width:60,
            }, {
                xtype: 'gridcolumn',
                text: me.strings.name,
                renderer: function(value, metaData, record) {
                    return '<div style="float: left; width: 15px; height: 15px;margin-right:5px; border: 1px solid rgba(0, 0, 0, .2);background: #'+record.get('color')+';"></div>'+value;
                },
                dataIndex : 'name',
                sortable : true,
                flex : 50 / 100
            }, {
                xtype : 'gridcolumn',
                text: me.strings.serviceName,
                dataIndex : 'serviceName',
                sortable : true,
                flex : 25 / 100,
            },{
                xtype : 'gridcolumn',
                tooltip : me.strings.source,
                text : me.strings.source,
                cls : 'source-lang',
                dataIndex : 'sourceLang',
                renderer : me.langRenderer,
                sortable : true,
                flex : 25 / 100,
            }, {
                xtype : 'gridcolumn',
                tooltip : me.strings.relaisLang,
                text : me.strings.relaisLang,
                cls : 'target-lang',
                dataIndex : 'targetLang',
                renderer : me.langRenderer,
                flex : 25 / 100,
                sortable : true
            } ]
        };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([ config ]);
    },

    /***
     * Load the current grid store for given task
     * @param task
     */
    loadForTask: function (task){
        var me = this,
            store = me.getStore();

        if( !task){
            return;
        }

        store && store.load({
            params:{
                taskGuid:task.get('taskGuid')
            }
        });
    },

    setTask: function (currentTask){
        this.task = currentTask;
        this.loadForTask(this.task);
    },

    langRenderer : function(val, md) {
        var lang = Ext.StoreMgr.get('admin.Languages').getById(val), label;
        if (lang) {
            label = lang.get('label');
            md.tdAttr = 'data-qtip="' + label + '"';
            return label;
        }
        return '';
    }
});