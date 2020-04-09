
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

Ext.define('Editor.view.HelpWindow', {
    extend: 'Editor.view.StatefulWindow',
    alias: 'widget.helpWindow',
    itemId: 'helpWindow',
    stateId:'helpWindow',
    reference: 'helpWindow',
    cls: 'helpWindow',
    minHeight : 750,
    width : 1024,
    autoHeight: true,
    autoScroll: true,
    modal : true,
    layout:'fit',
    viewModel:true,
    bind:{
    	doNotShowAgain:'{cbDoNotShowAgain.checked}'
    },
    strings:{
    	cbDoNotShowAgainLabel:'#UT#Dieses Fenster nicht mehr automatisch anzeigen.'
    		//do not automaticly show this window
    },
    getStateId:function(){
    	var me=this,
    		original=me.callParent();
    	return original+'.'+Editor.data.helpSection;
    },
    
    initConfig: function(instanceConfig) {
        var me = this,
            config = {
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
     * The component is not visible when the there is not state config for the window type
     */
    isComponentHidden:function(){
    	var state=Ext.state.Manager.getProvider().get(this.getStateId());
		return state===undefined || state==""
    }
});