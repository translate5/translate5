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
 * @class Editor.store.admin.Languages
 * @extends Ext.data.Store
 */
Ext.define('Editor.store.admin.Languages', {
    extend: 'Ext.data.ArrayStore',
    fields: ['id', 'label', {name: 'rtl', type: 'boolean'}, 'rfc5646'],
    data: Editor.data.languages,

    /***
    * Find language id in store by given rfc value
    * @param rfc
    * @returns {*}
    */
    getIdByRfc: function (rfc) {
        var rec = this.getByRfc(rfc);
        return rec !== null ? rec.get('id') : null;
    },

    /***
     * Find language in store by given rfc value. Case sensitiv is off when searching.
     * @param rfc
     * @returns {*}
     */
    getByRfc: function (rfc) {
        var rec = this.findRecord('rfc5646', rfc, 0, false, false, true);
        return rec !== null ? rec : null;
    },

    /***
     * Return rfc value of a language by given langauge id
     * @param id
     * @returns {string}
     */
    getRfcById: function (id){
        var rec = this.getById(id);
        return rec !== null ? rec.get('rfc5646') : '-';
    },

    /**
     * Return major rfc value of a language by given langauge id
     * @param id
     * @returns {string}
     */
    getMajorRfcById: function (id){
        var rfc = this.getRfcById(id).toLowerCase();
        return (rfc !== '-' && rfc.includes('-')) ? rfc.split('-')[0] : rfc;
    }
});