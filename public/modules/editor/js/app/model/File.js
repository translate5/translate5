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

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.model.File
 * @extends Ext.data.Model
 */
Ext.define('Editor.model.File', {
    extend: 'Ext.data.Model',
    fields: [
        {name: 'text', type: 'string', mapping: 'filename'},
        {name: 'id', type: 'int'},
        {name: 'parentId', type: 'int', critical: true},
        {
            name: 'href', type: 'string', convert: function (v) {
                //relative path fix since browser url does not always end with "/"
                if (v && v.length > 0) {
                    //encode the route and the filename
                    var parts = v.split('/');
                    for (var i = 0; i < parts.length; i++) {
                        parts[i] = encodeURIComponent(parts[i]);
                    }
                    return Editor.data.restpath + parts.join('/');
                }
                return v;
            }
        },
        'cls',
        {name: 'qtip', type: 'string', mapping: 'filename'},
        {name: 'leaf', type: 'boolean', mapping: 'isFile', defaultValue: false},
        {name: 'segmentid', type: 'int', defaultValue: 0},
        {name: 'index', type: 'int', critical: true}
    ],
    idProperty: 'id',
    proxy: {
        type: 'rest',
        url: Editor.data.restpath + 'filetree',
        writer: {
            encode: true,
            rootProperty: 'data'
        }
    },
    constructor: function () {
        this.callParent(arguments);
        //enabling loading indexAction for id === 0
        Ext.override(this.getProxy(), {
            isValidId: function (id) {
                return id || id > 0;
            }
        });
    }
});