
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
 * Mixin with Helpers regarding the Event
 * @class Editor.util.Event
 */
Ext.define('Editor.util.Event', {
    mixins: ['Editor.util.DevelopmentTools',
             'Editor.util.SegmentEditor'],

    event: null,                        // for KeyboardEvent (from Ext.event.Event)
    ignoreEvent: false,                 // ignore event? (= we do nothing here)
    stopEvent: false,                   // do we stop the event here?
    
    extEv: Ext.event.Event,
    
    /**
     * Has the Key-Event to be IGNORED?
     * @returns {Boolean}
     */
    eventHasToBeIgnored: function() {
        var me = this,
            keyCodesToIgnore = [
                                me.extEv.LEFT, me.extEv.UP, me.extEv.RIGHT, me.extEv.DOWN,          // Arrow Keys
                                me.extEv.ALT, me.extEv.CAPS_LOCK, me.extEv.CTRL, me.extEv.SHIFT,    // Modifier Keys
                                me.extEv.HOME, me.extEv.END, me.extEv.PAGE_UP, me.extEv.PAGE_DOWN,  // Special Keys
                                me.extEv.F1, me.extEv.F2, me.extEv.F3, me.extEv.F4, me.extEv.F5, me.extEv.F6, me.extEv.F7, me.extEv.F8, me.extEv.F9, me.extEv.F10, me.extEv.F11, me.extEv.F12, // Function Keys
                                me.extEv.CONTEXT_MENU                                               // Branded Keys
                               ];
        if(me.eventIsTranslate5()) {
            return true;                                                                            // Keyboard-Shortcuts in Translate5
        }
        if(me.event.altKey) {
            return true;                                                                            // ALT-Key pressed during event
        }
        if(me.event.ctrlKey) {
            keyCodesToIgnore.push(me.extEv.A);                                                      // Ctrl+A
            keyCodesToIgnore.push(me.extEv.C);                                                      // Ctrl+C
            keyCodesToIgnore.push(171);                                                             // Ctrl+-
            keyCodesToIgnore.push(173);                                                             // Ctrl++
            keyCodesToIgnore.push(190);                                                             // Ctrl+.
        }
        if(!Ext.isOpera) {
            keyCodesToIgnore.push(91, 92, 93, 224);                                                 // Branded keys cont.; see also: http://unixpapa.com/js/key.html
        }
        return (keyCodesToIgnore.indexOf(me.event.getKey()) != -1);
    },
    /**
     * Has the Key-Event to be IGNORED and STOPPED?
     * @returns {Boolean}
     */
    eventHasToBeIgnoredAndStopped: function() {
        var me = this,
            keyCodesToIgnoreAndStop = [me.extEv.ENTER];                                             // Enter: not allowed (in general, but in TrackChange things would get messy.).
        return (keyCodesToIgnoreAndStop.indexOf(me.event.getKey()) != -1);
    },
    /**
     * Is the Key-Event an Arrow-Key? (can eg indicate that the user is still editing)
     * @returns {Boolean}
     */
    eventIsArrowKey: function() {
        var me = this,
            keyCodesForArrows = [me.extEv.LEFT, me.extEv.UP, me.extEv.RIGHT, me.extEv.DOWN];        // Arrow Keys
        return (keyCodesForArrows.indexOf(me.event.getKey()) != -1);
    },
    /**
     * Is the Key-Event a DELETION?
     * @returns {Boolean}
     */
    eventIsDeletion: function() {
        var me = this,
            keyCodesForDeletion = [me.extEv.BACKSPACE, me.extEv.DELETE];
        if(!me.event){
            return false;
        }
        var res = (keyCodesForDeletion.indexOf(me.event.getKey()) != -1);
        return (keyCodesForDeletion.indexOf(me.event.getKey()) != -1);
    },
    /**
     * Is the Key-Event an INSERTION?
     * @returns {Boolean}
     */
    eventIsInsertion: function() {
        var me = this;
        //cut, copy and paste is handled via the according events, so the keyboard events itself are not doing an insertion itself
        if(me.eventIsCtrlV() || me.eventIsCtrlC() || me.eventIsCtrlX()) {
            return false; 
        }
        return true;
    },
    /**
     * Is the Key-Event an insertion by Ctrl+V?
     * @returns {Boolean}
     */
    eventIsCtrlV: function() {
        var me = this;
        if(!me.event){
            return;
        }
        return  me.event.ctrlKey && (me.event.getKey() == me.extEv.V);                              // Ctrl+V
    },
    /**
     * Is the Key-Event a deletion by Ctrl+X?
     * @returns {Boolean}
     */
    eventIsCtrlX: function() {
        var me = this;
        if(!me.event){
            return;
        }
        return  me.event.ctrlKey && (me.event.getKey() == me.extEv.X);                              // Ctrl+X
    },
    /**
     * Is the Key-Event by Ctrl+C?
     * @returns {Boolean}
     */
    eventIsCtrlC: function() {
        var me = this;
        if(!me.event){
            return;
        }
        return  me.event.ctrlKey && (me.event.getKey() == me.extEv.C);                              // Ctrl+C
    },
    /**
     * Is the Key-Event Ctrl+Y?
     * @returns {Boolean}
     */
    eventIsCtrlY: function() {
        var me = this;
        if(!me.event){
            return;
        }
        return  me.event.ctrlKey && (me.event.getKey() == me.extEv.Y);                              // Ctrl+Y (not ignored, but extra handling)
    },
    /**
     * Is the Key-Event Ctrl+Z?
     * @returns {Boolean}
     */
    eventIsCtrlZ: function() {
        var me = this;
        if(!me.event){
            return;
        }
        return  me.event.ctrlKey && (me.event.getKey() == me.extEv.Z);                              // Ctrl+Z (not ignored, but extra handling)
    },
    /**
     * Is the Key-Event a Keyboard-Shortcut in Translate5?
     * (see keyMapConfig in Editor.controller.Editor)
     * @returns {Boolean}
     */
    eventIsTranslate5: function() {
        var me = this,
            editorKeyMap,
            lastWasDigitPreparation,
            editorKeyMapBinding,
            editorKeyMapBindingKeys,
            eventAlt,
            eventCtrl,
            eventShift,
            keyName = '',
            isHandledByKeyMapConfig = false;
        
        if (!me.editor) {
            return;
        }
        
        editorKeyMap = me.editor.editorKeyMap;
        lastWasDigitPreparation = editorKeyMap.lastWasDigitPreparation;
        
        // check if our event is prepared for being handled by handleDigit()
        if (lastWasDigitPreparation) {
            me.consoleLog("(lastWasDigitPreparation => track nothing.)")
            return true;
        }
        
        // check if our event (pressed key, alt, ctrl, shift) is an item in the keyMapConfig
        eventAlt = me.event.altKey;
        eventCtrl = me.event.ctrlKey;
        eventShift = me.event.shiftKey;
        editorKeyMapBinding = editorKeyMap.binding;
        Ext.each(editorKeyMapBinding, function(name, index, bindings) {
            // check alt, ctrl, shift
            if ((bindings[index].alt == undefined || bindings[index].alt==eventAlt)
                    && (bindings[index].ctrl == undefined || bindings[index].ctrl==eventCtrl)
                    && (bindings[index].shift == undefined || bindings[index].shift==eventShift)) {
                // check key
                editorKeyMapBindingKeys = (Ext.typeOf(bindings[index].key) == "string") ? new Array(bindings[index].key) : bindings[index].key;
                Ext.each(editorKeyMapBindingKeys, function(bindedKey) {
                    if (Ext.typeOf(bindedKey) == "string") {
                        bindedKey = bindedKey.toLowerCase();
                    }
                    if (me.event.getKeyName() != undefined) {
                        keyName = me.event.getKeyName().toLowerCase();
                    }
                    // for characters: getKeyName(); for digits: getKey()
                    if (bindedKey == keyName || bindedKey == me.event.getKey() ) {
                        // Caution: ALL DIGITS without alt and without ctrl ARE handled by keyMapConfig, but only for checking the handleDigit()-procedure.
                        if (Ext.Array.contains(me.editor.DEC_DIGITS,bindedKey)) {
                            isHandledByKeyMapConfig = (bindings[index].defaultEventAction == 'stopEvent');
                            me.consoleLog("(is DEC_DIGITS; defaultEventAction checked; handled by keyMapConfig?: " + isHandledByKeyMapConfig + ".)");
                        } else {
                            me.consoleLog("(handled by keyMapConfig, see there for what it does.)");
                            isHandledByKeyMapConfig = true;
                        }
                    }
                    // continue iterating the keys for this item?
                    if (isHandledByKeyMapConfig) {
                        return false;  // breaks the iteration only (http://docs.sencha.com/extjs/6.2.0/classic/Ext.Array.html#method-each)
                    }
                 });
            }
            // continue iterating the items in the editorKeyMap?
            if (isHandledByKeyMapConfig) {
                return false;  // breaks the iteration  only (http://docs.sencha.com/extjs/6.2.0/classic/Ext.Array.html#method-each)
            }
        });
        return isHandledByKeyMapConfig;
    }
});