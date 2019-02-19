
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

Ext.define('Editor.view.admin.log.Grid', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.editorAdminLogGrid',
    plugins: ['gridfilters'],
    strings: {
        reload: '#UT# Aktualisieren',
        level: '#UT# Typ',
        level_fatal: '#UT# Fatal',
        level_error: '#UT# Error',
        level_warn: '#UT# Warnung',
        level_info: '#UT# Info',
        level_debug: '#UT# Debug',
        level_trace: '#UT# Trace',
        colUsername: '#UT# Benutzer',
        eventCode: '#UT# Fehlercode',
        domain: '#UT# Bereich',
        message: '#UT# Fehler',
        created: '#UT# Zeitpunkt'
    },
    entityUrlPart: null,
    imgTpl: new Ext.Template('<img valign="text-bottom" class="icon-error-level-{0}" src="'+Ext.BLANK_IMAGE_URL+'" alt="{0}" />'),
    constructor: function(config) {
        var store = Ext.data.StoreManager.lookup(this.store);
        if(store) {
            //see store definition why we do this:
            store.suppressNextFilter = true;
        }
        this.callParent([
            config
        ]);
    },
    initConfig: function(instanceConfig) {
        var me = this,
            config,
            levelFilter = [];
        Ext.Object.each(Editor.model.admin.task.Log.prototype.errorLevel, function(k, v) {
            levelFilter.push({
                id: k,
                text: me.imgTpl.apply([v]) + ' ' +  me.strings['level_'+v]
            });
        });
        config = {
            columns: [
                {
                    xtype: 'datecolumn',
                    dataIndex: 'created',
                    text: me.strings.created,
                    width: 100,
                    filter: {
                        type: 'date',
                        dateFormat: Editor.DATE_ISO_FORMAT
                    }
                },
                {
                    xtype: 'gridcolumn',
                    dataIndex: 'level',
                    text: me.strings.level,
                    width: 50,
                    tdCls: 'error-level',
                    renderer: function(v, meta, rec) {
                        var level = rec.getLevelName(),
                            img = me.imgTpl.apply([level]);
                        meta.tdAttr = 'data-qtip="' + me.strings['level_'+level]+'"';
                        return img;
                    },
                    filter: {
                        type: 'list',
                        options: levelFilter,
                        phpMode: false
                    }
                },
                {
                    xtype: 'gridcolumn',
                    dataIndex: 'eventCode',
                    width: 85,
                    text: me.strings.eventCode,
                    renderer: function(v, meta, rec) {
                        if(!Editor.data.errorCodesUrl) {
                            return v;
                        }
                        var url = Ext.String.format(Editor.data.errorCodesUrl, v);
                        return '<a href="'+url+'" target="_blank">'+v+'</a>';
                    },
                    filter: {
                        type: 'string'
                    }
                },
                {
                    flex: 1,
                    xtype: 'gridcolumn',
                    dataIndex: 'message',
                    renderer: function(v) {
                        return v.replace(/\n/, "<br>\n");
                    },
                    text: me.strings.message,
                    variableRowHeight: true, 
                    filter: {
                        type: 'string'
                    }
                },
                {
                    width: 180,
                    xtype: 'gridcolumn',
                    dataIndex: 'authUser',
                    text: me.strings.colUsername,
                    filter: {
                        type: 'string'
                    }
                },
                {
                    width: 100,
                    xtype: 'gridcolumn',
                    dataIndex: 'domain',
                    text: me.strings.domain,
                    filter: {
                        type: 'string'
                    }
                }
            ],
            dockedItems: [{
                xtype: 'toolbar',
                dock: 'top',
                items: [{
                    xtype: 'button',
                    itemId: 'userPrefReload',
                    iconCls: 'ico-refresh',
                    text: me.strings.reload,
                    handler: function() {
                        me.store.reload();
                    }
                }]
            }]
        };

        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    load: function(id) {
        var store = this.getStore();
        store.suppressNextFilter = true;
        this.filters.clearFilters();
        //see store definition why we do this:
        store.suppressNextFilter = false;
        store.loadData([], false);
        store.proxy.url = Editor.data.restpath+this.entityUrlPart+'/'+id+'/events';
        store.load({
            url: Editor.data.restpath+this.entityUrlPart+'/'+id+'/events'
        });
    }
});
