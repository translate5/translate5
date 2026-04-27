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

Ext.Loader.loadScript(Editor.data.pluginFolder + '/SpellCheck/custom/spell-check.js');

Ext.define('Editor.plugins.SpellCheck.controller.Editor', {
    extend: 'Ext.app.Controller',
    requires: ['Editor.util.SegmentContent', 'Editor.plugins.SpellCheck.view.SpellCheckTooltip'],

    mixins: ['Editor.util.DevelopmentTools', 'Editor.util.Event'],

    listen: {
        controller: {
            '#Editor': {
                beforeKeyMapUsage: 'addSpellcheckKeymapEntries',
            },
            '#Editor.$application': {
                editorViewportClosed: 'onDestroy',
            },
        },
        component: {
            '#t5Editor': {
                afterInstantiateEditor: 'onEditorInstantiate',
                beforeStartEdit: 'onStartEdit',
            },
            '#segmentgrid': {
                afterRender: 'initLanguages',
            },
            '#segmentStatusStrip #btnRunSpellCheck': {
                click: 'startSpellCheckViaButton',
            },
        },
    },

    statics: {
        ATTRIBUTE_ACTIVEMATCHINDEX: 'data-spellCheck-activeMatchIndex',
        // Milliseconds to pass before SpellCheck is started when no editing occurs
        EDIT_IDLE_MILLISECONDS: 1000,
    },

    // =========================================================================

    allMatches: null,

    spellCheckTooltip: null, // Ext.menu.Menu ("ToolTip"-instance)
    contextMenuHandler: null,

    // time since "nothing" is changed in the Editor's content;
    // 1) user: presses no key 2) editor: no push, no afterInsertMarkup
    editIdleTimer: null,

    spellCheckInProgressID: false, // id of the currently valid SpellCheck-Process (false if none is running)

    // TRANSLATE-1630 "Workaround for East Asian problems with spellchecker"
    // target-languages that cause problems when using SpellCheck via keyboard-idle
    languagesToStopIdle: ['ja', 'ko', 'zh'],

    /** @type SpellCheck */
    spellCheck: null,
    tagsConversion: null,

    /**
     * Per-field language config, keyed by 'source' | 'target'.
     * Each entry: { isSupportedLanguage: bool|undefined, longCode: string|null, disableSpellCheckByIdle: bool|null }
     * @type {Object}
     */
    languageConfig: null,

    /**
     * The field type currently open for editing: 'source' | 'target'
     * @type {string}
     */
    currentFieldType: 'target',

    // region Init and Destroy

    init: function () {
        this.callParent(arguments);
        this.languageConfig = {
            source: { isSupportedLanguage: undefined, longCode: null, disableSpellCheckByIdle: null },
            target: { isSupportedLanguage: undefined, longCode: null, disableSpellCheckByIdle: null },
        };
        this.consoleLog('0.1 init Editor.plugins.SpellCheck.controller.Editor');
    },

    onDestroy: function () {
        if (this.editor && this.contextMenuHandler) {
            this.editor.getEditorBody().removeEventListener('contextmenu', this.contextMenuHandler);
            this.contextMenuHandler = null;
        }

        this.terminateSpellCheck();

        if (this.spellCheckTooltip) {
            this.spellCheckTooltip.destroy();
            this.spellCheckTooltip = null;
        }

        Ext.dom.GarbageCollector.collect();
        this.editor = null;
        this.consoleLog('---------------- SpellCheck: onDestroy FINISHED. -------------');
    },

    /**
     * Init basics for both source and target languages.
     */
    initLanguages: function () {
        this.consoleLog('0.2 SpellCheck: initLanguages.');
        const languages = this.getTaskLanguages();

        for (const [field, langCode] of Object.entries(languages)) {
            this.languageConfig[field].disableSpellCheckByIdle = this.computeDisableSpellCheckByIdle(langCode);

            if (this.languageConfig[field].disableSpellCheckByIdle && field === 'target') {
                // Only add button once (for the target field, which is the default editing field)
                const editorComponent = Ext.ComponentQuery.query('t5editor')[0];

                if (editorComponent && editorComponent.statusStrip) {
                    this.addSpellCheckButton(editorComponent.statusStrip);
                }
            }

            this.fetchLanguageSupport(field, langCode);
        }
    },

    onStartEdit: function (editorView) {
        const fieldType = editorView.fieldTypeToEdit || 'target';

        if (fieldType === this.currentFieldType) {
            return;
        }

        this.consoleLog(`SpellCheck: onStartEdit - switching field to: ${fieldType}`);
        this.currentFieldType = fieldType;
        this.terminateSpellCheck();
        this.setBrowserSpellcheck();
    },

    onEditorInstantiate: function (editor) {
        this.consoleLog('0.3 SpellCheck: initEditor.');
        this.spellCheck = new SpellCheck();
        this.editor = editor;
        this.tagsConversion = editor.editor.getTagsConversion();
        this.initTooltips();

        this.editor.editor.registerModifier(
            RichTextEditor.EditorWrapper.EDITOR_EVENTS.DATA_CHANGED,
            (text, actions, position) =>
                this.spellCheck.cleanSpellcheckOnTypingInside(text, actions, position, this.tagsConversion),
            2,
        );
        this.editor.editor.registerAsyncModifier(
            RichTextEditor.EditorWrapper.EDITOR_EVENTS.DATA_CHANGED,
            async (text, runId) => {
                return this.startSpellCheckViaTimer(text, runId);
            },
        );

        this.initEvents();
        this.setBrowserSpellcheck();
    },

    initTooltips: function () {
        if (this.spellCheckTooltip) {
            return;
        }

        /** @type {Editor.plugins.SpellCheck.controller.SpellChecker} */
        const spellChecker = Editor.app.getController('Editor.plugins.SpellCheck.controller.SpellChecker');

        this.spellCheckTooltip = Ext.create('Editor.plugins.SpellCheck.view.SpellCheckTooltip', {
            callbacks: {
                onReplace: (quality, replacement) => {
                    this.applyReplacement(quality, replacement);
                    this.reloadFalsePositivePanel(this.editor.currentlyEditingRecord.getId());
                },
                onReplaceAll: (quality, replacement, saveAsDraft) => {
                    this.applyReplacement(quality, replacement);

                    const l10n = Editor.data.l10n.SpellCheck;
                    Ext.Msg.show({
                        title: l10n.replaceAllConfirmTitle,
                        message: l10n.replaceAllConfirmMessage,
                        buttons: Ext.Msg.OKCANCEL,
                        buttonText: {
                            ok: l10n.confirmProceed,
                            cancel: l10n.confirmCancel,
                        },
                        fn: (btn) => {
                            if (btn !== 'ok') {
                                return;
                            }

                            // this.getSegmentGrid().editingPlugin.cancelEdit();
                            spellChecker.replaceAllWith(quality.id, replacement, saveAsDraft, null, true);
                        },
                    });
                },
                onIgnore: (quality, segment) => {
                    (quality.falsePositive ? spellChecker.unignore(quality.id) : spellChecker.ignore(quality.id))
                        .then(() => {
                            quality.falsePositive = !quality.falsePositive;
                            this.reloadFalsePositivePanel(this.editor.currentlyEditingRecord.getId());
                        })
                        .catch();
                },
                onIgnoreAll: (quality, segment) => {
                    const l10n = Editor.data.l10n.SpellCheck;
                    Ext.Msg.show({
                        title: l10n.ignoreAllConfirmTitle,
                        message: l10n.ignoreAllConfirmMessage,
                        buttons: Ext.Msg.OKCANCEL,
                        buttonText: {
                            ok: l10n.confirmProceed,
                            cancel: l10n.confirmCancel,
                        },
                        fn: (btn) => {
                            if (btn !== 'ok') {
                                return;
                            }

                            // this.getSegmentGrid().editingPlugin.cancelEdit();
                            (quality.falsePositive
                                ? spellChecker.unignoreAll(quality.id)
                                : spellChecker.ignoreAll(quality.id)
                            )
                                .then(() => {
                                    this.reloadFalsePositivePanel(this.editor.currentlyEditingRecord.getId());
                                })
                                .catch();
                        },
                    });
                },
                // onSaveDraftChange: (checked) => {},
            },
        });
    },

    initEvents: function () {
        this.consoleLog('SpellCheck: initEvents...');

        this.contextMenuHandler = (event) => {
            const spellcheckClass = Editor.util.HtmlClasses.CSS_CLASSNAME_SPELLCHECK;
            let node = event.target;

            // Go upper until .t5spellcheck-node is met, if possible
            while (node.classList && !node.classList.contains(spellcheckClass) && node.parentNode) {
                node = node.parentNode;
            }

            if (node.nodeType === Node.DOCUMENT_NODE || !node.classList.contains(spellcheckClass)) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            this.showToolTip(node, event.pageX, event.pageY);

            return false;
        };

        this.editor.getEditorBody().addEventListener('contextmenu', this.contextMenuHandler);
    },

    addSpellcheckKeymapEntries: function (cont, area) {
        if (area === 'editor') {
            //same shortcut in Microsoft Word
            cont.keyMapConfig['Shift+F7'] = [
                Ext.event.Event.F7,
                { ctrl: false, alt: false, shift: true },
                this.startSpellCheckViaShortcut,
                true,
                this,
            ];
        }

        cont.keyMapConfig['CTRL+R'] = [
            Ext.event.Event.R,
            { ctrl: true, alt: false, shift: false },
            this.showToolTipViaShortcut,
            true,
            this,
        ];
    },

    /**
     * Adds a "Run SpellCheck"-Button to the StatusStrip.
     * @param {Editor.view.segments.StatusStrip} statusStrip
     */
    addSpellCheckButton: function (statusStrip) {
        if (statusStrip.down('#btnRunSpellCheck')) {
            return;
        }

        statusStrip.add({
            xtype: 'button',
            cls: 'spellcheck-icon',
            text: 'SpellCheck',
            itemId: 'btnRunSpellCheck',
            tooltip: 'F7',
        });
    },

    // endregion Init and Destroy

    // region Start spellcheck
    /**
     * Start SpellCheck after idle (= when the user stopped editing for a certain time, see EDIT_IDLE_MILLISECONDS).
     */
    startSpellCheckViaTimer(text, runId) {
        return new Promise((resolve, reject) => {
            const config = this.languageConfig[this.currentFieldType];

            if (!config.isSupportedLanguage) {
                this.consoleLog(
                    'startSpellCheckViaTimer not started because language is not supported or SpellCheck-Tool does not run.',
                );
                return resolve([text, runId]);
            }

            if (config.disableSpellCheckByIdle) {
                this.consoleLog('startSpellCheckViaTimer not started because disableSpellCheckByIdle is true.');

                return resolve([text, runId]);
            }

            // "reset" if a timer is already running
            clearTimeout(this.editIdleTimer);
            this.editIdleTimer = null;
            // if a spellcheck is already running
            this.spellCheckInProgressID = false;
            // start new timer
            this.consoleLog(`(${this.editIdleTimer}) startSpellCheckViaTimer (${Ext.Date.format(new Date(), 'c')})`);

            this.editIdleTimer = setTimeout(() => {
                this.consoleLog(
                    `(${this.editIdleTimer}) setTimeout => will startSpellCheck now (${Ext.Date.format(new Date(), 'c')})`,
                );
                this.startSpellCheck(text).then((result) => {
                    clearTimeout(this.editIdleTimer);
                    resolve([result, runId]);
                });
            }, this.self.EDIT_IDLE_MILLISECONDS);
        });
    },

    /**
     * Start SpellCheck via StatusStrip-button.
     */
    startSpellCheckViaButton: function () {
        this.consoleLog('startSpellCheckViaButton');
        this.terminateSpellCheck();
        this.editor.editor.triggerDataChanged();
    },

    /**
     * Start SpellCheck via F7.
     */
    startSpellCheckViaShortcut: function () {
        this.consoleLog('startSpellCheckViaShortcut');
        this.terminateSpellCheck();
        this.editor.editor.triggerDataChanged();

        return false;
    },

    // endregion Start spellcheck

    //region SpellCheck process

    /**
     * Prepare and run the SpellCheck (be sure to run this only for supported languages).
     */
    startSpellCheck: function (text) {
        return new Promise((resolve, reject) => {
            const config = this.languageConfig[this.currentFieldType];

            if (!config.isSupportedLanguage) {
                this.consoleLog(
                    'startSpellCheck failed eg because language is not supported or SpellCheck-Tool does not run.',
                );
                return resolve(text);
            }

            if (config.disableSpellCheckByIdle) {
                this.setEditorDisabled(true);
            }

            const originalTextCleared = this.spellCheck.cleanupSpellcheckNodes(text);
            const editorContentAsText = this.spellCheck.prepareTextForSpellCheck(text);

            if (editorContentAsText.trim() === '') {
                this.consoleLog('startSpellCheck stopped because editorContentAsText = ""');
                this.terminateSpellCheck();

                return resolve(originalTextCleared);
            }

            this.consoleLog(`startSpellCheck for editorContentAsText: ${editorContentAsText}`);
            this.consoleLog(`(0.3 => startSpellCheck (${Ext.Date.format(new Date(), 'c')}).)`);

            this.spellCheckInProgressID = Ext.Date.format(new Date(), 'time');
            this.consoleLog(`me.spellCheckInProgressID: ${this.spellCheckInProgressID}`);

            this.allMatches = null;

            this.runSpellCheck(editorContentAsText, text, this.spellCheckInProgressID)
                .then((result) => resolve(result))
                .catch((error) => reject(error));
        });
    },

    /**
     * What to do after the SpellCheck has been run.
     */
    finishSpellCheck: function (spellCheckProcessID) {
        if (spellCheckProcessID !== this.spellCheckInProgressID) {
            this.consoleLog('do NOT finishSpellCheck...');
            return;
        }

        this.consoleLog('finishSpellCheck...');
        this.spellCheckInProgressID = false;

        if (this.languageConfig[this.currentFieldType].disableSpellCheckByIdle) {
            this.setEditorDisabled(false);
        }
    },

    /**
     * "Terminate" the SpellCheck.
     */
    terminateSpellCheck: function () {
        this.consoleLog('terminateSpellCheck.');

        const config = this.languageConfig[this.currentFieldType];

        if (!config.isSupportedLanguage) {
            this.consoleLog(
                'terminateSpellCheck stopped eg because language is not supported or SpellCheck-Tool does not run.',
            );
            return;
        }

        clearTimeout(this.editIdleTimer);
        this.editIdleTimer = null;
        this.spellCheckInProgressID = false;

        if (this.editor === null) {
            return;
        }

        if (config.disableSpellCheckByIdle) {
            this.setEditorDisabled(false);
        }
    },

    /**
     * Run the SpellCheck for the given text.
     * The tool's specific code shall:
     * - call applySpellCheck()
     * @param {String} textToCheck
     * @param {String} originalText
     * @param {string} spellCheckProcessID
     */
    runSpellCheck: function (textToCheck, originalText, spellCheckProcessID) {
        this.consoleLog(`textToCheck: ${textToCheck}`);

        return new Promise((resolve, reject) => {
            this.queryForMatches(textToCheck)
                .then((matches) => {
                    resolve(this.applyFoundMatchesToEditorContent(matches, originalText, spellCheckProcessID));
                })
                .catch((error) => {
                    this.consoleLog(error);
                    this.terminateSpellCheck();

                    reject(error);
                });
        });
    },

    /**
     * Apply the matches found by the SpellCheck (store them and apply the result to the Editor).
     */
    applyFoundMatchesToEditorContent: function (matches, originalText, spellCheckProcessID) {
        if (matches.length === 0) {
            this.consoleLog('allMatchesOfTool: no errors.');
            this.finishSpellCheck(spellCheckProcessID);

            return originalText;
        }

        const spellCheckData = this.editor.currentlyEditingRecord?.get('spellCheck');
        const existingQualities = spellCheckData ? Object.values(spellCheckData).flat() : [];
        this.allMatches = this.spellCheck.transformMatches(matches, originalText, existingQualities);

        if (spellCheckProcessID !== this.spellCheckInProgressID) {
            this.consoleLog(
                `NO applySpellCheckResult, spellCheckProcess is no longer valid (${spellCheckProcessID}/${this.spellCheckInProgressID}).`,
            );
            this.finishSpellCheck(spellCheckProcessID);

            return originalText;
        }

        const result = this.spellCheck.applyMatches(originalText, this.allMatches);
        this.consoleLog(`allMatches applied (${spellCheckProcessID}).`);
        this.finishSpellCheck(spellCheckProcessID);

        return result;
    },

    /**
     * @param {String} textToCheck
     */
    queryForMatches: function (textToCheck) {
        const params = {
            text: textToCheck,
            language: this.languageConfig[this.currentFieldType].longCode,
        };

        return new Promise((resolve, reject) => {
            Ext.Ajax.request({
                url: Editor.data.restpath + 'plugins_spellcheck_spellcheckquery/matches',
                method: 'POST',
                params: params,
                success: (response) => {
                    this.consoleLog('runSpellCheckWithTool (LanguageTool) done.');
                    try {
                        const result = JSON.parse(response.responseText);
                        resolve(result && result.rows && result.rows.matches ? result.rows.matches : []);
                    } catch (error) {
                        reject(new Error('Spellcheck failed: invalid server response'));
                    }
                },
                failure: (response) => {
                    this.consoleLog(`runSpellCheckWithTool (LanguageTool) failed: ${response.status}`);
                    reject(new Error('Spellcheck failed: ' + response.status));
                },
            });
        });
    },

    //endregion SpellCheck process

    // region ToolTips

    /**
     * Returns the active match object, or null if none is available.
     * @param {HTMLElement} element
     * @returns {Object|null}
     */
    getActiveMatch: function (element) {
        if (this.allMatches === null || this.allMatches.length < 1) {
            return null;
        }

        const activeMatchIndex = element.getAttribute(this.self.ATTRIBUTE_ACTIVEMATCHINDEX);
        const activeMatch = this.allMatches[activeMatchIndex];

        return activeMatch || null;
    },

    showToolTip: function (element, pageX, pageY) {
        if (!this.spellCheckTooltip) {
            return;
        }

        const match = this.getActiveMatch(element);

        if (!match) {
            return;
        }

        const segment = this.editor.currentlyEditingRecord;
        this.spellCheckTooltip.loadMatch(segment, match);
        this.spellCheckTooltip.showAt(pageX, pageY);
    },

    /**
     * @param {Object} quality
     * @param {String} replaceText
     */
    applyReplacement: function (quality, replaceText) {
        this.editor.editor.replaceContentInRange(quality.range.start, quality.range.end, replaceText);
        this.spellCheckTooltip.hide();

        this.consoleLog(`replaceText (${replaceText}) applied.`);
    },

    showToolTipViaShortcut: function (_, event) {
        event.preventDefault();
        event.stopPropagation();

        if (!this.editor || !this.spellCheckTooltip) {
            return;
        }

        const spellcheckClass = Editor.util.HtmlClasses.CSS_CLASSNAME_SPELLCHECK;
        let node = this.editor.editor.getDomNodeUnderCursor();

        if (!node) {
            return;
        }

        // Go upper until .t5spellcheck-node is met, if possible
        while (node.classList && !node.classList.contains(spellcheckClass) && node.parentNode) {
            node = node.parentNode;
        }

        // If it was possible to met .t5spellcheck-node, e.g. cursor is inside such a node
        if (!node.classList || !node.classList.contains(spellcheckClass)) {
            return;
        }

        const rect = node.getBoundingClientRect();
        const x = rect.left + rect.width * 0.5;
        const y = rect.top + rect.height * 0.5;

        this.showToolTip(node, x, y);
    },
    // endregion

    /**
     * Fetch language support info for the given field ('source' or 'target') and RFC5646 lang code.
     * @param {string} field  'source' | 'target'
     * @param {string} langCode  RFC5646 language code
     */
    fetchLanguageSupport: function (field, langCode) {
        Ext.Ajax.request({
            url: Editor.data.restpath + 'plugins_spellcheck_spellcheckquery/languages',
            method: 'GET',
            params: { targetLangCode: langCode },
            success: (response) => {
                this.consoleLog(`- Checking supported languages (LanguageTool) done for ${field}.`);
                const resultLT = Ext.util.JSON.decode(response.responseText);

                if (resultLT.rows === false) {
                    this.languageConfig[field].isSupportedLanguage = false;
                    this.languageConfig[field].longCode = null;
                } else {
                    this.languageConfig[field].isSupportedLanguage = true;
                    this.languageConfig[field].longCode = resultLT.rows.longCode;
                }

                this.consoleLog(
                    `=> [${field}] isSupportedLanguage: ${this.languageConfig[field].isSupportedLanguage} (${this.languageConfig[field].longCode})`,
                );
            },
            failure: (response) => {
                this.consoleLog(
                    `- Checking supported languages (LanguageTool) failed for ${field}: ${response.status}`,
                );
                this.languageConfig[field].isSupportedLanguage = false;
                this.languageConfig[field].longCode = null;
            },
        });
    },

    /**
     * Returns true if spellcheck-by-idle should be disabled for the given RFC5646 lang code.
     * @param {string} langCode
     * @returns {boolean}
     */
    computeDisableSpellCheckByIdle: function (langCode) {
        const mainLang = langCode.split('-')[0];
        return this.languagesToStopIdle.indexOf(mainLang) !== -1;
    },

    /**
     * Set targetLangCode for the current task.
     * @returns {Object} {source: "en", target: "de"}, longCodes according to RFC5646
     */
    getTaskLanguages: function () {
        const languages = Ext.getStore('admin.Languages');
        const sourceLang = languages.getById(Editor.data.task.get('sourceLang'));
        const targetLang = languages.getById(Editor.data.task.get('targetLang'));

        return { source: sourceLang.get('rfc5646'), target: targetLang.get('rfc5646') };
    },

    /**
     * Disable the browser's SpellChecker?
     * (When isSupportedLanguage is false or undefined, we still need the browser's SpellCheck!)
     */
    setBrowserSpellcheck: function () {
        const editorBody = this.editor.editor.getEditorViewNode();
        editorBody.spellcheck = !this.languageConfig[this.currentFieldType].isSupportedLanguage;
        this.consoleLog(`Browser-Spellcheck is set to: ${editorBody.spellcheck}.`);
    },

    /**
     * @returns {Editor.view.segments.Grid}
     */
    getSegmentGrid: function () {
        return Ext.ComponentQuery.query('#segmentgrid')[0];
    },

    reloadFalsePositivePanel: function (segmentId) {
        Editor.app.getController('Editor.controller.MetaPanel').reloadStore(segmentId);
    },
});
