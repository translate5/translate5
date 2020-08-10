
/*
START LICENSE AND COPYRIGHT

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a plug-in for translate5. 
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 For the license of this plug-in, please see below.
 
 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and 
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the 
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html
   
 There is a plugin exception available for use with this release of translate5 for 
 translate5 plug-ins that are distributed under GNU GENERAL PUBLIC LICENSE version 3: 
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/gpl.html
             http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Tooltip that can be used as a plugin for any component
 * 
 * @class Editor.view.plugins.ComponentToolTip
 * @extends Ext.tip.ToolTip
 */
Ext.define('Editor.view.ComponentToolTip', {
    extend: 'Ext.plugin.Abstract',
    requires: [
        'Ext.tip.ToolTip'
    ],
    alias: 'plugin.tooltip',
    config: {
        text: '',
        dismissDelay: 2000
    },
    init: function(cmp) {
        this.cmp = cmp;
        cmp.on({
            scope: this,
            afterrender: 'onAfterRender'
        });
    },
    onAfterRender: function() {
        this.tip = new Ext.tip.ToolTip({
            target: this.cmp.el,
            html: this.getText(),
            dismissDelay: this.getDismissDelay()
        });
    },
    destroy: function() {
        this.callParent();
        Ext.destroy(this.tip);
    }
});
