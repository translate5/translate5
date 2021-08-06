
/*
START LICENSE AND COPYRIGHT

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a plug-in for translate5. 
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 For the license of this plug-in, please see below.
 
 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and 
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the 
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html
   
 There is a plugin exception available for use with this release of translate5 for 
 translate5 plug-ins that are distributed under GNU GENERAL PUBLIC LICENSE version 3: 
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/gpl.html
             http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Main Controller of the Visual Review
 * Defines the Layout of the review Panel and it's controls, listens to the relevant global events and perocesses them
 * 
 * @class Editor.plugins.Okapi.controller.Global
 * @extends Ext.app.Controller
 */
Ext.define('Editor.plugins.Okapi.controller.BconfPrefs', {
    extend: 'Ext.app.Controller',
    /*
    requires: ['Editor.plugins.Okapi.view.preferences.BconfGrid'],
    models: ['Editor.plugins.Okapi.model.Bconf'],
    stores: ['Editor.plugins.Okapi.store.Bconf'],
    */
    listen: {
        component: {
            '#preferencesOverviewPanel': {
                added: 'addBconfToOverviewPanel',
                beforeshow: 'showBconfInOverviewPanel',
            }
        }
    },
    refs : [{
        ref : 'preferencesOverviewPanel',
        selector : '#preferencesOverviewPanel'
    }],
    routes: {
        'bconfprefs': 'onBconfRoute'
    },
    // just a reference to our view
    bconfPanel: null,
    // shows the preference panel in the preferences (bconf-section is shown via 'showBconfInOverviewPanel' afterwards)
    onBconfRoute: function() {
        
        console.log('onBconfRoute');
        return;
        
        if(Editor.app.authenticatedUser.isAllowed('pluginOkapiFontPrefs')){
            // QUIRK: just to make sure, not the same thing can happen as with Quirk in ::showBconfInOverviewPanel
            var pop = this.getPreferencesOverviewPanel();
            if(pop){
                Editor.app.openAdministrationSection(pop, 'reviewbconf');
            }
        }
    },
    // adds the Font-Prefs-Panel to the Overview Panel if the right is present
    addBconfToOverviewPanel: function(panel, opts){
        
        console.log('addBconfToOverviewPanel', panel, opts);
        return;
        
        if(Editor.app.authenticatedUser.isAllowed('pluginOkapiFontPrefs')){
            this.bconfPanel = panel.add({xtype: 'okapiBconf'});
        }
    },
    // shows the bconf-section in the preferences panel if the hash tells so
    showBconfInOverviewPanel: function(panel, opts){
        
        console.log('showBconfInOverviewPanel', panel, opts);
        return;
        
        if(window.location.hash == '#reviewbconf' && Editor.app.authenticatedUser.isAllowed('pluginOkapiBconfPrefs')){
            // TODO QUIRK: How can we be instantiated without the overview-Panel not being instatiated ? It happens, when the #reviewbconf hash is set on login
            var pop = this.getPreferencesOverviewPanel();
            if(pop){
                pop.setActiveItem(this.bconfPanel);
            }
        }
    }
});
