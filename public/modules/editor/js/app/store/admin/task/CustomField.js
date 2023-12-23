/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * @extends Ext.data.Store
 */
Ext.define('Editor.store.admin.task.CustomField', {
    extend: 'Ext.data.Store',
    requires: ['Editor.model.admin.task.CustomField'],
    model: 'Editor.model.admin.task.CustomField',
    autoLoad: false,

    /**
     * Retrieves all records independently of filtering
     *
     * @see https://forum.sencha.com/forum/showthread.php?310616
     * @returns {Ext.util.Collection}
     */
    getUnfilteredData: function(){
        return (this.isFiltered() || this.isSorted()) ? this.getData().getSource() : this.getData();
    },

    /**
     * Creates the data-source for the import wizard
     * This store will only contain the customers task custom fields or the presets bound to no customer
     *
     * @param {int} customerId
     * @returns {Ext.data.Store}
     */
    /*createImportWizardCustomFieldsMetaData(customerId){
        var cid, item, items = [];
        this.getUnfilteredData().each(record => {
            cid = record.get('customerId');
            if (cid === null || cid === customerId) {
                item = {
                    'id': record.id,
                    'label': record.get('label'),
                    'tooltip': record.get('tooltip'),
                    'type': record.get('type'),
                    'picklistData': record.get('picklistData'),
                    'regex': record.get('regex'),
                    'mode': record.get('mode'),
                    'cid': (cid === null ? 0 : cid),
                    'placesToShow': record.get('placesToShow'),
                    'position': record.get('position')
                };
                // if no customer specific default given we look for the system default (which can only be attached to a record with id 'null'
                items.push(item);
            }
            return true;
        });
        return Ext.create('Ext.data.Store', {
            fields: ['id', 'label', 'tooltip', 'type', 'picklistData', 'regex', 'mode', 'cid', 'placesToShow', 'position'],
            sorters: [
                { 'property': 'cid', 'direction': 'DESC' },
                { 'property': 'position', 'direction': 'ASC' }
            ],
            data : items
        });
    }*/
});