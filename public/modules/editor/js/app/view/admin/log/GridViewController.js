
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

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.view.admin.log.GridViewController
 * @extends Ext.app.ViewController
 */
Ext.define('Editor.view.admin.log.GridViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.editorlogGridViewController',
    listen: {
        component: {
            "gridpanel": {
                cellclick: 'handleGridClick'
            },
            '#cancelBtn': {
                click: 'closeDetails'
            }
        }
    },
    handleGridClick: function(table, td, idx, rec, tr) {
        var extra = {};
        if(! Ext.fly(td).hasCls('message') || !rec.get('extra')) {
            return;
        }
        Ext.Object.each(rec.get('extra'), function(k, v){
            if(Ext.isObject(v)) {
                v = JSON.stringify(v, null, 2);
            }
            else if(Ext.isArray(v)) {
                v = v.join("\n");
            }
            extra[k] = v;
        });
        this.getView().down('gridpanel').hide();
        this.getView().down('#detailview propertygrid').setSource(extra);
        this.getView().down('#eventdata').update('<table>'+tr.outerHTML+'</table>');
        this.getView().down('#detailview').show();
    },
    close: function() {
        this.getView().close();
    },
    closeDetails: function() {
        this.getView().down('#detailview').hide();
        this.getView().down('gridpanel').show();
    }
});