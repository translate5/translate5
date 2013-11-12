/*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor Javascript GUI and build on ExtJs 4 lib
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics; All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com
 
 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty
 for any legal issue, that may arise, if you use these FLOSS exceptions and recommend
 to stick to GPL 3. For further information regarding this topic please see the attached 
 license.txt of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
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
 */
Ext.define('Editor.model.Segment', {
//  requires: [
//    'Editor.model.Terminologie'
//  ],
  extend: 'Ext.data.Model',
  fields: [
    {name: 'id', type: 'int'},
    {name: 'fileId', type: 'int'},
    {name: 'segmentNrInTask', type: 'int'},
    {name: 'source', type: 'string'},
    {name: 'sourceEdited', type: 'string'},
    {name: 'relais', type: 'string'},
    {name: 'target', type: 'string'},
    {name: 'edited', type: 'string'},
    {name: 'userName', type: 'string'},
    {name: 'timestamp', type: 'date'},
    {name: 'editable', type: 'boolean'},
    {name: 'autoStateId', type: 'int'},
    {name: 'workflowStep', type: 'string'},
    {name: 'matchRate', type: 'int'},
    //{name: 'terms', type: 'string'},
    {name: 'comments', type: 'string', persist: false},
    {name: 'qmId', type: 'string'},
    {name: 'stateId', type: 'int'}
  ],
  idProperty: 'id',
//  hasMany: {model: 'Editor.model.Terminologie', name: 'terms'},
  proxy : {
    type : 'rest',
    url: Editor.data.restpath+'segment',
    reader : {
      root: 'rows',
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