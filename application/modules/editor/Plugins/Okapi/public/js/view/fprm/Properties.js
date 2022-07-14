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

    fieldConfigs: {},
    initComponent: function(){
        this.callParent(arguments);
        var descriptionClassName = this.self.getName().replace('fprm', 'fprm.gui')
        var descriptionClass = Ext.ClassManager.lookupName(descriptionClassName)
        if(descriptionClass){ this.readDescription(descriptionClass) }
    },

    readDescription(descriptionClass){
        var fields = descriptionClass.fields,
            fieldConfigs = this.fieldConfigs = {},
            name, cfg, parent;
        /** Speaking names for indexes of the field arrays */
        const fieldLabel = 0, parentSelector = 1, config = 2;

        for(name in fields){
            cfg = Object.assign(this.getFieldConfig(name), fields[name][config])
            cfg.fieldLabel = fields[name][fieldLabel]
            cfg.parentSelector = 'fprm_' + fields[name][parentSelector]
            fieldConfigs[name] = cfg;
            parent = Ext.getCmp(cfg.parentSelector) || this.formPanel
            parent.add(cfg)
        }
    },

    parseFprm: function(fprm){
        var obj = {};
        fprm.split('\n').forEach(function(line){
            if(line.startsWith('#')){ return }
            var [, name, value] = line.match(/^(.+?)=(.*)$/) // line.split('=') is not enough, values can contain =
            obj[name] = value;
        })
        return obj;
    },

    updateFprm(fprm){
        this.callParent(arguments);
    },

    getFieldConfig: function(name){
        var [id, typeSuffix] = name.split('.');
        console.log(id)
        var xtype = this.suffixMap[typeSuffix];
        return {
            xtype,
            id: id,
            fieldLabel: id,
            labelWidth: 'auto',
            name,
        };
    },

    /**
     * @method
     * @abstrace
     * Template method to enable special field handling in subclasses
     */
    handleFieldconfigSpecial: Ext.emptyFn,

    suffixMap: {
        b: 'checkbox',
        i: 'numberfield',
        undefined: 'textfield'
    },


    compileFprm(){
        this.setLoading(false)
    },

});