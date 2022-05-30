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
Ext.define('Editor.plugins.Okapi.model.BconfFilterModel', {
    extend: 'Ext.data.Model',
    storeId: 'bconfFilterStore',
    alias: 'model.bconfFilterModel',
    getId: function(){
        var {bconfId, okapiId} = this.getData();
        return `${bconfId}-.-${okapiId}`; // Slash / as separator will lead to 404
    },
    proxy: {
        idParam: 'id',
        type: 'rest',
        url: Editor.data.restpath + 'plugins_okapi_bconffilter',
        reader: {
            rootProperty: 'rows',
        },
        writer: {
            encode: true,
            rootProperty: 'data',
            writeAllFields: false

        },
        api: {
            read: undefined // is set on grid init with bconfId as filter
        }
    },
    idProperty: 'okapiId',
    fields: [ {
        name: 'okapiId',
        type: 'string',
    }, {
        name: 'bconfId',
        type: 'int',
        reference: 'bconfmodel',
        critical: true
    },{
        name: 'id',
        presist: true,
        calculate: function(data){
            return [data.bconfId, data.okapiId]
        }
},{
        name: 'isCustom',
        type: 'bool',
        defaultValue: true,
        persist: false
    }, {
        name: 'description',
        type: 'string'

    }, {
        name: 'extensions',
        persist: false, // Normal saving can lead to many requests for changed extensions,
        defaultValue: []
    },
    ]
});
