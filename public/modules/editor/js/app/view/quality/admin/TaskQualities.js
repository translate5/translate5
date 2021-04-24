
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
 * Shows a short overview of the quality status of the task
 */
Ext.define('Editor.view.quality.admin.TaskQualities', {
    extend:'Ext.panel.Panel',
    alias: 'widget.taskQualities',
    // controller: 'taskQualities',
    itemId:'taskQualities',
    extraParams: [], // Extra params property used for store proxy binding
    title: '#UT#Qualitätssicherung',
    //card type, used for card display order
    importType: 'postimport',
    store: null,
    reAnalysisHidden: true,
    // autoScroll: true,
    requires: [
        'Editor.store.quality.Task'
    ],
    strings: {
        category: '#UT#Kategorie',
        total: '#UT#Anzahl',
        errors: '#UT#Fehler',
        falsePositives: '#UT#Falsch positive',
        status: '#UT#Status',
        completeTipCaption: '#UT#Vollständig analysiert',
        incompleteTipCaption: '#UT#Unvollständig analysiert',
        incompleteTipText: '#UT#Diese Kategorie wurde nicht oder nur unvollständig analysiert',
        startAnalysisHint: '#UT#Bitte stoßen Sie unten eine neue Analyse an um das Problem zu beheben',
        faultyTipCaption: '#UT#Interne Tag Fehler',
        faultyTipText: '#UT#Es gibt Interne Tag Fehler die einen fehlerfreien Export der Aufgabe verhindern',
        newAnalysis: '#UT#Neu analysieren',
    },
    publishes: {
        //publish this field so it is bindable
        extraParams: true
    },
    /**
     * allow the store extra params to be configurable on grid level. This will enable flexible loads via binding
     * This function only expects and handles extraParams with valid taskGuid
     */
    setExtraParams: function(extraParams){
        var me = this;
        if(!me.reAnalysisHidden){
            me.down('#analysisToolbar').setHidden(true);
            me.reAnalysisHidden = true;
        }
        if(extraParams && extraParams.taskGuid){
            me.store.load({
                params: {
                    taskGuid: extraParams.taskGuid
                }
            });
        } else {
            me.store.removeAll(false);
        }
    },
    initConfig: function(instanceConfig) {
        this.store = Ext.create('Editor.store.quality.Task');
        var me = this,
            config = {
                title: this.title,
                cls: 'taskQualities',
                items:[{
                    xtype: 'grid',
                    itemId: 'taskQualitiesGrid',
                    store: me.store,
                    viewConfig: {
                        getRowClass: function(record){
                            return record.get('qfaulty') ? 'x-grid-faulty' : '';
                        }
                    },
                    columns: [{
                        xtype: 'gridcolumn',
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
                            var html = '';
                            // type is complete ore incompletely analysed
                            if(isComplete){
                                html = '<span class="x-grid-symbol t5-quality-complete" data-qtip="'
                                    + '<b>' + me.strings.completeTipCaption +'</b>">'
                                    + Ext.String.fromCodePoint(parseInt('0xf00c', 16)) + '</span>';
                            } else {
                                html = '<span class="x-grid-symbol t5-quality-incomplete" data-qtip="'
                                    + '<b>' + me.strings.incompleteTipCaption +'</b><br/>'
                                    + me.strings.incompleteTipText + '. ' + me.strings.startAnalysisHint
                                    + '">'+ Ext.String.fromCodePoint(parseInt('0xf071', 16)) + '</span>';
                                // somehow ugly: adjusting the view from a column renderer.
                                // The store load-event comes to early and this is the easiest way, this one detail does not really justify an own view-controller
                                if(me.reAnalysisHidden){
                                    me.down('#analysisToolbar').setHidden(false);
                                    me.reAnalysisHidden = false;
                                }
                            }
                            // type blocks exporting of task
                            if(record.get('qfaulty') == true){
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
                    hidden: !me.reAnalysisVisible,
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
     * Opens the re-analysis dialog
     */
    onAnalysisButtonClick: function(btn){
        // TODO AUTOQA: implement
        console.log('onAnalysisButtonClick: ', btn);
    }    
});

