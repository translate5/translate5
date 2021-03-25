
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

/**
 * TreeStore for quality statistics
 * @class EEditor.store.quality.Statistics
 * @extends Ext.data.TreeStore
 */
Ext.define('Editor.store.quality.Statistics', {
    extend : 'Ext.data.TreeStore',
    // fields of store are generated dynamically, so no model can be used 
    autoLoad: false,
    autoSync: false,
    folderSort: true,
    isLoaded: false,
    proxy : {
        type : 'rest',
        appendId: false,
        reader: {
            type : 'json'
        },
        url: Editor.data.restpath+'quality/statistics'
    },
    root: {
        expanded: true,
        text: "My Root"
    },
    constructor: function(config) {
        var config = arguments[0] || {};
        Ext.applyIf(config, {
            fields: this.generateFields()
        });
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
            sev = Editor.data.task.get('mqmSeverities');
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
