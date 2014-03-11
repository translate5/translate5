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
 * contains the Admin Task Export Menu
 * @class Editor.view.admin.ExportMenu
 */
Ext.define('Editor.view.admin.ExportMenu', {
  extend: 'Ext.menu.Menu',
  itemId: 'exportMenu',
  messages: {
      exportDef: '#UT#exportieren',
      exportDiff: '#UT#exportieren mit Änderungshistorie',
      exportQmField: '#UT#Export QM-Statistik (XML) für Feld: {0}'
  },
  alias: 'widget.adminExportMenu',
  makePath: function(path, field) {
      var task = this.initialConfig.task;
      return Editor.data.restpath+Ext.String.format(path, task.get('id'), task.get('taskGuid'), field);
  },
  initComponent: function() {
    var me = this,
        fields = this.initialConfig.fields;
    
    me.items = [{
        itemId: 'exportItem',
        hrefTarget: '_blank',
        href: me.makePath('task/export/id/{0}'),
        text: me.messages.exportDef
    },{
        itemId: 'exportDiffItem',
        hrefTarget: '_blank',
        href: me.makePath('task/export/id/{0}/diff/1'),
        text : me.messages.exportDiff
    }];
    
    if(fields === false) {
        me.callParent(arguments);
        return;
    }
    
    fields.each(function(field){
        if(!field.get('editable')) {
            return;
        }
        me.items.push({
            hrefTarget: '_blank',
            href: me.makePath('qmstatistics/index/taskGuid/{1}/?type={2}', field.get('name')),
            text : Ext.String.format(me.messages.exportQmField, field.get('label'))
        });
    });
    me.callParent(arguments);
  }
});