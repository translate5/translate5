
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
 * @class Editor.view.GridHeaderToolTip
 *
 *  Text tooltips should be stored in the grid column definition
 *
 *  Original Code from Sencha forum url:
 *  http://www.sencha.com/forum/showthread.php?132637-Ext.ux.grid.HeaderToolTip
 */
Ext.define('Editor.view.GridHeaderToolTip', {
    alias: 'plugin.headertooltip',
    init : function(grid){
        if( grid.headerCt ) {
            this.initColumnHeaders( grid.headerCt, grid );
        } else if( grid.lockable ){
            this.initColumnHeaders( grid.lockedGrid.headerCt, grid );
            this.initColumnHeaders( grid.normalGrid.headerCt, grid );
        }
    },
    initColumnHeaders: function( headerCt, grid ) {
        headerCt.on("afterrender", function(g) {
            grid.tip = Ext.create('Ext.tip.ToolTip', {
                target: headerCt.el,
                delegate: ".x-column-header",
                trackMouse: true,
                renderTo: Ext.getBody(),
                listeners: {
                    beforeshow: function(tip) {
                        var c = headerCt.down('gridcolumn[id=' + tip.triggerElement.id  +']');
                        if (c && c.tooltip) {
                            tip.update(c.tooltip);
                        } else {
                            tip.clearTimers();
                            return false;
                        }
                    }
                }
            });
        });
    }
});