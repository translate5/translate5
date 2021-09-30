
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
 * @class Editor.view.help.Documentation
 * @extends Ext.panel.Panel
 */
Ext.define('Editor.view.help.Documentation', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.helpDocumentation',
    header:false,

    strings:{
        btnOpenInBrowserText:'#UT#In neuem Browser-Tab öffnen'
    },

    initConfig: function(instanceConfig) {
        var me = this,
            url=me.getDocumentationUrl(),
            config = {
                html:'<iframe src="'+url+'" width="100%" height="100%" ></iframe>',
                hidden:(url === "" || url === undefined),
                tbar:['->',{
                    xtype: 'button',
                    glyph: 'f35d@FontAwesome5FreeSolid',
                    text:me.strings.btnOpenInBrowserText,
                    handler:function (){
                        window.open(url);
                    }
                }]
            };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },

    /***
     * Get the configured documentation url from where the pdf will be loaded
     */
    getDocumentationUrl:function(){
        var sectionConfig=Editor.data.frontend.helpWindow[Editor.data.helpSection];
        return sectionConfig.documentationUrl;
    }

});