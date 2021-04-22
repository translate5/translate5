
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
 * Filter Panel for qualities
 * TODO FIXME: why does the store need tpo be created explicitly instead of config option ?
 * Ext.util.Observable.capture('your store reference here', function(evname) {console.log(evname, arguments);})
 */
Ext.define('Editor.view.quality.FilterPanel', {
    extend: 'Ext.tree.Panel',
    requires: [
        'Editor.view.quality.FilterPanelController',
        'Editor.store.quality.Filter'
    ],    
    controller: 'qualityFilterPanel',
    alias: 'widget.qualityFilterPanel',
    itemId: 'qualityFilterPanel',
    cls: 'qualityFilterPanel',
    title: '#UT#Qualitätssicherung',
    rootVisible: false,
    reAnalysisHidden: true, // quirky: this prop is set by the column-renderer and after tfhe store's "load" event it is evaluated in "afterLoad" where it steers the visibility of our footer
    useArrows: true,
    // we catch the beforestaterestore event to load the store when the panel is initially open
    listeners: {
        beforeexpand: 'onBeforeExpand',
        collapse: 'onCollapse',
        beforestaterestore: 'onBeforeStateRestore',
        beforecheckchange: 'onBeforeCheckChange',
        checkchange: 'onCheckChange',
        removed: 'onRemoved'
    },
    strings: {
        modeAll: '#UT#Alle zeigen',
        modeErrors: '#UT#Nur Fehler',
        modeFalsePositives: '#UT#Nur Falsch-Positive',
        incompleteCatCaption: '#UT#Unvollständig analysiert',
        incompleteCatText: '#UT#Die Qualität wurde nicht oder nur unvollständig analysiert. Bitte stoßen Sie unten eine neue Analyse an um das Problem zu beheben',
        newAnalysis: '#UT#Neu analysieren',
    },
    initConfig: function(instanceConfig) {
        var me = this,
        filterMode = Editor.app.getController('Quality').getFilterMode(),
        config = {
            title: this.title,
            store: Ext.create('Editor.store.quality.Filter'),
            columns: [{
                xtype: 'treecolumn',
                iconCls: 'x-tree-noicon',
                dataIndex:'text',
                renderer: function (text, meta, record){
                    if(record.isQualityRoot()){
                        meta.tdCls = Ext.String.trim(meta.tdCls + ' x-tree-root');
                    }
                    if(record.isEmpty()){
                        meta.tdCls = Ext.String.trim(meta.tdCls + ' x-tree-check-disabled');
                    }
                    if(record.isFaulty()){
                        meta.tdCls = Ext.String.trim(meta.tdCls + ' x-tree-faulty');
                    }
                    var symbol = '';
                    // special for rubrics: add icon for incompletely tagged quality types
                    if(record.isIncomplete()){
                        symbol += '<span class="x-tree-symbol t5-quality-incomplete" data-qtip="'
                            + '<b>' + me.strings.incompleteCatCaption +'</b><br/>' + me.strings.incompleteCatText + '">'
                            + Ext.String.fromCodePoint(parseInt('0xf071', 16)) + '</span> ';
                        me.reAnalysisHidden = false; // triggers the showing of the re-analysis toolbar/button
                    }
                    // special for mqm: add category-index / mqm-id
                    if(record.get('qtype') == 'mqm' && record.get('qcatidx') > -1){
                        symbol += '<img class="x-tree-symbol qmflag qmflag-' + record.get('qcatidx') + '" src="' 
                            + Editor.data.segments.subSegment.tagPath + 'qmsubsegment-' + record.get('qcatidx') + '-left.png"> ';
                    }
                    return symbol + text + ' ('+record.get('qcount')+')';
                },
                sortable: true,
                flex: 1
            }],
            dockedItems: [{
                xtype: 'toolbar',
                dock: 'top',
                items: [{
                    xtype: 'combo',
                    displayField: 'text',
                    valueField: 'mode',
                    queryMode:'local',
                    itemId: 'modeSelector',
                    forceSelection: true,
                    value: filterMode,
                    listeners:{
                       change: 'onFilterModeChanged'
                    },
                    store: Ext.create('Ext.data.Store', {
                       fields: [ 'text', 'mode' ],
                       data : [
                           { 'text': me.strings.modeAll, 'mode': 'all' },
                           { 'text': me.strings.modeErrors, 'mode': 'error' },
                           { 'text': me.strings.modeFalsePositives, 'mode': 'falsepositive' }
                       ]
                    })
                }]
            },{
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
                        click: 'onAnalysisButtonClick'
                    }
                }]
            }]
        };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    /**
     * Used to finalize the view after the store was loaded
     */
    afterLoad: function(){
        this.down('#analysisToolbar').setHidden(this.reAnalysisHidden);
        this.reAnalysisHidden = true; // reset for the next load
    },
    uncheckAll: function(){
        this.getController().uncheckAll();
    }
});
