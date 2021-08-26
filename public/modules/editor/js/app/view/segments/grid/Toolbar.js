
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
 * @class Editor.view.segments.grid.Toolbar
 * @extends Ext.toolbar.Toolbar
 * @initalGenerated
 */
Ext.define('Editor.view.segments.grid.Toolbar', {
    extend: 'Ext.toolbar.Toolbar',
    alias: 'widget.segmentsToolbar',

    requires: [
    	'Editor.view.segments.grid.ToolbarViewModel'
    ],
    //Item Strings: 
    item_viewModesMenu: '#UT#Ansichtsmodus',
    item_viewModeBtn: '#UT#Details (nur Lesemodus)',
    item_editModeBtn: '#UT#Details',
    item_ergonomicModeBtn: '#UT#Normal',
    item_ergonomicModeReadonlyBtn: '#UT#Normal (nur Lesemodus)',
    item_hideTagBtn: '#UT#Tags verbergen',
    item_shortTagBtn: '#UT#Tag-Kurzansicht',
    item_fullTagBtn: '#UT#Tag-Vollansicht',
    item_optionsTagBtn: '#UT#Einstellungen',
    item_zoomIn: '#UT#Segmentschriftgrad vergrößern',
    item_zoomOut: '#UT#Segmentschriftgrad verkleinern',
    item_clearSortAndFilterBtn: '#UT#Tabelle zurücksetzen',
    item_clearSortAndFilterTooltip: '#UT#Sortierung und Filter zurücksetzen',
    item_watchListFilterBtn: '#UT#Lesezeichen',
    item_helpTooltip: '#UT#Tastaturkürzel nachschlagen',
    item_showBookmarkedSegments: '#UT#Nur Segmente mit Lesezeichen anzeigen',
    item_repeatedFilterBtn: '#UT#Nur wiederholt',
    item_showRepeatedSegments: '#UT#Nur Segmente mit Wiederholungen anzeigen',
    item_themeMenuConfigText:'#UT#Layout',
    strings:{
        interfaceTranslation:'#UT#Oberfläche'
    },
    viewModel: {
        type:'segmentsToolbar'
    },
    initConfig: function(instanceConfig) {
            var me = this,
		        config,
		        menu;
            
            menu={
                xtype: 'menu',
                items: [{
                    xtype: 'menuitem',
                    mode:{
                        type: 'editMode'
                    },
                    text: me.item_editModeBtn,
                    group: 'toggleView',
                    textAlign: 'left'
                },{
                    xtype: 'menuitem',
                    mode:{
                        type: 'ergonomicMode',
                    },
                    text: me.item_ergonomicModeBtn,
                    group: 'toggleView',
                    textAlign: 'left'
                },{
                    xtype: 'menuseparator'
                },{
                    xtype: 'menucheckitem',
                    itemId: 'hideTagBtn',
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
                },{
                    xtype: 'menuseparator'
                }]
            };
            
            //add the available translate5 translations
            Ext.Object.each(Editor.data.translations, function(i, n) {
                menu.items.push({
                    xtype: 'menucheckitem',
                    itemId: 'localeMenuItem'+i,
                    checked: Editor.data.locale==i,
                    text: n+' '+me.strings.interfaceTranslation,
                    value:i,
                    tagMode: 'full',
                    group: 'localeMenuGroup'
                });
            });


            // add change user theme only if allowed
            if(Editor.data.frontend.changeUserThemeVisible){
                // add themes menu
                menu.items.push(me.getThemeMenuConfig());
            }

            config = {
                items: [{
                    xtype: 'button',
                    text:me.item_viewModesMenu,
                    itemId: 'viewModeMenu',
                    menu:menu
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
                    text: me.item_watchListFilterBtn,
                    tooltip: {
                        text: me.item_showBookmarkedSegments,
                        showDelay: 0
                    },
                    icon: Editor.data.moduleFolder+'images/show_bookmarks.png'
                },{
                    xtype: 'button',
                    glyph: 'f0c5@FontAwesome5FreeSolid',
                    itemId: 'filterBtnRepeated',
                    text: me.item_repeatedFilterBtn,
                    bind: {
                        hidden: '{!taskHasDefaultLayout}'
                    },
                    enableToggle: true,
                    tooltip: {
                        text: me.item_showRepeatedSegments,
                        showDelay: 0
                    },
                },{
                    xtype: 'tbseparator',
                    hidden: !Editor.data.task.hasMqm()
                },{
                	xtype: 'tbfill'
                },{
                    xtype: 'button',
                    itemId: 'optionsBtn',
                    text: me.item_optionsTagBtn
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
                }]
            };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },

    /***
     * Create the theme menu picker config
     * @returns {{text: string, menu: {items: *[]}}}
     */
    getThemeMenuConfig:function(){
        var me=this,
            config,
            uiThemesRecord = Editor.app.getUserConfig('extJs.cssFile',true),
            menuItems = [];

        Ext.Array.each(uiThemesRecord.get('defaults'), function(i) {
            menuItems.push({
                text: Ext.String.capitalize(i),
                value:i,
                checked: uiThemesRecord.get('value') === i,
                group: 'uiTheme',
                checkHandler: function (item){
                    // on item select, change the user state config, and reload the application
                    // after reload, the user will get the changed theme
                    uiThemesRecord.set('value',item.value);
                    uiThemesRecord.save({
                        callback:function(){
                            location.reload();
                        }
                    });
                }
            });
        });

        config = {
            text:me.item_themeMenuConfigText,
            menu:{
                items:menuItems
            }
        };

        return config;
    }
});