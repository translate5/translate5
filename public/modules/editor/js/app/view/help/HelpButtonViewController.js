
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
 * @class Editor.view.HeadPanelViewController
 * @extends Ext.app.ViewController
 */


Ext.define('Editor.view.help.HelpButtonViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.helpButton',
    listen: {
        controller: {
            '#Editor.$application': {
                //FIXME könnte eigentlich durch on route changes ersetzt werden, dann bräuchts kein eigenes event mehr...
                // → das geht aber nur wenn #task standardmäßig getriggert wird!
                adminSectionChanged: 'onApplicationSectionChanged',
                editorViewportOpened: 'onEditorViewportOpened'
            }
        },
        component: {
            '#mainHelpButton':{
                click:'onMainHelpButtonClick'
            }
        }
    },
    
    onMainHelpButtonClick:function(){
        this.showHelpWindow();
    },
    
    /***
     * Check if the help window should be shown based on multiple conditions.
     * 
     * If the defined loader returns empty content, the window is not shown.
     * If the doNotShowAgain(user state propertie) is boolean true, the window is not shown.
     */
    showHelpWindow: function(doNotShowAgain) {
        var me = this,
            win = Ext.widget('helpWindow');
        
        win.setTitle(me.getView().text + ' - ' + Editor.data.helpSectionTitle)

        //if no loader is defined, the content is remote -> is not the default page
        if(!win.getLoader()){
            me.getView().setHidden(false);
            if(doNotShowAgain === true){
                return;
            }
            win.show();
            return;
        }
        
        //check the loader content. If the response returns empty string there is no need to 
        // show the help window
        win.getLoader().on({
            load:function(cmp,response){
                //if the loader returns empty content, do not show the button and the window
                if(response.responseText==""){
                    me.getView().setHidden(true);
                    return;
                }
               
                me.getView().setHidden(false);

                //ig do not show again user state config is true, do not show the window
                if(doNotShowAgain === true){
                    return;
                }
                win.show();
            }
        });
    },
    
    onEditorViewportOpened: function() {
        this.onApplicationSectionChanged(this.getView().up('#segmentgrid'));
    },

  /***
   * On component view change event handler. This event is a global event.
   */
  onApplicationSectionChanged: function(panel){
      Editor.data.helpSection = panel.helpSection || panel.xtype;
      Editor.data.helpSectionTitle = panel.getTitle();
      
      var me = this,
          sectionConfig = Editor.data.frontend.helpWindow[Editor.data.helpSection];
      
      if(!sectionConfig || sectionConfig.loaderUrl === ""){
          //the button is not visible when there is not url defined for the section
          me.getView().setHidden(true);
          return;
      }
      //the help button exist, call the show help window function
      me.showUserStateHelpWindow();
  },

  
  /***
   * Show the help window only it is alowed for the curent userstate
   */
  showUserStateHelpWindow:function(){
      var me=this,
          provider=Ext.state.Manager.getProvider(),
          helpWindowState=provider.get(Editor.view.help.HelpWindow.getStateIdStatic());

      if(!helpWindowState){
          //no state is found in the state provider
          return;
      }
      
      me.showHelpWindow(helpWindowState.doNotShowAgain);
  }
});