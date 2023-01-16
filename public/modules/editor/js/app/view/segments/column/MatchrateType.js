
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
    requires:[
    	'Editor.util.LanguageResources'
	],
    mixins: ['Editor.view.segments.column.BaseMixin'],
    dataIndex: 'matchRateType',
    width: 82,
    text: '#UT#Matchrate Typ',
    strings: {
        //see in localizedjsstrings
    },
    imgTpl: new Ext.XTemplate([
       '<tpl for=".">',
           '<tpl for="types">',
               '<img valign="text-bottom" class="matchRateType type-{type}" src="{path}" alt="{type}"/>',
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
            tdCls = 'matchrateTypeColumn',
            config = {
                tdCls: tdCls,
                editor: {
                    xtype: 'displayfield',
                    getModelData: function() {
                        return null;
                    },
                    cls: 'matchrateTypeEdit',
                    ownQuicktip: true,
                    renderer: me.ownQuicktip(tdCls)
                }
            };
        config.filter = {
            type: 'list',
            labelField: 'label',
            updateBuffer: 0,
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
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    ownQuicktip: function(tdCls) {
        return function(value, field) {
            var context = field.ownerCt.context,
                qtip, cell;
            if(context && context.row){
                cell = Ext.fly(context.row).down('td.'+tdCls);
                if(cell) {
                    qtip = cell.getAttribute('data-qtip');
                    field.getEl().dom.setAttribute('data-qtip', qtip ? qtip : '');
                }
            }
            return value;
        }
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
            firstType, useAsLabel,
            prefix = value.shift(), //import, edited, pretranslated
            isImport = (prefix == 'import'),
            label = '',
            translate = function(value) {
                return me.strings.type[value] || value;
            },
            qtip = function(meta, msg, desc) {
                desc = desc ? '<br>'+desc : '';
                meta.myLabel = msg; //as ref for the list filter renderer
                
                if(record && record.get('matchRate')){
                	meta.tdAttr = 'data-qtip="<b>'+msg+'</b>'+desc+'<br/>'+Editor.util.LanguageResources.getMatchrateTooltip(record.get('matchRate'))+'"';
                	return;
                }
            	meta.tdAttr = 'data-qtip="<b>'+msg+'</b>'+desc+'"';
            };
           
        if(prefix == Editor.data.LanguageResources.matchrateTypeChangedState) {
            return '...'; //do nothing here since pending save
        }
        
        //nothing given, legacy data before introducing matchtype
        if(value.length == 0) {
            //no icon and label, only tooltip
            qtip(meta, me.strings.olddata, me.strings.olddataDesc);
            return '';
        }
        
        useAsLabel = firstType = value.shift();
        //empty, when there was no target and matchRate = 0
        if(firstType == 'empty') {
            meta.myLabel = me.strings.noTarget;
            //no icon and label, and no tooltip
            return '';
        }
        //none, when there was no value given in the segment
        if(firstType == 'none') {
            qtip(meta, me.strings.noValueDefined, me.strings.noValueDefinedDesc);
            //no icon and label, only tooltip
            return '';
        }
        //unknown, when the given value is not registered in translate5
        if(firstType == 'unknown' || prefix == 'pretranslated' && firstType != 'source') {
            useAsLabel = value.shift(); //when unknown remove it to prevent a not found image, the real value is in the next field
        }

        //translating and formatting known types 
        label = Ext.String.format(me.strings[prefix], translate(useAsLabel));
        if(value.length>0){
            label = label + ' (' +value.join(';')+ ')';
        }

        //using tooltip 
        qtip(meta, label, me.strings[prefix+'Desc'][firstType]);
        value.unshift(firstType);
        var newValue=[];
        //and image
        Ext.Array.each(value, function(val, idx){
            var path;
            if(Editor.data.segments.matchratetypes && Editor.data.segments.matchratetypes[val]) {
                path = Editor.data.segments.matchratetypes[val];
                newValue.push({
                    type: val,
                    path: path
                });
            }
        });
        return me.imgTpl.apply({types: newValue, edited: !isImport});
    }
});