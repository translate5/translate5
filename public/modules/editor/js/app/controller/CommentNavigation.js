
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
 * Editor.controller.Comments encapsulates the comment functionality
 * @class Editor.controller.Comments
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.CommentNavigation', {
  extend: 'Ext.app.Controller',
  models: ['Comment'],
  stores: ['comments.AllComments'],
  views: ['comments.Navigation'],

  refs: [{
    ref: 'commentList',
    selector: '#commentList'
  }],
  control: {
    commentNavigation: {
      'expand': 'loadStore'
    }
  },
  listen: {
    component: {
      '#commentList': {
        itemclick: 'jumpThere'
      }
    }
  },

  loadStore: function () {
    var cl = this.getCommentList();
    cl.store.load();
  },
  jumpThere: function (origin, record, item, index, e) {
    record.data.type = 'segment';
    switch (record.data.type) {
      case 'segment':
        var
          grid = Ext.getCmp('segment-grid'),
          rec = grid.store.getById(record.data.segmentId);
        //grid.scrollTo(recIdx); // does not animate upwards direction
        grid.setSelection(rec);
        grid.view.scrollRowIntoView(rec, true);


      case 'floating': //TODO: scroll to annotation in Visual Review
        break;
      case 'video': //TODO: scroll to annotation in Video
    }

  }

}
)