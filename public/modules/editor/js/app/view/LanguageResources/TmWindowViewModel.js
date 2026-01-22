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
 * @class Editor.view.LanguageResources.TmWindowViewModel
 * @extends Ext.app.ViewModel
 */
Ext.define('Editor.view.LanguageResources.TmWindowViewModel', {
    extend: 'Ext.app.ViewModel',
    alias: 'viewmodel.tmwindow',
    data: {
        serviceName: false,
        resourceType: false,
        resourceId: null,
        uploadLabel: null,
        engineBased: null,
        useEnginesCombo: true,
        domainCodePreset: '',
        strippingFramingTagsSupported: false,
        resegmentationSupported: false,
    },
    stores: {
        customers: {
            model: 'Editor.model.admin.Customer',
            pageSize: 0,
            autoLoad: true
        },
        customersDefaultRead: {
            source: '{customers}',
            pageSize: 0,
            filters: {
                property: 'id',
                operator: "in",
                value: '{resourcesCustomers.value}'
            }
        },
        customersDefaultWrite: {
            source: '{customersDefaultRead}',
            pageSize: 0,
            filters: {
                property: 'id',
                operator: "in",
                value: '{useAsDefault.value}'
            }
        },
        customersDefaultPivot: {
            source: '{customers}',
            pageSize: 0,
            filters: {
                property: 'id',
                operator: "in",
                value: '{resourcesCustomers.value}'
            }
        }
    },
    formulas: {
        useEnginesListCombo: function (get) {
            return get('engineBased') && get('useEnginesCombo');
        },
        useEngineTextfield: function (get) {
            return get('engineBased') && !get('useEnginesCombo');
        },
        hasDomaincodeTextfield: function (get) {
            return get('domainCodePreset') === '' && !get('useEnginesCombo');
        },
        getDomaincodePreset: function (get) {
            return get('domainCodePreset');
        },
        isTermCollectionResource: function (get) {
            return get('serviceName') === Editor.model.LanguageResources.Resource.TERMCOLLECTION_SERVICE_NAME;
        },
        isTmResourceType: function (get) {
            return get('resourceType') === Editor.util.LanguageResources.resourceType.TM;
        },
        isTqeResource: function (get) {
            return ['Llama','OpenAI','Azure','AzureOpenAI'].includes(get('serviceName'));
        },
        isStrippingFramingTagsSupported: function (get) {
            return get('strippingFramingTagsSupported');
        },
        isResegmentingTmxSupported: function(get) {
            return get('resegmentationSupported');
        }
    }
});
