
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
    item_ergonomicModeBtn: '#UT#Ergonimic',
    item_hideTagBtn: '#UT#Tags verbergen',
    item_shortTagBtn: '#UT#Tag-Kurzansicht',
    item_fullTagBtn: '#UT#Tag-Vollansicht',
    item_qmsummaryBtn: '#UT#QM-Subsegment-Statistik',
    item_optionsTagBtn: '#UT#Einstellungen',
    item_clearSortAndFilterBtn: '#UT#Sortierung und Filter zurücksetzen',
    item_watchListFilterBtn: '#UT#Merkliste',
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
                            itemId: 'viewMode',
                            text: me.item_viewModeBtn,
                            group: 'toggleView',
                            textAlign: 'left'
                        },{
                            xtype: 'menucheckitem',
                            itemId: 'editMode',
                            checked: true,
                            text: me.item_editModeBtn,
                            group: 'toggleView',
                            textAlign: 'left'
                        },{
                            xtype: 'menucheckitem',
                            itemId: 'ergonomicMode',
                            text: me.item_ergonomicModeBtn,
                            group: 'toggleView',
                            textAlign: 'left'
                        }]
                    }
                },{
                    xtype: 'tbseparator'
                },{
                    xtype: 'button',
                    disabled: true,
                    itemId: 'hideTagBtn',
                    enableToggle: true,
                    text: me.item_hideTagBtn,
                    tagMode: 'hide',
                    toggleGroup: 'tagMode'
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