
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

Ext.define('Editor.plugins.ChangeLog.view.Changelog', {
    extend: 'Ext.window.Window',
    alias: 'widget.changeLogWindow',
    itemId: 'changeLogWindow',
    requires: ['Editor.plugins.ChangeLog.view.TypeColumn'],
    cls: 'changeLogWindow',
    height:500,
    width : 800,
    autoHeight: true,
    autoScroll: true,
    modal: true,
    header:true,
    layout:'fit',
    strings: {
        date: '#UT#Datum',
        jiranumber: '#UT#Änderungsnr.',
        description: '#UT#Beschreibung',
        title:'#UT#Aktuelle Änderungen an der Anwendung',
        close:'#UT#Schließen'
    },
    listeners: {
        afterlayout: function() {
            var height = Ext.getBody().getViewSize().height;
            if (this.getHeight() > height) {
                this.setHeight(height);
            }
            this.center();
        }
    },
    initComponent: function() {
        var me = this;
        me.callParent(arguments);
    },
    initConfig: function(instanceConfig) {
        var me = this;
        me.title = me.strings.title;
        config = {
            items:[{
                xtype: 'grid',
                itemId: 'changeLogGrid',
                cls:'changeLogGrid',
                store: instanceConfig.changeLogStore,
                strings:me.strings,
                plugins: ['gridfilters'],
                columns: [{
                    xtype: 'datecolumn',
                    dataIndex: 'dateOfChange',
                    filter: {
                        type: 'date',
                        dateFormat:Editor.DATEONLY_ISO_FORMAT
                    },
                    cellWrap: true,
                    width: 100,
                    text: me.strings.date
                },{
                    xtype:'typecolumn'
                },{
                    xtype: 'gridcolumn',
                    cellWrap: true,
                    width: 150,
                    dataIndex: 'jiraNumber',
                    filter: {
                        type: 'string'
                    },
                    text: me.strings.jiranumber,
                    renderer: function(v) {
                        var url = Ext.String.format(Editor.data.plugins.ChangeLog.jiraIssuesUrl, v);
                        return '<a href="'+url+'" target="_blank">'+v+'</a>';
                    }
                },{
                    xtype: 'gridcolumn',
                    flex: 1,
                    cellWrap: true,
                    dataIndex: 'description',
                    filter: {
                        type: 'string'
                    },
                    text: me.strings.description,
                    renderer: function(v, meta, rec) {
                        var t = rec.get('title');
                        if(t == v) {
                            return '<b>'+t+'</b>';
                        }
                        return '<b>'+t+'</b><br>'+v;
                    }
                }],
                dockedItems:[{
                    xtype: 'toolbar',
                    flex: 1,
                    dock: 'bottom',
                    ui: 'footer',
                    layout: {
                        pack: 'end',
                        type: 'hbox'
                    },
                    items:[{
                            xtype: 'button',
                            text: me.strings.close,
                            itemId:'btnCloseWindow',
                            glyph: 'f00d@FontAwesome5FreeSolid'
                    }]
                },{
                    xtype: 'pagingtoolbar',
                    itemId: 'pagingtoolbar',
                    dock:'bottom',
                    store: instanceConfig.changeLogStore,
                }]
            }]
        };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});