
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
 * @class Editor.view.segments.Grid
 * @extends Editor.view.ui.segments.Grid
 * @initalGenerated
 */
Ext.define('Editor.view.segments.grid.Toolbar', {
    extend: 'Ext.toolbar.Toolbar',
    alias: 'widget.segmentsToolbar',

    //Item Strings: 
    item_viewModesMenu: '#UT#Ansichtsmodus',
    item_viewModeBtn: '#UT#Details (nur Lesemodus)',
    item_editModeBtn: '#UT#Details',
    item_ergonomicModeBtn: '#UT#Normal',
    item_ergonomicModeReadonlyBtn: '#UT#Normal (nur Lesemodus)',
    item_hideTagBtn: '#UT#Tags verbergen',
    item_shortTagBtn: '#UT#Tag-Kurzansicht',
    item_fullTagBtn: '#UT#Tag-Vollansicht',
    item_qmsummaryBtn: '#UT#MQM',
    item_qmsummaryTooltip: '#UT#MQM Statistik',
    item_optionsTagBtn: '#UT#Einstellungen',
    item_zoomIn: '#UT#Segmentschriftgrad vergrößern',
    item_zoomOut: '#UT#Segmentschriftgrad verkleinern',
    item_clearSortAndFilterBtn: '#UT#Tabelle zurücksetzen',
    item_clearSortAndFilterTooltip: '#UT#Sortierung und Filter zurücksetzen',
    item_watchListFilterBtn: '#UT#Lesezeichen',
    item_helpTooltip: '#UT#Tastaturkürzel nachschlagen',
    viewModel: {
        formulas: {
            isNormalEdit: function(get) {
                return get('viewmodeIsEdit') && !get('editorIsReadonly');
            },
            isNormalView: function(get) {
                return get('viewmodeIsEdit') && get('editorIsReadonly');
            },
            isErgoEdit: function(get) {
                return get('viewmodeIsErgonomic') && !get('editorIsReadonly');
            },
            isErgoView: function(get) {
                return get('viewmodeIsErgonomic') && get('editorIsReadonly');
            }
        }
    },
    initConfig: function(instanceConfig) {
            var me = this,
            config = {
                items: [{
                    xtype: 'button',
                    text:me.item_viewModesMenu,
                    itemId: 'viewModeMenu',
                    menu: {
                        xtype: 'menu',
                        items: [{
                            xtype: 'menucheckitem',
                            mode:{
                                type: 'editMode',
                                readonly: true
                            },
                            bind: {
                                checked: '{isNormalView}'
                            },
                            text: me.item_viewModeBtn,
                            group: 'toggleView',
                            textAlign: 'left'
                        },{
                            xtype: 'menucheckitem',
                            checked: true, //FIXME must always calculated now! also the visible items! → do this through a view model for the Toolbar
                            mode:{
                                type: 'editMode',
                                readonly: false
                            },
                            bind: {
                                checked: '{isNormalEdit}',
                                hidden: '{taskIsReadonly}'
                            },
                            text: me.item_editModeBtn,
                            group: 'toggleView',
                            textAlign: 'left'
                        },{
                            xtype: 'menucheckitem',
                            mode:{
                                type: 'ergonomicMode',
                                readonly: false
                            },
                            bind: {
                                checked: '{isErgoEdit}',
                                hidden: '{taskIsReadonly}'
                            },
                            text: me.item_ergonomicModeBtn,
                            group: 'toggleView',
                            textAlign: 'left'
                        },{
                            xtype: 'menucheckitem',
                            mode:{
                                type: 'ergonomicMode',
                                readonly: true
                            },
                            bind: {
                                checked: '{isErgoView}'
                            },
                            text: me.item_ergonomicModeReadonlyBtn,
                            group: 'toggleView',
                            textAlign: 'left'
                        },{
                            xtype: 'menuseparator'
                        },{
                            xtype: 'menucheckitem',
                            itemId: 'hideTagBtn',
                            bind: {
                                disabled: '{!editorIsReadonly}'
                            },
                            text: me.item_hideTagBtn,
                            tagMode: 'hide',
                            group: 'tagMode'
                        },{
                            xtype: 'menucheckitem',
                            itemId: 'shortTagBtn',
                            checked: true,
                            text: me.item_shortTagBtn,
                            tagMode: 'short',
                            group: 'tagMode'
                        },{
                            xtype: 'menucheckitem',
                            itemId: 'fullTagBtn',
                            checked: true,
                            text: me.item_fullTagBtn,
                            tagMode: 'full',
                            group: 'tagMode'
                        }]
                    }
                },{
                    xtype: 'tbseparator'
                },{
                    xtype: 'button',
                    type: 'segment-zoom',
                    itemId: 'zoomInBtn',
                    iconCls: 'ico-zoom-in',
                    tooltip: {
                        text: me.item_zoomIn,
                        showDelay: 0
                    }
                },{
                    xtype: 'button',
                    type: 'segment-zoom',
                    itemId: 'zoomOutBtn',
                    iconCls: 'ico-zoom-out',
                    tooltip: {
                        text: me.item_zoomOut,
                        showDelay: 0
                    }
                },{
                    xtype: 'tbseparator'
                },{
                    xtype: 'button',
                    itemId: 'clearSortAndFilterBtn',
                    cls: 'clearSortAndFilterBtn',
                    tooltip: {
                        text: me.item_clearSortAndFilterTooltip,
                        showDelay: 0
                    },
                    text: me.item_clearSortAndFilterBtn
                },{
                    xtype: 'tbseparator'
                },{
                    xtype: 'button',
                    itemId: 'watchListFilterBtn',
                    cls: 'watchListFilterBtn',
                    enableToggle: true,
                    icon: Editor.data.moduleFolder+'images/star.png',
                    text: me.item_watchListFilterBtn
                },{
                    xtype: 'tbseparator',
                    hidden: !Editor.data.task.hasQmSub()
                },{
                    xtype: 'button',
                    itemId: 'qmsummaryBtn',
                    text: me.item_qmsummaryBtn,
                    tooltip: {
                        text: me.item_qmsummaryTooltip,
                        showDelay: 0
                    },
                    hidden: !Editor.data.task.hasQmSub()
                },{
                    xtype: 'tbfill'
                },{
                    xtype: 'button',
                    //FIXME let me come from a config:
                    href: 'http://confluence.translate5.net/display/BUS/Editor+keyboard+shortcuts',
                    hrefTarget: '_blank',
                    icon: Editor.data.moduleFolder+'images/help.png',
                    tooltip: {
                        text: me.item_helpTooltip,
                        showDelay: 0
                    }
                },{
                    xtype: 'button',
                    itemId: 'optionsBtn',
                    text: me.item_optionsTagBtn
                }]
            };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});