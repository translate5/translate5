
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

Ext.define('Editor.store.admin.Config', {
    extend : 'Ext.data.Store',
    model: 'Editor.model.Config',
    alias: 'store.config',
    autoLoad: false,
    remoteFilter: false,
    remoteSort: false,
    pageSize: 0,
    sorters: ['guiGroup', 'guiName'],
    groupField: 'guiGroup',
    RUNTIMEOPTIONS_CONFIG_PREFIX: 'runtimeOptions.',
    
    /***
     * Get the config value from given record. Here also custom value convert is applied.
     */
    getRecordValue:function(record){
        if(!record){
            return null;
        }
        //Convert the map types to object (this is not done by the model instance). 
        if(record.get('type') == 'map'){
            return Ext.JSON.decode(record.get('value'));
        }
        return record.get('value');
    },
    
    /***
     * Search for config value by given name
     */
    getConfig:function(name){
        var me=this,
            hasFilters = me.filters && me.filters.length > 0;
            
         if(hasFilters){
             return me.searchFiltered(name);
         }   
            
        var pos = me.findExact('name',me.RUNTIMEOPTIONS_CONFIG_PREFIX+name),//TODO: get me from const
            row;
        
        if (pos < 0) {
            return null;
        }
        return me.getRecordValue(me.getAt(pos))
    },

    /***
     * Search the config by name including the filtered rows
     */
    searchFiltered:function(name){
        var me=this,
            data = Ext.StoreManager.get('admin.task.Config').getData().getSource(),
            record = data && data.getByKey(me.RUNTIMEOPTIONS_CONFIG_PREFIX+name);
        return me.getRecordValue(record);
    }
});