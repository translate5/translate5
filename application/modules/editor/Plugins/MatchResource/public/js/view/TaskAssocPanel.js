
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3
			 http://www.gnu.org/licenses/agpl.html

END LICENSE AND COPYRIGHT
*/

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.plugins.pluginFeasibilityTest.view.Panel
 * @extends Ext.panel.Panel
 */
Ext.define('Editor.plugins.MatchResource.view.TaskAssocPanel', {
    extend : 'Ext.panel.Panel',
    alias : 'widget.matchResourceTaskAssocPanel',
    requires: ['Editor.view.admin.TaskActionColumn'],
    cls : 'adminTaskGrid',
    title: '#UT#Matchressourcen zuweisen',
    strings: {
        reload: '#UT#Aktualisieren',
        save: '#UT#Speichern',
        empty: '#UT#Keine Match Resource in der Sprachkombination des geöffneten Tasks verfügbar.',
        groupHeader: '#UT#Ressource: {name}',
        checked: '#UT#Ressource in Aufgabe verwenden',
        name: '#UT#Name',
        segmentsUpdateable: '#UT#Segmente zurückspeichern',
        source: '#UT#Quellsprache',
        target: '#UT#Zielsprache'
    },
    padding: 0,
    layout: 'fit',
    initConfig : function(instanceConfig) {
        var me = this,
        config = {
            title: me.title, //see EXT6UPD-9
            dockedItems : [{
                xtype : 'toolbar',
                dock : 'bottom',
                ui: 'footer',
                layout: {
                    type: 'hbox',
                    pack: 'start'
                },
                items : [{
                    iconCls: 'ico-refresh',
                    xtype: 'button',
                    itemId: 'btnReload',
                    text: me.strings.reload
                },{
                    xtype: 'tbfill'
                },{
                    xtype: 'button',
                    itemId: 'btnSaveChanges',
                    tooltip: me.strings.save,
                    text: me.strings.save
                }]
            }],
            items : [ {
                xtype : 'grid',
                itemId : 'tmTaskAssocGrid',
                store : 'Editor.plugins.MatchResource.store.TaskAssocStore',
                emptyText: me.strings.empty,
                features : [ {
                    id: 'group',
                    ftype: 'grouping',
                    groupHeaderTpl: me.strings.groupHeader,
                    hideGroupedHeader: true,
                    enableGroupingMenu: false
                } ],
                columns : [ {
                    xtype : 'checkcolumn',
                    text : '',
                    tooltip : me.strings.checked,
                    dataIndex : 'checked',
                    sortable : true,
                    width:60,
                }, {
                    xtype : 'checkcolumn',
                    tooltip : me.strings.segmentsUpdateable,
                    cls: 'segmentsUpdateable',
                    dataIndex : 'segmentsUpdateable',
                    sortable : true,
                    renderer: function(value, meta, record) {
                        this.disabled = !record.get('writable'); //disable checkbox casually
                        var res = this.defaultRenderer(value, meta, record);
                        this.disabled = false; //if disabled remains true, the whole column is not clickable
                        return res;
                    },
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
                    tooltip : me.strings.source,
                    cls : 'source-lang',
                    dataIndex : 'sourceLang',
                    renderer : me.langRenderer,
                    sortable : true,
                    flex : 25 / 100,
                }, {
                    xtype : 'gridcolumn',
                    tooltip : me.strings.target,
                    cls : 'target-lang',
                    dataIndex : 'targetLang',
                    renderer : me.langRenderer,
                    flex : 25 / 100,
                    sortable : true
                } ]
            } ],// end of items
        };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([ config ]);
    },
    langRenderer : function(val, md) {
        var lang = Ext.StoreMgr.get('admin.Languages').getById(val), label;
        if (lang) {
            label = lang.get('label');
            md.tdAttr = 'data-qtip="' + label + '"';
            return label;
        }
        return '';
    },
});