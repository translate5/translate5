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
 * TreeStore für Editor.model.QmSummary
 * @class Editor.store.QmSummary
 * @extends Ext.data.TreeStore
 */
Ext.define('Editor.store.QmSummary', {
    extend : 'Ext.data.TreeStore',
    //model: 'Editor.model.QmSummary',
    //fields of store are generated dynamically, so no model can be used 
    autoLoad: false,
    autoSync: false,
    folderSort: true,
    isLoaded: false,
    listeners: {
        load: {
            fn: function(){ this.isLoaded = true; }
        }
    },
    proxy : {
        type : 'rest',
        url: Editor.data.restpath+'qmstatistics'
    },
    constructor: function(config) {
        var config = arguments[0] || {};
        Ext.applyIf(config, {
            fields: this.generateFields()
        });
        //arguments[0] = fields;
        this.callParent(arguments);
    },
    /**
     * Generates dynamically 
     */
    generateFields: function() {
        var sevCols = [{
                name: 'text',
                type: 'string'
            },{
                name: 'totalTotal',
                type: 'integer'
            },{
                name: 'total',
                type: 'integer'
            },{
                name: 'qmtype',
                type: 'integer'
            }],
            sev = Editor.data.task.get('qmSubSeverities');
        Ext.each(sev, function(severity,index, myself) {
            sevCols.push({
                name: 'total'+ severity.text.charAt(0).toUpperCase() + severity.text.slice(1).toLowerCase(),
                type: 'integer'
            });
            sevCols.push({
                name: severity.text.toLowerCase(),
                type: 'integer'
            });
        });
        return sevCols;
    }
});