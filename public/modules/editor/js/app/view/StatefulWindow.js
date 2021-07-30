
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

/***
 * Stateful window component. When this component is used, the component states: width,height,position,doNotShowAgain
 * will be saved.
 */
Ext.define('Editor.view.StatefulWindow', {
    extend: 'Ext.window.Window',

    /**
     * @cfg {Boolean} doNotShowAgain
     */
    doNotShowAgain:false,
    
    publishes: {
        //publish this field so it is bindable
        doNotShowAgain: true
    },
    
    //events to trigger the sataet update
    stateEvents:['doNotShowAgainChange'],

    stateful:{
        doNotShowAgain:true,
        //Info: those fields are enabled by default when a window is set to statefull
        //we do not need them so far, since it will make the the databadse configuration dificult (more properties to handle)
        width:false,
        height:false,
        maximized:false,
        size:false,
        pos:false
    },
    
    /***
     * add our custom config to the state return object
     */
    getState: function() {
        var me = this,
            state = me.callParent() || {};
        state = me.addPropertyToState(state, 'doNotShowAgain');
        return state;
    },
    
    /***
     * After applying the default component states, add the custom one
     */
    applyState: function(state) {
        if(Ext.isEmpty(state) || Ext.Object.isEmpty(state)){
            return;
        }
        //INFO: do not call parent, since all default state properties are disabled(see stateful object above)
        //if we want to save the other state props, enable call parent and remove the disabled object from stateful
        //this.callParent(arguments)
        this.setDoNotShowAgain(state.doNotShowAgain);
    },
    
    setDoNotShowAgain:function(value){
        var me=this;
        if (me.doNotShowAgain != value) {
            me.doNotShowAgain = value;
            if (me.rendered && me.isVisible()) {
                //trigger the state update
                me.fireEvent('doNotShowAgainChange',value);
            }
        }
    },
    
    getDoNotShowAgain:function(){
        return this.doNotShowAgain;
    }
});