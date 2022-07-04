
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

Ext.define('Editor.view.admin.log.Grid', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.editorAdminLogGrid',
    requires: ['Editor.view.admin.log.GridViewController'],
    controller: 'editorlogGridViewController',
    cls: 'event-log-grid',
    strings: {
        reload: '#UT# Aktualisieren',
        level: '#UT# Typ',
        level_fatal: '#UT# Schwerer Fehler',
        level_error: '#UT# Fehler',
        level_warn: '#UT# Warnung',
        level_info: '#UT# Info',
        level_debug: '#UT# Debug',
        level_trace: '#UT# Trace',
        colUsername: '#UT# Benutzer',
        eventCode: '#UT# Code',
        eventCodeLong: '#UT# Ereignis- bzw. Fehlercode',
        domain: '#UT# Bereich',
        message: '#UT# Ereignis',
        created: '#UT# Zeitpunkt',
        moreInfo: '#UT# Mehr Info',
        eventCodeInfoText : '#UT#Für zusätzliche Information zu einem Ereignis bitte auf den Code klicken.<br/>Nicht alle Fehler können hier aufgelistet werden, bitte beachten Sie daher auch das <a href="#preferences/adminSystemLog">generelle System Log</a>!'
    },
    layout: 'fit',
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
        Ext.Object.each(Editor.util.Util.prototype.errorLevel, function(k, v) {
            levelFilter.push({
                id: k,
                text: me.imgTpl.apply([v]) + ' ' +  me.strings['level_'+v]
            });
        });
        config = {
            items : [{
                xtype: 'gridpanel',
                plugins: ['gridfilters'],
                store: me.store,
                columns: [
                    {
                        xtype: 'datecolumn',
                        dataIndex: 'created',
                        text: me.strings.created,
                        width: 100,
                        format: Ext.grid.column.Date.prototype.format + ' H:i:s',
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
                            var level = Editor.util.Util.getErrorLevelName(v),
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
                        tdCls: 'message',
                        renderer: function(v, meta, rec) {
                            var data = rec.get('extra');
                            if(data) {
                                v += ' <img class="icon-log-more-info" src="'+Ext.BLANK_IMAGE_URL+'" alt="'+me.strings.moreInfo+'">';
                            }
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
                        glyph: 'f2f1@FontAwesome5FreeSolid',
                        text: me.strings.reload,
                        handler: function() {
                            me.down('gridpanel').store.reload();
                        }
                    },{
                        xtype:'displayfield',
                        value:me.strings.eventCodeInfoText
                    }]
                },{
                    dock: 'bottom',
                    xtype: 'pagingtoolbar',
                    store: me.store
                }]
                
            },{
                itemId: 'detailview',
                xtype: 'panel',
                hidden: true,
                scrollable: 'y',
                items: [{
                    xtype: 'container',
                    itemId: 'eventdata'
                },{
                    title: me.strings.details,
                    nameColumnWidth: 200,
                    xtype: 'propertygrid'
                }],
                dockedItems: [
                    {
                        xtype: 'toolbar',
                        dock: 'bottom',
                        ui: 'footer',
                        items: [
                            {
                                xtype: 'tbfill'
                            },
                            {
                                xtype: 'button',
                                itemId: 'cancelBtn',
                                glyph: 'f00d@FontAwesome5FreeSolid',
                                text: me.strings.btnBack
                            }
                        ]
                    }
                ]
            }]
        };

        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    load: function(id) {
        var grid = this.down('gridpanel'),
        store = grid.getStore();
        store.suppressNextFilter = true;
        grid.filters.clearFilters();
        //see store definition why we do this:
        store.suppressNextFilter = false;
        store.loadData([], false);
        store.proxy.url = Editor.data.restpath+this.entityUrlPart+'/'+id+'/events';
        store.load({
            url: Editor.data.restpath+this.entityUrlPart+'/'+id+'/events'
        });
    }
});
