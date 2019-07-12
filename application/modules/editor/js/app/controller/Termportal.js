
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
 * @class Editor.controller.Termportal
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.Termportal', {
    extend : 'Ext.app.Controller',
    
    refs:[{
        ref: 'headToolBar',
        selector: 'headPanel toolbar#top-menu'
    }],

    listen: {
        component: {
            'headPanel toolbar#top-menu': {
                afterrender: 'onHeadPanelAfterRender'
            },
            'viewport container[region="center"] panel':{
                hide:'onCentarPanelComponentAfterLayout'
            }
        },
        controller:{
            '#Editor.$application': {
                editorViewportOpen: 'onEditorViewportOpen'
            }
        }
    },

    strings:{
        termPortal:'#UT#Terminologieportal'
    },
    
    /***
     * hide the termportal button when editor is opened
     */
    onEditorViewportOpen:function(){
        this.getHeadToolBar() && this.getHeadToolBar().down('#btnTermPortal').setHidden(true);
    },

    /**
     * On head panel after render handler
     */
    onHeadPanelAfterRender: function(toolbar) {
        //if we are in edit task mode, do not add the portal button
        if(Ext.ComponentQuery.query('#segmentgrid')[0]){
            return;
        }
        var me=this;
        
        if(!me.isTermportalAllowed()){
            return;
        }
        var pos = toolbar.items.length - 2;
        toolbar.insert(pos, {
            xtype: 'button',
            itemId: 'btnTermPortal',
            text:me.strings.termPortal,
            listeners:{
                click:{
                    fn:'onTermPortalButtonClick',
                    scope:me
                }
            }
        });
    },

    /***
     * Term portal button handler
     */
    onTermPortalButtonClick:function(){
        if(this.isTermportalAllowed()){
            var apiUrl=Editor.data.restpath+'termportal',
                appName='termportal',
                url=Editor.data.restpath+'apps?name='+appName+'&apiUrl='+apiUrl;
            window.open(url, 'termportalandinstanttranslate').focus();
            // Yet, this still does not always re-focus an already existing Termportal-Tab:
            // - "Firefox (51.) gets the handle but cannot run any Element.focus() " 
            //   (https://developer.mozilla.org/en-US/docs/Web/API/Window/open#Note_on_use_of_window_open)
            // - "It may fail due to user settings and the window isn't guaranteed to be frontmost before this method returns." 
            //   (https://developer.mozilla.org/en-US/docs/Web/API/Window/focus)
        }  
    },
    
    /***
     * Fires when the components in this container are arranged by the associated layout manager.
     */
    onCentarPanelComponentAfterLayout:function(){
        if(!this.isTermportalAllowed()){
            return;
        }
        //set the component to visible on each centar panel element hide
        this.setTermportalButtonHidden(false);
    },

    /**
     * Set the term portal button hidden property
     */
    setTermportalButtonHidden:function(isHidden){
        if(!this.getHeadToolBar() || !this.getHeadToolBar().down('#btnTermPortal')){
            return;
        }
        this.getHeadToolBar().down('#btnTermPortal').setHidden(isHidden);
    },

    /**
     * Check if the user has right to use the term portal
     */
    isTermportalAllowed:function(){
        var userRoles=Editor.data.app.user.roles.split(",");
        return (Ext.Array.indexOf(userRoles, "termCustomerSearch") >= 0) || (Ext.Array.indexOf(userRoles, "termProposer") >= 0) ;
    }
});
    