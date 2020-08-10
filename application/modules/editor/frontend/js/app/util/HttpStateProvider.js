
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
/***
 * State provider which manages the translate5 component state. 
 * Each stateful component state is saved in the database separately for each user,
 */
Ext.define('Editor.util.HttpStateProvider',{
	extend: 'Ext.state.Provider',
    requires: [ 'Editor.store.UserConfig' ],
    uses:[
    	'Ext.state.Provider',
    	'Ext.util.Observable' 
	],
    /**
     * The internal store.
     */
    store: null,

    /**
     * If set to true (default), the store's write event will be buffered to avoid multiple calls at the same time.
     */
    buffered: true,

    /**
     * Defines the buffer time (in milliseconds) for the buffered store.
     */
    writeBuffer: 2000,

    /***
     * Prefix for the state record name in the store
     */
    DEFAULT_STATE_PREFIX:'runtimeOptions.frontend.defaultState.',
    
    constructor: function (config) {
        config = config || {};
        var me = this;
        Ext.apply(me, config);

        if (!me.store) {
            me.store = Ext.create('Editor.store.UserConfig');
        }
        
        me.callParent(arguments);
        
        if (me.buffered) {
            me.on({
                'statechange': {
                    scope : me,
                    buffer: me.writeBuffer,
                    fn    : me.sync
                }
            });
        } else {
            me.on({
                'statechange': {
                    scope: me,
                    fn   : me.sync
                }
            });
        }
    },
    
    /***
     * Set state propertie in the store. If no record is found add new.
     */
    set: function (name, value) {
        var me = this,
        	pos = me.findRecordIndex(name), 
        	row;

        if(pos < 0){
        	return;
        }
        row = me.store.getAt(pos);
        //if the store value is empty, this config is disabled (the default value in zf_config is empty)
        if(row.get('value')==""){
        	return;
        }
        row.set('value', me.encodeValue(value));
        me.fireEvent('statechange', me, name, value);
    },

    /***
     * Get the state record from the store by name.
     */
    get: function (name, defaultValue) {
        var me = this,
        	pos = me.findRecordIndex(name), 
        	row, 
        	value;
        if (pos > -1) {
            row = me.store.getAt(pos);
            value = me.decodeValue(row.get('value'));
        } else {
            value = defaultValue;
        }
        return value;
    },

    /***
     * Remove state record by name
     */
    clear: function (name) {
        var me = this,
        	pos = me.findRecordIndex(name);
        if (pos > -1) {
            me.store.removeAt(pos);
            me.fireEvent('statechange', me, name, null);
        }
    },

    /***
     * Sync the store records with the database
     */
    sync: function () {
    	this.store.sync();
    },
 
    /***
     * Encode the record value as json string
     */
    encodeValue:function(value){
    	if(!value || value==""){
    		return "";
    	}
    	return JSON.stringify(value);
    },
    
    /***
     * Parse the json value
     */
    decodeValue:function(value){
    	if(!value || value==""){
    		return "";
    	}
    	return JSON.parse(value);
    },
    
    /***
     * Find the record for the stateful component.
     */
    findRecordIndex:function(name){
    	var me=this,
    		originalName=me.DEFAULT_STATE_PREFIX+name;
    	return this.store.findExact('name', originalName);
    }

});