
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

//FIXME HERE revoke eventCodeInfoText translation to original btw add the new one to the Grid.js
// add missing title to translations

Ext.define('Editor.view.admin.log.SystemGrid', {
    extend: 'Ext.grid.Panel',
    requires: ['Editor.model.admin.Log'],
    alias: 'widget.editorSystemLogGrid',
    itemId: 'adminSystemLog',
    cls: 'event-log-grid',
    strings: {
        title: '#UT# System Log',
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
        eventCodeInfoText : '#UT#Für zusätzliche Information zu einem Ereignis bitte auf den Code klicken'
    },
    //layout: 'fit',
    imgTpl: new Ext.Template('<img valign="text-bottom" class="icon-error-level-{0}" src="'+Ext.BLANK_IMAGE_URL+'" alt="{0}" />'),
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
            glyph: 'xf022@FontAwesome5FreeSolid',
            store: Ext.create('Ext.data.Store', {
                model: 'Editor.model.admin.Log',
                autoLoad: true,
                remoteFilter: true,
                remoteSort: true,
                pageSize: 25,
                proxy: {
                    type: 'rest',
                    url: Editor.data.restpath+'log',
                    reader: {
                        rootProperty: 'rows',
                        type: 'json'
                    }
                }
            }),
            title: me.strings.title,
            plugins: ['gridfilters', {
                ptype: 'rowexpander',
                rowBodyTpl : new Ext.XTemplate(
                    '<tpl if="file">',
                        '<p><b>File:</b> {file} ({line})</p>',
                    '</tpl>',
                    '<p><b>Request:</b> {httpHost} {method} {url}</p>',
                    '<tpl if="trace">',
                        '<p><b>Trace:</b> <pre>{trace}</pre></p>',
                    '</tpl>',
                    '<tpl if="extra">',
                        '<p><b>Extra:</b> <pre>{extra}</pre></p>',
                    '</tpl>'
                )
            }],
            columns: [
                {
                    xtype: 'datecolumn',
                    dataIndex: 'created',
                    text: me.strings.created,
                    width: 160,
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
                    width: 160,
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
                        me.store.reload();
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
        };

        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});
