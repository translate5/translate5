
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
 * Editor.controller.JsLogger encapsulates the jslogger-functionality
 * (for the jslogger, see jslogger.phtml).
 * @class Editor.controller.JsLogger
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.JsLogger', {
    extend : 'Ext.app.Controller',
    refs : [{
        ref : 'segmentGrid',
        selector : '#segmentgrid'
    }],
    listen: {
            component: {
                '#segmentgrid' : {
                    beforeedit: 'startVideoLogging',
                    canceledit: 'stopVideoLogging',
                    edit: 'stopVideoLogging'
                }
            },
            controller: {
                '#SnapshotHistory': {
                    addLogEntryToLogger: 'addLogEntryToLogger'
                },
                '#Editor.$application': {
                    editorAppLaunched: 'onEditorAppLaunched'
                },
                '#Editor.plugins.TrackChanges.controller.Editor': {
                    addLogEntryToLogger: 'addLogEntryToLogger'
                }
            }
    },
    strings: {
        msgTitle: '#UT#Videoaufnahme im Fehlerfall',
        msgInfo: '#UT#Helfen Sie bei der Entwicklung von translate5: Erlauben Sie Bildschirmvideos im Fehlerfall.<br /><br />Bei einer Reihe von Fehlern kann das Open Source-Entwicklungsteam von translate5 diese nur reproduzieren und beheben, wenn die Benutzeraktionen, die  zu dem translate5-Fehler geführt haben, als Video aufgenommen werden. Es wird nur aufgezeichnet, was in translate5 passiert. Die Videoaufnahme startet erst, wenn ein Segment zur Bearbeitung geöffnet ist, und sie endet, sobald die Bearbeitung des Segments endet.<br /><br />Das Team von translate5 wäre sehr dankbar, wenn Sie diese Funktion aktivieren - vielen Dank im Voraus!<br /><br />Auch wenn die Videoaufzeichnung aktiviert ist, werden Videoaufzeichnungen nur gesendet, wenn Sie einen aufgetretenen Fehler melden.<br /><br />Videos, die mit einem Fehler in Verbindung stehen und an den Server gesendet wurden, werden gelöscht, sobald sie zur Behebung des Fehlers nicht mehr benötigt werden. Der einzige Verwendungszweck der Videos ist die Entwicklung von translate5. Die Daten werden nur für diesen Zweck verwendet.<br /><br />Weitere Informationen zur Videoaufzeichnung mit theRootCause und zum Datenschutz finden Sie unter https://confluence.translate5.net/x/EAArBw'
    },
    
    hasJsLogger: false,
    enableJsLoggerVideoUser: false,
    
    /**
     * If jslogger (see jslogger.phtml) is included, we call initJsLogger() for further settings.
     * We do this once after the Editor-App is launched because customers might use the Editor only
     * (= without the rest of translate5), but the user's decision about activating the video-recording
     * refers to everything they do as soon as they are logged in (don't ask them multiple times eg every time
     * they open the Editor).
     */
    onEditorAppLaunched: function () {
       if(window.jslogger) this.initJsLogger();
    },
    
    /**
     * (1) Sets that jslogger exists and
     * (2) asks the user to decide about the video-recording.
     */
    initJsLogger: function () {
        this.hasJsLogger = true;
        this.setJsLoggerVideoByUser();
    },
    
    /**
     * Returns true if we don't use a jsLogger at all.
     * @return {Boolean}
     */
    hasNoJsLogger: function() {
        return !this.hasJsLogger;
    },
    
    /**
     * Returns true if we don't use video-recording.
     * @return {Boolean}
     */
    hasNoVideorecording: function() {
        return !this.enableJsLoggerVideoUser;
    },
    
    /**
     * If the video recording is activated in Zf_configuration,
     * the user gets a message and can activate the video-recording.
     */
    setJsLoggerVideoByUser: function() {
        var me = this;
        if (me.hasNoJsLogger()) {
            return;
        }
        if (!Editor.data.enableJsLoggerVideoConfig) {
            return;
        }
        
        var myMsg = Ext.create('Ext.window.MessageBox', {
            // set closeAction to 'destroy' if this instance is not
            // intended to be reused by the application
            closeAction: 'destroy'
        }).show({
            title: me.strings.msgTitle,
            message:  me.strings.msgInfo,
            buttons: Ext.Msg.YESNO,
            fn: function(btn) {
                if (btn === 'yes') {
                	 me.enableJsLoggerVideoUser = true;
                }
            }
        });
    },
    
    // ------------------------------------------------------------------
    // Helpers for therootcause-Logger
    // ------------------------------------------------------------------
    
    /**
     * Add the given logMessage as Editor-content to therootcause-logger.
     * @param {String} logMessage
     */
    addLogEntryToLogger: function(logMessage) {
        if (this.hasNoJsLogger()) {
            return;
        }
        jslogger.addLogEntry({ type : 'info', message : logMessage});
    },
    
    /**
     * Start the video-recording of therootcause-logger if video-recording is enabled.
     */
    startVideoLogging: function() {
        if (this.hasNoJsLogger() || this.hasNoVideorecording()) {
            return;
        }
        jslogger.startVideoRecording();
    },
    
    /**
     * Stop the video-recording of therootcause-logger if video-recording is enabled.
     */
    stopVideoLogging: function() {
        if (this.hasNoJsLogger() || this.hasNoVideorecording()) {
            return;
        }
        jslogger.stopVideoRecording();
    }
});