
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
                }
            }
    },
    hasJsLogger: false,
    enableJsLoggerVideoUser: false,
    
    /**
     * If jslogger (see jslogger.phtml) is included, we call initJsLogger() for further settings.
     */
    init: function () {
        try {
            jslogger && this.initJsLogger();
        } catch(err) {
            // = no jslogger
        }
    },
    
    /**
     * (1) Sets that jslogger exists and
     * (2) makes the user set the video-recording.
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
        var me = this,
            title,
            info;
        if (me.hasNoJsLogger()) {
            return;
        }
        if (!Editor.data.enableJsLoggerVideoConfig) {
            return;
        }
        // TODO: title and info-text
        title = 'enableJsLoggerVideo',
        info = 'Help translate5\'s development with screen videos in case of errors...';
        Ext.Msg.confirm(title, info, function(btn){
            if(btn === 'yes') {
                me.enableJsLoggerVideoUser = true;
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