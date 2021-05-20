
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

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.model.Segment
 * @extends Ext.data.Model
 * @param {Array|Ext.data.Store} Segment Field Definitions. As Array: ready to use field config. As Store: segment fields store!
 */
Ext.define('Editor.model.Segment', {
    statics: {
        redefine: function(fluentFields) {
            var me = this,
                newFields = [];
            if(fluentFields instanceof Ext.data.Store) {
                fluentFields.each(function(rec) {
                    newFields.push({name: rec.get('name'), type: 'string'});
                    if(rec.get('editable')) {
                        newFields.push({name: rec.get('name')+'Edit', type: 'string'});
                    }
                });
                fluentFields = newFields;
            }
            me.replaceFields(fluentFields, me.previousFluentFields);
            me.previousFluentFields = fluentFields.map(function(item){
                return item.name;
            });
        }
    },
    extend: 'Ext.data.Model',
    fields: [
        {name: 'id', type: 'int'},
        {name: 'fileId', type: 'int'},
        {name: 'isFirstofFile', type: 'boolean', persist: false, defaultValue: false},
        {name: 'segmentNrInTask', type: 'int'},
        {name: 'userName', type: 'string'},
        {name: 'timestamp', type: 'date'},
        {name: 'editable', type: 'boolean', persist: false},
        {name: 'pretrans', type: 'boolean', persist: false},
        {name: 'autoStateId', type: 'int'},
        {name: 'workflowStep', type: 'string'},
        {name: 'workflowStepNr', type: 'integer', persist: false},
        {name: 'matchRate', type: 'int'},
        {name: 'matchRateType', type: 'string'},
        //{name: 'terms', type: 'string'},
        {name: 'durations', defaultValue: {}}, //we are using an object here
        {name: 'comments', type: 'string', persist: false},
        {name: 'stateId', type: 'int'},
        {name: 'metaCache', convert: function(val) {
            if(Ext.isObject(val)){
                return val;
            }
            if(!val || val===""){
                return null;
            }
            return Ext.JSON.decode(val);
        }, persist: false},
        {name: 'isWatched', type: 'boolean', persist: false},
        {name: 'isRepeated', type: 'int', persist: false},
        {name: 'segmentUserAssocId', type: 'int', persist: false}
    ],
    idProperty: 'id',
    proxy : {
        type : 'rest',
        url: Editor.data.restpath+'segment',
        reader : {
            rootProperty: 'rows',
            type : 'json'
        },
        writer: {
            encode: true,
            rootProperty: 'data',
            writeAllFields: false
        }
    },
    /**
     * Updates the segment length in the metaCache for the given editable field name
     * @param {String} fieldname for which the length is changed
     * @param {Integer} new length value
     */
    updateMetaCacheLength: function (field, length) {
        var id = this.get('id'), 
            meta = this.get('metaCache');
        if(meta && meta.siblingData && meta.siblingData[id]) {
            meta.siblingData[id].length[field] = length;
            this.set('metaCache', meta);
        }        
    }
});
