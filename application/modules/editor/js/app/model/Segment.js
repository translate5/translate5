
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
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
 * 
 * redefinition algorithm tested in 4.0.7 and 4.2
 */
if(!Editor.model) {
    Editor.model = {};
}
Editor.model._Segment = function(fields) {
    if(fields instanceof Ext.data.Store) {
        var newFields = [];
        fields.each(function(rec) {
            newFields.push({name: rec.get('name'), type: 'string'});
            if(rec.get('editable')) {
                newFields.push({name: rec.get('name')+'Edit', type: 'string'});
            }
        });
        fields = Ext.Array.merge(Editor.model._Segment.defaultFields, newFields);
    }
    else {
        Editor.model._Segment.defaultFields = fields;
    }
    Ext.define('Editor.model.Segment', {
        statics: {
            redefine: Editor.model._Segment
        },
        extend: 'Ext.data.Model',
        fields: fields,
        idProperty: 'id',
        proxy : {
            type : 'rest',
            url: Editor.data.restpath+'segment',
            reader : {
                root: 'rows',
                //intercept readRecords method to set segments meta info only on store reads, not on plain model reads
                readRecords: function(data) {
                    if(data && data.firstSegmentId) {
                        //first editiable segment, not first at all!
                        this.firstSegmentId = data.firstSegmentId;
                    }
                    if(data && data.lastSegmentId) {
                        //last editiable segment, not first at all!
                        this.lastSegmentId = data.lastSegmentId;
                    }
                    return this.self.prototype.readRecords.apply(this, arguments);
                },
                type : 'json'
            },
            writer: {
                encode: true,
                root: 'data',
                writeAllFields: false
            }
        },
        /**
         * konvertiert die serverseitig als string gespeicherte QM Liste in ein Array
         * @returns Integer[]
         */
        getQmAsArray: function (){
            return Ext.Array.map(this.get('qmId').replace(/^[;]+|[;]+$/g, '').split(';'), function(item){
                return parseInt(item);
            });
        },
        /**
         * konvertiert ein Array mit QmIds zurück in das serverseitig benötigte String Format
         * @param {Integer[]} qmArray
         */
        setQmFromArray: function (qmArray){
            if(qmArray.length > 0){
                this.set('qmId', ';'+qmArray.sort().join(';')+';');
            }
            else {
                this.set('qmId', '');
            }
        }
    });
};

//default fields:
Editor.model._Segment([
    {name: 'id', type: 'int'},
    {name: 'fileId', type: 'int'},
    {name: 'segmentNrInTask', type: 'int'},
    {name: 'userName', type: 'string'},
    {name: 'timestamp', type: 'date'},
    {name: 'editable', type: 'boolean'},
    {name: 'autoStateId', type: 'int'},
    {name: 'workflowStep', type: 'string'},
    {name: 'workflowStepNr', type: 'integer', persist: false},
    {name: 'matchRate', type: 'int'},
    //{name: 'terms', type: 'string'},
    {name: 'durations', defaultValue: {}}, //we are using an object here
    {name: 'comments', type: 'string', persist: false},
    {name: 'qmId', type: 'string'},
    {name: 'stateId', type: 'int'}
]);