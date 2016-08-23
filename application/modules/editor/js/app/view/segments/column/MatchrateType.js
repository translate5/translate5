
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
 * @class Editor.view.segments.column.MatchrateType
 * @extends Ext.grid.column.Column
 */
Ext.define('Editor.view.segments.column.MatchrateType', {
    extend: 'Ext.grid.column.Column',
    alias: 'widget.matchrateTypeColumn',
    mixins: ['Editor.view.segments.column.BaseMixin'],
    dataIndex: 'matchRateType',
    width: 82,
    text: '#UT#Matchrate Typ',
    tdCls: 'matchrateTypeColumn',
    strings: {
        noValueDefined: '#UT#Kein Wert definiert!',
        imported: '#UT#Importiert: {0}',
        edited: '#UT#Nach Bearbeitung: {0}'
    },
    imgTpl: new Ext.XTemplate([
       '<tpl for=".">',
           '<tpl for="types">',
               '<img valign="text-bottom" class="matchRateType type-{.}" src="'+Editor.data.moduleFolder+'images/matchratetypes/{.}.png" alt="{.}"/>',
           '</tpl>',
           '<tpl if="edited">',
               '<img valign="text-bottom" class="matchRateEdited" src="'+Editor.data.moduleFolder+'images/pencil.png"/>',
           '</tpl>',
       '</tpl>'
    ]),
    initComponent: function() {
        var me = this;
        me.scope = me; //so that renderer can access this object instead the whole grid.
        me.initBaseMixin();
        me.callParent(arguments);
        if(me.xtype != 'matchrateTypeColumn'){
            return;
        }
        //use show / hide handlers only for matchRateType column
        me.on({
            show: function() {
                this.up('grid').getEl().addCls('matchratetype-visible');
            },
            hide: function() {
                this.up('grid').getEl().removeCls('matchratetype-visible');
            },
            //overrides needed to refresh list filter on each usage 
            afterRender: function() {
                Ext.override(this.filter, {
                    show: function() {
                        this.loaded = false;
                        this.callParent(arguments);
                    },
                    showMenu: function() {
                        var me = this;
                        //we want to recreate/refresh the menu on every usage
                        if(me.menu) {
                            //me.menu.destroy();
                            me.menu = null;
                        }
                        me.callParent(arguments);
                        me.store.removeAll();
                    }
                });
            }
        });
    },
    initConfig: function(instanceConfig) {
        var me = this,
            config = {
            };
        config.filter = {
            type: 'list',
            labelField: 'label',
            store: new Ext.data.Store({
                fields: [{
                    name: 'id',
                    mapping: 'matchrateType'
                },{
                    name: 'label',
                    convert: function(value, record) {
                        var meta = {},
                            icons = me.renderer(record.get('matchrateType'), meta, record);
                        return icons + ' ' + meta.myLabel;
                    }
                }],
                proxy: {
                    type: 'ajax',
                    url: Editor.data.restpath+'segment/matchratetypes',
                    reader: {
                        type: 'json'
                    }
                },
                autoLoad: true
            })
        };
        if (instanceConfig) {
            me.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    /**
     * renders a nice icon and a tooltip to the matchrate type value
     * @param {Integer} value
     * @param {Object} meta
     * @param {Editor.model.Segment} record
     * @see {Ext.grid.column.Column}
     * @returns String
     */
    renderer: function(value,meta,record){
        //data comes valid from server, so no checks here needed
        var me = this,
            value = value && value.split(';') || [],
            firstType,
            prefix = value.shift(),
            isImport = (prefix == 'import'),
            qtip = function(meta, msg) {
                meta.myLabel = msg; //as ref for the list renderer
                meta.tdAttr = 'data-qtip="'+msg+'"';
            };
            
        if(!Editor.data.plugins.MatchResource) {
            return value;
        }
            
        if(prefix == Editor.data.plugins.MatchResource.matchrateTypeChangedState) {
            return '...'; //do nothing here since pending save
        }
            
        if(value.length == 0) {
            qtip(meta, me.strings.noValueDefined);
            return '';
        }
        firstType = value.shift();
        label = Ext.String.format(isImport ? me.strings.imported : me.strings.edited, firstType);
        if(value.length > 0) {
            label = label + ' (' + value.join(';') + ')';
        }
        qtip(meta, label);
        value.unshift(firstType);
        return me.imgTpl.apply({types: value, edited: !isImport});
    }
});