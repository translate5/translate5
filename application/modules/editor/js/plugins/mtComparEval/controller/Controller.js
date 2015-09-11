/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
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
 * Die Einstellungen werden in einem Cookie gespeichert
 * @class Editor.controller.Preferences
 * @extends Ext.app.Controller
 */
Ext.define('Editor.plugins.mtComparEval.controller.Controller', {
  extend : 'Ext.app.Controller',
  views: ['Editor.plugins.mtComparEval.view.Panel'],
  models: ['Editor.plugins.mtComparEval.model.Taskmeta'],
  refs: [{
      ref: 'taskTabs',
      selector: '.adminTaskPreferencesWindow > .tabpanel'
  },{
      ref: 'resultBox',
      selector: '.mtComparEvalPanel #resultBox'
  },{
      ref: 'startButton',
      selector: '.mtComparEvalPanel button#sendto'
  }],
  init : function() {
    this.control({
        '.adminTaskPreferencesWindow': {
            render: this.onParentRender//,
            //close: this.onParentClose
        },
        '.adminTaskPreferencesWindow .mtComparEvalPanel button#sendto': {
            click: this.handleStartButton
        }
    });
    
  },
  handleStartButton: function() {
      var me = this;
      me.meta.set('mtCompareEvalState', me.meta.STATE_IMPORTING);
      me.meta.setDirty();
      me.meta.save({
          success: function() {
              me.startWaitingForImport();
          },
          failure: function() {
              me.showResult('Could not sent Task to MT-ComparEval, try again!');
          }
      });
  },
  onParentClose: function() {
      if(this.checkImportState) {
          Ext.TaskManager.stop(this.checkImportStateTask);
      }
  },
  /**
   * Checks if all actually loaded tasks are imported completly
   */
  checkImportState: function() {
      var me = this, 
          metaReloaded = function(rec) {
              if(rec.isImporting()) {
                  return;
              }
              var bar = me.getResultBox().down('.progressbar');
              me.showImportedMessage(rec);
              bar && bar.destroy();
              Ext.TaskManager.stop(me.checkImportStateTask);
          };
      me.meta.reload({
          success: metaReloaded
      });
  },
  showImportedMessage: function(rec) {
      var me = this, 
          msg = 'MT-ComparEval has imported translate5 Task "{0}" as experiment nr {1}.<br /><br /><a href="{2}" target="_blank">open results in MT-ComparEval</a><br /><br />';
      me.showResult(Ext.String.format(msg, me.actualTask.get('taskName'), rec.get('mtCompareEvalId'), rec.get('mtCompareURL')));
      me.getStartButton().setText('Resend Task to MT-ComparEval');
      me.getStartButton().enable();
  },
  /**
   * 
   */
  startWaitingForImport: function() {
      var me = this;
      me.showResult('');
      me.getResultBox().add({
          xtype: 'progressbar',
          width:250
      }).wait({
          interval: 1000,
          text: 'Importing Task in MT-ComparEval!'
      });
      me.getStartButton().disable();
      if(!this.checkImportStateTask) {
          this.checkImportStateTask = {
              run: this.checkImportState,
              scope: this,
              interval: 10000
          };
      }
      Ext.TaskManager.start(this.checkImportStateTask);
  },
  showResult: function(msg) {
      this.getResultBox().update(msg);
  },
  /**
   * inject the plugin tab and load the task meta data set
   */
  onParentRender: function(window) {
      var me = this;
      me.actualTask = window.actualTask;
      me.meta = Editor.plugins.mtComparEval.model.Taskmeta.load(me.actualTask.get('taskGuid'), {
          success: function(rec) {
              me.meta = rec;
              if(rec.isImporting()) {
                  me.startWaitingForImport();
              }
              if(rec.isImported()) {
                  me.showImportedMessage(rec);
              }
          },
          failure: function() {
              me.showResult('Could not load MT-ComparEval information for this task!');
          }
      });
      this.getTaskTabs().add({xtype: 'mtComparEvalPanel', actualTask: me.actualTask});
  }
});
