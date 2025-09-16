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

Ext.define('Editor.plugins.SpellCheck.controller.Editor', {
    extend: 'Ext.app.Controller',
    requires: ['Editor.util.SegmentContent'],

    mixins: ['Editor.util.DevelopmentTools', 'Editor.util.Event'],

    refs: [
        {
            ref: 'segmentGrid',
            selector: '#segmentgrid',
        },
        {
            ref: 'editorPanel',
            selector: '#SpellCheckEditorPanel',
        },
        {
            ref: 'concordenceSourceSearch',
            selector: 'languageResourceSearchGrid #sourceSearch',
        },
    ],

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
            },
            '#segmentgrid': {
                afterRender: 'initTargetLang',
            },
            '#segmentStatusStrip #btnRunSpellCheck': {
                click: 'startSpellCheckViaButton',
            },
        },
    },

    spellCheckMessages: {
        moreInformation: 'Mehr Informationen',
    },

    statics: {
        // spellcheck-Node
        NODE_NAME_MATCH: 'span',
        // CSS-Classes for the spellcheck-Node
        // INFO: moved as separate constant in Editor.util.HtmlClasses
        // CSS_CLASSNAME_MATCH: 't5quality',
        // CSS-Classes for error-types
        // Attributes for the spellcheck-Node
        ATTRIBUTE_ACTIVEMATCHINDEX: 'data-spellCheck-activeMatchIndex',
        ATTRIBUTE_QTIP: 'data-qtip',
        // In ToolTips
        CSS_CLASSNAME_TOOLTIP_HEADER: 'spellcheck-tooltip-header',
        CSS_CLASSNAME_REPLACEMENTLINK: 'spellcheck-replacement',
        CSS_CLASSNAME_TOOLTIP_MOREINFO: 'spellcheck-tooltip-moreinformation',
        // Milliseconds to pass before SpellCheck is started when no editing occurs
        EDIT_IDLE_MILLISECONDS: 1000,
    },

    // =========================================================================

    targetLangCode: null, // language to be checked
    isSupportedLanguage: undefined, // if the language is supported by our tool(s):
    // - initially: undefined
    // - on task is opened => start setLanguageSupport() => result: true or false
    // - on push: when isSupportedLanguage is still undefined => start setLanguageSupport() => result: true or false

    // all matches as found by the tool
    // allMatchesOfTool: null,
    // data of all matches found by the tool(s); here already stored independently from the tool
    // allMatches: null,
    // bookmarks of all ranges for the matches found by the tool(s); here already stored independently from the tool
    // allMatchesRanges: null,
    activeMatchNode: null, // node of single match currently in use

    spellCheckTooltip: null, // Ext.menu.Menu ("ToolTip"-instance)

    // time since "nothing" is changed in the Editor's content;
    // 1) user: presses no key 2) editor: no push, no afterInsertMarkup
    editIdleTimer: null,

    spellCheckInProgressID: false, // id of the currently valid SpellCheck-Process (false if none is running)
    // TODO: Instead of using IDs for the processes it would be better to use an object for each process
    // (= handle the SpellCheck-Processes via a class with each process as an instance from it!).

    // segmentId: null,                // ID of the currently edited Segment

    // bookmarkForCaret: null,         // position of the caret in the Editor

    // =========================================================================

    // TRANSLATE-1630 "Workaround for east asian problems with spellchecker"
    // target-languages that cause problems when using SpellCheck via keyboard-idle
    languagesToStopIdle: ['ja', 'ko', 'zh'],
    // = use button instead of idle (will be set according to the target-language)
    disableSpellCheckByIdle: null,

    tagsConversion: null,

    // =========================================================================
    // Init
    // =========================================================================

    init: function () {
        this.callParent(arguments);
        this.consoleLog('0.1 init Editor.plugins.SpellCheck.controller.Editor');
    },

    onDestroy: function () {
        // TODO fixme
        return;
        var me = this;

        if (me.spellCheckTooltip) {
            me.spellCheckTooltip.destroy();
            me.spellCheckTooltip = null;
        }

        Ext.dom.GarbageCollector.collect();
        me.editor = null;
        me.terminateSpellCheck();
        me.consoleLog('---------------- SpellCheck: onDestroy FINISHED. -------------');
    },

    /**
     * Init basics according to the task's target-language:
     * - what's the target-language?
     * - do we use keyboard-idle or a statusStrip-button to start a SpellCheck?
     */
    initTargetLang: function () {
        this.consoleLog('0.2 SpellCheck: initTargetLang.');
        this.setTargetLangCode();
        this.setDisableSpellCheckByIdle();
        this.setLanguageSupport();
    },

    /**
     * Initialize Editor in general and language-support.
     */
    onEditorInstantiate: function (editor) {
        this.consoleLog('0.3 SpellCheck: initEditor.');
        this.editor = editor;
        this.tagsConversion = editor.editor.getTagsConversion();
        this.editor.editor.registerModifier(
            RichTextEditor.EditorWrapper.EDITOR_EVENTS.DATA_CHANGED,
            (text, actions, position) => this._cleanSpellcheckOnTypingInside(text, actions, position, this.tagsConversion),
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

    /**
     * Init ToolTips
     */
    initTooltips: function () {
        const me = this;
        me.spellCheckTooltip = Ext.create('Ext.menu.Menu', {
            minWidth: 200,
            plain: true,
            renderTo: Ext.getBody(),
            items: [],
            onClick: Ext.emptyFn, // Actually, it's a built-in behaviour that ENTER-key press is equvalent to menuitem-click
            // But somewhy this was not working that way until i made onClick to me empty function
            listeners: {
                beforeshow: function () {
                    me.handleSpellCheckTooltip();
                },
            },
        });
    },

    /**
     * Init Events
     */
    initEvents: function () {
        const tooltipBody = Ext.getBody();

        this.consoleLog('SpellCheck: initEvents...');

        // TODO do we still need it?
        // docEl.on({
        //     click: {
        //         delegated: false,
        //         fn: () => { this.consoleLog('SpellCheck: handleClickInEditor...'); this.terminateSpellCheck(); },
        //         scope: this,
        //         preventDefault: false
        //     }
        // });

        this.editor.getEditorBody().addEventListener('contextmenu', event => {
            let node = event.srcElement;

            // Go upper until .t5spellcheck-node is met, if possible
            while (node.classList && !node.classList.contains('t5spellcheck') && node.parentNode) {
                node = node.parentNode;
            }

            if (
                node.nodeType === Node.DOCUMENT_NODE
                || ! node.classList.contains(Editor.util.HtmlClasses.CSS_CLASSNAME_SPELLCHECK)
            ) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            this.showToolTip(node, event.pageX, event.pageY);

            return false;
        });

        tooltipBody.on({
            click: {
                delegated: false,
                delegate: 'div.' + this.self.CSS_CLASSNAME_REPLACEMENTLINK,
                fn: this.applyReplacement,
                scope: this,
                preventDefault: true,
            },
        });
    },

    /**
     * Adds a keyboard shortcut for starting the SpellCheck
     */
    addSpellcheckKeymapEntries: function (cont, area) {
        if (area === 'editor') {
            //same shortcut in Microsoft Word
            cont.keyMapConfig['F7'] = [
                Ext.event.Event.F7,
                { ctrl: false, alt: false, shift: false },
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
     * @param {Object} statusStrip
     */
    addSpellCheckButton: function (statusStrip) {
        statusStrip.add({
            xtype: 'button',
            cls: 'spellcheck-icon',
            text: 'SpellCheck',
            itemId: 'btnRunSpellCheck',
            tooltip: 'F7',
        });
    },

    /**
     * Start SpellCheck after idle (= when the user stopped editing for a certain time, see EDIT_IDLE_MILLISECONDS).
     */
    startSpellCheckViaTimer(text, runId) {
        return new Promise((resolve, reject) => {
            if (!this.isSupportedLanguage) {
                this.consoleLog(
                    'startSpellCheckViaTimer not started because language is not supported or SpellCheck-Tool does not run.',
                );
                resolve([text, runId]);
            }

            if (this.disableSpellCheckByIdle) {
                this.consoleLog('startSpellCheckViaTimer not started because disableSpellCheckByIdle is true.');
                resolve([text, runId]);
            }

            // "reset" if a timer is already running
            clearTimeout(this.editIdleTimer);
            this.editIdleTimer = null;
            // if a spellcheck is already running
            this.spellCheckInProgressID = false;
            // start new timer
            this.consoleLog(
                '(' + this.editIdleTimer + ') startSpellCheckViaTimer (' + Ext.Date.format(new Date(), 'c') + ')',
            );

            this.editIdleTimer = setTimeout(() => {
                this.consoleLog(
                    '(' +
                        this.editIdleTimer +
                        ') setTimeout => will startSpellCheck now (' +
                        Ext.Date.format(new Date(), 'c') +
                        ')',
                );
                this._startSpellCheck(text).then((result) => {
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

    /**
     * Prepare and run the SpellCheck (be sure to run this only for supported languages).
     */
    _startSpellCheck: function (text) {
        return new Promise((resolve, reject) => {
            if (!this.isSupportedLanguage) {
                // Should not be the case when we got here already, but that is not enough: it MUST NOT happen.
                this.consoleLog(
                    'startSpellCheck failed eg because language is not supported or SpellCheck-Tool does not run.',
                );

                resolve(text);
            }

            if (this.disableSpellCheckByIdle) {
                this.setEditorDisabled(true);
            }

            // TrackChanges must remove its placeholder.
            this.fireEvent('removePlaceholdersInEditor');

            const originalTextCleared = this.cleanupSpellcheckNodes(RichTextEditor.stringToDom(text));
            let editorContentAsText = this._getContentWithWhitespaceImagesAsText(text);
            editorContentAsText = this.cleanupForSpellchecking(RichTextEditor.stringToDom(editorContentAsText));
            editorContentAsText = RichTextEditor.unescapeHtml(editorContentAsText);

            if (editorContentAsText.trim() === '') {
                this.consoleLog('startSpellCheck stopped because editorContentAsText = ""');
                this.terminateSpellCheck();

                resolve(originalTextCleared);
            }

            this.consoleLog('startSpellCheck for editorContentAsText: ' + editorContentAsText);
            this.consoleLog('(0.3 => startSpellCheck (' + Ext.Date.format(new Date(), 'c') + ').)');

            const spellCheckProcessID = (this.spellCheckInProgressID = Ext.Date.format(new Date(), 'time'));
            this.consoleLog('me.spellCheckInProgressID: ' + spellCheckProcessID);

            // "ignore" multiple whitespaces, because we delete them anyway on save.
            // TODO FIXME: If whitespace is removed, the just captured caret bookmark is invalid and the caret will be inside the next word (if there is a next word)
            // TODO fix
            // me.collapseMultipleWhitespaceInEditor();

            this.allMatches = null;

            this.runSpellCheck(editorContentAsText, originalTextCleared, spellCheckProcessID)
                .then((result) => resolve(result))
                .catch((error) => reject(error));
        });
    },

    cleanupForSpellchecking: function (dom) {
        let result = '';

        for (const node of dom.childNodes) {
            if (node.nodeName === '#text') {
                result += node.data;

                continue;
            }

            if (node.nodeName === 'SPAN') {
                result += this.cleanupForSpellchecking(node);

                continue;
            }

            if (node.nodeName === 'IMG') {
                continue;
            }

            if (node.nodeName === 'DEL') {
                continue;
            }

            if (node.childNodes.length > 0) {
                result += this.cleanupForSpellchecking(node);

                continue;
            }

            result += node.outerHTML;
        }

        return result;
    },

    cleanupSpellcheckNodes: function (dom) {
        let result = dom.innerHTML;

        for (const node of dom.querySelectorAll('span' + Editor.util.HtmlClasses.CSS_CLASSNAME_SPELLCHECK)) {
            result = result.replace(node.outerHTML, node.innerHTML);
        }

        return result;
    },

    /**
     * What to do after the SpellCheck has been run.
     */
    finishSpellCheck: function (spellCheckProcessID) {
        let me = this;

        if (spellCheckProcessID !== me.spellCheckInProgressID) {
            me.consoleLog('do NOT finishSpellCheck...');

            return;
        }

        me.consoleLog('finishSpellCheck...');

        me.spellCheckInProgressID = false;

        if (me.disableSpellCheckByIdle) {
            me.setEditorDisabled(false);
        }
    },

    /**
     * "Terminate" the SpellCheck.
     */
    terminateSpellCheck: function () {
        this.consoleLog('terminateSpellCheck.');

        if (!this.isSupportedLanguage) {
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

        if (this.disableSpellCheckByIdle) {
            this.setEditorDisabled(false);
        }
    },

    /**
     * Apply the matches found by the SpellCheck (store them and apply the result to the Editor).
     */
    applySpellCheck: function (matches, originalText, spellCheckProcessID) {
        if (spellCheckProcessID !== this.spellCheckInProgressID) {
            this.consoleLog(
                'NO applySpellCheck, spellCheckProcess is no longer valid (' +
                    spellCheckProcessID +
                    '/' +
                    this.spellCheckInProgressID +
                    ').',
            );
            this.finishSpellCheck(spellCheckProcessID);

            return originalText;
        }

        if (matches.length === 0) {
            this.consoleLog('allMatchesOfTool: no errors.');
            // this.cleanSpellCheckMarkupInEditor(); // in case there have been errors marked before
            // this.bookmarkForCaret = null;
            this.finishSpellCheck(spellCheckProcessID);

            return originalText;
        }

        this.storeMatchesFromTool(matches, originalText);

        return this.applySpellCheckResult(matches, spellCheckProcessID, originalText);
    },

    /**
     * Apply the results.
     */
    applySpellCheckResult: function (matches, spellCheckProcessID, originalText) {
        if (spellCheckProcessID !== this.spellCheckInProgressID) {
            this.consoleLog(
                'NO applySpellCheckResult, spellCheckProcess is no longer valid (' +
                    spellCheckProcessID +
                    '/' +
                    this.spellCheckInProgressID +
                    ').',
            );
            this.finishSpellCheck(spellCheckProcessID);

            return originalText;
        }

        if (matches.length > 0) {
            this.consoleLog('allMatches applied (' + spellCheckProcessID + ').');

            const ret = this.applyAllMatches(spellCheckProcessID, originalText);
            this.finishSpellCheck(spellCheckProcessID);

            return ret;
        }

        return this.finishSpellCheck(spellCheckProcessID);
    },

    /**
     * Is the language supported by the tool(s) we use?
     * The tool's specific code shall:
     * - store the result in me.isSupportedLanguage
     */
    setLanguageSupport: function () {
        const me = this,
            url = Editor.data.restpath + 'plugins_spellcheck_spellcheckquery/languages',
            method = 'GET',
            params = {
                targetLangCode: me.targetLangCode,
            };

        Ext.Ajax.request({
            url: url,
            method: method,
            params: params,
            success: function (response) {
                me.consoleLog('- Checking supported languages (LanguageTool) done.');
                const resultLT = Ext.util.JSON.decode(response.responseText);
                me.setIsSupportedLanguage(resultLT);
            },
            failure: function (response) {
                me.consoleLog('- Checking supported languages (LanguageTool) failed: ' + response.status);
                me.terminateSpellCheck();
            },
        });
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
        this.consoleLog('textToCheck: ' + textToCheck);

        return new Promise((resolve, reject) => {
            this.runSpellCheckWithTool(textToCheck)
                .then((matches) => {
                    resolve(this.applySpellCheck(matches, originalText, spellCheckProcessID));
                })
                .catch((error) => {
                    this.consoleLog(error);
                    this.terminateSpellCheck();

                    reject(error);
                });
        });
    },

    /**
     * Store data for all matches found by the tool (=> then accessible independent of tool).
     */
    storeMatchesFromTool: function (matches, originalText) {
        const internalTagsPositions = [];
        const internalTagsWithPositions = this.editor.editor.getInternalTagsPositions();
        for (const position of Object.keys(internalTagsWithPositions)) {
            // TODO use constant instead of a literal
            if (internalTagsWithPositions[position].type !== 'whitespace') {
                internalTagsPositions.push(position);
            }
        }

        const deletionsPositions = [];
        const doc = RichTextEditor.stringToDom(originalText);
        const deletions = doc.querySelectorAll('del');
        for (const deletion of deletions) {
            const offsets = RichTextEditor.calculateNodeOffsets(doc, deletion);
            deletionsPositions.push(offsets);
        }

        this.allMatches = [];

        for (const [index, match] of matches.entries()) {
            // offsets of text-only version
            const matchStart = match.offset;
            const matchEnd = matchStart + match.context.length;
            const singleMatch = {
                matchIndex: index,
                range: this.getRangeForMatch(matchStart, matchEnd, internalTagsPositions, deletionsPositions),
                message: this.getMessageForMatchFromTool(match),
                replacements: this.getReplacementsForMatchFromTool(match),
                infoURLs: this.getInfoURLsForMatchFromTool(match),
                cssClassErrorType: this.getCSSForMatchFromTool(match),
            };

            this.allMatches.push(singleMatch);
        }
    },

    // =========================================================================
    // Helpers for the SpellChecker
    // =========================================================================

    /**
     * Get the range of a match in the Editor using the offsets of the text-only version.
     * @param {int} matchStart
     * @param {int} matchEnd
     * @param {int[]} internalTagsPositions
     * @param {Object<start: int, end: int>[]} deletionsPositions
     * @returns {Object}
     */
    getRangeForMatch: function (
        matchStart,
        matchEnd,
        internalTagsPositions,
        deletionsPositions
    ) {
        let deletionsBeforeStartLength = 0;
        let deletionsBeforeEndLength = 0;

        for (const deletion of deletionsPositions) {
            const deletionLength = deletion.end - deletion.start;
            const biasedStart = deletion.start - deletionsBeforeStartLength;
            const biasedEnd = deletion.end - deletionsBeforeEndLength - deletionLength;

            if (biasedStart < matchStart) {
                deletionsBeforeStartLength += deletionLength;
            }

            if (biasedEnd < matchEnd) {
                deletionsBeforeEndLength += deletionLength;
            }
        }

        let start = matchStart
            // Since internal tag length is 1 we can calculate amount of filtered tags
            + internalTagsPositions.filter((el) => el < matchStart + deletionsBeforeStartLength).length
            + deletionsBeforeStartLength;

        let end = matchEnd
            + internalTagsPositions.filter((el) => el < matchEnd + deletionsBeforeEndLength).length
            + deletionsBeforeEndLength;

        return {
            start: start,
            end: end,
        };
    },

    /**
     * Apply results to captured content from the Editor.
     */
    applyAllMatches: function (spellCheckProcessID, originalText) {
        let result = '';

        let index = 0;
        let previousRangeEnd = 0;

        for (const match of this.allMatches) {
            if (previousRangeEnd !== match.range.start) {
                result += this._replaceSpace(this.editor.editor.getContentInRange(previousRangeEnd, match.range.start));
            }

            result += this.createSpellcheckNode(
                this._replaceSpace(this.editor.editor.getContentInRange(match.range.start, match.range.end)),
                index
            );

            if (index === this.allMatches.length - 1) {
                const contentLeft = this.editor.editor.getContentInRange(
                    match.range.end,
                    this.editor.editor.getContentLength()
                );

                if (contentLeft === '&nbsp;') {
                    // If this is the latest nbsp in the whole content
                    // do not replace it with space because it will be trimmed on applying it to editor
                    result += contentLeft;
                } else {
                    result += this._replaceSpace(
                        contentLeft,
                        true,
                        false
                    );
                }
            }

            previousRangeEnd = match.range.end;
            index++;
        }

        if (!this.spellCheckInProgressID) {
            return originalText;
        }

        return result;
    },

    // applyMatch: function (result, index, start, end) {
    //     let pointer = 0;
    //     const _this = this;
    //
    //     function traverseNodes(node, start, end) {
    //         if (_this.tagsConversion.isTrackChangesDelNode(node)) {
    //             return false;
    //         }
    //
    //         if (node.nodeType === Node.TEXT_NODE) {
    //             const isWithinNode = pointer + node.length >= start && end <= pointer + node.length;
    //
    //             if (isWithinNode) {
    //                 const string = node.data.substring(start - pointer, end - pointer);
    //                 const textBefore = node.data.substring(0, start - pointer);
    //                 const textAfter = node.data.substring(end - pointer);
    //
    //                 const spellCheckNode = _this.createSpellcheckNode(string, index);
    //                 node.parentNode.insertBefore(document.createTextNode(textBefore), node);
    //                 node.parentNode.insertBefore(RichTextEditor.stringToDom(spellCheckNode).firstChild, node);
    //                 node.parentNode.insertBefore(document.createTextNode(textAfter), node);
    //                 node.parentNode.removeChild(node);
    //
    //                 return true;
    //             }
    //
    //             pointer += node.length;
    //         }
    //
    //         if (_this.tagsConversion.isTag(node)) {
    //             pointer++;
    //
    //             return false;
    //         }
    //
    //         if (node.nodeType === Node.ELEMENT_NODE) {
    //             for (let child of node.childNodes) {
    //                 const changed = traverseNodes(child, start, end);
    //
    //                 // This is done to prevent endless recursion
    //                 if (changed) {
    //                     return true;
    //                 }
    //             }
    //         }
    //
    //         return false;
    //     }
    //
    //     traverseNodes(result, start, end);
    // },

    /**
     * Create and return a new node for SpellCheck-Match of the given index.
     * For match-specific data, get the data from the tool.
     *
     * @param {string} text
     * @param {int} index
     *
     * @returns {Object}
     */
    createSpellcheckNode: function (text, index) {
        const match = this.allMatches[index];

        return (
            '<' +
            this.self.NODE_NAME_MATCH +
            ' ' +
            'class="' +
            Editor.util.HtmlClasses.CSS_CLASSNAME_SPELLCHECK +
            ' ' +
            match.cssClassErrorType +
            ' ownttip" ' +
            this.self.ATTRIBUTE_ACTIVEMATCHINDEX +
            '="' +
            index +
            '" ' +
            this.self.ATTRIBUTE_QTIP +
            '="' +
            Editor.data.l10n.SpellCheck.nodeTitle +
            '">' +
            text +
            '</' +
            this.self.NODE_NAME_MATCH +
            '>'
        );
    },

    /**
     * TRANSLATE-1630 "Workaround for east asian problems with spellchecker"
     * For example, typing Japanese with European keys requires time for the user
     * to use Space and Enter. Running a SpellCheck disrupts the typing. Hence,
     * the SpellCheck must only start when intentionally invoked by the user
     * (= using a Button instead of keyboard-Idle).
     */
    setDisableSpellCheckByIdle: function () {
        const targetLangSplit = this.targetLangCode.split('-'),
            mainLang = targetLangSplit[0];

        // is the target-language one of those that cause problems?
        this.disableSpellCheckByIdle = this.languagesToStopIdle.indexOf(mainLang) !== -1;

        // if yes, add button in statusStrip:
        if (this.disableSpellCheckByIdle) {
            this.addSpellCheckButton(Ext.ComponentQuery.query('t5editor')[0].statusStrip);
        }
    },

    /***
     * Set targetLangCode for the current task.
     * @returns {String}
     */
    setTargetLangCode: function () {
        const task = Editor.data.task,
            languages = Ext.getStore('admin.Languages'),
            targetLang = languages.getById(task.get('targetLang'));

        this.targetLangCode = targetLang.get('rfc5646');
    },

    /**
     * Disable the browser's SpellChecker?
     * (When me.isSupportedLanguage is false or undefined, we still need the browser's SpellCheck!)
     */
    setBrowserSpellcheck: function () {
        const editorBody = this.editor.editor.getEditorViewNode();
        editorBody.spellcheck = this.isSupportedLanguage ? false : true;
        this.consoleLog('Browser-Spellcheck is set to: ' + editorBody.spellcheck + '.');
    },

    // /**
    //  * Enable SnapshotHistory if needed.
    //  */
    // setSnapshotHistory: function () {
    //     var me = this;
    //     if (me.isSupportedLanguage) {
    //         me.fireEvent('activateSnapshotHistory');
    //         me.consoleLog('Spellcheck activateSnapshotHistory: yes');
    //     } else {
    //         me.consoleLog('Spellcheck activateSnapshotHistory: no');
    //     }
    // },

    // region ToolTips

    /***
     * Tooltips for the spellcheck-matches.
     */
    handleSpellCheckTooltip: function () {
        let me = this;

        // remove formerly added menu-items
        let oldMenuItems = me.spellCheckTooltip.items.items;
        for (const itemToRemove of oldMenuItems) {
            me.spellCheckTooltip.remove(itemToRemove);
        }

        // update Tooltip
        let spellCheckData = me.getSpellCheckData();

        if (spellCheckData !== false) {
            me.spellCheckTooltip.add(spellCheckData);
        }
    },

    getSpellCheckData: function () {
        let me = this;

        if (me.allMatches === null || me.allMatches.length < 1) {
            return false;
        }

        const activeMatchIndex = me.activeMatchNode.getAttribute(me.self.ATTRIBUTE_ACTIVEMATCHINDEX);
        const activeMatch = me.allMatches[activeMatchIndex];

        if (!activeMatch) {
            return false;
        }

        const message = activeMatch.message;
        const replacements = activeMatch.replacements;
        const infoURLs = activeMatch.infoURLs;
        const items = [];

        // message
        items.push({
            text: '<b>' + message + '</b>',
            cls: me.self.CSS_CLASSNAME_TOOLTIP_HEADER,
        });

        // replacement(s)
        if (replacements.length > 0) {
            for (const replacement of replacements) {
                items.push({
                    text: replacement.replace(' ', '&nbsp;'), // quick and dirty workaround for empty spaces (e.g. when "  " should be replaced with " " or when " - " should be replaced with " – ")
                    cls: me.self.CSS_CLASSNAME_REPLACEMENTLINK,
                });
            }
        }

        // infoURL(s)
        if (infoURLs.length > 0) {
            for (const url of infoURLs) {
                items.push({
                    text: Editor.data.l10n.SpellCheck.moreInformation,
                    cls: me.self.CSS_CLASSNAME_TOOLTIP_MOREINFO,
                    href: url,
                    hrefTarget: '_blank',
                });
            }
        }

        return items;
    },

    showToolTip: function (element, pageX, pageY) {
        this.activeMatchNode = element;
        this.initTooltips(); // me.spellCheckTooltip.hide() is not enough (e.g. after a contextmenu had been shown with a long list of replacements, the next contextmenu was placed as if it still has that height)
        this.spellCheckTooltip.showAt(pageX, pageY);
    },

    /**
     * Apply replacement as suggested in the ToolTip.
     */
    applyReplacement: function (event) {
        const index = this.activeMatchNode.getAttribute(this.self.ATTRIBUTE_ACTIVEMATCHINDEX);
        const range = this.allMatches[index].range;
        const replaceText = event.currentTarget.querySelector('a:first-child span:first-child').innerHTML;
        this.editor.editor.replaceContentInRange(range.start, range.end, replaceText);
        this.spellCheckTooltip.hide();

        this.consoleLog('replaceText (' + replaceText + ') applied.');
    },
    // endregion

    languageToCheckLongCode: null, // longCode of LanguageTool's language, e.g "en", "en-AU", ...

    /**
     * Checks if the language of the current task is supported
     * and stores the result in me.isSupportedLanguage.
     * @param {Boolean|Object} resultLT
     */
    setIsSupportedLanguage: function (resultLT) {
        if (resultLT.rows === false) {
            this.isSupportedLanguage = false;
            this.languageToCheckLongCode = null;
            this.consoleLog(
                '=> isSupportedLanguage: ' + this.isSupportedLanguage + ' (' + this.languageToCheckLongCode + ')',
            );

            return;
        }

        this.isSupportedLanguage = true;
        this.languageToCheckLongCode = resultLT.rows.longCode;
        this.consoleLog(
            '=> isSupportedLanguage: ' + this.isSupportedLanguage + ' (' + this.languageToCheckLongCode + ')',
        );
        this.consoleLog('0.4 SpellCheck: initSpellCheckInEditor.');
        this.initTooltips();
        // this.setSnapshotHistory();
    },
    /**
     * @param {String} textToCheck
     */
    runSpellCheckWithTool: function (textToCheck) {
        const params = {
            text: textToCheck,
            language: this.languageToCheckLongCode,
        };

        return new Promise((resolve, reject) => {
            Ext.Ajax.request({
                url: Editor.data.restpath + 'plugins_spellcheck_spellcheckquery/matches',
                method: 'POST',
                params: params,
                success: (response) => {
                    this.consoleLog('runSpellCheckWithTool (LanguageTool) done.');
                    const result = JSON.parse(response.responseText);
                    resolve(result && result.rows && result.rows.matches ? result.rows.matches : []);
                },
                failure: (response) => {
                    this.consoleLog('runSpellCheckWithTool (LanguageTool) failed: ' + response.status);
                    reject(new Error('Spellcheck failed: ' + response.status));
                },
            });
        });
    },

    /**
     * extract data from match: css according to issueType.
     * @param {Object} match
     * @returns {String}
     */
    getCSSForMatchFromTool: function (match) {
        return Editor.data.plugins.SpellCheck.cssMap[match.rule.issueType] || '';
    },
    /**
     * extract data from match: message
     * @param {Object} match
     * @returns {String}
     */
    getMessageForMatchFromTool: function (match) {
        return match.message;
    },
    /**
     * extract data from match: replacement(s)
     * @param {Object} match
     * @returns {Array}
     */
    getReplacementsForMatchFromTool: function (match) {
        let replacements = [];

        for (const replacement of match.replacements) {
            replacements.push(replacement.value);
        }

        return replacements;
    },
    /**
     * extract data from match: URL(s) for more information
     * @param {Object} match
     * @returns {Array}
     */
    getInfoURLsForMatchFromTool: function (match) {
        let infoURLs = [];

        if (!match.rule.urls) {
            return infoURLs;
        }

        for (const url of match.rule.urls) {
            infoURLs.push(url.value);
        }

        return infoURLs;
    },

    /**
     * Replace whitespace-images with whitespace-text. Returns the new html.
     * TODO Copied from Editor.util.Range
     *
     * @params {string} text
     * @returns {String} html
     */
    _getContentWithWhitespaceImagesAsText: function (text) {
        let html = text;

        const dom = RichTextEditor.stringToDom(html);

        for (const node of dom.childNodes) {
            if (node.nodeName === 'IMG' && node.classList.contains('whitespace')) {
                html = html.replace(node.outerHTML, ' ');
            }
        }

        return html;
    },

    showToolTipViaShortcut: function (_, event) {
        event.preventDefault();
        event.stopPropagation();

        let node = this.editor.editor.getDomNodeUnderCursor();

        if (!node) {
            return;
        }

        // Go upper until .t5spellcheck-node is met, if possible
        while (node.classList && !node.classList.contains('t5spellcheck') && node.parentNode) {
            node = node.parentNode;
        }

        // If it was possible to met .t5spellcheck-node, e.g. cursor is inside such a node
        if (!node.classList?.contains('t5spellcheck')) {
            return;
        }

        const rect = node.getBoundingClientRect();
        const x = rect.left + rect.width * 0.5;
        const y = rect.top + rect.height * 0.5;

        this.showToolTip(node, x, y);
    },

    _cleanSpellcheckOnTypingInside: function (rawData, actions, previousPosition, tagsConversion) {
        if (!actions.length) {
            return [rawData, previousPosition];
        }

        const doc = RichTextEditor.stringToDom(rawData);

        let position = previousPosition;

        for (const action of actions) {
            if (!action.type) {
                continue;
            }

            const calculatedPosition = this._processNodes(doc, action, tagsConversion);

            position = calculatedPosition !== null ? calculatedPosition : position;
        }

        return [doc.innerHTML, position];
    },

    _processNodes: function (doc, action, tagsConversion) {
        const _this = this;
        const position = action.position;
        let pointer = 0;

        function traverseNodes(node) {
            if (node.nodeType === Node.TEXT_NODE) {
                const isWithinNode = pointer <= position && pointer + node.length >= position;

                if (!isWithinNode) {
                    pointer += node.length;

                    return null;
                }

                const isInserting = action.type === RichTextEditor.EditorWrapper.ACTION_TYPE.INSERT;
                const dom = !isInserting && action.content.length ? action.content[0].toDom() : null;
                const isDeletingSpellCheck =
                    !isInserting &&
                    dom &&
                    (
                        tagsConversion.isSpellcheckNode(dom) ||
                        // If one of its children is a spellcheck node
                        (dom.nodeType === Node.ELEMENT_NODE && !!dom.querySelectorAll('.t5spellcheck').length)
                    );

                // if we are inserting or deleting inside a spellcheck node, unwrap it
                if (
                    tagsConversion.isSpellcheckNode(node.parentNode) &&
                    (isInserting || isDeletingSpellCheck)
                ) {
                    _this._unwrapNodesWithMatchIndex(
                        doc,
                        node.parentNode.getAttribute(_this.self.ATTRIBUTE_ACTIVEMATCHINDEX)
                    );

                    return isInserting ? position + action.correction : position;
                }

                // if we are deleted at the beginning of the spellcheck node with the del or backspace key
                // and due to calculation of the position of a change current node can be right before the
                // spellcheck node that we need to unwrap
                let siblingToUnwrap = node.nextSibling &&
                    tagsConversion.isSpellcheckNode(node.nextSibling) ? node.nextSibling : null;

                // sometimes next sibling should be checked on a parent node
                if (! siblingToUnwrap && node.parentNode.lastChild === node) {
                    siblingToUnwrap = node.parentNode.nextSibling &&
                        tagsConversion.isSpellcheckNode(node.parentNode.nextSibling) ? node.parentNode.nextSibling : null;
                }

                if (siblingToUnwrap && isDeletingSpellCheck) {
                    _this._unwrapNodesWithMatchIndex(
                        doc,
                        siblingToUnwrap.getAttribute(_this.self.ATTRIBUTE_ACTIVEMATCHINDEX)
                    );

                    return isInserting ? position + action.correction : position;
                }

                return isInserting ? position + action.correction : position;
            }

            if (tagsConversion.isTag(node)) {
                pointer++;

                return null;
            }

            if (node.nodeType === Node.ELEMENT_NODE) {
                for (let child of node.childNodes) {
                    const changed = traverseNodes(child);

                    // This is done to prevent endless recursion
                    if (changed) {
                        return changed;
                    }
                }
            }

            return null;
        }

        return traverseNodes(doc);
    },

    _unwrapNodesWithMatchIndex: function (doc, matchIndex) {
        const nodes = doc.querySelectorAll(`span[${this.self.ATTRIBUTE_ACTIVEMATCHINDEX}*="${matchIndex}"]`);

        for (const node of nodes) {
            this._unwrapSpellcheckNode(node);
        }
    },

    _unwrapSpellcheckNode: function (spellCheckNode) {
        const spellCheckNodeParent = spellCheckNode.parentNode;
        const insertFragment = document.createRange().createContextualFragment(spellCheckNode.innerHTML);
        spellCheckNodeParent.insertBefore(insertFragment, spellCheckNode);
        spellCheckNodeParent.removeChild(spellCheckNode);
    },

    _replaceSpace: function (text, start = true, end = true) {
        let result = text;

        if (start) {
            result = result.replace(/^&nbsp;/, ' ');
        }

        if (end) {
            result = result.replace(/&nbsp;$/, ' ');
        }

        return result;
    },
});
