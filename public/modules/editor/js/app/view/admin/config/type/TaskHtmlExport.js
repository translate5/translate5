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

/**
 * Config editor class for fixed map configurations. In those kind of configs, only the values are editable ans the user
 * can not add new or remove existing records.
 */
Ext.define('Editor.view.admin.config.type.TaskHtmlExport', {
    //extend: 'Editor.view.admin.config.type.SimpleMap',

    /**
     * Static must be overridden and can not be inherited
     */
    statics: {
        getConfigEditor: function (record) {
            let data = [];
            for (const element of Editor.data.segments.autoStateFlags) {
                data.push([element.id, element.label]);
            }

            return Ext.create('Ext.grid.CellEditor', {
                field: {
                    xtype: 'combo',
                    name: 'value',
                    store: Ext.create('Ext.data.Store', {
                        fields: ['id', 'value'],
                        data : data
                    }),
                    displayField: 'value',
                    valueField: 'id',
                    value:record.get('value'),
                    queryMode: 'local',
                    typeAhead: false
                    //filterPickList: true
                },
                completeOnEnter: false
            });
        },
        renderer: function (value) {
            value = parseInt(value);
            for (const element of Editor.data.segments.autoStateFlags) {
                if (value === element.id) {
                    return element.label;
                }
            }
            return '';
        }
    }
});
