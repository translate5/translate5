
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
    
    /**
     * if disabled, the state provider does just not save the changed states until it is enabled again
     */
    disabled: false,
    
    /***
     * Prefix for the state record name in the store
     */
    DEFAULT_STATE_PREFIX: ['runtimeOptions.frontend.defaultState'],
    
    constructor: function (config) {
        config = config || {};
        var me = this;
        Ext.apply(me, config);

        if (!me.store) {
            me.store = Ext.create('Editor.store.UserConfig',{
                autoLoad:true
            });
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
    
    enable: function() {
        this.disabled = false;
    },
    
    disable: function() {
        this.disabled = true;
    },
    
    /***
     * Set state propertie in the store. If no record is found add new.
     */
    set: function (name, value) {
        var me = this,
            pos = me.findRecordIndex(name), 
            row, val;

        if(pos < 0 || me.disabled){
            return;
        }
        row = me.store.getAt(pos);
        val = row.get('value');
        //if the store value is empty, this config is disabled (the default value in zf_config is empty)
        if(Ext.isEmpty(val)){
            return;
        }
        row.set('value', value);
        me.fireEvent('statechange', me, name, value);
    },

    /***
     * Get the state record from the store by name.
     */
    get: function (name, defaultValue) {
        var me = this,
            pos = me.findRecordIndex(name),
            row;
            
        if (pos < 0) {
            return defaultValue;
        }
        row = me.store.getAt(pos);
        return row.get('value');
    },

    /***
     * Remove state record by name
     * TODO, currently not used, unclear if this should be the same/similar as the reset method  
     */
    clear: function (name) {
        var me = this,
            pos = me.findRecordIndex(name);

        if (pos > -1 && !me.disabled) {
            me.store.removeAt(pos);
            me.fireEvent('statechange', me, name, null);
        }
    },

    /***
     * Sync the store records with the database
     */
    sync: function () {
        var me=this;
    	me.store.sync({
            success:function(){
                me.fireEvent('statesynchronized', me);
            }
        });
    },
    
    /**
     * Find the record for the stateful component.
     * @param {String} stateId Is the stateId, already including subpaths if defined
     */
    findRecordIndex: function(stateId){
        return this.store.findExact('name', this.getCanonicalPath(stateId));
    },
    /**
     * returns true if the given Component has a custom state stored
     * @param {Ext.state.Stateful}
     * @return {Boolean}
     */
    hasCustomState: function(comp) {
        var id = comp.stateId;
        if(!id) {
            return false;
        }
        return this.getCustomStateIds(id).length > 0;
    },
    /**
     * returns true if custom states are stored
     * @param {String/String[]} subpaths The subpath or name or an array of subpaths with name.
     * @return {Boolean}
     */
    hasCustomStates: function(subpath) {
        var me = this;
        return me.getCustomStateIds(subpath).length > 0;
    },
    
    /**
     * returns a list with the stateIds having a custom state
     * @param {String/String[]} subpaths The subpath or name or an array of subpaths with name.
     * @return {Array}
     */
    getCustomStateIds: function(subpath) {
        var me = this,
            result = [];
        me.getConfigRecords(subpath).each(function(rec){
            var val = rec.get('value');
            if(Ext.isObject(val) && !Ext.Object.isEmpty(val)) {
                //return the stateIds (name - prefix)
                result.push(rec.get('name').substring(me.DEFAULT_STATE_PREFIX[0].length + 1));
            }
        });
            
        return result;
    },
    
    /**
     * resets all stored user states so that the defaults are used
     
     * @param {String/String[]} subpaths The subpath or name or an array of subpaths with name.
     */
    reset: function(subpath) {
        var me = this,
            remove;

        remove = me.getConfigRecords(subpath);
        if(remove.length === 0){
            return;
        }
        remove.each(function(rec){
            //set the value to empty map (this will not remove the state record from the store)
            rec.set('value', {});
        })
        Ext.state.Manager.getProvider().sync();
    },
    
    /**
     * Get all state records, optionally of a sub path
     * @param {String/String[]} subpaths The subpath or name or an array of subpaths with name.
     * @return Editor.model.UserConfig[]
     */
    getConfigRecords: function(subpath){
        return this.store.query('name', this.getCanonicalPath(subpath));
    }, 
    
    /**
     * returns the canonical config path to a state
     * 
     * @param {String/String[]} subpaths The subpath or an array of subpaths.
     * @return {String} The whole config name as string
     */
    getCanonicalPath: function(subpaths) {
        subpaths =  (typeof subpaths === 'string') ? [subpaths] : subpaths;
        return this.DEFAULT_STATE_PREFIX.concat(subpaths).join('.');
    }
});