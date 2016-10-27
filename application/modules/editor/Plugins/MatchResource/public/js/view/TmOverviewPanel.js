/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.plugins.MatchResource.view.TmOverviewPanel
 * @extends Ext.panel.Panel
 */
Ext.define('Editor.plugins.MatchResource.view.TmOverviewPanel', {
    extend : 'Ext.grid.Panel',
    alias: 'widget.tmOverviewPanel',
    itemId: 'tmOverviewPanel',
    title:'#UT#Matchressourcen',
    strings: {
        name: '#UT#Name',
        edit: '#UT#Bearbeiten',
        erase: '#UT#Löschen',
        download: '#UT#Dateibasiertes TM herunterladen und lokal speichern',
        resource: '#UT#Ressource',
        color: '#UT#Farbe',
        refresh: '#UT#Aktualisieren',
        add: '#UT#Hinzufügen',
        noTaskAssigned:'#UT#Keine Aufgaben zugewiesen.',
        sourceLang: '#UT#Quellsprache',
        targetLang: '#UT#Zielsprache',
        taskassocgridcell:'#UT#Zugewiesene Aufgaben'
    },
    cls:'tmOverviewPanel',
    height: '100%',
    layout: {
        type: 'fit'
    },
    initConfig: function(instanceConfig) {
        var me = this,
            config = {
                title: me.title, //see EXT6UPD-9
                store : 'Editor.plugins.MatchResource.store.TmMts',
                viewConfig: {
                    getRowClass: function(record) {
                        return record.get('filebased') ? 'match-ressource-filebased' : 'match-ressource-non-filebased';
                    }
                },
                columns: [{
                    xtype: 'gridcolumn',
                    width: 100,
                    dataIndex: 'name',
                    filter: {
                        type: 'string'
                    },
                    text: me.strings.name
                },{
                    xtype: 'gridcolumn',
                    width: 100,
                    dataIndex: 'sourceLang',
                    renderer : me.langRenderer,
                    cls: 'source-lang',
                    filter: {
                        type: 'string'
                    },
                    text: me.strings.sourceLang
                },{
                    xtype: 'gridcolumn',
                    width: 100,
                    dataIndex: 'targetLang',
                    renderer : me.langRenderer,
                    cls: 'target-lang',
                    filter: {
                        type: 'string'
                    },
                    text: me.strings.targetLang
                },{
                    xtype: 'gridcolumn',
                    width: 100,
                    dataIndex: 'color',
                    renderer: function(value, metaData, record) {
                        return '<div style="float: left; width: 15px; height: 15px;margin-right:5px; border: 1px solid rgba(0, 0, 0, .2);background: #'+record.data.color+';"></div>';
                    },
                    text: me.strings.color
                },{
                    xtype: 'actioncolumn',
                    width: 60,
                    items: [{
                        tooltip: me.strings.edit,
                        action: 'edit',
                        iconCls: 'ico-tm-edit'
                    },{
                        tooltip: me.strings.erase,
                        action: 'delete',
                        iconCls: 'ico-tm-delete'
                    },{
                        tooltip: me.strings.download,
                        action: 'download',
                        iconCls: 'ico-tm-download'
                    }]
                },{
                    xtype: 'gridcolumn',
                    width: 100,
                    text: me.strings.resource,
                    dataIndex: 'serviceName',
                    filter: {
                        type: 'string'
                    }
                },{
                    xtype:'gridcolumn',
                    width: 40,
                    dataIndex:'taskList',
                    tdCls: 'taskList',
                    cls: 'taskList',
                    text: me.strings.taskassocgridcell,
                    renderer: function(v, meta, rec){
                        var tasks = [], i;
                        
                        if(!v || v.length == 0){
                            tasks.push(this.strings.noTaskAssigned);
                        }
                        else {
                            for(i = 0;i<v.length;i++){
                                tasks.push(v[i]);
                            }
                        }
                        meta.tdAttr = 'data-qtip="'+tasks.join('<br />')+'"';
                        return v.length;
                    }
                }],
                dockedItems: [{
                    xtype: 'toolbar',
                    dock: 'top',
                    items: [{
                        xtype: 'button',
                        iconCls: 'ico-tm-add',
                        itemId: 'btnAddTm',
                        text: me.strings.add,
                        tooltip: me.strings.add
                    },{
                        xtype: 'button',
                        iconCls: 'ico-refresh',
                        itemId: 'btnRefresh',
                        text: me.strings.refresh,
                        tooltip: me.strings.refresh
                    }]
                },{
                    xtype: 'pagingtoolbar',
                    store: 'Editor.plugins.MatchResource.store.TmMts',
                    dock: 'bottom',
                    displayInfo: true
            }]
      };

      if (instanceConfig) {
          me.self.getConfigurator().merge(me, config, instanceConfig);
      }
      return me.callParent([config]);
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