
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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
Ext.Loader.setConfig({
  enabled: true
});
Ext.data.Connection.disableCaching = false;
Ext.data.JsonP.disableCaching = false;
Ext.data.proxy.Server.prototype.noCache = false;
Ext.Ajax.disableCaching = false;

Ext.ariaWarn = Ext.emptyFn; 

Ext.override(Ext.data.Connection, {
    timeout: 60000
});
Ext.Ajax.timeout = 60000;
Ext.override(Ext.data.proxy.Ajax, { timeout: 60000 });
Ext.override(Ext.form.action.Action, { timeout: 60 });

Ext.Loader.setPath('Editor.controller.Localizer', Editor.data.basePath+'/editor/js/app-localized.js');
Ext.Loader.setPath('Editor.plugins', Editor.data.pluginFolder);

Editor.DATE_ISO_FORMAT = 'Y-m-d H:i:s';
Editor.DATE_HOUR_MINUTE_ISO_FORMAT = 'Y-m-d H:i';
Editor.DATEONLY_ISO_FORMAT = 'Y-m-d';
Editor.DATE_TIME_LOCALIZED_FORMAT = Ext.form.field.Date.prototype.format +' '+ Ext.form.field.Time.prototype.format;//localized date time format

Ext.ClassManager.onCreated(function(className) {
    var boot = Ext.fly('loading-indicator-text');
    if(boot) {
        boot.update(className);
    }
    else {
        Ext.Logger.info("Lazy load of: "+className);
    }
});

