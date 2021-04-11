
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
    checkPropagation: 'both',
    rootVisible: false,
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
        modeFalsePositives: '#UT#Nur Falsch-Positive'
    },
    initConfig: function(instanceConfig) {
        var me = this, config = {
            title: this.title,
            store: Ext.create('Editor.store.quality.Filter'),
            columns: [{
                xtype: 'treecolumn',
                iconCls: 'x-tree-noicon',
                dataIndex:'text',
                renderer: function (text, meta, record){
                    if(record.get('qroot')){
                        meta.tdCls = Ext.String.trim(meta.tdCls + ' x-tree-root');
                    }
                    if(record.get('qcount') == 0){
                        meta.tdCls = Ext.String.trim(meta.tdCls + ' x-tree-check-disabled');
                    }
                    return text + ' ('+record.get('qcount')+')';
                },
                sortable: true,
                flex: 1
            }],
            dockedItems: [{
                xtype: 'toolbar',
                dock: 'top',
                items: [
                    {
                        xtype: 'combo',
                        displayField: 'text',
                        valueField: 'mode',
                        queryMode:'local',
                        forceSelection: true,
                        // selectOnFocus: true,
                        value: 'all',
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
                    }
                ]
            },{
                // TODO: this will become the UI to refresh the qualities
                xtype: 'toolbar',
                dock: 'bottom',
                hidden: true,
                items: [
                    {
                        xtype: 'button',
                        text: 'JUST A DUMMY'
                    }
                ]
            }]
        };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    uncheckAll: function(){
        this.getController().uncheckAll();
    }
    /*
    initComponent: function() {
        Ext.applyIf(this, {
            viewConfig: {
                 singleSelect: true
            }
        });
        this.callParent(arguments);
    },
    */
    /*
    viewConfig:{
        markDirty: false
    }
    */
});
