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
Ext.define('Editor.plugins.Okapi.view.fprm.Xml', {
    extend: 'Editor.plugins.Okapi.view.FprmEditor',
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
            var ret = true, lastCheck = this.lastCheck;
            if(lastCheck.xml === xml){
                ret = lastCheck.ret;
            } else if(xml){
                lastCheck.xml = xml;
                ret = Editor.util.Util.getXmlError(xml || '<xml/>');
            }
            lastCheck.ret = ret;
            return ret;
        }
    }],

    fprmDataLoaded: function(height){
        this.down('[name=xml]').setHeight(height - 114);
        this.callParent(arguments);
    }
});