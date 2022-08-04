
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
 * @class Editor.view.LanguageResources.SearchGrid
 * @extends Ext.grid.Panel
 */
Ext.define('Editor.view.LanguageResources.SearchGrid', {
    extend : 'Ext.grid.Panel',
    requires: [
               'Editor.view.LanguageResources.SearchGridViewController',
               'Editor.view.LanguageResources.SearchGridViewModel'
               ],
    alias : 'widget.languageResourceSearchGrid',
    controller: 'languageResourceSearchGrid',
    viewModel: {
        type: 'languageResourceSearchGrid'
    },
    bind: {
        store: '{editorsearch}'
    },
    itemId:'searchGridPanel',
    cls:'searchGrid',
    assocStore : [],
    border: false,
    layout: 'fit',
    scrollable: true,
    strings: {
        source: '#UT#Ausgangstext',
        target: '#UT#Zieltext',
        match: '#UT#Matchrate',
        ctrl: '#UT#STRG',
        sourceEmptyText:'#UT#Suche im Ausgangstext',
        targetEmptyText:'#UT#Suche im Zieltext',
        tmresource:'#UT#TM-Ressource',
        search:'#UT#Alle durchsuchen',
        singleSearch:'#UT#Suche in {0}'
    },
    viewConfig: {
        enableTextSelection: true,
        getRowClass: function(record) {
			//same class generation in MatchGrid!
            var me=this,
            	result = ['segment-font-sizable', 'match-state-'+record.get('state')];
            
            if(me.lookupViewModel().get('viewmodeIsErgonomic')){
                result.push('ergonomic-font');
            } else {
                result.push('view-editor-font-size');
            }
            return result.join(' ');
        },
        onScrollEnd: function(x, y) {
            if(this.getHeight() + y + 10 >= this.el.dom.scrollHeight) {
                this.fireEvent('scrollbottomreached', this);
            }
        }
    },
    initConfig: function(instanceConfig) {
        var me = this,
            config = null,
            segField = Editor.model.segment.Field,
            searchItems = [];

        me.assocStore = instanceConfig.assocStore;

        me.assocStore.each(function(rec){
            if(rec.get('searchable')){
                searchItems.push({
                    text: Ext.String.format(me.strings.singleSearch, rec.get('name')+' ('+rec.get('serviceName')+')'),
                    iconCls: 'coloricon', //css class does not exist, but a value is needed here to trigger the icon rendering
                    service: rec
                });
            }
        });

        config = {
                columns: [{
                    xtype: 'gridcolumn',
                    enableTextSelection: true,
                    hideable: false,
                    sortable: false,
                    flex: 2,
                    dataIndex: 'source',
                    tdCls: 'x-selectable segment-tag-column source'+segField.getDirectionCls('source'),
                    cellWrap: true,
                    text: me.strings.source
                },{
                    xtype: 'gridcolumn',
                    enableTextSelection: true,
                    flex: 2,
                    dataIndex: 'target',
                    tdCls: 'x-selectable segment-tag-column target'+segField.getDirectionCls('target'),
                    hideable: false,
                    sortable: false,
                    cellWrap: true,
                    text: me.strings.target
                },{
                    xtype: 'gridcolumn',
                    flex: 1,
                    hideable: false,
                    sortable: false,
                    dataIndex: 'service',
                    renderer: function(val, meta, record) {
                        var str = me.assocStore.findRecord('languageResourceId',record.get('languageResourceid'),0,false,true,true);
                        meta.tdStyle="background-color:#"+str.get('color')+" !important;";
                        return str.get('name')+' ('+str.get('serviceName')+')';
                    },
                    text: me.strings.tmresource
                }],
                dockedItems: [{
                    xtype: 'panel',
                    layout: 'column',
                    border: false,
                    padding:'10 10 10 10',
                    items:[{
                        xtype:'textfield',
                        id:'sourceSearch',
                        width: '31%',
                        name:'source',
                        enableKeyEvents: true,
                        padding:'0 10 0 0',
                        emptyText:me.strings.sourceEmptyText,
                    },{
                        xtype:'textfield',
                        id:'targetSearch',
                        width: '31%',
                        name:'target',
                        enableKeyEvents: true,
                        padding:'0 10 0 0',
                        emptyText:me.strings.targetEmptyText,
                    },{
                        xtype:'splitbutton',
                        name:'btnSubmit',
                        itemId:'searchButton',
                        text:me.strings.search,
                        iconCls:'ico-tm-magnifier',
                        menu: {
                            xtype: 'menu',
                            items: searchItems
                        }
                    }]
                }]
            };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});