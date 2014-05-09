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

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
Ext.define('Editor.view.comments.Grid', {
  extend: 'Ext.grid.Panel',
  alias: 'widget.commentsGrid',
  cls: 'comments-grid',
  store: 'Comments',
  text_edited: '#UT#bearbeitet',
  text_edit: '#UT#Bearbeiten',
  text_delete: '#UT#Löschen',
  hideHeaders: true,
  viewConfig: {
      getRowClass: function(record) {
          if(!record.get('isEditable')) {
              return 'readonly';
          }
      }
  },
  commentTpl: null,
  initComponent: function() {
    var me = this;
    
    me.commentTpl = new Ext.XTemplate([
       '<div class="comment">',
       '<span class="content">{content}</span>',
       '<span class="author">{author}</span> - ',
       '<span class="created">{created}</span>',
       '<tpl if="isMod">',
           ' <span class="modified">({label} {modified})</span>',
       '</tpl>',
       '</div>'
    ]);
    
    Ext.applyIf(me, {
      columns: [
                {
                    xtype: 'gridcolumn',
                    dataIndex: 'comment',
                    flex: 1,
                    itemId: 'commentColumn',
                    renderer: function(v, meta, rec) {
                        var modified = Ext.Date.format(rec.get('modified'), Editor.DATE_ISO_FORMAT),
                            created = Ext.Date.format(rec.get('created'), Editor.DATE_ISO_FORMAT),
                            data = {
                                content: v,
                                isMod: (created !== modified),
                                created: created,
                                modified: modified,
                                label: me.text_edited,
                                author: rec.get('userName')
                            };
                        return me.commentTpl.apply(data);
                    }
                },
        {
            xtype: 'actioncolumn',
            width: 60,
            items: [{
                iconCls: 'ico-comment-edit',
                tooltip: me.text_edit
            },{
                iconCls: 'ico-comment-delete',
                tooltip: me.text_delete
            }]
        }
      ]
    });
    me.callParent(arguments);
  }
});