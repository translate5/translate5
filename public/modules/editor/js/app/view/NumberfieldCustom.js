
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * @class Editor.view.NumberfieldCustom
 * @extends Ext.form.field.Display
 */

Ext.define('Editor.view.NumberfieldCustom', {
    extend: 'Ext.form.field.Number',
    alias: 'widget.numberfieldcustom',
    mouseWheelEnabled:false,

    /***
     * if set to true, the custom pricision will be used
     * @private
     */
    useCustomPrecision:false,

    setValue:function(v){
        var me=this;
        if(!me.useCustomPrecision || !v){
            return this.superclass.setValue.apply(me, arguments);
        }

        //replace the decimal separator with .
        v = String(v).replace(me.decimalSeparator, ".");
        //format with custom reciion function
        v = me.fixPrecisionCustom(v);
        //replace . with the configured decimal separator
        v = String(v).replace(".", me.decimalSeparator);
        //return Ext.form.NumberField.superclass.setRawValue.call(this, v);
        return me.superclass.setRawValue.call(me, v);
    },

    onSpinUp:function() {
        var me=this;
        if(!me.useCustomPrecision){
            return me.superclass.onSpinUp.apply(this, arguments);
        }
        if (!me.readOnly) {
            var val = parseFloat(me.step);
            if(me.getValue() !== '') {
                val = parseFloat(me.getValue());
                if(!val){
                    val=0;
                }
                me.setValue((val + parseFloat(me.step)));
            }
        }
    },

    fixPrecisionCustom:function(value){
        var me=this,
            nan = isNaN(value);
        if(!me.allowDecimals || me.decimalPrecision == -1 || nan || !value){
            return nan ? '' : value;
        }
        return parseFloat(value).toFixed(me.decimalPrecision);
    }
});