
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

Ext.define('Editor.view.searchandreplace.SearchReplaceWindow', {
    extend: 'Ext.window.Window',
    alias: 'widget.searchreplacewindow',
    itemId: 'searchreplacewindow',
    stateful: true,
    stateId: 'editor.searchreplacewindow',
    requires:[
        'Editor.view.searchandreplace.TabPanel'
    ],
    listeners: {
        beforeclose: function() {
            this.saveState();
        }
    },
    minHeight : 350,
    width : 370,
    constrainHeader: true,
    autoHeight: true,
    layout:'fit',
    x: 10,
    y: 10,
    strings:{
        windowTitle:'#UT#Suchen und ersetzen',
        helpButtonTooltip:'#UT#Info zum Suchen und Ersetzen'
    },
    applyState: function(state) {
        var me = this,
            search = me.down('#searchTab #searchField'),
            ctrl = Editor.app.getController('SearchReplace');
        me.callParent([state]);
        
        //init radio buttons in a stateful way
        Ext.applyIf(state, {
            searchTab: {
                searchType: 'normalSearch'
            },
            replaceTab: {
                searchType: 'normalSearch'
            }
        });

        state.searchTab && me.down('#searchTab').form.setValues(state.searchTab);
        state.replaceTab && me.down('#replaceTab').form.setValues(state.replaceTab);

        //the search fields of replace and search are on a magical way in sync, so we just set one here
        if(search && state.searchValue) {
            search.setValue(state.searchValue);
        }
        //the searchInField is calculated, depending on how the search is started. 
        //the default value is here the controllers defaultColumnDataIndex which we just set here from the state
        if(state.searchInField) {
            ctrl.defaultColumnDataIndex = state.searchInField;
        }
        else {
            ctrl.defaultColumnDataIndex = Editor.controller.SearchReplace.prototype.defaultColumnDataIndex;
        }
    },
    getState: function() {
        var me = this, 
            state = me.callParent();
            
        //first we save all settings in general
        state.searchTab = me.down('#searchTab').getValues();
        state.replaceTab = me.down('#replaceTab').getValues();
                
        //then we deal some fields in a special way:
        // the search fields of replace and search are on a magical way in sync, 
        // so we store only the search field
        delete state.searchTab.searchField;
        delete state.replaceTab.searchField;

        state.searchValue = me.down('#searchTab #searchField').getValue();
        try {
            state.searchInField = me.down('searchreplacetabpanel').getActiveTab().down('#searchInField').getValue();
        }
        catch(e) {
            //if we can not find the field, we just do not save that value
            state.searchInField = null;
        }
        return state;
    },
    initConfig : function(instanceConfig) {
        var me = this,
            config = {
                title:me.strings.windowTitle,
                items:[{
                    flex:1,
                    xtype:'searchreplacetabpanel',
                }],
                tools:[{
                    type:'help',
                    tooltip:me.strings.helpButtonTooltip,
                    handler:function(){
                        window.open('https://confluence.translate5.net/display/BUS/Search+and+replace','_blank');
                    }
                }]
        };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});