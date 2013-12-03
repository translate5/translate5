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
Ext.define('Editor.view.admin.TaskGrid', {
  extend: 'Ext.grid.Panel',
  requires: ['Editor.view.admin.TaskActionColumn', 'Editor.view.GridHeaderToolTip','Editor.view.CheckColumn', 'Editor.view.admin.task.GridFilter'],
  alias: 'widget.adminTaskGrid',
  itemId: 'adminTaskGrid',
  cls: 'adminTaskGrid',
  title: '#UT#Aufgabenübersicht',
  plugins: ['headertooltip'],
  layout: {
      type: 'fit'
  },
  text_cols: {
      taskNr: '#UT#Auftragsnr.',
      taskName: '#UT#Aufgabenname',
      taskActions: '#UT#Aktionen',
      sourceLang: '#UT#Quellsprache',
      relaisLang: '#UT#Relaissprache',
      targetLang: '#UT#Zielsprache',
      state: '#UT#Status',
      pmGuid: '#UT#Projektmanager',
      users: '#UT#Benutzer',
      wordCount: '#UT#Wörter',
      wordCountTT: '#UT#Anzahl Wörter',
      targetDeliveryDate: '#UT#Lieferdatum (soll)',
      realDeliveryDate: '#UT#Lieferdatum (ist)',
      referenceFiles: '#UT#Referenzdateien',
      terminologie: '#UT#Terminologie',
      fullMatchEdit: '#UT#100% Matches sind editierbar',
      orderdate: '#UT#Bestelldatum',
      enableSourceEditing: '#UT#Quellsprache bearbeitbar'
  },
  strings: {
      ended: '#UT#beendet',
      noUsers: '#UT#Keine Benutzer zugeordnet!',
      locked: '#UT#in Arbeit',
      lockedBy: '#UT#Bearbeitet und Gesperrt durch {0}',
      addTask: '#UT#Aufgabe hinzufügen',
      addTaskTip: '#UT#Eine neue Aufgabe hinzufügen.',
      reloadBtn: '#UT#Aktualisieren',
      reloadBtnTip: '#UT#Aufgabenliste vom Server aktualisieren.'
  },
  store: 'admin.Tasks',
  features: [{
    ftype: 'adminTaskGridFilter'
  }],
  viewConfig: {
      /**
       * returns a specific row css class
       * To prevent duplication of logic in css and js, the task state is not provided directly as css class.
       * Instead the task methods are used to calculate the css classes.
       * 
       * @param {Editor.model.admin.Task} task
       * @return {Boolean}
       */
      getRowClass: function(task) {
          var res = [],
              user = Editor.app.authenticatedUser,
              actions = this.panel.availableActions,
              isNotAssociated = !(task.get('userState') || task.get('userRole'));
          
          Ext.Array.each(actions, function(action) {
              if(user.isAllowed(action, task)) {
                  res.push(action);
              }
          });
          
          if(isNotAssociated) { //with this user
              res.push('not-associated');
          }
          if(task.isLocked()) {
              res.push('locked');
          }
          if(task.isOpenable()) {
              res.push('openable');
          }
          if(task.isPending()) {
              //FIXME see TRANSLATE-91, the out commented line is a workaround:
              //res.push('pending');
          }
          if(task.isReadOnly()) {
              res.push('readonly');
          }
          if(task.isEnded()) {
              res.push('end');
          }
          if(task.isFinished() || task.isWaiting()) {
              res.push('finished');
          }
          if(task.get('userCount') == 0) {
              res.push('no-users');
          }
          return res.join(' ');
      }
  },
  /**
   * renders the value of the language columns
   * @param {String} val
   * @returns {String}
   */
  langRenderer: function(val, md) {
      var lang = this.languageStore.getById(val), 
          label;
      if(lang){
          label = lang.get('label');
          md.tdAttr = 'data-qtip="' + label + '"';
          return label;
      }
      return '';
  },

  initComponent: function() {
    var me = this,
        utStates = Editor.data.app.utStates,
        actions;
    me.userTipTpl = new Ext.XTemplate(
            '<tpl>',
            '<table class="task-users">',
            '<tpl for=".">',
            '<tr>',
            '<td class="username">{userName}</td><td class="login">{login}</td><td class="role">{[this.getRole(values.role)]}</td><td class="state">{[this.getState(values.state)]}</td>',
            '</tr>',
            '</tpl>',
            '</table>',
            '</tpl>',
            {
                getState: function(state) {
                    if(state == 'edit') {
                        return me.strings.locked;
                    }
                    return utStates[state];
                },
                getRole: function(role) {
                    return Editor.data.app.utRoles[role];
                }
            }
    );
    me.userStore = Ext.getStore('admin.Users');
    
    Ext.applyIf(me, {
      languageStore: Ext.StoreMgr.get('admin.Languages'),
      columns: [{
          text: me.text_cols.taskActions,
          menuDisabled: true,//must be disabled, because of disappearing filter menu entry on missing filter
          xtype: 'taskActionColumn',
          sortable: false
      },{
          xtype: 'gridcolumn',
          width: 70,
          menuDisabled: true,
          dataIndex: 'state',
          tdCls: 'state',
          renderer: function(v, meta, rec) {
              var userState = rec.get('userState');
              if(rec.isLocked()) {
                  meta.tdAttr = 'data-qtip="' + Ext.String.format(me.strings.lockedBy, rec.get('lockingUsername'))+'"';
                  return me.strings.locked;
              }
              if(rec.isEnded()) {
                  return me.strings.ended;
              }
              if(!userState || userState.length == 0) {
                  //if we got only v here, the state should be handled like locked or ended above
                  return utStates[v] ? utStates[v] : v; 
              }
              //if no global state is applicable, use userState instead
              return utStates[userState];
          },
          text: me.text_cols.state,
          sortable: false
      },{
          xtype: 'gridcolumn',
          width: 220,
          dataIndex: 'taskName',
          text: me.text_cols.taskName
      },{
          xtype: 'gridcolumn',
          width: 110,
          dataIndex: 'taskNr',
          tdCls: 'taskNr',
          text: me.text_cols.taskNr
      },{
          xtype: 'numbercolumn',
          width: 70,
          dataIndex: 'wordCount',
          format: '0',
          text: me.text_cols.wordCount
      },{
          xtype: 'gridcolumn',
          width: 110,
          cls: 'source-lang',
          renderer: me.langRenderer,
          dataIndex: 'sourceLang',
          tooltip: me.text_cols.sourceLang,
          text: me.text_cols.sourceLang,
          sortable: false
      },{
          xtype: 'gridcolumn',
          width: 110,
          cls: 'relais-lang',
          renderer: me.langRenderer,
          dataIndex: 'relaisLang',
          tooltip: me.text_cols.relaisLang,
          text: me.text_cols.relaisLang,
          sortable: false
      },{
          xtype: 'gridcolumn',
          width: 110,
          cls: 'target-lang',
          renderer: me.langRenderer,
          dataIndex: 'targetLang',
          tooltip: me.text_cols.targetLang,
          text: me.text_cols.targetLang,
          sortable: false
      },{
          xtype: 'owncheckcolumn',
          cls: 'ref-files',
          width: 45,
          dataIndex: 'referenceFiles',
          tooltip: me.text_cols.referenceFiles,
          text: me.text_cols.referenceFiles
      },{
          xtype: 'owncheckcolumn',
          width: 45,
          cls: 'terminologie',
          dataIndex: 'terminologie',
          tooltip: me.text_cols.terminologie,
          text: me.text_cols.terminologie
      },{
          xtype: 'gridcolumn',
          width: 45,
          renderer: function(v, meta, rec){
              if(v == 0) {
                  return '<b>'+v+' !</b>';
              }
              return v;
          },
          tdCls: 'task-users',
          cls: 'task-users',
          dataIndex: 'userCount',
          tooltip: me.text_cols.users,
          text: me.text_cols.users
      },{
          xtype: 'gridcolumn',
          width: 135,
          dataIndex: 'pmName',
          renderer: function(v, meta) {
              meta.tdAttr = 'data-qtip="' + v + '"';
              return v;
          },
          text: me.text_cols.pmGuid
      },{
          xtype: 'datecolumn',
          width: 100,
          dataIndex: 'orderdate',
          text: me.text_cols.orderdate
      },{
          xtype: 'datecolumn',
          width: 120,
          dataIndex: 'targetDeliveryDate',
          text: me.text_cols.targetDeliveryDate
      },{
          xtype: 'datecolumn',
          width: 120,
          dataIndex: 'realDeliveryDate',
          text: me.text_cols.realDeliveryDate
      },{
          xtype: 'owncheckcolumn',
          width: 45,
          cls: 'fullMatchEdit',
          hidden: true,
          dataIndex: 'edit100PercentMatch',
          tooltip: me.text_cols.fullMatchEdit,
          text: me.text_cols.fullMatchEdit
      },{
          xtype: 'owncheckcolumn',
          hidden: ! Editor.data.enableSourceEditing,
          hideable: Editor.data.enableSourceEditing,
          width: 55,
          cls: 'source-edit',
          dataIndex: 'enableSourceEditing',
          tooltip: me.text_cols.enableSourceEditing,
          text: me.text_cols.enableSourceEditing
      }],
      dockedItems: [{
          xtype: 'toolbar',
          dock: 'top',
          items: [{
              xtype: 'button',
              iconCls: 'ico-task-add',
              itemId: 'add-task-btn',
              text: me.strings.addTask,
              hidden: ! Editor.app.authenticatedUser.isAllowed('editorAddTask'),
              tooltip: me.strings.addTaskTip
          },{
              xtype: 'button',
              iconCls: 'ico-refresh',
              itemId: 'reload-task-btn',
              text: me.strings.reloadBtn,
              tooltip: me.strings.reloadBtnTip
          }]
        },{
            xtype: 'pagingtoolbar',
            store: 'admin.Tasks',
            dock: 'bottom',
            displayInfo: true
        }]
    });

    me.callParent(arguments);
    actions = me.down('.taskActionColumn');
    if(actions && actions.items.length > 0) {
        me.availableActions = Ext.Array.map(actions.items, function(item) {
            return item.isAllowedFor;
        });
    }
    else {
        me.availableActions = [];
    }
    this.view.on('afterrender', function(){
        me.tooltip = me.createToolTip();
    });
  },
  createToolTip: function() {
      var me = this;
      return Ext.create('Ext.tip.ToolTip', {
          target: me.view.el,
          delegate: 'td.task-users',
          dismissDelay: 0,
          showDelay: 200,
          maxWidth: 500,
          renderTo: Ext.getBody(),
          listeners: {
              beforeshow: function updateTipBody(tip) {
                  var tr = Ext.fly(tip.triggerElement).up('tr'),
                      rec = me.view.getRecord(tr),
                      users = rec.get('users');
                  if(!users || users.length == 0) {
                      tip.update(me.strings.noUsers);
                      return;
                  }
                  tip.update(me.userTipTpl.apply(rec.get('users')));
              }
          }
      });
  },
  onDestroy: function() {
      this.tooltip.destroy();
      this.callParent(arguments);
  }
});