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

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * Erweitert den Ext Standard HTML Editor um
 *  - Markup und Unmarkup Funktionen der Editor Html Tags
 *  - Methoden für das Umschalten zwischen den verschiedenen Tag Modi im HTMLEditor
 *  überschreibt Methoden die das gewünschte Verhalten verhindern
 * @class Editor.view.segments.HtmlEditor
 * @extends Ext.form.field.HtmlEditor
 */
Ext.define('Editor.view.segments.HtmlEditor', {
    extend: 'Ext.form.field.HtmlEditor',
    alias: 'widget.segmentsHtmleditor',
    itemId: 'segmentsHtmleditor',
    markupImages: null,
    tags: null,
    //prefix der img Tag ids im HTML Editor
    idPrefix: 'tag-image-',
    requires: [
        'Editor.view.segments.HtmlEditorLayout',
        'Editor.view.segments.MinMaxLength',
        'Editor.view.segments.PixelMapping',
        'Editor.view.segments.StatusStrip'
    ],
    mixins: [
        'Editor.util.HtmlCleanup'
    ],
    componentLayout: 'htmleditorlayout',
    cls: 'x-selectable', //TRANSLATE-1021

    //Konfiguration der parent Klasse
    enableFormat: false,
    enableFontSize: false,
    enableColors: false,
    enableAlignments: false,
    enableLists: false,
    enableLinks: false,
    enableFont: false,
    isTagOrderClean: true,
    isRtl: false,
    tagsCheckResult: null,
    contentEdited: false, //is set to true if text content or content tags were modified
    disableErrorCheck: false,
    segmentLengthStatus: ['segmentLengthValid'], // see Editor.view.segments.MinMaxLength.lengthstatus
    lastSegmentLength: null,
    currentSegment: null,
    statusStrip: null,
    tagsFromReferenceFieldOnly: false,

    strings: {
        tagOrderErrorText: '#UT# Einige der im Segment verwendeten Tags sind in der falschen Reihenfolgen (schließender vor öffnendem Tag).',
        tagMissingText: '#UT#Folgende Tags fehlen:<br/><ul><li>{0}</li></ul>So entfernen Sie den Fehler:<br/><ul><li>Klicken Sie auf OK, um diese Nachricht zu entfernen</li><li>Drücken Sie ESC, um das Segment ohne Speichern zu verlassen</li><li>Öffnen Sie das Segment erneut</li></ul>Wiederholen Sie jetzt Ihre Änderungen.<br/>Verwenden Sie alternativ die Hilfeschaltfläche, und suchen Sie nach Tastenkombinationen, um die fehlenden Tags aus der Quelle einzugeben.',
        tagDuplicatedText: '#UT#Die nachfolgenden Tags wurden beim Editieren dupliziert, das Segment kann nicht gespeichert werden. Löschen Sie die duplizierten Tags. <br />Duplizierte Tags:{0}',
        tagExcessText: '#UT#Die folgenden Tags existieren nicht in der Quellsprache:<br/><ul><li>{0}</li></ul>So entfernen Sie den Fehler:<br/><ul><li>Klicken Sie auf OK, um diese Nachricht zu entfernen</li><li>Drücken Sie ESC, um das Segment ohne Speichern zu verlassen</li><li>Öffnen Sie das Segment erneut</li></ul>Wiederholen Sie jetzt Ihre Änderungen.',
        tagRemovedText: '#UT# Es wurden Tags mit fehlendem Partner entfernt!',
        cantEditContents: '#UT#Es ist Ihnen nicht erlaubt, den Segmentinhalt zu bearbeiten. Bitte verwenden Sie STRG+Z um Ihre Änderungen zurückzusetzen oder brechen Sie das Bearbeiten des Segments ab.',
    },

    //***********************************************************************************
    //Begin Events
    //***********************************************************************************
    /**
     * @event contentErrors
     * @param {Editor.view.segments.HtmlEditor} the htmleditor itself
     * @param {String} error message
     * Fires if the content contains tag errors, the result of the handler must be boolean,
     * true if saving should be processed, false if not.
     * @param {Bool} isTagError For tag-errors there is a config-item that allows to ignore the validation.
     * For historical reasons, the default assumes that an error is a tag-error unless set otherwise.
     */
    //***********************************************************************************
    //End Events
    //***********************************************************************************

    initComponent: function () {
        var me = this;
        me.viewModesController = Editor.app.getController('ViewModes');
        me.metaPanelController = Editor.app.getController('Editor');
        me.segmentsController = Editor.app.getController('Segments');
        me.intImgTpl = new Ext.Template([
            '<img id="' + me.idPrefix + '{key}" class="{type}" title="{title}" alt="{text}" src="{path}" data-length="{length}" data-pixellength="{pixellength}" />'
        ]);
        me.intImgTplQid = new Ext.Template([
            '<img id="' + me.idPrefix + '{key}" class="{type}" title="{title}" alt="{text}" src="{path}" data-length="{length}" data-pixellength="{pixellength}" data-t5qid="{qualityId}" />'
        ]);
        me.intSpansTpl = new Ext.Template([
            '<span title="{title}" class="short">{shortTag}</span>',
            '<span data-originalid="{id}" data-length="{length}" class="full">{text}</span>'
        ]);
        me.termSpanTpl = new Ext.Template([
            '<span class="{className}" title="{title}"">'
        ]);
        me.termSpanTplQid = new Ext.Template([
            '<span class="{className}" title="{title}" data-t5qid="{qualityId}">'
        ]);
        me.intImgTpl.compile();
        me.intImgTplQid.compile();
        me.intSpansTpl.compile();
        me.termSpanTpl.compile();
        me.termSpanTplQid.compile();
        me.callParent(arguments);
        //add the status strip component to the row editor
        me.statusStrip = me.add({
            xtype: 'segments.statusstrip',
            htmlEditor: me
        });

        this.tagsCheck = null;
    },
    /**
     * Applies our templates to the given data by type
     * @returns string
     */
    applyTemplate: function (type, data) {
        switch (type) {
            case 'internalimg':
                return (this.hasQIdProp(data) ? this.intImgTplQid.apply(data) : this.intImgTpl.apply(data));

            case 'internalspans':
                return this.intSpansTpl.apply(data);

            case 'termspan':
                return (this.hasQIdProp(data) ? this.termSpanTplQid.apply(data) : this.termSpanTpl.apply(data));

            default:
                console.log('Invalid type "' + type + '" when using compileTemplate!');
                return '';
        }
    },

    hasQIdProp: function (data) {
        return (data.qualityId && data.qualityId != null && data.qualityId != '');
    },

    setHeight: function (height) {
        var me = this,
            stripHeight = Math.max(0, me.statusStrip.getHeight() - 5); //reduce statusStrip height about 5px
        me.callParent([height + stripHeight]);
    },

    initFrameDoc: function () {
        var me = this;
        me.callParent(arguments);
        me.iframeEl.on('load', function (ev) {
            //when the editor layout is changed(ex: the languageresources pannel is inserted)
            //somehow the iframe is reset completely and the iframe body has no css classes (the body is not ready even if the initialized field is true)
            //when this is the case, init the iframe document
            //issues:TRANSLATE-1219
            //       TRANSLATE-1794
            var edBody = Ext.fly(ev.target.contentDocument.body);
            if (!edBody.hasCls('htmlEditor') && me.initialized) {
                //If you get multiple of that log entries,
                // your code architecture is bad due to much DOM Manipulations at the wrong place. See TRANSLATE-1219
                Ext.log({msg: 'HtmlEditor iframe is re-initialised!', level: 'warn'});
                me.initFrameDoc();
            }
        });
        me.fireEvent('afterinitframedoc', me);
    },

    /**
     * Überschreibt die Methode um den Editor Iframe mit eigenem CSS ausstatten
     * @returns string
     */
    getDocMarkup: function () {
        var me = this,
            version = Editor.data.app.version,
            dir = (me.isRtl ? 'rtl' : 'ltr'),
            //ursprünglich wurde ein body style height gesetzt. Das führte aber zu Problemen beim wechsel zwischen den unterschiedlich großen Segmente, daher wurde die Höhe entfernt.
            //INFO: the class htmlEditor has no function expect for the check if the body classes are loaded
            body = '<html><head><style type="text/css">body{border:0;margin:0;padding:{0}px;}</style>{1}</head><body dir="{2}" style="direction:{2};font-size:12px;" class="htmlEditor {3}"></body></html>',
            additionalCss = Editor.data.editor.htmleditorCss.map(function (css) {
                return '<link type="text/css" rel="stylesheet" href="' + css + '?v=' + version + '" />';
            }).join("\n");
        return Ext.String.format(body, me.iframePad, additionalCss, dir, me.currentSegmentSize);
    },

    /**
     * overriding default method since under some circumstances this.getWin() returns null which gives an error in original code
     */
    getDoc: function () {
        //it is possible that dom is not initialized
        if (!this.iframeEl || !this.iframeEl.dom) {
            return null;
        }
        return this.iframeEl.dom.contentDocument || (this.getWin() && this.getWin().document);
    },

    /**
     * reintroduce our body tag check here,
     * we have to wait with editor initialzation until body tag is ready
     */
    getEditorBody: function () {
        var doc = this.getDoc(),
            body = doc && (doc.body || doc.documentElement);
        if (body && body.tagName == 'BODY') {
            return body;
        }
        return false;
    },

    /**
     * Setzt Daten im HtmlEditor und fügt markup hinzu
     * @param {String} value
     * @param {Editor.models.Segment} segment
     * @param {String} fieldName
     * @param {String} referenceFieldName
     * @param {Boolean} tagsFromReferenceFieldOnly
     */
    setValueAndMarkup: function (
        value,
        segment,
        fieldName,
        referenceFieldName = '',
        tagsFromReferenceFieldOnly = false
    ) {
        //check tag is needed for the checkplausibilityofput feature on server side
        var me = this,
            segmentId = segment.get('id'),
            checkTag = me.getDuplicateCheckImg(segmentId, fieldName);

        let data = {
                value: value,
                segment: segment,
                fieldName: fieldName,
                referenceFieldName: referenceFieldName
            };

        if (Ext.isGecko && (data.value === '' || data.value === checkTag)) {
            // TODO new richtext editor: is this still needed ?
            // TRANSLATE-1042: Workaround Firefox
            // - add invisible placeholder, otherwise Firefox might not be able to detect selections correctly (= html instead of the body)
            // - will be removed on saving anyway (or even before during clean-up of the TrackChanges)
            data.value = '&#8203;';
        }

        // gives plugin a chance to pre-process the markup to set. We want to keep the value manipulatable, so we create a temp obj.
        me.fireEvent('beforeSetValueAndMarkup', data);

        me.currentSegment = data.segment;

        if (referenceFieldName) {
            this.markupForEditor(segment.get(referenceFieldName));
            this.tagsCheck = new TagCheck(this.tags, this.idPrefix);
        }

        me.setValue(me.markupForEditor(data.value, tagsFromReferenceFieldOnly) + checkTag);
        me.statusStrip.updateSegment(data.segment, fieldName);

        if (!referenceFieldName) {
            this.tagsCheck = new TagCheck(this.tags, this.idPrefix);
        }

        me.fireEvent('afterSetValueAndMarkup');

        if (Ext.isGecko) {
            me.getFocusEl().focus(); // TRANSLATE-1042: Workaround Firefox TODO renewal of RichText editor: is this still needed ?
        }
    },

    /**
     * Fixing focus issues EXT6UPD-105 and EXT6UPD-137
     */
    privates: {
        getFocusEl: function () {
            return Ext.isGecko ? this.iframeEl : Ext.fly(this.getEditorBody());
        }
    },

    /**
     * Fixing focus issues EXT6UPD-105
     */
    pushValue: function () {
        this.callParent();
        //do toggle on off not only on gecko, but also on IE
        if (!Ext.isGecko && Ext.isIE11) {
            this.setDesignMode(false);  //toggle off first
            this.setDesignMode(true);
        }
    },

    /**
     * Holt Daten aus dem HtmlEditor und entfernt das markup
     * @return String
     */
    getValueAndUnMarkup: function () {
        let result,
            body = this.getEditorBody();

        this.checkTags(body);

        this.checkSegmentLength(body.innerHTML || "");

        result = this.unMarkup(body);

        this.contentEdited = this.plainContent.join('') !== result.replace(/<img[^>]+>/g, '');

        return result;
    },

    /**
     * Finds Elements in the current Markup by Selector
     * @return NodeList|bool: list with elements or false if not found
     */
    getElementsBySelector(selector) {
        var body = this.getEditorBody();
        if (!body) {
            return false;
        }
        if (selector) {
            return body.querySelectorAll(selector);
        }
        return false;
    },

    /**
     * - replaces div/span to images
     * - prepares content to be edited
     * - resets markupImages (tag map for restoring and tag check)
     * - resets plainContent for checking if plain content was changed
     *
     * @private not for direct usage!
     * @param value {String}
     * @returns {String}
     */
    markupForEditor: function (value, tagsFromReferenceFieldOnly = false) {
        var me = this,
            plainContent = [],
            result;
        me.contentEdited = false;
        me.markupImages = {};
        me.tags = {
            open: [],
            close: [],
            single: [],
            whitespace: [],
        };

        result = me.markup(value, plainContent, false, tagsFromReferenceFieldOnly);
        me.plainContent = plainContent; //stores only the text content and content tags for "original content has changed" comparision

        return result.join('');
    },

    /**
     * Inserts the given string (containing div/span internal tags) at the cursor position in the editor
     * @param value {String}
     */
    insertMarkup: function (value) {
        var html = this.markup(value).join(''),
            doc = this.getDoc(),
            sel, range, frag, node, el, lastNode, rangeForNode,
            termTags, termTageNode, arrLength, i;

        if (!window.getSelection) {
            //FIXME Not supported by your browser message!
            return;
        }
        sel = doc.getSelection();
        if (sel.getRangeAt) {
            range = sel.getRangeAt(0);
            el = doc.createElement("div");
            frag = doc.createDocumentFragment();
            el.innerHTML = html;
            while ((node = el.firstChild)) {
                lastNode = frag.appendChild(node);
            }
            // remove term-tag-markup (will be added again after saving where appropriate)
            termTags = frag.querySelectorAll('span.term');
            arrLength = termTags.length;
            for (i = 0; i < arrLength; i++) {
                termTageNode = termTags[i];
                while (termTageNode.firstChild) {
                    if (termTageNode.isSameNode(lastNode)) {
                        // If this term-tag-node is the lastNode, we must use its content as lastNode before we remove it.
                        lastNode = termTageNode.firstChild;
                    }
                    termTageNode.parentNode.insertBefore(termTageNode.firstChild, termTageNode);
                }
                termTageNode.parentNode.removeChild(termTageNode);
            }
            // insert
            this.fireEvent('beforeInsertMarkup', range);
            range = sel.getRangeAt(0); // range might have changed during handling the beforeInsertMarkup
            if (!range.collapsed) {
                range.deleteContents();
            }
            range.insertNode(frag);
            rangeForNode = range.cloneRange();
            if (lastNode !== undefined) {
                range.setStartAfter(lastNode);
                range.setEndAfter(lastNode);
            }
            this.fireEvent('afterInsertMarkup', rangeForNode);
            this.fireEvent('saveSnapshot'); // Keep a snapshot from the new content
        }
    },

    setSegmentSize: function (grid, size, oldSize) {
        var body = Ext.fly(this.getEditorBody());
        if (body) {
            body.removeCls(oldSize);
            body.addCls(size);
        }
        this.currentSegmentSize = size;
    },

    /**
     * converts the given HTML String to HTML ready for the Editor (div>span to img tags)
     * Each call adds the found internal tags to the markupImages map.
     * @param value {String}
     * @param plainContent {Array} optional, needed for markupForEditor only
     * @param complementExisting {Boolean} optional, leads to not oeverwriting existing image-map entries
     */
    markup: function (
        value,
        plainContent,
        complementExisting,
        tagsFromReferenceFieldOnly = false
    ) {
        var me = this,
            tempNode = document.createElement('DIV'); // TODO: is using the main windows document OK here ?

        if (!plainContent) {
            plainContent = [];
        }

        me.createRuler();
        me.result = [];
        //tempnode mit inhalt füllen => Browser HTML Parsing
        value = value.replace(/ </g, Editor.TRANSTILDE + '<');
        value = value.replace(/> /g, '>' + Editor.TRANSTILDE);
        Ext.fly(tempNode).update(value);

        me.replaceTagsToImages(tempNode, plainContent, complementExisting, tagsFromReferenceFieldOnly);

        Ext.destroy(me.measure);
        me.destroyRuler();

        return me.result;
    },

    getInitialData: function () {
        return {
            fullPath: Editor.data.segments.fullTagPath,
            shortPath: Editor.data.segments.shortTagPath
        };
    },

    replaceTagsToImages: function (
        rootnode,
        plainContent,
        complementExisting,
        tagsFromReferenceFieldOnly = false
    ) {
        var me = this;

        let openTagNumber = 0;
        let closeTagNumber = 0;
        let whitespaceTagNumber = 0;
        let singleTagNumber = 0;

        Ext.each(rootnode.childNodes, function (item) {
            if (Ext.isTextNode(item)) {
                var text = item.data.replace(new RegExp(Editor.TRANSTILDE, "g"), ' ');
                me.result.push(Ext.htmlEncode(text));
                plainContent.push(Ext.htmlEncode(text));
                return;
            }

            //if somehow an HTML comment is added, this may not be processed.
            if (item.nodeName === '#comment') {
                return;
            }

            // INS- & DEL-nodes
            if ((item.tagName.toLowerCase() === 'ins' || item.tagName.toLowerCase() === 'del')) {
                var regExOpening = new RegExp('<\s*' + item.tagName.toLowerCase() + '.*?>'),              // Example: /<\s*ins.*?>/g
                    regExClosing = new RegExp('<\s*\/\s*' + item.tagName.toLowerCase() + '\s*.*?>'),      // Example: /<\s*\/\s*ins\s*.*?>/g
                    openingTag = item.outerHTML.match(regExOpening)[0],
                    closingTag = item.outerHTML.match(regExClosing)[0];
                switch (true) {
                    case /(^|[\s])trackchanges([\s]|$)/.test(item.className):
                        // Keep nodes from TrackChanges, but run replaceTagsToImages for them as well
                        me.result.push(openingTag);
                        plainContent.push(openingTag);
                        me.replaceTagsToImages(item, plainContent, complementExisting);
                        me.result.push(closingTag);
                        plainContent.push(closingTag);
                        break;
                    case /(^|[\s])tmMatchGridResultTooltip([\s]|$)/.test(item.className):
                        // diffTagger-markups in Fuzzy Matches: keep the text from ins-Tags, remove del-Tags completely
                        if (item.tagName.toLowerCase() === 'ins') {
                            var replacedText = item.textContent.replace(new RegExp(Editor.TRANSTILDE, "g"), ' ');
                            me.result.push(Ext.htmlEncode(replacedText));
                            plainContent.push(Ext.htmlEncode(replacedText));
                        }
                        if (item.tagName.toLowerCase() === 'del') {
                            // -
                        }
                        break;
                }
                return;
            }

            if (item.tagName == 'IMG' && !me.tagsCheck.isDuplicateSaveTag(item)) {
                me.result.push(me.imgNodeToString(item, true));
                return;
            }

            // Span für Terminologie
            if (/(^|[\s])term([\s]|$)/.test(item.className)) {
                var termdata = {
                    className: item.className,
                    title: item.title,
                    qualityId: me.getElementsQualityId(item)
                };
                if (me.fieldTypeToEdit) {
                    var replacement = me.fieldTypeToEdit + '-$1';
                    termdata.className = termdata.className.replace(/(transFound|transNotFound|transNotDefined)/, replacement);
                }
                me.result.push(me.applyTemplate('termspan', termdata));
                me.replaceTagsToImages(item, plainContent, complementExisting);
                me.result.push('</span>');
                return;
            }

            //some tags are marked as to be igored in the editor, so we ignore them
            if (item.tagName == 'DIV' && /(^|[\s])ignoreInEditor([\s]|$)/.test(item.className)) {
                return;
            }

            //if we copy and paste content there could be other divs, so we allow only internal-tag divs:
            if (item.tagName != 'DIV' || !/(^|[\s])internal-tag([\s]|$)/.test(item.className)) {
                return;
            }

            let data = me.getData(item, me.getInitialData());
            let tag;

            if (tagsFromReferenceFieldOnly) {
                switch (data.type) {
                    case 'open':
                        tag = me.tagsCheck.getOpeningReferenceTagAtIndexOrNext(openTagNumber);
                        openTagNumber++;

                        if (null === tag) {
                            return;
                        }

                        data = tag.data;

                        break;

                    case 'close':
                        tag = me.tagsCheck.getClosingReferenceTagAtIndexOrNext(closeTagNumber);
                        closeTagNumber++;

                        if (null === tag) {
                            return;
                        }

                        data = tag.data;

                        break;

                    case 'whitespace':
                        tag = me.tagsCheck.getWhitespaceReferenceTagAtIndex(whitespaceTagNumber);
                        whitespaceTagNumber++;

                        if (null === tag) {
                            return;
                        }

                        data = tag.data;

                        break;

                    case 'single':
                        tag = me.tagsCheck.getSingleReferenceTagAtIndexOrNext(singleTagNumber);
                        singleTagNumber++;

                        if (null === tag) {
                            return;
                        }

                        data = tag.data;

                        break;
                }
            }

            //cache the data to be rendered via svg and the html for unmarkup
            if (!complementExisting || !(data.key in me.markupImages)) {
                me.markupImages[data.key] = {
                    type: data.type,
                    shortTag: data.shortTag,
                    fullTag: data.text,
                    fullWidth: data.fullWidth,
                    shortWidth: data.shortWidth,
                    whitespaceTag: data.whitespaceTag,
                    html: me.renderInternalTags(data.className, data),
                    data: data
                };

                me.tags[data.whitespaceTag ? 'whitespace' : data.type].push({data: data});
            }

            if (me.viewModesController.isFullTag() || data.whitespaceTag) {
                data.path = me.getSvg(data.text, data.fullWidth);
            } else {
                data.path = me.getSvg(data.shortTag, data.shortWidth);
            }

            me.result.push(me.applyTemplate('internalimg', data));
            plainContent.push(me.markupImages[data.key].html);
        });
    },

    getSvg: function (text, width) {
        var prefix = 'data:image/svg+xml;charset=utf-8,',
            svg = '',
            //cell = Ext.fly(this.up('segmentroweditor').context.row).select('.segment-tag-column .x-grid-cell-inner').first(),
            cell = Ext.fly(this.getEditorBody()),
            styles = cell.getStyle(['font-size', 'font-style', 'font-weight', 'font-family', 'line-height', 'text-transform', 'letter-spacing', 'word-break']),
            lineHeight = styles['line-height'].replace(/px/, '');

        if (!Ext.isNumber(lineHeight)) {
            lineHeight = Math.round(styles['font-size'].replace(/px/, '') * 1.3);
        }

        //padding left 1px and right 1px by adding x+1 and width + 2
        //svg += '<?xml version="1.0" encoding="UTF-8" standalone="no"?>';
        svg += '<svg xmlns="http://www.w3.org/2000/svg" height="' + lineHeight + '" width="' + (width + 2) + '">';
        svg += '<rect width="100%" height="100%" fill="rgb(207,207,207)" rx="3" ry="3"/>';
        svg += '<text x="1" y="' + (lineHeight - 5) + '" font-size="' + styles['font-size'] + '" font-weight="' + styles['font-weight'] + '" font-family="' + styles['font-family'].replace(/"/g, "'") + '">'
        svg += Ext.String.htmlEncode(text) + '</text></svg>';
        return prefix + encodeURI(svg);
    },

    /**
     * daten aus den tags holen
     * TODO FIXME: this method should not be called "getData" as this has a common meaning in ExtJS and is misleading. For the Richtext editor rework, rename to "evaluateImageData"
     */
    getData: function (item, data) {
        var me = this,
            divItem, spanFull, spanShort, split,
            shortTagContent;
        divItem = Ext.fly(item);
        spanFull = divItem.down('span.full');
        spanShort = divItem.down('span.short');
        data.text = spanFull.dom.innerHTML.replace(/"/g, '&quot;');
        data.id = spanFull.getAttribute('data-originalid');
        data.qualityId = me.getElementsQualityId(divItem);
        data.title = Ext.htmlEncode(spanShort.getAttribute('title'));
        data.length = spanFull.getAttribute('data-length');
        data.originalItem = item;
        data.className = item.className;

        //old way is to use only the id attribute, new way is to use separate data fields
        // both way are currently used!
        if (!data.id) {
            split = spanFull.getAttribute('id').split('-');
            data.id = split.shift();
        }
        shortTagContent = spanShort.dom.innerHTML;
        data.nr = shortTagContent.replace(/[^0-9]/g, '');
        if (shortTagContent.search(/locked/) !== -1) {
            data.nr = 'locked' + data.nr;
        }
        // Fallunterscheidung Tag Typ
        data = me.renderTagTypeInData(item.className, data);

        //if it is a whitespace tag we have to precalculate the pixel width of the tag (if possible)
        if (data.whitespaceTag) {
            data.pixellength = Editor.view.segments.PixelMapping.getPixelLengthFromTag(item, me.currentSegment.get('metaCache'), me.currentSegment.get('fileId'));
        } else {
            data.pixellength = 0;
        }
        // get the dimensions of the inner spans
        this.measure(data);

        return data;
    },

    /**
     * Add type etc. to data according to tag-type.
     * @param {String} className
     * @param {object} data
     * @return object data
     */
    renderTagTypeInData: function (className, data) {
        data = {...data, ...this.getTagType(className, data.nr)};

        data.key = data.type + data.nr;
        data.shortTag = '&lt;' + data.shortTag + '&gt;';
        data.whitespaceTag = /nbsp|tab|space|newline|char/.test(className);

        if (data.whitespaceTag) {
            data.type += ' whitespace';

            if (/newline/.test(className)) {
                data.type += ' newline';
            }

            data.key = 'whitespace' + data.nr;
        } else {
            data.key = data.type + data.nr;
        }

        return data;
    },

    /**
     * Get the tag type (open, close, single) and the short tag (e.g. "1" or "/1")
     * @param className
     * @param nr
     * @returns {{shortTag: string, type: string, suffix: string}|{}|{shortTag, type: string, suffix: string}}
     */
    getTagType: function (className, nr) {
        switch (true) {
            case /open/.test(className):
                return {
                    type: 'open',
                    suffix: '-left',
                    shortTag: nr
                };

            case /close/.test(className):
                return {
                    type: 'close',
                    suffix: '-right',
                    shortTag: '/' + nr
                };

            case /single/.test(className):
                return {
                    type: 'single',
                    suffix: '-single',
                    shortTag: nr + '/',
                };
        }

        return {};
    },

    /**
     * Render html for internal Tags displayed as div-Tags.
     * In case of changes, also check $htmlTagTpl in ImageTag.php
     * @param string className
     * @param object data
     * @return String
     */
    renderInternalTags: function (className, data) {
        var me = this;
        return '<div class="' + className + '">' + me.applyTemplate('internalspans', data) + '</div>';
    },

    /**
     * Insert whitespace; we use the ("internal-tag"-)divs here, because insertMarkup()
     * will render ("internal-tag"-)divs to the ("tag-image"-)images we finally need.
     * For titles etc, see also whitespaceTagReplacer() in editor_Models_Segment_Whitespace
     * @param {String} whitespaceType ('nbsp'|'newline'|'tab')
     * @param {integer} tagNr
     */
    insertWhitespaceInEditor: function (whitespaceType, tagNr) {
        var me = this,
            userCanInsertWhitespaceTags = this.tagsCheck.isAllowedAddingWhitespaceTags(),
            classNameForTagType,
            data,
            className,
            html;

        if (!userCanInsertWhitespaceTags) {
            return;
        }

        data = me.getInitialData();
        data.nr = tagNr;

        switch (whitespaceType) {
            case 'nbsp':
                classNameForTagType = 'single 636861722074733d226332613022206c656e6774683d2231222f nbsp';
                data.title = '&lt;' + data.nr + '/&gt;: No-Break Space (NBSP)';
                data.id = 'char';
                data.length = '1';
                data.text = '⎵';
                break;
            //previously here was a hardReturn which makes mostly no sense, since just a \n (called here softreturn) is used in most data formats
            case 'newline':
                classNameForTagType = 'single 736f667452657475726e2f newline';
                data.title = '&lt;' + data.nr + '/&gt;: Newline';
                data.id = 'softReturn';
                data.length = '1';
                data.text = '↵';
                break;
            case 'tab':
                classNameForTagType = 'single 7461622074733d22303922206c656e6774683d2231222f tab';
                data.title = '&lt;' + data.nr + '/&gt;: 1 tab character';
                data.id = 'tab';
                data.length = '1';
                data.text = '→';
                break;
        }

        className = classNameForTagType + ' internal-tag ownttip';
        data = me.renderTagTypeInData(className, data);
        html = me.renderInternalTags(className, data);
        me.insertMarkup(html);
    },

    /**
     * ersetzt die images durch div und spans im string
     * @private
     * @param node dom-node
     * @return String
     */
    unMarkup: function (node) {
        var me = this,
            result = [];

        if (!node.hasChildNodes()) {
            return "";
        }

        Ext.each(node.childNodes, function (item) {
            var text;
            if (Ext.isTextNode(item)) {
                text = item.data;
                result.push(Ext.htmlEncode(text));
                return;
            }
            if (!item.tagName) {
                //try to find out why sometimes tagName is undefined. So nodeName is !== #text
                //  Since this results in a therootcause entry the following log is catched there too
                Ext.Logger.warn('tagName is undefined, nodeName is: ' + item.nodeName);
            }
            // Keep nodes from TrackChanges
            if ((item.tagName.toLowerCase() == 'ins' || item.tagName.toLowerCase() == 'del') && /(^|[\s])trackchanges([\s]|$)/.test(item.className)) {
                var clone = item.cloneNode(false);
                clone.innerHTML = "";
                result.push(clone.outerHTML.replace(/<[^<>]+>$/, '')); //add start tag
                result.push(me.unMarkup(item));
                result.push('</' + item.tagName.toLowerCase() + '>');
                return;
            }
            // recursive processing of Terminologie spans, removes the term span
            //@todo die Term Spans fliegen hier richtigerweise raus (wg. Umsortierung des Textes)
            //Allerdings muss danach die Terminologie anhand der Begriffe im Text wiederhergestellt werden.
            if (item.tagName == 'SPAN' && item.hasChildNodes()) {
                result.push(me.unMarkup(item));
                return;
            }
            if (item.tagName == 'IMG') {
                result.push(me.unmarkImage(item));
                return;
            }
            if (item.hasChildNodes()) {
                result.push(me.unMarkup(item));
                return;
            }
            result.push(item.textContent || item.innerText);
        });
        return result.join('');
    },

    unmarkImage: function (item) {
        var me = this;
        if (me.tagsCheck.isDuplicateSaveTag(item)) {
            img = Ext.fly(item);
            return me.getDuplicateCheckImg(img.getAttribute('data-segmentid'), img.getAttribute('data-fieldname'));
        } else if (markupImage = me.getMarkupImage(item.id)) {
            return markupImage.html;
        } else if (/^qm-image-/.test(item.id)) {
            return me.imgNodeToString(item, false);
        }
    },

    /**
     * generates a img tag string
     * @param {Image} imgNode
     * @param {Boolean} markup flag whether markup or unmarkup process
     * @returns {String}
     */
    imgNodeToString: function (imgNode, markup) {
        //it may happen that internal tags already converted to img are tried to be markuped again. In that case, just return the tag:
        if (/^tag-image-/.test(imgNode.id)) {
            return imgNode.outerHTML;
        }
        var id = '',
            src = imgNode.src.replace(/^.*\/\/[^\/]+/, ''),
            img = Ext.fly(imgNode),
            comment = img.getAttribute('data-comment'),
            qualityId = this.getElementsQualityId(img);
        if (markup) { //on markup an id is needed for remove orphaned tags
            //qm-image-open-#
            //qm-image-close-#
            id = (/open/.test(imgNode.className) ? 'open' : 'close');
            id = ' id="qm-image-' + id + '-' + (qualityId ? qualityId : '') + '"';
        }
        return Ext.String.format('<img{0} class="{1}" data-t5qid="{2}" data-comment="{3}" src="{4}" />', id, imgNode.className, (qualityId ? qualityId : ''), (comment ? comment : ''), src);
    },

    /**
     * returns a IMG tag with a segment identifier for "checkplausibilityofput" check in PHP
     * @param {Integer} segmentId
     * @param {String} fieldName
     * @return {String}
     */
    getDuplicateCheckImg: function (segmentId, fieldName) {
        return '<img src="' + Ext.BLANK_IMAGE_URL + '" class="duplicatesavecheck" data-segmentid="' + segmentId + '" data-fieldname="' + fieldName + '">';
    },

    /**
     * disables the hasAndDisplayErrors method on its next call, used for save and ignore the tag checks
     */
    disableContentErrorCheckOnce: function () {
        this.disableErrorCheck = true;
    },

    /**
     * used by the row editor for content validation
     * @return {Boolean}
     */
    hasAndDisplayErrors: function () {
        let me = this,
            msg = '',
            meta = me.currentSegment.get('metaCache');

        //if the segment length is not in the defined range, add an error message - not disableable, so before disableErrorCheck
        if (!me.segmentLengthStatus.includes('segmentLengthValid')) { // see Editor.view.segments.MinMaxLength.lengthstatus
            //fire the event, and get the message from the segmentminmaxlength component
            msg = Ext.ComponentQuery.query('#segmentMinMaxLength')[0].renderErrorMessage(me.segmentLengthStatus, meta);
            me.fireEvent('contentErrors', me, msg, false);

            return true;
        }

        //if we are running a second time into this method triggered by callback,
        //  the callback can disable a second error check
        if (me.disableErrorCheck) {
            me.fireEvent('contentErrors', me, null, true);
            me.disableErrorCheck = false;

            return false;
        }

        //since this error can't be handled somehow, we don't fire an event but show the message and stop immediatelly
        if (Editor.data.task.get('notEditContent') && me.contentEdited) {
            Editor.MessageBox.addError(me.strings.cantEditContents);

            return true;
        }

        if (!this.tagsCheckResult.isSuccessful()) {
            //first item the field to check, second item: the error text:
            let todo = [
                    ['missingTags', 'tagMissingText'],
                    ['duplicatedTags', 'tagDuplicatedText'],
                    ['excessTags', 'tagExcessText']
                ];

            for (let i = 0; i < todo.length; i++) {
                let missingSvg = '';

                if (this.tagsCheckResult[todo[i][0]].length > 0) {
                    msg += me.strings[todo[i][1]];

                    for(const tagData of me.tagsCheckResult[todo[i][0]]) {
                        const tag = tagData.data;
                        missingSvg += '<img src="' + me.getSvg(tag.whitespaceTag ? tag.fullTag : tag.shortTag, tag.whitespaceTag ? tag.fullWidth : tag.shortWidth) + '"> ';
                        //msg += '<img src="'+me.getSvg(tag.whitespaceTag ? tag.fullTag : tag.shortTag, tag.whitespaceTag ? tag.fullWidth : tag.shortWidth)+'"> ';
                    }

                    msg = Ext.String.format(msg, missingSvg);
                    msg += '<br /><br />';
                }
            }

            me.fireEvent('contentErrors', me, msg, true);

            return true;
        }

        if (!me.isTagOrderClean) {
            me.fireEvent('contentErrors', me, me.strings.tagOrderErrorText, true);

            return true;
        }

        me.fireEvent('contentErrors', me, null, true);

        return false;
    },

    /**
     * check and fix tags
     * @param node
     */
    checkTags: function (node)  {
        let nodeList = node.getElementsByTagName('img');

        this.fixDuplicateImgIds(nodeList);

        this.tagsCheckResult = this.tagsCheck.checkContentTags(nodeList, this.markupImages);

        if (!this.tagsCheckResult.isSuccessful()) {
            if (this.disableErrorCheck) {
                // if there are content tag errors, and we are in "save anyway" mode, we remove orphaned tags then
                this.removeOrphanedTags(nodeList);
            }

            //no more checks if missing tags found
            return;
        }

        this.removeOrphanedTags(nodeList);

        this.isTagOrderClean = this.tagsCheck.checkTagOrder(nodeList);
    },

    /**
     * Fixes duplicate img ids in the opened editor on unmarkup (MQM tags)
     * Works with <img> tags with the following specifications:
     * IMG needs an id Attribute. Assuming that the id contains the strings "-open" or "-close". The rest of the id string is identical.
     * Needs also an attribute "data-t5qid" which is containing the plain ID of the tag pair.
     * If a duplicated img tag is found, the "123" of the id will be replaced with a generated Ext.id()
     *
     * example, tag with needed infos:
     * <img id="foo-open-123" data-t5qid="123"/> open tag
     * <img id="foo-close-123" data-t5qid="123"/> close tag
     *
     * copying this tags will result in
     * <img id="foo-open-ext-456" data-t5qid="ext-456"/>
     * <img id="foo-close-ext-456" data-t5qid="ext-456"/>
     *
     * Warning:
     * fixing IDs means that existing ids are wandering forward:
     * Before duplicating:
     * This is the [X 1]testtext[/X 1].
     * after duplicating, before fixing:
     * This [X 1]is[/X 1] the [X 1]testtext[/X 1].
     * after fixing:
     * This [X 1]is[/X 1] the [X 2]testtext[/X 2].
     *
     * @param {Array} nodeList
     */
    fixDuplicateImgIds: function(nodeList) {
        let ids = {},
            stackList = {},
            updateId = function (img, newQid, oldQid) {
                img.id = img.id.replace(new RegExp(oldQid + '$'), newQid);
                img.setAttribute('data-t5qid', newQid);
            };

        for (let img of nodeList) {
            let newQid,
                oldQid = this.getElementsQualityId(img),
                id = img.id;

            if (!id || this.tagsCheck.isDuplicateSaveTag(img)) {
                continue;
            }

            if (!ids[id]) {
                //id does not yet exist, then it is not duplicated => out
                ids[id] = true;

                continue;
            }

            if (stackList[id] && stackList[id].length > 0) {
                newQid = stackList[id].shift();
                updateId(img, newQid, oldQid);

                continue;
            }

            let open = new RegExp("-open");

            let pid;

            if (open.test(id)) {
                pid = id.replace(open, '-close');
            } else {
                pid = id.replace(/-close/, '-open');
            }

            if (!stackList[pid]) {
                stackList[pid] = [];
            }

            newQid = Ext.id();
            stackList[pid].push(newQid);

            updateId(img, newQid, oldQid);
        }
    },

    /**
     * removes orphaned tags (MQM only)
     * assuming same id for open and close tag. Each Tag ID contains the string "-open" or "-close"
     * prepends "remove-" to the id of an orphaned tag
     * @param {Array} nodelist
     */

    removeOrphanedTags: function (nodeList) {
        let me = this,
            openers = {},
            closers = {},
            hasRemoves = false;

        for (let img of nodeList) {
            if (me.tagsCheck.isDuplicateSaveTag(img)) {
                return;
            }

            if (/-open/.test(img.id)) {
                openers[img.id] = img;
            }

            if (/-close/.test(img.id)) {
                closers[img.id] = img;
            }
        }

        for (const [id, img] of Object.entries(openers)) {
            let closeId = img.id.replace(/-open/, '-close');

            if (closers[closeId]) {
                //closer zum opener => aus "closer entfern" liste raus
                delete closers[closeId];
            } else {
                //kein closer zum opener => opener zum entfernen markieren
                hasRemoves = true;
                img.id = 'remove-' + img.id;
            }
        }

        for (const [id, img] of Object.entries(closers)) {
            hasRemoves = true;
            img.id = 'remove-' + img.id;
        }

        if (hasRemoves) {
            // TODO fix this
            Editor.MessageBox.addInfo(this.strings.tagRemovedText);
        }
    },

    showShortTags: function () {
        this.rendered && this.setImagePath('shortPath');
    },

    showFullTags: function () {
        this.rendered && this.setImagePath('fullPath');
    },

    setImagePath: function (target) {
        var me = this;
        Ext.each(Ext.query('img', true, me.getEditorBody()), function (item) {
            var markupImage;
            if (markupImage = me.getMarkupImage(item.id)) {
                if (target == 'fullPath' || markupImage.whitespaceTag) {
                    item.src = me.getSvg(Ext.String.htmlDecode(markupImage.fullTag), markupImage.fullWidth);
                } else {
                    item.src = me.getSvg(Ext.String.htmlDecode(markupImage.shortTag), markupImage.shortWidth);
                }
            }
        });
    },

    /**
     * @param imgHtml string containing the MarkupImageId ([open|close|single][0-9]+) prefixed by this.idPrefix
     * @returns this.markupImages item
     */
    getMarkupImage: function (imgHtml) {
        var matches = imgHtml.match(new RegExp('^' + this.idPrefix + '((open|close|single|whitespace)([0-9]+|locked[0-9]+))'));
        if (!matches || matches.length != 4) {
            return null;
        }
        if (this.markupImages[matches[1]]) {
            return this.markupImages[matches[1]];
        }
        return null;
    },

    /**
     * überschreibt die Methode um die leere Toolbar ausblenden
     */
    createToolbar: function () {
        this.callParent(arguments);
        this.toolbar.hide();
    },

    /**
     * überschreibt die Methode um HtmlEditor Funktionen (bold etc. pp.) zu deaktivieren
     */
    execCmd: function () {
    },

    hasSelection: function () {
        var doc = this.getDoc();
        if (doc.selection) {
            return doc.selection.type.toLowerCase() != 'none';
        }
        if (!doc.getSelection) {
            return false;
        }
        var sel = doc.getSelection();
        return !(sel.isCollapsed || sel.rangeCount > 0 && sel.getRangeAt(0).collapsed);
    },

    destroyEditor: function () {
        //do nothing, here since the getWin().un('unload',...); in the original method throws an exception
        // since our htmlEditor is only destroyed once at page reload we just do nothing here
        // the comments in the original method about leaked IE6/7 can be ignored so far
    },

    setDirectionRtl: function (isRtl) {
        var me = this;
        me.isRtl = isRtl;
        if (!me.getDoc().editorInitialized) {
            return;
        }
        var body = Ext.fly(me.getEditorBody()),
            dir = isRtl ? 'rtl' : 'ltr';
        body.set({"dir": dir});
        body.setStyle('direction', dir);
    },

    /**
     * Check if the segment character number is within the defined borders
     * and set the segment's length status accordingly.
     * @param {String} segmentText
     */
    checkSegmentLength: function (segmentText) {
        var me = this,
            meta = me.currentSegment && me.currentSegment.get('metaCache'),
            fileId = me.currentSegment.get('fileId');
        me.segmentLengthStatus = Ext.ComponentQuery.query('#segmentMinMaxLength')[0].getMinMaxLengthStatus(segmentText, meta, fileId);
    },

    /**
     * Get the character count of the segment text + sibling segment lengths, without the tags in it (whitespace tags evaluated to their length)
     * @param {String} text optional, if omitted use currently stored value
     * @return {Integer} returns the transunit length
     */
    getTransunitLength: function (text) {
        var me = this,
            additionalLength = 0,
            meta = me.currentSegment.get('metaCache'),
            field = me.dataIndex,
            textLength;

        //function can be called with given text - or not. Then it uses the current value.
        if (!Ext.isDefined(text) || text === null) {
            text = me.getValue();
        }
        if (!Ext.isString(text)) {
            text = "";
        }

        //add the length of the text itself
        textLength = me.getLength(text, meta, me.currentSegment.get('fileId'));

        //only the segment length + the tag lengths:
        me.lastSegmentLength = textLength;

        //add the length of the sibling segments (min max length is given for the whole transunit, not each mrk tag
        if (meta && meta.siblingData) {
            Ext.Object.each(meta.siblingData, function (id, data) {
                if (me.currentSegment.get('id') == id) {
                    return; //dont add myself again
                }
                if (data.length && data.length[field]) {
                    additionalLength += data.length[field];
                }
            });
        }

        //add additional string length of transunit to the calculation
        if (meta && meta.additionalUnitLength) {
            additionalLength += meta.additionalUnitLength;
        }

        //add additional string length of mrk (after mrk) to the calculation
        if (meta && meta.additionalMrkLength) {
            additionalLength += meta.additionalMrkLength;
        }

        //return 30; // for testing
        return additionalLength + textLength;
    },

    /**
     * Return the text's length either based on pixelMapping or as the number of code units in the text.
     * @param {String} text
     * @param {Object} meta
     * @param {Integer} fileId
     * @return {Integer}
     */
    getLength: function (text, meta, fileId) {
        var me = this,
            div = document.createElement('div'),
            pixelMapping = Editor.view.segments.PixelMapping,
            isPixel = (meta && meta.sizeUnit === pixelMapping.SIZE_UNIT_FOR_PIXELMAPPING),
            length;
        //clean del tag
        text = me.cleanDeleteTags(text);
        // use div, then (1) retrieve "text" only without html-tags and (2) add the lengths of tags (= img)
        div.innerHTML = text;

        // (1) text
        text = div.textContent || div.innerText || "";
        //remove characters with 0 length:
        text = text.replace(/\u200B|\uFEFF/g, '');
        if (isPixel) {
            // ----------- pixel-based -------------
            length = pixelMapping.getPixelLength(text, meta, fileId);
        } else {
            // ----------- char-based -------------
            length = text.length;
        }

        // (2) add the length stored in each img tag
        Ext.fly(div).select('img').each(function (item) {
            //for performance reasons the pixellength is precalculated on converting the div span to img tags
            var attr = (isPixel ? 'data-pixellength' : 'data-length'),
                l = parseInt(item.getAttribute(attr) || "0");
            //data-length is -1 if no length provided
            if (l > 0) {
                length += l;
            }
        });

        div = null;
        return length;
    },

    /**
     * returns the last calculated segment length (with tag lengths, without sibling lengths)
     * @return {Integer}
     */
    getLastSegmentLength: function () {
        return this.lastSegmentLength;
    },

    /**
     * Comapatibility function to retrieve the quality id from a DOM node or a Ext Node
     * NOTE: historically the quality-id was encoded as "data-seq"
     * TODO FIXME: this is somehow a duplicate of Editor.util.SegmentEditor.fetchQualityId (wich works on DOM node). Unify ...
     */
    getElementsQualityId: function (ele) {
        var id = ele.getAttribute('data-t5qid');
        if (!id && ele.getAttribute('data-seq')) {
            id = ele.getAttribute('data-seq');
        }
        return id;
    },

    /**
     * Creates the hidden "ruler" div that is used to measure text-length's
     */
    createRuler: function () {
        this.ruler = this.getDoc().createElement('div');
        this.ruler.classList.add(Ext.baseCSSPrefix + 'textmetrics');
        this.ruler.setAttribute('role', 'presentation');
        this.ruler.dataset.sticky = true;
        this.ruler.style.position = 'absolute';
        this.ruler.style.left = '-1000px';
        this.ruler.style.top = '-1000px';
        this.ruler.style.visibility = 'hidden';
        this.getEditorBody().append(this.ruler);
    },

    /**
     * Removes the hidden "ruler2 div from our iframe
     */
    destroyRuler: function () {
        this.ruler.remove();
        delete this.ruler;
    },

    /**
     * Measures the passed internal tag's data evaluating the width of the full apan & short span
     * @param {Object} data
     */
    measure: function (data) {
        this.ruler.innerHTML = data.text;
        data.fullWidth = Math.ceil(this.ruler.getBoundingClientRect().width);
        this.ruler.innerHTML = data.shortTag;
        data.shortWidth = Math.ceil(this.ruler.getBoundingClientRect().width);
    }
});
