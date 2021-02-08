
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
  requires: [
      'Editor.view.admin.TaskActionColumn',
      'Editor.view.CheckColumn',
      'Editor.view.admin.customer.CustomerFilter',
      'Editor.view.admin.task.filter.AdvancedFilter',
      'Editor.view.admin.task.filter.PercentFilter',
      'Editor.view.admin.TaskGridViewController'
  ],
  controller: 'taskGrid',
  alias: 'widget.adminTaskGrid',
  itemId: 'adminTaskGrid',
  stateId: 'adminTaskGrid',
  stateful:true,
  cls: 'adminTaskGrid',
  title: '#UT#Aufgaben',
  helpSection: 'taskoverview',
  glyph: 'xf03a@FontAwesome5FreeSolid',
  plugins: ['gridfilters'],
  layout: {
      type: 'fit'
  },
  text_cols: { // in case of any changes, pls also update getTaskGridTextCols() in editor_Models_Task
      // sorted by appearance
      workflow: '#UT#Workflow',
      taskActions: '#UT#Aktionen',
      state: '#UT#Status (Aufgabe)',
      customerId: '#UT#Endkunde',
      taskName: '#UT#Name',
      taskNr: '#UT#Auftragsnr.',
      wordCount: '#UT#Wörter',
      wordCountTT: '#UT#Anzahl Wörter',
      fileCount: '#UT#Dateien',
      sourceLang: '#UT#Quellsprache',
      relaisLang: '#UT#Relaissprache',
      targetLang: '#UT#Zielsprache',
      referenceFiles: '#UT#Referenzdateien',
      terminologie: '#UT#Terminologie',
      userCount: '#UT#Zahl zugewiesener Benutzer',
      users: '#UT#Benutzer',
      taskassocs: '#UT#Anzahl zugewiesene Sprachressourcen',
      pmName: '#UT#Projektmanager',
      pmGuid: '#UT#Projektmanager',
      orderdate: '#UT#Bestelldatum',
      edit100PercentMatch: '#UT#100%-Treffer editierbar',
      fullMatchEdit: '#UT#100% Matches sind editierbar',
      emptyTargets: '#UT#Übersetzungsaufgabe (kein Review)',
      lockLocked: '#UT#In importierter Datei gesperrte Segmente sind in translate5 gesperrt',
      enableSourceEditing: '#UT#Quellsprache bearbeitbar',
      workflowState:'#UT#Workflow-Status',//Info:(This is not task grid column header) this is an advanced filter label text. It is used only for advanced filter label in the tag field
      workflowUserRole:'#UT#Benutzer-Rolle',//Info:(This is not task grid column header) this is an advanced filter label text. It is used only for advanced filter label in the tag field
      userName:'#UT#Benutzer',//Info:(This is not task grid column header) this is an advanced filter label text. It is used only for advanced filter label in the tag field
      segmentCount:'#UT#Segmentanzahl',
      segmentFinishCount:'#UT#% abgeschlossen',
      id:'#UT#Id',
      taskGuid:'#UT#Task-Guid',
      workflowStepName:'#UT#Aktueller Workflow-Schritt',
      userState:'#UT#Mein Job-Status',
      userJobDeadline:'#UT#Meine Deadline',
      assignmentDate:'#UT#Benutzer-Zuweisungsdatum',
      finishedDate:'#UT#Benutzer-Abschlussdatum',
      deadlineDate:'#UT#Benutzer-Deadline/s',
      assignmentDateHeader:'#UT#Zuweisungsdatum',
      finishedDateHeader:'#UT#Abschlussdatum',
      deadlineDateHeader:'#UT#Deadline Datum',
  },
  strings: {
      noRelaisLang: '#UT#- Ohne Relaissprache -',
      ended: '#UT#beendet',
      noUsers: '#UT#Keine Benutzer zugeordnet!',
      notFound: '#UT#nicht gefunden',
      locked: '#UT#in Arbeit',
      lockedBy: '#UT#Bearbeitet und Gesperrt durch {0}',
      lockedMultiUser: '#UT#In Bearbeitung durch:',
      lockedSystem: '#UT#Durch das System gesperrt mit dem Status \'{0}\'',
      addProject:'#UT#Projekt hinzufügen',
      addProjectTip:'#UT#Neues Projekt hinzufügen',
      exportMetaDataBtn: '#UT#Meta-Daten exportieren',
      exportMetaDataBtnTip: '#UT#Meta-Daten für alle gefilterten Aufgaben exportieren.',
      showKPIBtn: '#UT#Auswertungen anzeigen',
      showKPIBtnTip: '#UT#Auswertungen für alle gefilterten Aufgaben anzeigen.',
      reloadBtn: '#UT#Aktualisieren',
      reloadBtnTip: '#UT#Aufgabenliste vom Server aktualisieren.',
      emptyTargets: '#UT#Übersetzungsaufgabe - alle zielsprachlichen Segmente beim Import leer (nicht angehakt bedeutet Reviewaufgabe)."',
      addFilterTooltip:'#UT#Filter hinzufügen',
      currentWorkflowStepProgressTooltip:'#UT#% abgeschlossen durch zugewiesene Benutzer im aktuellen Workflowschritt',
      addFilterText:'#UT#Erweiterte Filter',
      jobStatus:'#UT#Job-Status',
      exelExportedTooltip:'#UT#Gesperrt da als Excel exportiert. Zum Entsperren Excel re-importieren. Falls Excel nicht zur Hand: Neu exportieren.',
  },
  states: {
      open: '#UT#offen',
      waiting: '#UT#wartend',
      finished: '#UT#abgeschlossen',
      end: '#UT#beendet',
      unconfirmed: '#UT#nicht bestätigt',
      error:'#UT#error',
      import: '#UT#import',
      locked: '#UT#in Arbeit',
      forMe: '#UT#für mich '
  },
  store: 'admin.Tasks',
  
  visibleColumns:[],//The configured columns will be visible by default (use itemId or stateId to define the visible column)
  
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
              if(user.isAllowed('downloadImportArchive', task)){
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
            '<tr>',
                '<th>#</th>',
                '<th>'+me.text_cols.userName+'</th>',
                '<th>'+me.text_cols.workflowUserRole+'</th>',
                '<th>'+me.strings.jobStatus+'</th>',
                '<th>'+me.strings.assignmentDateHeader+'</th>',
                '<th>'+me.strings.deadlineDateHeader+'</th>',
                '<th>'+me.strings.finishedDateHeader+'</th>',
            '</tr>',
            '<tpl for="users">',
            '<tr>',
                '<td class="">{#}</td>',
                '<td class="">{userName}</td>',
                '<td class="">{[this.getRole(parent, values)]}</td>',
                '<td class="">{[this.getState(parent, values)]}</td>',
                '<td class="">{[Ext.util.Format.date(values.assignmentDate,Editor.DATE_TIME_LOCALIZED_FORMAT)]}</td>',
                '<td class="">{[this.getDeadlineDate(values.deadlineDate)]}</td>',
                '<td class="">{[Ext.util.Format.date(values.finishedDate,Editor.DATE_TIME_LOCALIZED_FORMAT)]}</td>',
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
                },
                getDeadlineDate: function(date) {
                    
                    var deadlineDate=(date && date!='') && new Date(date);
                    
                    if(!deadlineDate){
                        return '';
                    }
                    if(deadlineDate < new Date()){
                        return '<span class="redTextColumn">'+Ext.util.Format.date(deadlineDate,Editor.DATE_TIME_LOCALIZED_FORMAT)+'</span>';
                    }
                    return Ext.util.Format.date(deadlineDate);
                }
            }
    );
    me.userStore = Ext.getStore('admin.Users');
    me.callParent(arguments);
    actions = me.down('taskActionColumn');

    me.availableActions = [];
    if(actions && actions.items.length > 0) {
        Ext.Array.each(actions.items, function(item) {
            me.availableActions=Ext.Array.push(me.availableActions,item.isAllowedFor);
        });
    }
    this.view.on('afterrender', function(){
        me.tooltip = me.createToolTip();
    });
    
    me.setVisibleColumns();
  },
  initConfig: function(instanceConfig) {
      var me = this,
          states = [],
          config,
          //we must have here an own ordered list of states to be filtered 
          userStates=['open','waiting','finished','unconfirmed'],//TODO get me from backend
          stateFilterOrder = ['open','locked','end','unconfirmed','import','error'],
          relaisLanguages = Ext.Array.clone(Editor.data.languages),
          addQtip = function(meta, text) {
              meta.tdAttr = 'data-qtip="' + Ext.String.htmlEncode(text)+'"';
          },
          multiUserTpl = new Ext.XTemplate(
                me.strings.lockedMultiUser,
                '<br>',
              '<tpl for=".">',
              '{userName} ({login})<br>',
              '</tpl>'
          );
          multiUserTpl.compile();
          
          //we're hardcoding the state filter options order, all other (unordered) workflow states are added below
          Ext.Array.each(stateFilterOrder, function(state){
              if(me.states[state]) {
                  states.push([state, me.states[state]]);
              }
          });
          
          Ext.Array.each(userStates, function(state){
              if(me.states[state]) {
                  userStates.push([state, me.states[state]]);
              }
          });
        
          //adding additional, not ordered states
          Ext.Object.each(Editor.data.app.workflows, function(key, workflow){
              Ext.Object.each(workflow.states, function(key, value){
                  if(!me.states[key]) {
                      userStates.push([key, me.states.forMe+' '+value]);
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
                  var wfMeta = rec.getWorkflowMetaData(),
                      allStates = me.prepareStates(wfMeta);

                  if(rec.isImporting() || rec.isErroneous()) {
                      addQtip(meta, me.errorTipTpl.apply(rec.get('lastErrors')));
                      return rec.get('state');
                  }
                  
                  if(rec.isLocked() && rec.isCustomState()) {
                      var statusTooltip = Ext.String.format(me.strings.lockedSystem, rec.get('state'));
                      //use different tooltip for exel exported tasks
                      if(rec.isExcelExported()){
                          statusTooltip = me.strings.exelExportedTooltip;
                      }
                      addQtip(meta, statusTooltip);
                      return me.strings.locked;
                  }
                  if(rec.isLocked() && rec.isUnconfirmed()) {
                      addQtip(meta, Ext.String.format(me.strings.lockedBy, rec.get('lockingUsername')));
                      return me.strings.locked;
                  }
                  if(rec.isUnconfirmed()) {
                      addQtip(meta, me.states.unconfirmed);
                      return me.states.unconfirmed;
                  }
                  //locked and editable means multi user editing
                  if(rec.isLocked() && rec.isEditable()) {
                      addQtip(meta, multiUserTpl.apply(rec.get('users')));
                      return me.strings.locked;
                  }
                  if(rec.isLocked()) {
                      addQtip(meta, Ext.String.format(me.strings.lockedBy, rec.get('lockingUsername')));
                      return me.strings.locked;
                  }
                  if(rec.isEnded()) {
                      addQtip(meta, me.strings.ended);
                      return me.strings.ended;
                  }
                  v = allStates[v] ? allStates[v] : v;
                  addQtip(meta, v);
                  return v;
              },
              text: me.text_cols.state,
              sortable: false
          },{
              xtype: 'gridcolumn',
              width: 70,
              dataIndex: 'userState',
              stateId: 'userState',
              text: me.text_cols.userState,
              filter: {
                  type: 'list',
                  options: userStates,
                  phpMode: false
              },
              renderer: function(v, meta, rec) {
                  var userState = rec.get('userState'),
                      wfMeta = rec.getWorkflowMetaData(),
                      allStates = me.prepareStates(wfMeta);
                  addQtip(meta, allStates[userState]);
                  return allStates[userState];
              }
          },{
              xtype: 'gridcolumn',
              width: 130,
              dataIndex: 'userAssocDeadline',
              stateId: 'userAssocDeadline',
              text: me.text_cols.userJobDeadline,
              renderer:me.userJobDeadlineRenderer
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
              width: 135,
              dataIndex: 'workflowStepName',
              stateId:'workflowStepName',
              tooltip: me.text_cols.workflowStepName,
              renderer: function(v) {
                  return me.getWorkflowStepNameTranslated(v);
                  
              },
              filter: {
                  type: 'list',
                  store:'admin.WorkflowSteps'
              },
              text: me.text_cols.workflowStepName
          },
          {
              xtype: 'gridcolumn',
              cls:'gridColumnInfoIconTooltipLeft',
              width: 135,
              dataIndex: 'segmentFinishCount',
              stateId:'segmentFinishCount',
              renderer:me.currentWorkflowStepProgressRenderer,
              tooltip:me.strings.currentWorkflowStepProgressTooltip,
              filter: {
                  type: 'percent',
                  totalField:'segmentFinishCount'
              },
              text: me.text_cols.segmentFinishCount
          },{
              xtype: 'gridcolumn',
              width: 70,
              dataIndex: 'segmentCount',
              stateId:'segmentCount',
              filter: {
                  type: 'numeric'
              },
              text: me.text_cols.segmentCount
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
                  glyph: 'f2f1@FontAwesome5FreeSolid',
                  itemId: 'reload-task-btn',
                  text: me.strings.reloadBtn,
                  tooltip: me.strings.reloadBtnTip
              },{
                  xtype: 'button',
                  glyph: 'f067@FontAwesome5FreeSolid',
                  itemId: 'add-project-btn',
                  text: me.strings.addProject,
                  hidden: ! Editor.app.authenticatedUser.isAllowed('editorAddTask'),
                  tooltip: me.strings.addProjectTip
              },{
                  xtype:'button',
                  itemId:'addAdvanceFilterBtn',
                  glyph: 'f0b0@FontAwesome5FreeSolid',
                  text:me.strings.addFilterText,
                  tooltip:me.strings.addFilterText
              },{
                  xtype: 'button',
                  glyph: 'f56e@FontAwesome5FreeSolid',
                  hidden:!Editor.app.authenticatedUser.isAllowed('editorTaskKpi'),
                  itemId: 'export-meta-data-btn',
                  text: me.strings.exportMetaDataBtn,
                  tooltip: me.strings.exportMetaDataBtnTip
              },{
                  xtype: 'button',
                  glyph: 'f46d@FontAwesome5FreeSolid',
                  hidden:!Editor.app.authenticatedUser.isAllowed('editorTaskKpi'),
                  itemId: 'show-kpi-btn',
                  text: me.strings.showKPIBtn,
                  tooltip: me.strings.showKPIBtnTip
              }]
            },{
                xtype: 'editorAdminTaskFilterAdvancedFilter'
            },{
                xtype: 'pagingtoolbar',
                itemId:'pageingtoolbar',
                store: 'admin.Tasks',
                dock: 'bottom',
                displayInfo: true
            },{
                xtype: 'label',
                hidden:!Editor.app.authenticatedUser.isAllowed('editorTaskKpi'),
                itemId: 'kpi-average-processing-time-label'
            },{
                xtype: 'label',
                hidden:!Editor.app.authenticatedUser.isAllowed('editorTaskKpi'),
                itemId: 'kpi-excel-export-usage-label'
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
                text: 'id',
                text: me.text_cols.id
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
                    return val+' ('+me.getWorkflowStepNameTranslated(rec.get('workflowStepName'))+')';
                },
                filter: {
                    type: 'string'
                },
                text: 'workflow'
            });
        }
        if (instanceConfig) {
            config=me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
  },
  
  /***
   * Set the configured visible columns
   */
  setVisibleColumns:function(){
      var me=this,
        cols = me.getColumns(),
        colIndex=null;
    
      if(me.visibleColumns.length==0){
          return;
      }
      
    Ext.each(cols, function(col) {
        colIndex=col.dataIndex ? col.dataIndex : col.stateId;
        if(!colIndex){
            return true;
        }
        col.setVisible(Ext.Array.contains(me.visibleColumns, colIndex));
    });
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
      if(this.tooltip){
          this.tooltip.destroy();
    }
    this.callParent(arguments);
  },
  
  /***
   * Set/add filter to the task grid from filter object.
   * If the filter is not found as grid column, it will only be applied to the store
   */
  activateGridColumnFilter: function(filters,suspendFilterchange) {
      var me=this;
      if(suspendFilterchange){
          me.suspendEvents('filterchange');
      }
      // for each filter object in the array
      Ext.each(filters, function(filter) {
        var value=filter.get('value'),
            operator=filter.get('operator'),
            property=filter.get('property'),
            gridFilter = me.getColumnFilter(property);
        
        if(!gridFilter){
            //the filter does not exist as column in the grid, filter the store with the filter params
            //INFO: this can be the case when the grid is filtered with one of the advanced filters
            me.getStore().addFilter({
                "id":property+operator,//use the property and operator as unique id
                "operator":operator,
                "value":value,
                "property":property
            });
            return true;
        }
        gridFilter.setActive(true);
        switch(gridFilter.type) {
            case 'date':
            case 'numeric':
            case 'percent':
                switch (operator) {
                    case 'gt' :
                        value = {gt: value};
                        break;
                    case 'lt' :
                        value = {lt: value};
                        break;
                    case 'eq' :
                        value = {eq: value};
                        break;
                }
                gridFilter.setValue(value);
                gridFilter.setActive(true);
                break;
            default :
                gridFilter.setValue(value);
                break;
        }
      });
      if(suspendFilterchange){
          me.resumeEvents('filterchange');
      }
    },
    
    /***
     * Get grid column filter by property
     */
    getColumnFilter:function(property){
        var cols = this.getColumns(),
            filter=null;
        Ext.each(cols, function(col) {
            if(col.filter && col.filter.dataIndex==property) {
                filter=col.filter;
                return false;
            }
        });
        return filter;
    },
    
    /***
     * Get task grid active filter/s by property
     */
    getActiveFilter:function(property){
        var me=this,
            returnFilter=[],
            activefilters = me.getStore().getFilters(false);
        activefilters.each(function(item){
            if(property==item.getProperty()){
                returnFilter.push(item);
            }
        });
        return returnFilter;
    },
    
    /***
     * Render the progres bar in the segmentFinishCount column
     */
    currentWorkflowStepProgressRenderer:function(value,meta,rec){
        if(!value || rec.get('segmentCount')<1){
            value=0;
        }
        if(value>0){
            value=value/rec.get('segmentCount');
        }
        value=Ext.util.Format.percent(value);
        meta.tdAttr = 'data-qtip="'+value+'"';
        return '<div class="x-progress x-progress-default" style="height: 13px;">'+
                    '<div class="x-progress-bar x-progress-bar-default" style="width: ' + value + '">'+
                    '</div>'+
               '</div>';
    },
    
    /***
     * Render the deadline dates for the current user.
     * Overdued dates will be displayed as red
     */
    userJobDeadlineRenderer:function(v, meta, rec) {
        if(!rec.get('users')){
          return '';
          }
        var me=this,
            users=rec.get('users'),
            values=[];
        
        for(var i=0;i<users.length;i++){
            var user=users[i],
                redClass="",
                deadlineDate=user['deadlineDate'] && new Date(user['deadlineDate']);
            
            if(!deadlineDate || user['userGuid']!=Editor.data.app.user['userGuid']){
                continue;
            }
            
            if(deadlineDate < new Date()){
                redClass="redTextColumn"
            }
            values.push('<span class="'+redClass+'">'+Ext.util.Format.date(deadlineDate,Editor.DATE_TIME_LOCALIZED_FORMAT)+'</span>');
        }
        return values.join(', ');
    },
    
    /***
     * Return the translated workflowStep name
     */
    getWorkflowStepNameTranslated:function(stepName){
        if(!stepName){
            return "";
        }
        var store=Ext.StoreManager.get('admin.WorkflowSteps'),
            rec=store.getById(stepName);
        if(rec){
            stepName=rec.get('text');
        }
        return stepName;
    }
    
});