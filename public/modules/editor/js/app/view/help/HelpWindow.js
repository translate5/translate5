
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

Ext.define('Editor.view.help.HelpWindow', {
    extend: 'Editor.view.StatefulWindow',
    requires:[
        'Editor.view.help.Video',
        'Editor.view.help.Documentation'
    ],
    alias: 'widget.helpWindow',
    itemId: 'helpWindow',
    stateId:'helpWindow',
    reference: 'helpWindow',
    cls: 'helpWindow',
    autoScroll: true,
    modal : true,
    layout:'fit',
    viewModel:true,
    bind:{
        doNotShowAgain:'{cbDoNotShowAgain.checked}'
    },
    strings:{
        cbDoNotShowAgainLabel:'#UT#Dieses Fenster nicht mehr automatisch anzeigen.',
        documentationTitle: '#UT#PDF Dokumentation'
    },
    statics:{
        //get the current window state id without creating the window
        getStateIdStatic:function(){
            return 'helpWindow.'+Editor.data.helpSection;
        }
    },
    
    getStateId:function(){
        var me=this,
            original=me.callParent();
        return original+'.'+Editor.data.helpSection;
    },

    initConfig: function(instanceConfig) {
        var me = this,
            config = {
                //max height 750, if viewport is smaller use max 95% of the viewport.
                height: Math.min(750, parseInt(Editor.app.viewport.getHeight() * 0.95)),
                //max width 1024, if viewport is smaller use max 95% of the viewport.
                width: Math.min(1024, parseInt(Editor.app.viewport.getWidth() * 0.95)),
                items:me.getHelpWindowItems(),
                dockedItems: [{
                    xtype: 'toolbar',
                    dock: 'bottom',
                    hidden:me.isComponentHidden(),
                    items: [{
                        xtype:'checkboxfield',
                        boxLabel:me.strings.cbDoNotShowAgainLabel,
                        reference: 'cbDoNotShowAgain',
                        publishes: 'value',
                        name:'cbDoNotShowAgain',
                        itemId:'cbDoNotShowAgain',
                        bind:{
                            value:'{helpWindow.doNotShowAgain}'
                        }
                    }]
                }]
            };

        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },

    /***
     * Get help window items configuration. When there are more than one item to be show in the help section (video and docu),
     * the items will be added in tabpanel
     */
    getHelpWindowItems:function (){
        var me=this,
            helpsection = Editor.data.frontend.helpWindow[Editor.data.helpSection],
            items = [];

        if(helpsection.loaderUrl !== undefined && helpsection.loaderUrl !== ""){
            items.push({
                xtype:'helpVideo',
                title:'Video'
            });
        }
        if(helpsection.documentationUrl !== undefined && helpsection.documentationUrl !== ""){
            items.push({
                xtype:'helpDocumentation',
                title:me.strings.documentationTitle
            });
        }

        if(items.length === 0){
            return [];
        }

        // for single item, title is not needed
        if(items.length === 1){
            items[0].title = null;
            return items;
        }

        // pack them in tab panel
        return [{
            xtype:'tabpanel',
            items:items
        }];
    },

    /***
     * Get the window state record from the state provider(this state is not the actual state of the window, it is the state record in the
     * state provider store)
     */
    getProviderState:function(){
        return Ext.state.Manager.getProvider().get(this.self.getStateIdStatic());
    },
    /***
     * The component is not visible when the there is not state config for the window type
     */
    isComponentHidden:function(){
        var state=this.getProviderState();
        return state===undefined || state==="";
    }
});