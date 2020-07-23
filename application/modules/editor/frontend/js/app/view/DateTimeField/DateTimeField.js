/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * @class Editor.view.DateTimeField.DateTimeField
 * @extends Ext.form.field.Date
 */
Ext.define('Editor.view.DateTimeField.DateTimeField', {
    extend: 'Ext.form.field.Date',
    alias: 'widget.datetimefield',
    requires: ['Editor.view.DateTimeField.DateTimePicker'],

    //<locale>
    /**
     * @cfg {String} format
     * The default date format string which can be overriden for localization support. The format must be valid
     * according to {@link Ext.Date#parse}.
     */
    format: "m/d/Y H:i",
    //</locale>
    /**
     * @cfg {String} altFormats
     * Multiple date formats separated by "|" to try when parsing a user input value and it does not match the defined
     * format.
     */
    altFormats: "m/d/Y H:i:s|c",
    width: 270,

    collapseIf: function(e) {
        var me = this,
            picker = me.picker;

        if (picker.timePicker && !e.within(picker.timePicker.el, false, true)) {
            me.callParent([e]);
        }
    },

    createPicker: function() {
        var me = this,
            parentPicker = this.callParent(),
            parentConfig = Ext.clone(parentPicker.initialConfig),
            initialConfig = Ext.clone(me.initialConfig),
            excludes = ['renderTo', 'width', 'height', 'bind', 'reference'];

        // Avoiding duplicate ids error
        parentPicker.destroy();

        for (var i=0; i < excludes.length; i++) {
            if (initialConfig.hasOwnProperty([excludes[i]])) {
                delete initialConfig[excludes[i]];
            }
        }
        
        return Ext.create('Editor.view.DateTimeField.DateTimePicker', Ext.merge(initialConfig, parentConfig));
    },

    getErrors: function(value) {
        value = arguments.length > 0 ? value : this.formatDate(this.processRawValue(this.getRawValue()));

        var me = this,
            format = Ext.String.format,
            errors = me.superclass.superclass.getErrors.apply(this, arguments),
            disabledDays = me.disabledDays,
            disabledDatesRE = me.disabledDatesRE,
            minValue = me.minValue,
            maxValue = me.maxValue,
            len = disabledDays ? disabledDays.length : 0,
            i = 0,
            svalue,
            fvalue,
            day,
            time;

        if (value === null || value.length < 1) { // if it's blank and textfield didn't flag it then it's valid
            return errors;
        }

        svalue = value;
        value = me.parseDate(value);
        if (!value) {
            errors.push(format(me.invalidText, svalue, Ext.Date.unescapeFormat(me.format)));
            return errors;
        }

        time = value.getTime();
        if (minValue && time < minValue.getTime()) {
            errors.push(format(me.minText, me.formatDate(minValue)));
        }

        if (maxValue && time > maxValue.getTime()) {
            errors.push(format(me.maxText, me.formatDate(maxValue)));
        }

        if (disabledDays) {
            day = value.getDay();

            for(; i < len; i++) {
                if (day === disabledDays[i]) {
                    errors.push(me.disabledDaysText);
                    break;
                }
            }
        }

        fvalue = me.formatDate(value);
        if (disabledDatesRE && disabledDatesRE.test(fvalue)) {
            errors.push(format(me.disabledDatesText, fvalue));
        }

        return errors;
    },

    getRefItems: function() {
        var me = this,
            result = me.callParent();

        if (me.picker && me.picker.timePicker){
            result.push(me.picker.timePicker);
        }
        
        return result;
    },

    onExpand: function() {
        var me = this,
            timePicker;

        me.callParent();
        timePicker = me.picker && me.picker.timePicker;

        if (timePicker) {
            me.picker.alignTimePicker();
        }
    }
});