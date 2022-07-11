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
Ext.define('Editor.plugins.Okapi.view.fprm.Icml', {
    extend: 'Editor.plugins.Okapi.view.FprmEditor',

    width: '61%',
    defaultFocus: 'textarea',

    formItems: [{
        xtype: 'textarea',
        width: '100%',
        minWidth: 800,
        height: '100%',
        name: 'xml',
        fieldCls: 'mono',
        scroll: true,
        validateOnBlur: false,
        inputAttrTpl: 'spellcheck="false"',
        checkChangeBuffer: 500,
        checkChangeEvents: [],
        lastCheck: {},
        validator: function(xml){
            var ret = true, lastCheck = this.lastCheck, me = this;
            if(lastCheck.xml === xml){
                ret = lastCheck.ret
            } else {
                lastCheck.xml = xml;
                var unevenMatch = lastCheck.unevenMatch = xml.match(/^ (  )*[^ ]/m);
                if(unevenMatch){
                    var lineBreakAfter = xml.indexOf('\n', unevenMatch.index),
                        lineBreakBefore = xml.lastIndexOf('\n', unevenMatch.index) + 1,
                        line = xml.substring(lineBreakBefore, lineBreakAfter);
                        ret = '#UT#Uneven number of leading spaces at line <br>"<span style=\"font-family:monospace\"">'
                            + line + '</span>"';
                    if(lastCheck.highlightTask){
                        lastCheck.highlightTask.destroy();
                    }
                    lastCheck.highlightTask = this.up('window').on('activate', function(){
                        this.focus()
                        setTimeout(function(){
                            window.find(line + '\n', true, false, true)
                            delete lastCheck.highlightTask;
                        }, 50)
                    }, this, {single: true, delay: 50, destroyable:true})
                }
            }
            return lastCheck.ret = ret;
        }
    }],

    parseFprm(fprm){
        return {xml: fprm}
    },

    compileFprm: function(){
        return this.down('[name=xml]').getValue()
    }
})