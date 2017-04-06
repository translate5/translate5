
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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
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
    item_viewModesMenu: '#UT#Editormodi',
    item_viewModeBtn: '#UT#Ansichtsmodus',
    item_editModeBtn: '#UT#Bearbeitungsmodus',
    item_ergonomicModeBtn: '#UT#Ergonomic',
    item_ergonomicModeReadonlyBtn: '#UT#Ergonomic (Ansicht)',
    item_hideTagBtn: '#UT#Tags verbergen',
    item_shortTagBtn: '#UT#Tag-Kurzansicht',
    item_fullTagBtn: '#UT#Tag-Vollansicht',
    item_qmsummaryBtn: '#UT#QM-Subsegment-Statistik',
    item_optionsTagBtn: '#UT#Einstellungen',
    item_clearSortAndFilterBtn: '#UT#Sortierung und Filter zurücksetzen',
    item_watchListFilterBtn: '#UT#Merkliste',
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
                        }]
                    }
                },{
                    xtype: 'tbseparator'
                },{
                    xtype: 'button',
                    itemId: 'hideTagBtn',
                    enableToggle: true,
                    text: me.item_hideTagBtn,
                    tagMode: 'hide',
                    toggleGroup: 'tagMode',
                    bind: {
                        disabled: '{!editorIsReadonly}'
                    }
                },{
                    xtype: 'button',
                    itemId: 'shortTagBtn',
                    enableToggle: true,
                    pressed: true,
                    text: me.item_shortTagBtn,
                    tagMode: 'short',
                    toggleGroup: 'tagMode'
                },{
                    xtype: 'button',
                    itemId: 'fullTagBtn',
                    enableToggle: true,
                    tagMode: 'full',
                    text: me.item_fullTagBtn,
                    toggleGroup: 'tagMode'
                },{
                    xtype: 'tbseparator'
                },{
                    xtype: 'button',
                    itemId: 'clearSortAndFilterBtn',
                    cls: 'clearSortAndFilterBtn',
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
                    hidden: !Editor.data.task.hasQmSub()
                },{
                    xtype: 'tbfill'
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