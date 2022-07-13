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
 * @extend Editor.plugins.Okapi.view.FprmEditor
 */
Ext.define('Editor.plugins.Okapi.view.fprm.Properties', {
    extend: 'Editor.plugins.Okapi.view.FprmEditor',
    alternateClassName: [],

    parseFprm: function(fprm){
        var ret = {};
        fprm.split('\n').forEach(function(line){
            if(!line.startsWith('#')){
                const [name, value] = line.split('=')
                ret[name] = value;
            }
        })
        return ret;
    },

    updateFprm(fprm){
        this.callParent(arguments);

    },

    setupForm(keyValues){
        var me = this;
        f= me.formPanel
        // TODO BCONF Load json description
        Object.entries(keyValues).forEach(function addField([key, value]){
            const [id, typeSuffix] = key.split('.'),
                type = me.suffixMap[typeSuffix],
                fieldConfig = {
                    xtype: type,
                    id,
                    fieldLabel: id,
                    labelWidth: 'auto',
                    name: key,
                    value
                }
            //TODO: add configs from description json
            me.formPanel.add(fieldConfig)
        })
    },

    suffixMap: {
        b: 'checkbox',
        i: 'numberfield',
        s: 'textfield',
        undefined: 'textfield'
    },

    compileFprm(){
        this.setLoading(false)
    },

});