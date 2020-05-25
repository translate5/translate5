
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
Ext.define('Editor.view.LanguageResources.TaskAssocPanel', {
    extend : 'Ext.panel.Panel',
    alias : 'widget.languageResourceTaskAssocPanel',
    itemId:'languageResourceTaskAssocPanel',
    viewModel: {
        type: 'languageResourceTaskAssocPanel'
    },
    requires: [
        'Editor.view.admin.TaskActionColumn',
        'Editor.view.LanguageResources.TaskAssocPanelViewModel'
    ],
    cls : 'adminTaskGrid',
    title: '#UT#Sprach-Resourcen',
    strings: {
        reload: '#UT#Aktualisieren',
        save: '#UT#Speichern',
        empty: '#UT#Keine Sprachressource in der Sprachkombination des geöffneten Tasks verfügbar.',
        groupHeader: '#UT#Aufgabe: {[values.rows[0].data.taskName]}',
        checked: '#UT#Ressource in Aufgabe verwenden',
        name: '#UT#Name',
        segmentsUpdateable: '#UT#Segmente zurückspeichern',
        source: '#UT#Quellsprache',
        target: '#UT#Zielsprache'
    },
    padding: 0,
    layout:'fit',
    initConfig : function(instanceConfig) {
        var me = this,
        config = {
            title: me.title, //see EXT6UPD-9
            dockedItems : [],
            items : [{
                xtype : 'grid',
                itemId : 'languageResourcesTaskAssocGrid',
            	bind:{
            		store:'{taskAssoc}'
    			},
                emptyText: me.strings.empty,
                features : [ {
                    id: 'group',
                    ftype: 'grouping',
                    groupHeaderTpl: me.strings.groupHeader,
                    hideGroupedHeader: false,
                    enableGroupingMenu: true,
                    groupers:[{property:'serviceName'},{property:'targetLang'}]
                } ],
                columns : [ {
                    xtype : 'checkcolumn',
                    text : '',
                    tooltip : me.strings.checked,
                    dataIndex : 'checked',
                    sortable : true,
                    cls: 'taskAssocChecked',
                    width:60,
                }, {
                    xtype : 'checkcolumn',
                    tooltip : me.strings.segmentsUpdateable,
                    cls: 'segmentsUpdateable',
                    dataIndex : 'segmentsUpdateable',
                    sortable : true,
                    renderer: function(value, meta, record) {
                        return record.get('writable') ? this.defaultRenderer(value, meta, record) : '';
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
                	text:'ServiceName',
                    dataIndex : 'serviceName',
                    sortable : true,
                    flex : 25 / 100,
                },{
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
            }],// end of items
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
    }
});