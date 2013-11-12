/*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor Javascript GUI and build on ExtJs 4 lib
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics; All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com
 
 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty
 for any legal issue, that may arise, if you use these FLOSS exceptions and recommend
 to stick to GPL 3. For further information regarding this topic please see the attached 
 license.txt of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
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