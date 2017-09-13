
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

/**
 * @class SearchReplace
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.SearchReplace', {
    extend : 'Ext.app.Controller',
    
    requires:['Editor.view.searchandreplace.SearchReplaceWindow'],
    listen:{
        component:{
            '#segmentgrid':{
                afterrender:'onSegmentGridAfterRender'
            }
        }
    },
    
    showSarchReplaceIn:['source','target'],

    /***
     * On segment grid after render handler
     * Here we add the search and replace menu item on the needed column
     */
    onSegmentGridAfterRender:function(segmentGrid){
        var me=this,
            menu = segmentGrid.headerCt.getMenu();
        
        me.addSearchReplaceMenu(menu);
        
        menu.on({
            beforeshow:{
                fn:me.onSegmentGridMenuBeforeShow,
                scope:me
                
            }
        });
    },
    
    addSearchReplaceMenu:function(gridMenu){
        // add menu item  into the menu and store its reference
        var me=this,
            menuItem = gridMenu.add({
                itemId:'searchReplaceMenu',
                text: 'Search and replace window',
                iconCls:'x-fa fa-search',
                handler:me.showSearchAndReplaceWindow
            });
    },
    
    onSegmentGridMenuBeforeShow:function(menu){
        // get data index of column for which menu will be displayed
        var me=this,
            searchReplaceMenu=menu.down('#searchReplaceMenu'),
            currentDataIndex = menu.activeHeader.dataIndex;
        // show/hide menu item in the menu
        if(Ext.Array.contains(me.showSarchReplaceIn,currentDataIndex)) {
            searchReplaceMenu.show();
            return;
        }
        searchReplaceMenu.hide();
    },
    
    showSearchAndReplaceWindow:function(){
        var searchReplaceWindow=Ext.create('Editor.view.searchandreplace.SearchReplaceWindow');
        searchReplaceWindow.show();
    }
});