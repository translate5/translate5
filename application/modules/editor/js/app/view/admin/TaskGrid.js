
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

Ext.define('Editor.view.admin.TaskGrid', {
  extend: 'Ext.grid.Panel',
  requires: ['Editor.view.admin.TaskActionColumn','Editor.view.CheckColumn','Editor.view.admin.customer.CustomerFilter'],
  alias: 'widget.adminTaskGrid',
  itemId: 'adminTaskGrid',
  cls: 'adminTaskGrid',
  title: '#UT#Aufgabenübersicht',
  plugins: ['gridfilters'],
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
      customerId: '#UT#Endkunde',
      pmGuid: '#UT#Projektmanager',
      users: '#UT#Benutzer',
      wordCount: '#UT#Wörter',
      wordCountTT: '#UT#Anzahl Wörter',
      fileCount: '#UT#Dateien',
      targetDeliveryDate: '#UT#Lieferdatum (soll)',
      realDeliveryDate: '#UT#Lieferdatum (ist)',
      referenceFiles: '#UT#Referenzdateien',
      terminologie: '#UT#Terminologie',
      fullMatchEdit: '#UT#100% Matches sind editierbar',
      lockLocked: '#UT#In importierter Datei gesperrte Segmente sind in translate5 gesperrt',
      orderdate: '#UT#Bestelldatum',
      enableSourceEditing: '#UT#Quellsprache bearbeitbar',
      emptyTargets: '#UT#Übersetzungsaufgabe (kein Review)'
  },
  strings: {
      noRelaisLang: '#UT#- Ohne Relaissprache -',
      ended: '#UT#beendet',
      noUsers: '#UT#Keine Benutzer zugeordnet!',
      notFound: '#UT#nicht gefunden',
      locked: '#UT#in Arbeit',
      lockedBy: '#UT#Bearbeitet und Gesperrt durch {0}',
      lockedSystem: '#UT#Durch das System gesperrt mit dem Status \'{0}\'',
      addTask: '#UT#Aufgabe hinzufügen',
      addTaskTip: '#UT#Eine neue Aufgabe hinzufügen.',
      reloadBtn: '#UT#Aktualisieren',
      reloadBtnTip: '#UT#Aufgabenliste vom Server aktualisieren.',
      emptyTargets: '#UT#Übersetzungsaufgabe - alle zielsprachlichen Segmente beim Import leer (nicht angehakt bedeutet Reviewaufgabe)."'
  },
  states: {
      user_state_open: '#UT#offen',
      user_state_waiting: '#UT#wartend',
      user_state_finished: '#UT#abgeschlossen',
      task_state_end: '#UT#beendet',
      task_state_unconfirmed: '#UT#nicht bestätigt',
      task_state_import: '#UT#import',
      locked: '#UT#in Arbeit',
      forMe: '#UT#für mich '
  },
  store: 'admin.Tasks',
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
          if(task.isErroneous()) {
              if(Editor.data.import.createArchivZip && user.isAllowed('downloadImportArchive', task)){
                  res.push('downloadable');
              }
              res.push('error');
              return res.join(' ');
          }
          if(task.isCustomState()) {
              res.push('customState');
              res.push('state-'+task.get('state'));
          }
          if(task.isUnconfirmed()) {
              res.push('unconfirmed');
          }
          if(task.isOpenable() && !task.isCustomState()) {
              res.push('openable');
          }
          if(task.isReadOnly() && !task.isCustomState()) {
              res.push('readonly');
          }
          if(task.isImporting() && !task.isCustomState()) {
              res.push('import');
          }
          if(task.isEnded() && !task.isCustomState()) {
              res.push('end');
          }
          if((task.isFinished() || task.isWaiting()) && !task.isCustomState()) {
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
      var me = this,
          lang = me.languageStore.getById(val), 
          label;
      if(lang){
          label = lang.get('label');
          md.tdAttr = 'data-qtip="' + label + '"';
          return label;
      }
      if (!val || val == "0") {
          return '';
      }
      return me.strings.notFound;
  },
  /**
   * renders the value (= names) of the customer column
   * @param {String} val
   * @returns {String}
   */
  customerRenderer: function(val, md, record) {
      var customer = record.get('customerName');
      if(customer){
          md.tdAttr = 'data-qtip="' + customer + ' (id: ' + val + ')"';
          return customer;
      }
      return this.strings.notFound;
  },

  initComponent: function() {
    var me = this,
        actions;

    me.errorTipTpl = new Ext.XTemplate(
        '<tpl for=".">',
        '<img valign="text-bottom" class="icon-error-level-{[this.getLevel(values)]}" alt="{[this.getLevel(values)]}" src="'+Ext.BLANK_IMAGE_URL+'"/>{message}<br>',
        '</tpl>',
        {
            getLevel: function(values) {
                return Editor.model.admin.task.Log.prototype.getLevelName(values.level)
            }
        }
    );
    me.userTipTpl = new Ext.XTemplate(
            '<tpl>',
            '<table class="task-users">',
            '<tpl for="users">',
            '<tr>',
            '<td class="username">{userName}</td><td class="login">{login}</td><td class="role">{[this.getRole(parent, values)]}</td><td class="state">{[this.getState(parent, values)]}</td>',
            '</tr>',
            '</tpl>',
            '</table>',
            '</tpl>',
            {
                getState: function(data, user) {
                    if(user.state == 'edit') {
                        return me.strings.locked;
                    }
                    return data.states[user.state];
                },
                getRole: function(data, user) {
                    return data.roles[user.role];
                }
            }
    );
    me.userStore = Ext.getStore('admin.Users');
    me.callParent(arguments);
    actions = me.down('taskActionColumn');
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
  initConfig: function(instanceConfig) {
      var me = this,
          states = [],
          config,
          //we must have here an own ordered list of states to be filtered 
          stateFilterOrder = ['user_state_open','user_state_waiting','user_state_finished','locked', 'task_state_end', 'user_state_unconfirmed', 'task_state_import'],
          relaisLanguages = Ext.Array.clone(Editor.data.languages),
          addQtip = function(meta, text) {
              meta.tdAttr = 'data-qtip="' + Ext.String.htmlEncode(text)+'"';
          };
          
          //we're hardcoding the state filter options order, all other (unordered) workflow states are added below
          Ext.Array.each(stateFilterOrder, function(state){
              if(me.states[state]) {
                  states.push([state, me.states[state]]);
              }
          });
        
          //adding additional, not ordered states
          Ext.Object.each(Editor.data.app.workflows, function(key, workflow){
              Ext.Object.each(workflow.states, function(key, value){
                  var state = 'user_state_'+key;
                  if(!me.states[state]) {
                      states.push([state, me.states.forMe+' '+value]);
                  }
              });
          });
        
          relaisLanguages.unshift([0, me.strings.noRelaisLang]);
          
          config = {
                  title: me.title, //see EXT6UPD-9
          languageStore: Ext.StoreMgr.get('admin.Languages'),
          customerStore: Ext.StoreManager.get('customersStore'),
          columns: {
              defaults: {
                  menuDisabled: ! Editor.app.authenticatedUser.isAllowed('editorTaskOverviewColumnMenu')
              },
          items:[{
              text: me.text_cols.taskActions,
              menuDisabled: true,//must be disabled, because of disappearing filter menu entry on missing filter
              xtype: 'taskActionColumn',
              stateId:'taskGridActionColumn',
              sortable: false
          },{
              xtype: 'gridcolumn',
              width: 70,
              dataIndex: 'state',
              stateId:'state',
              filter: {
                  type: 'list',
                  options: states,
                  phpMode: false
              },
              tdCls: 'state',
              renderer: function(v, meta, rec) {
                  var userState = rec.get('userState'),
                      wfMeta = rec.getWorkflowMetaData(),
                      allStates = me.prepareStates(wfMeta);

                  if(rec.isImporting() || rec.isErroneous()) {
                      addQtip(meta, me.errorTipTpl.apply(rec.get('lastErrors')));
                      return rec.get('state');
                  }
                  if(rec.isLocked() && rec.isCustomState()) {
                      addQtip(meta, Ext.String.format(me.strings.lockedSystem, rec.get('state')));
                      return me.strings.locked;
                  }
                  if(rec.isLocked() && rec.isUnconfirmed()) {
                      addQtip(meta, Ext.String.format(me.strings.lockedBy, rec.get('lockingUsername')));
                      return me.strings.locked;
                  }
                  if(rec.isUnconfirmed()) {
                      addQtip(meta, me.states.task_state_unconfirmed);
                      return me.states.task_state_unconfirmed;
                  }
                  if(rec.isLocked()) {
                      addQtip(meta, Ext.String.format(me.strings.lockedBy, rec.get('lockingUsername')));
                      return me.strings.locked;
                  }
                  if(rec.isEnded()) {
                      addQtip(meta, me.strings.ended);
                      return me.strings.ended;
                  }
                  if(!userState || userState.length == 0) {
                      //if we got only v here, the state should be handled like locked or ended above
                      v = allStates[v] ? allStates[v] : v;
                      addQtip(meta, v);
                      return v; 
                  }
                  //if no global state is applicable, use userState instead
                  addQtip(meta, allStates[userState]);
                  return allStates[userState];
              },
              text: me.text_cols.state,
              sortable: false
          },{
              xtype: 'gridcolumn',
              width: 135,
              renderer: me.customerRenderer,
              dataIndex: 'customerId',
              stateId: 'customerId',
              filter: {
                  type: 'customer' // [Multitenancy]
              },
              text: me.text_cols.customerId
          },{
              xtype: 'gridcolumn',
              width: 220,
              dataIndex: 'taskName',
              stateId:'taskName',
              filter: {
                  type: 'string'
              },
              text: me.text_cols.taskName
          },{
              xtype: 'gridcolumn',
              width: 110,
              dataIndex: 'taskNr',
              stateId: 'taskNr',
              filter: {
                  type: 'string'
              },
              tdCls: 'taskNr',
              text: me.text_cols.taskNr
          },{
              xtype: 'numbercolumn',
              width: 70,
              dataIndex: 'wordCount',
              stateId: 'wordCount',
              filter: {
                  type: 'numeric'
              },
              format: '0',
              text: me.text_cols.wordCount
          },{
              xtype: 'numbercolumn',
              width: 70,
              dataIndex: 'fileCount',
              stateId: 'fileCount',
              filter: {
                  type: 'numeric'
              },
              hidden: true,
              sortable: false,
              format: '0',
              text: me.text_cols.fileCount
          },{
              xtype: 'gridcolumn',
              width: 110,
              cls: 'source-lang',
              renderer: me.langRenderer,
              dataIndex: 'sourceLang',
              stateId: 'sourceLang',
              filter: {
                  type: 'list',
                  options: Editor.data.languages,
                  phpMode: false
              },
              tooltip: me.text_cols.sourceLang,
              text: me.text_cols.sourceLang,
              sortable: false
          },{
              xtype: 'gridcolumn',
              width: 110,
              cls: 'relais-lang',
              renderer: me.langRenderer,
              dataIndex: 'relaisLang',
              stateId: 'relaisLang',
              filter: {
                  type: 'list',
                  options: relaisLanguages,
                  phpMode: false
              },
              tooltip: me.text_cols.relaisLang,
              text: me.text_cols.relaisLang,
              sortable: false
          },{
              xtype: 'gridcolumn',
              width: 110,
              cls: 'target-lang',
              renderer: me.langRenderer,
              dataIndex: 'targetLang',
              stateId: 'targetLang',
              filter: {
                  type: 'list',
                  options: Editor.data.languages,
                  phpMode: false
              },
              tooltip: me.text_cols.targetLang,
              text: me.text_cols.targetLang,
              sortable: false
          },{
              xtype: 'owncheckcolumn',
              cls: 'ref-files',
              width: 45,
              dataIndex: 'referenceFiles',
              stateId: 'referenceFiles',
              filter: {
                  type: 'boolean'
              },
              tooltip: me.text_cols.referenceFiles,
              text: me.text_cols.referenceFiles
          },{
              xtype: 'owncheckcolumn',
              width: 45,
              cls: 'terminologie',
              dataIndex: 'terminologie',
              stateId: 'terminologie',
              filter: {
                  type: 'boolean'
              },
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
              stateId: 'userCount',
              filter: {
                  type: 'numeric'
              },
              tooltip: me.text_cols.users,
              text: me.text_cols.users
          },{
              xtype: 'gridcolumn',
              width: 135,
              dataIndex: 'pmName',
              stateId: 'pmName',
              filter: {
                  type: 'string'
              },
              renderer: function(v, meta,rec) {
            	  var tooltip=v,
            	  	  ret=v;
            	  if(Editor.data.frontend.tasklist.pmMailTo){
            		  tooltip=rec.get('pmMail');
            		  ret='<a alt="'+tooltip+'" href="mailto:'+tooltip+'">'+v+'</a>';
            		  meta.tdAttr = 'data-qtip="'+tooltip+'"';
            	  }
                  return ret;
              },
              text: me.text_cols.pmGuid
          },{
              xtype: 'datecolumn',
              width: 100,
              dataIndex: 'orderdate',
              stateId: 'orderdate',
              filter: {
                  type: 'date',
                  dateFormat: Editor.DATE_ISO_FORMAT
              },
              text: me.text_cols.orderdate
          },{
              xtype: 'datecolumn',
              width: 120,
              dataIndex: 'targetDeliveryDate',
              stateId: 'targetDeliveryDate',
              filter: {
                  type: 'date',
                  dateFormat: Editor.DATE_ISO_FORMAT
              },
              text: me.text_cols.targetDeliveryDate
          },{
              xtype: 'datecolumn',
              width: 120,
              dataIndex: 'realDeliveryDate',
              stateId: 'realDeliveryDate',
              filter: {
                  type: 'date',
                  dateFormat: Editor.DATE_ISO_FORMAT
              },
              text: me.text_cols.realDeliveryDate
          },{
              xtype: 'owncheckcolumn',
              width: 45,
              cls: 'fullMatchEdit',
              dataIndex: 'edit100PercentMatch',
              stateId: 'edit100PercentMatch',
              filter: {
                  type: 'boolean'
              },
              tooltip: me.text_cols.fullMatchEdit,
              text: me.text_cols.fullMatchEdit
          },{
              xtype: 'owncheckcolumn',
              width: 45,
              cls: 'empty-targets',
              dataIndex: 'emptyTargets',
              stateId: 'emptyTargets',
              filter: {
                  type: 'boolean'
              },
              tooltip: me.strings.emptyTargets,
              text: me.text_cols.emptyTargets
          },{
              xtype: 'owncheckcolumn',
              width: 45,
              cls: 'lockLocked',
              dataIndex: 'lockLocked',
              stateId: 'lockLocked',
              filter: {
                  type: 'boolean'
              },
              tooltip: me.text_cols.lockLocked,
              text: me.text_cols.lockLocked
          },{
              xtype: 'owncheckcolumn',
              hidden: ! Editor.data.enableSourceEditing,
              hideable: Editor.data.enableSourceEditing,
              width: 55,
              cls: 'source-edit',
              dataIndex: 'enableSourceEditing',
              stateId: 'enableSourceEditing',
              filter: {
                  type: 'boolean'
              },
              tooltip: me.text_cols.enableSourceEditing,
              text: me.text_cols.enableSourceEditing
          }]
          },
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
                itemId:'pageingtoolbar',
                store: 'admin.Tasks',
                dock: 'bottom',
                displayInfo: true
            }]
        };
        
        if(Editor.data.debug && Editor.data.debug.showTaskGuid) {
            config.columns.items.unshift({
                xtype: 'gridcolumn',
                width: 60,
                dataIndex: 'id',
                stateId: 'id',
                filter: {
                    type: 'numeric'
                },
                text: 'id'
            },{
                xtype: 'gridcolumn',
                width: 140,
                dataIndex: 'taskGuid',
                stateId: 'taskGuid',
                filter: {
                    type: 'string'
                },
                text: 'taskGuid'
            },{
                xtype: 'gridcolumn',
                width: 120,
                dataIndex: 'workflow',
                stateId: 'workflow',
                renderer: function(val, meta, rec) {
                    return val+' ('+rec.get('workflowStepName')+')';
                },
                filter: {
                    type: 'string'
                },
                text: 'workflow'
            });
        }
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
  },
  /**
   * prepares (merges) the states, and cache it internally
   * @param wfMeta
   */
  prepareStates: function(wfMeta) {
      if(!wfMeta.mergedStates) {
          //copy the states:
          wfMeta.mergedStates = Ext.applyIf({}, wfMeta.states);
          //add the grid only pendingStates to the copied mergedStates Object:
          Ext.applyIf(wfMeta.mergedStates, wfMeta.pendingStates);
      }
      return wfMeta.mergedStates;
  },
  createToolTip: function() {
      var me = this;
      return Ext.create('Ext.tip.ToolTip', {
          target: me.view.el,
          delegate: 'td.task-users',
          dismissDelay: 0,
          showDelay: 200,
          maxWidth: 1000,
          renderTo: Ext.getBody(),
          listeners: {
              beforeshow: function updateTipBody(tip) {
                  var tr = Ext.fly(tip.triggerElement).up('tr'),
                      rec = me.view.getRecord(tr),
                      wf = rec.getWorkflowMetaData(),
                      data = {
                          states: wf.states,
                          roles: wf.roles,
                          users: rec.get('users')
                      };
                  if(!data.users || data.users.length == 0) {
                      tip.update(me.strings.noUsers);
                      return;
                  }
                  tip.update(me.userTipTpl.apply(data));
              }
          }
      });
  },
  onDestroy: function() {
      this.tooltip.destroy();
      this.callParent(arguments);
  }
});