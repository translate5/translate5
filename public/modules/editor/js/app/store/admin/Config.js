
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
    
    /**
     * Get the config value from given record.
     */
    getRecordValue: function(record){
        if (! record){
            return null;
        }
        return record.get('value');
    },
    
    /**
     * Search for config value by given name
     */
    getConfig: function(name){
         if (this.filters && this.filters.length > 0){
             return this.searchFiltered(name);
         }   
            
        let pos = this.findExact('name', this.RUNTIMEOPTIONS_CONFIG_PREFIX + name);

        if (pos < 0) {
            return null;
        }
        return this.getRecordValue(this.getAt(pos));
    },

    /**
     * Search the config by name including the filtered rows
     */
    searchFiltered: function(name){
        var data = this.getData().getSource(),
            record = data ? data.getByKey(this.RUNTIMEOPTIONS_CONFIG_PREFIX + name) : null;
        return record ? this.getRecordValue(record) : null;
    },
    
    /**
     * Add additional params to the store proxy. The newExtra params will be merged into 
     * the existing proxy extra params
     */
    setExtraParams: function(newExtra){
        var existing = this.getProxy().getExtraParams(),
            merged = Ext.Object.merge(existing, newExtra);
        this.getProxy().setExtraParams(merged);
    }
});