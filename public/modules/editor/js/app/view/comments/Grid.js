
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
  initConfig: function(instanceConfig) {
    var me = this,
    config;
    
    me.commentTpl = new Ext.XTemplate([
       '<div class="comment">',
       '<span class="content">{content:nl2br}</span>',  //nl2br format methode anwenden!
       '<span class="author">{author}</span> - ',
       '<span class="created">{created}</span>',
       '<tpl if="isMod">',
           ' <span class="modified">({label} {modified})</span>',
       '</tpl>',
       '</div>'
    ]);
    
    config = {
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
    };
    if (instanceConfig) {
        me.self.getConfigurator().merge(me, config, instanceConfig);
    }
    return me.callParent([config]);
  }
});