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
 * Represents an entry in the match_analysis_pricing_preset_prices-table
 * Such an entry is mapped to a certain presetId and languageId,
 * and contains prices for each matchrate-range defined for that presetId
 *
 * Model like
 * {
 *  "id": 123,
 *  "presetId": 234,
 *  "languageId": "4",
 *  "currency": "$",
 *  "pricesByRangeIds": ["rangeId1": 0.0005, "rangeId2": 0.0003],
 *  "noMatchPrice": 0.0003
 * }
 * @link \MittagQI\Translate5\Plugins\MatchAnalysis\Models\Pricing\PresetPrices::getByPresetId
 */

Ext.define('Editor.plugins.MatchAnalysis.model.admin.pricing.PresetPricesModel', {
    extend: 'Ext.data.Model',
    alias: 'model.pricingpresetpricesModel',
    idProperty: 'id',
    proxy: {
        type: 'rest',
        url: Editor.data.restpath + 'plugins_matchanalysis_pricingpresetprices',
        reader: {
            rootProperty: 'rows',
        },
        api: {
            /** @link self.proxy.setPresetId sets this for easy filtering */
            read: undefined
        },

        /**
         * Sets the id of the currently opened pricing preset to add GET-param to read-url
         *
         * @see Editor.view.admin.pricing.PresetPricesGrid.initComponent
         */
        setPresetId: function(presetId){
            this.api.read = this.getUrl() + '?presetId=' + presetId;
        }
    },
    fields: [{
        name: 'id',
        type: 'int'
    }, {
        name: 'presetId',
        type: 'int',
        reference: 'Editor.model.admin.pricing.PresetModel',
        defaultValue: 0 /** @see self.proxy.setPresetId */
    }, {
        name: 'languageId',
        type: 'int'
    }, {
        name: 'currency',
        convert: v => Ext.String.htmlEncode(v),
        type: 'string'
    }, {
        name: 'pricesByRangeIds',
        convert: v => Ext.String.htmlEncode(v),
        type: 'string'
    }, {
        name: 'noMatch',
        type: 'number'
    }]
});