Ext.application({
    name : 'Editor',
    models : [ 'File', 'Segment', 'admin.User', 'admin.Task', 'segment.Field','Config','TaskConfig','CustomerConfig'],
    stores : [ 'Files', 'ReferenceFiles', 'Segments', 'AlikeSegments', 'admin.Languages','UserConfig','admin.Config','admin.CustomerConfig','admin.task.Config'],
    requires: [
        'Editor.view.ViewPort',
        'Editor.view.ViewPortEditor',
        'Editor.view.ViewPortSingle',
        'Editor.model.ModelOverride', 
        'Editor.util.TaskActions',
        'Editor.util.messageBus.MessageBus',
        'Editor.util.messageBus.EventDomain',
        'Editor.util.HttpStateProvider'
    ].concat(Editor.data.app.controllers.require),
    controllers: Editor.data.app.controllers.active,
    appFolder : Editor.data.appFolder,
    windowTitle: '',
    viewport: null,
    //***********************************************************************************
    //Begin Events
    //***********************************************************************************
    /**
     * @event editorViewportClosed
     * Fires after the editor viewport was destroyed and before the other viewport is created.
     */
    /**
     * @event adminViewportClosed
     * Fires after the admin viewport was destroyed and before the other viewport is created.
     */
    /**
     * @event editorViewportOpened
     * Fires after the editor viewport was opened by the app (nothing to do with ext rendered or show).
     */
    /**
     * @event adminViewportOpened
     * Fires after the admin viewport was opened by the app (nothing to do with ext rendered or show).
     */
    
    /**
     * @event editorConfigLoaded
     * Fires after the task specific and customer specific config is loaded. After successful config load the editor viewport
     * will be opened
     */
    
    //***********************************************************************************
    //End Events
    //***********************************************************************************
    listen: {
        component: {
            'viewport > #adminMainSection': {
                tabchange: 'onAdminMainSectionChange'
            }
        }
    },
    init: function() {
        //enable json in our REST interface
        Ext.Ajax.setDefaultHeaders({
            'Accept': 'application/json'
        });
        //init the plugins namespace
        Ext.ns('Editor.plugins');
        
        //create and set the application state provider
        var provider=Ext.create('Editor.util.HttpStateProvider');
        //load the store data directly. With this no initial store load is required (the app state can be applied directly)
        provider.store.loadRawData(Editor.data.app.configData ? Editor.data.app.configData : []);
        Ext.state.Manager.setProvider(provider);
        
        this.callParent(arguments);
        this.logoutOnWindowClose();
    },
  
    launch : function() {
        var me=this;
        me.initViewportLaunch();
    },
  
    /***
     * Init and prepare the viewport for application launch
     */
    initViewportLaunch:function(){
        var me = this,
            viewSize = Ext.getBody().getViewSize(),
            initMethod = Editor.data.app.initMethod;
        
            Ext.QuickTips.init();
            me.windowTitle = Ext.getDoc().dom.title;
        
            me.authenticatedUser = Ext.create('Editor.model.admin.User', Editor.data.app.user);

            //Check if it is task route. If yes, use the redirect to task method.
            //if it is not a task route, open the administration
            //if no route is provided, use the defautl me[Editor.data.app.initMethod]();
            if(me.isEditTaskRoute()){
                //check if the taskid is provided in the hash
                var taskId=me.parseTaskIdFromTaskEditHash(false);
                if(taskId>0 && !Editor.data.task){
                    Editor.data.task={};
                    //set the taskId to the task global object
                    //translate5 will try to load task with this id
                    Editor.data.task.id = taskId;
                    //the state is edit since the route is for task editing
                    Editor.data.app.initState = 'edit';
                    initMethod = 'openEditor';
                }
            }else if(window.location.hash!=''){
                initMethod = 'openAdministration';
            }

            me[initMethod]();

            me.browserAdvice();
            Editor.MessageBox.showInitialMessages();
            
            //Logs the users userAgent and screen size for usability improvements:
            Ext.Ajax.request({
                url: Editor.data.pathToRunDir+'/editor/index/logbrowsertype',
                method: 'post',
                params: {
                    appVersion: navigator.appVersion,
                    userAgent: navigator.userAgent,
                    browserName: navigator.appName,
                    maxHeight: window.screen.availHeight,
                    maxWidth: window.screen.availWidth,
                    usedHeight: viewSize.height,
                    usedWidth: viewSize.width
                }
            });
            
            me.fireEvent('editorAppLaunched');
    },
    /**
     * If configured the user is logged out on window close
     */
    logoutOnWindowClose: function() {
        //ignore the logout on window close also when the current domain is configured 
        //to use openid sso provider and we redirect directly to the sso provider without showing
        //the translate5 login form
        var ignoreOnOpenIdRequest=Editor.data.frontend.isOpenIdCustomerDomain && Editor.data.frontend.isOpenIdOnLoginRedirect;
        if(!Editor.data.logoutOnWindowClose || ignoreOnOpenIdRequest) {
            return;
        }      
        var me=this,
            logout =function(e) {
                if(!Editor.data.logoutOnWindowClose){
                    return;
                }
                //send logout request, this will destroy the user session
                navigator.sendBeacon(Editor.data.pathToRunDir+'/login/logout');
                function sleep(delay) {
                    const start = new Date().getTime();
                    while (new Date().getTime() < start + delay);
                }
                //wait 0,5 second for the logout request to be processed
                //the beforeunload is also triggered with application reload(browser reload)
                //so we need to give the logout request some time untill the new page reload is requested
                sleep(500);
            };
        Ext.get(window).on({
            beforeunload:logout
        });
    },
    /**
     * opens the editor with the given Task
     * firing the adminViewportClosed event
     * @param {Editor.model.admin.Task} task
     */
    openEditor: function(task) {
        var me = this,
            languages = Ext.getStore('admin.Languages'),
            closeEvent;
        
        if(! (task instanceof Editor.model.admin.Task)) {
            me.openTaskDirect();
            return;
        }
        
        me.loadEditorConfigData(task,function(){
            
            me.fireEvent('editorConfigLoaded', me, task);
            
            Editor.data.task = task;
            Editor.model.Segment.redefine(task.segmentFields());
            
            Editor.data.taskLanguages = {
                source: languages.getById(task.get('sourceLang')),
                relais: languages.getById(task.get('relaisLang')),
                target: languages.getById(task.get('targetLang'))
            }
            
            if(me.viewport){
                //trigger closeEvent depending on which viewport was open
                closeEvent = me.viewport.isEditorViewport ? 'editorViewportClosed' : 'adminViewportClosed';
                me.viewport.destroy();
                me.fireEvent(closeEvent);
            }
            else {
                Ext.getBody().removeCls('loading');
                Ext.select("body > div.loading").destroy();
            }
            task.initWorkflow();
            me.getController('ViewModes').activate();
            me.viewport = Ext.create(Editor.data.app.viewport, {
                renderTo : Ext.getBody()
            });
            me.viewport.show();
            //vp.doLayout();
            //this.viewport.setLoading(true);
            /*
            - destroys all admin components, updates all editor stores. Inits the editor component if needed.
            */
            //enable logout split button
            //disable logout normal Button
            me.fireEvent('editorViewportOpened', me, task);
            Ext.getDoc().dom.title = me.windowTitle + ' - ' + task.getTaskName(); 
            me.getController('Fileorder').loadFileTree();//@todo bei ITL muss der load wiederum automatisch geschehen
        });
    },
    /**
     * Used to open a task directly by URL / page reload with already opened task / sessionAuth and not over task overview
     */
    openTaskDirect: function(){
        var me = this;
        Editor.model.admin.Task.load(Editor.data.task.id, {
            preventDefaultHandler: true,
            scope: me,
            success: function(task) {
                task.set('userState',Editor.data.app.initState);
                task.save({
                    scope: me,
                    preventDefaultHandler: true,
                    success: me.openEditor,
                    failure: me.handleOpenTaskDirectError
                });
            },
            failure: me.handleOpenTaskDirectError
        });
    },
    handleOpenTaskDirectError: function(record, op, success) {
        if(!Editor.data.editor.toolbar.hideLeaveTaskButton) {
            this.openAdministration();
            if(op.error.status == 404 && record.get('taskGuid') == '') {
                Editor.MessageBox.getInstance().showDirectError('The requested task does not exist anymore.');
            } else {
                Editor.app.getController('ServerException').handleFailedRequest(op.error.status, op.error.statusText, op.error.response);
            }
            return;
        }
        var loadBox = Ext.select("body > div.loading"),
            msg = '<div id="head-panel"></div>',
            response = op.error.response,
            title = 'Uups... The requested task could not be opened',
            respText = response && response.responseText;

        if(op.error.status == 404 && record.get('taskGuid') == '') {
            msg += '<h1>'+title+'</h1>The requested task does not exist anymore.';
        }else if(respText) {
            msg += Editor.app.getController('ServerException').renderHtmlMessage(title, Ext.JSON.decode(respText));
        }
        
        if(loadBox) {
            loadBox.setCls('loading-error');
            loadBox.update(msg);
        }
    },
    /**
     * opens the admin viewport
     * firing the editorViewportClosed event
     */
    openAdministration: function(task) {
        var me = this, tabPanel;
        if(!Editor.controller.admin || ! Editor.controller.admin.TaskOverview) {
            return;
        }
        if(me.viewport){
            me.getController('ViewModes').deactivate();
            me.viewport.destroy();
            me.fireEvent('editorViewportClosed');
        }
        else {
            Ext.getBody().removeCls('loading');
            Ext.select("body > div.loading").destroy();
        }
        me.viewport = Ext.create('Editor.view.ViewPort', {
            renderTo: Ext.getBody()
        });
        me.viewport.show();
        
        me.getController('admin.TaskOverview').loadTasks();
        
        /*
        - hides all editor components, inits all admin components, stores, etc.
        - empty editor stores
        */
        //disable logout split button
        //enable logout normal Button
        me.fireEvent('adminViewportOpened');
        tabPanel = me.viewport.down('#adminMainSection');

        // on intial load we have to trigger the change manually
        me.onAdminMainSectionChange(tabPanel, tabPanel.getActiveTab(),task);
        
        //set the value used for displaying the help pages
        Ext.getDoc().dom.title = me.windowTitle;
    },
    /**
     * requests the application to open the desired application section, and redirects the route to the given one
     */
    openAdministrationSection: function(panel, redirectRoute) {
        var me = this,
            mainTabs = me.viewport.down('> #adminMainSection');

        //what  happens if panel does not belong to the tabpanel?
        mainTabs.setActiveTab(panel);
        me.redirectTo(redirectRoute);
        
        //if we are in a task, we have to stop routing, leave it, and resume routing after the task was closed (a new one was loaded, for routing open tasks)
    },
    /**
     * If the main tab panel changes, we have to update the hash route for it
     * The first route in a panel is defined as the main route where the redirect should go
     * @param {Ext.tab.Panel} tabpanel
     * @param {Ext.Component} activatedPanel
     */
    onAdminMainSectionChange: function(tabpanel, activatedPanel,task) {
        var me = this,
            ctrl = activatedPanel.getController(),
            conf = ctrl && ctrl.defaultConfig,
            mainRoute = conf && conf.routes && Object.keys(conf.routes)[0];
        me.fireEvent('adminSectionChanged', activatedPanel);

        if(!mainRoute) {
            return;
        }
        me.redirectTo(mainRoute);
    },
    mask: function(msg, title) {
        if(!this.appMask) {
            this.appMask = Ext.widget('messagebox');
        }
        this.appMask.wait(msg, title);
    },
    unmask: function() {
        //no "this" usage, so we can use this method directly as failure handler 
        Editor.app.appMask && Editor.app.appMask.close();
    },
    logout: function() {
        window.location = Editor.data.loginUrl;
    },
    /**
     * sets the locale / language to be used by the application. Restarts the application.
     * @param {String} lang
     */
    setTranslation: function(lang) {
        var formSpec = {
                tag: 'form',
                action: window.location.href,
                method: 'POST',
                target: '_self',
                style: 'display:none',
                cn: [{
                    tag: 'input',
                    type: 'hidden',
                    name: 'locale',
                    value: Ext.String.htmlEncode(lang)
                }]
            },
            // Create the form
            form = Ext.DomHelper.append(Ext.getBody(), formSpec);

        Editor.data.logoutOnWindowClose = false;
        form.submit();
    },
    browserAdvice: function() {
        var me = this,
            supportedBrowser = false;
        //Feature disabled
        if(!Editor.data.supportedBrowsers) {
            return;
        }
        Ext.Object.each(Editor.data.supportedBrowsers, function(idx, version) {
            if(Ext.browser.name == idx && Ext.browser.version.major >= version) {
                supportedBrowser = true;
                return false;
            }
        });
        if(!supportedBrowser) {
            Ext.MessageBox.alert(me.browserAdviceTextTitle, me.browserAdviceText);
        }
    },
    
    /***
     * Get all classes with which are using the mixin
     */
    getClassesByMixin:function(mixinName){
        var classes=[];
        Ext.iterate(Ext.ClassManager.classes,function(className,c){
            if(c.prototype &&c.prototype.mixins &&  c.prototype.mixins[mixinName]){
                classes.push(className);
            }
        });
        return classes;
    },

     /**
     * Check if in the current hash, the edit task route is defined. The edit task route is only valid
     * when the segments-editor is opened
     */
    isEditTaskRoute:function(){
        return window.location.hash.startsWith('#task') && window.location.hash.endsWith('/edit')
    },

    /***
     * Get task id from the task edit route.
     * {Boolean} checkEditTaskRoute : validate if the current route is task edit route
     */
    parseTaskIdFromTaskEditHash:function(checkEditTaskRoute){
        if(checkEditTaskRoute && !this.isEditTaskRoute()){
            return -1;
        }
        //task edit route: task/:taskId/:segmentNrInTask/edit
        var h = window.location.hash.split('/');
        return (h && h.length>1) ? parseInt(h[1]) : -1;
    },

    /***
     * Get segmentNrInTask from the task edit route
     * {Boolean} checkEditTaskRoute : validate if the current route is task edit route
     */
    parseSegmentIdFromTaskEditHash:function(checkEditTaskRoute){
        if(checkEditTaskRoute && !this.isEditTaskRoute()){
            return -1;
        }
        //task edit route: task/:taskId/:segmentNrInTask/edit
        var h = window.location.hash.split('/');
        return (h && h.length==4) ? parseInt(h[2]) : -1;
    },
    
    /***
     * Load the task specific config store.
     */
    loadEditorConfigData:function(task,callback){
        var me=this,
            store = Ext.StoreManager.get('admin.task.Config');
        
        store.loadByTaskGuid(task.get('taskGuid'),function(records, operation, success){
            me.unmask();
            if(!success){
                Editor.app.getController('ServerException').handleCallback(records, operation, false);
                return;
            }
            store.clearFilter(true);
            callback();
        });
    },
    
    /***
     * Return the task specific config value by given config name
     */
    getTaskConfig:function(configName){
        return Ext.StoreManager.get('admin.task.Config').getConfig(configName);
    },
    
    /***
     * Get the user specific config by given config name.
     * INFO: currently the user configs are loaded also in the state provider and with that,
     * no need for separate user config store, just load the data from there.
     * 
     * {Boolean} returnRecord : the record will be returned instead of the record value
     */
    getUserConfig:function(configName,returnRecord){
        var store=Ext.state.Manager.getProvider().store,
            pos = store.findExact('name', 'runtimeOptions.'+configName),
            row;
        if (pos < 0) {
            return null;
        }
        row = store.getAt(pos);
        if(returnRecord){
            return row;
        }
        return row.get('value');
    }
});
