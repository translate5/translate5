import Ruler from "./ruler";
import Templating from "./templating";
import stringToDom from "../Tools/string-to-dom";
import isEqual from 'lodash/isEqual';

const htmlEncode = require('js-htmlencode').htmlEncode;

export default class TagsConversion {
    static TYPE = {
        SINGLE: 'single',
        OPEN: 'open',
        CLOSE: 'close',
        WHITESPACE: 'whitespace',
    };

    constructor(editorElement, tagsModeProvider) {
        this._editorElement = editorElement;
        this._idPrefix = 'tag-image-';
        this._ruler = new Ruler(editorElement);
        this._tagModeProvider = tagsModeProvider;
        this._templating = new Templating(this._idPrefix);
    };

    transform(item, pixelMapping = null) {
        if (this.isTextNode(item)) {
            let text = item.cloneNode();
            text.data = item.data;

            return text;
        }

        // INS- & DEL-nodes
        if (this.isTrackChangesNode(item)) {
            let regExOpening = new RegExp('<\s*' + item.tagName.toLowerCase() + '.*?>'), // Example: /<\s*ins.*?>/g
                regExClosing = new RegExp('<\s*\/\s*' + item.tagName.toLowerCase() + '\s*.*?>'), // Example: /<\s*\/\s*ins\s*.*?>/g
                openingTag = item.outerHTML.match(regExOpening)[0],
                closingTag = item.outerHTML.match(regExClosing)[0];

            let result = null;

            switch (true) {
                case /(^|\s)trackchanges(\s|$)/.test(item.className):
                    // Keep nodes from TrackChanges, but run replaceTagToImage for them as well
                    result = stringToDom(openingTag + closingTag).childNodes[0];
                    for (const child of item.childNodes) {
                        result.appendChild(this.transform(child, pixelMapping));
                    }

                    break;

                case /(^|\s)tmMatchGridResultTooltip(\s|$)/.test(item.className):
                    // diffTagger-markups in Fuzzy Matches: keep the text from ins-Tags, remove del-Tags completely
                    if (item.tagName.toLowerCase() === 'ins') {
                        result = item.cloneNode();
                        result.data = htmlEncode(item.textContent);
                    }

                    if (item.tagName.toLowerCase() === 'del') {
                        // -
                    }

                    break;
            }

            return result;
        }

        if (this.isMQMNode(item)) {
            return item.cloneNode();
        }

        if (this._isImageNode(item) && this.isInternalTagNode(item)) {
            // Already converted internal tag
            return item.cloneNode();
        }

        // Span for terminology
        if (this.isTermNode(item)) {
            let termData = {
                className: item.className,
                title: item.title,
                qualityId: TagsConversion.getElementsQualityId(item)
            };

            // TODO fix this
            if (this.fieldTypeToEdit) {
                let replacement = this.fieldTypeToEdit + '-$1';
                termData.className = termData.className.replace(/(transFound|transNotFound|transNotDefined)/, replacement);
            }

            let result = this._applyTemplate('termspan', termData) + '</span>';

            let term = stringToDom(result).childNodes[0];

            item.childNodes.forEach((child) => {
                term.appendChild(this.transform(child, pixelMapping));
            });

            return term;
        }

        //some tags are marked as to be ignored in the editor, so we ignore them
        if (this._isIgnoredNode(item)) {
            return null;
        }

        //if we copy and paste content there could be other divs, so we allow only internal-tag divs:
        if (this.isInternalTagNode(item)) {
            return stringToDom(this._replaceInternalTagToImage(item, this._editorElement, pixelMapping)).childNodes[0];
        }

        return null;
    }

    /**
     * Generate a new whitespace internal tag
     *
     * @param {string} whitespaceType - the type of the whitespace tag (nbsp, newline, tab)
     * @param {int} tagNr - the number of the tag
     * @returns {Node}
     */
    generateWhitespaceTag(whitespaceType, tagNr) {
        let classNameForTagType,
            className;

        let data = this._getInitialData();
        data.nr = tagNr;

        switch (whitespaceType) {
            case 'nbsp':
                classNameForTagType = 'single 636861722074733d226332613022206c656e6774683d2231222f nbsp whitespace';
                data.title = '&lt;' + data.nr + '/&gt;: No-Break Space (NBSP)';
                data.id = 'char';
                data.length = '1';
                data.text = '⎵';
                break;

            case 'newline':
                classNameForTagType = 'single 736f667452657475726e2f newline whitespace';
                data.title = '&lt;' + data.nr + '/&gt;: Newline';
                //previously here was a hardReturn which makes mostly no sense, since just a \n (called here softreturn) is used in most data formats
                data.id = 'softReturn';
                data.length = '1';
                data.text = '↵';
                break;

            case 'tab':
                classNameForTagType = 'single 7461622074733d22303922206c656e6774683d2231222f tab whitespace';
                data.title = '&lt;' + data.nr + '/&gt;: 1 tab character';
                data.id = 'tab';
                data.length = '1';
                data.text = '→';
                break;

            default:
                let tagData = whitespaceType.split("|");
                classNameForTagType = 'single ' + tagData[1] + ' char';
                data.title = '&lt;' + data.nr + '/&gt;: ' + tagData[2];
                data.id = 'char';
                data.length = '1';
                data.text = tagData[0];
        }

        className = classNameForTagType + ' internal-tag ownttip';
        data = this._addTagType(className, data);

        return stringToDom(this._renderInternalTags(className, data)).childNodes[0];
    }

    /**
     * What's the number for the next Whitespace-Tag?
     *
     * @return number nextTagNr
     */
    getNextWhitespaceTagNumber(imgInTarget) {
        let collectedIds = ['0'];

        // target
        for (const imgNode of imgInTarget) {
            let imgClassList = imgNode.classList;
            if (imgClassList.contains('single') || imgClassList.contains('open')) {
                collectedIds.push(imgNode.id);
            }
        }

        // use the highest
        return Math.max.apply(null, collectedIds.map(function (val) {
            return parseInt(val.replace(/[^0-9]*/, ''));
        })) + 1;
    }

    isTermNode(item) {
        return /(^|[\s])term([\s]|$)/.test(item.className);
    }

    isSpellcheckNode(item) {
        return /(^|[\s])t5spellcheck([\s]|$)/.test(item.className);
    }

    isInternalTagNode(item) {
        return /(^|[\s])internal-tag([\s]|$)/.test(item.className);
    }

    isInternalSpanNode(item) {
        return this.isTermNode(item) || this.isSpellcheckNode(item);
    }

    isWhitespaceNode(item) {
        return this._isWhitespaceTag(item.className);
    }

    getInternalTagType(item) {
        return this.getInternalTagTypeByClass(item.className);
    }

    getInternalTagTypeByClass(className) {
        if (this._isOpenTag(className)) {
            return TagsConversion.TYPE.OPEN;
        }

        if (this._isCloseTag(className)) {
            return TagsConversion.TYPE.CLOSE;
        }

        if (this._isWhitespaceTag(className)) {
            return TagsConversion.TYPE.WHITESPACE;
        }

        if (this._isSingleTag(className)) {
            return TagsConversion.TYPE.SINGLE;
        }

        throw new Error('Unknown internal tag type');
    }

    /**
     * @param {HTMLDivElement} item
     * @returns {string}
     */
    getInternalTagNumber(item) {
        if (item.tagName === 'IMG') {
            if (this.isMQMNode(item)) {
                return this.#getMqmFlagNumber(item);
            }

            return item.getAttribute('data-tag-number');
        }

        const spanShort = item.querySelector('span.short');

        if (!spanShort) {
            // span.short can be missing in case of drag'n'drop from another field in segment grid
            return '0';
        }

        let number;

        let shortTagContent = spanShort.innerHTML;
        number = shortTagContent.replace(/[^0-9]/g, '');

        if (shortTagContent.search(/locked/) !== -1) {
            number = 'locked' + data.nr;
        }

        return number;
    }

    /**
     * @param {HTMLElement} htmlNode
     * @returns {boolean}
     */
    isTag(htmlNode) {
        return this.isInternalTagNode(htmlNode)
            || this.isWhitespaceNode(htmlNode)
            || htmlNode.tagName === 'IMG';
    }

    /**
     * @param {HTMLElement} tag1
     * @param {HTMLElement} tag2
     * @returns {boolean}
     */
    isSameTag(tag1, tag2) {
        return (
            this.isInternalTagNode(tag1)
            && this.isInternalTagNode(tag2)
            && this.getInternalTagNumber(tag1) === this.getInternalTagNumber(tag2)
            && this.getInternalTagType(tag1) === this.getInternalTagType(tag2)
        )
        || (
            this.isTag(tag1) && this.isTag(tag2) && isEqual(Array.from(tag1.classList), Array.from(tag2.classList))
        )
    }

    _replaceInternalTagToImage(item, editorElement, pixelMapping) {
        let data = this._extractInternalTagsData(item, pixelMapping);

        if (this._tagModeProvider.isFullTagMode() || data.whitespaceTag || data.numberTag || data.placeableTag) {
            data.path = this._getSvg(data.text, data.fullWidth, editorElement);
        } else {
            data.path = this._getSvg(data.shortTag, data.shortWidth, editorElement);
        }

        return this._applyTemplate('internalimg', data);
    }

    /**
     * returns true if given html node is a duplicatesavecheck img tag
     *
     * @param {HTMLElement} img
     * @return {Boolean}
     */
    static isDuplicateSaveTag(img) {
        return img.tagName === 'IMG' && img.className && /duplicatesavecheck/.test(img.className);
    }

    /**
     * Convert HTML image node to string
     *
     * @param {HTMLElement} imgNode
     * @param {boolean} markup
     * @returns {string|string|string|string|*}
     * @private
     */
    _imgNodeToString(imgNode, markup) {
        //it may happen that internal tags already converted to img are tried to be markuped again. In that case, just return the tag:
        if (/^tag-image-/.test(imgNode.id)) {
            return imgNode.outerHTML;
        }

        let id = '',
            src = imgNode.src.replace(/^.*\/\/[^\/]+/, ''),
            // TODO get rid of Ext dependency
            img = Ext.fly(imgNode),
            comment = img.getAttribute('data-comment'),
            qualityId = TagsConversion.getElementsQualityId(img);

        if (markup) {
            //on markup an id is needed for remove orphaned tags
            //qm-image-open-#
            //qm-image-close-#
            id = (/open/.test(imgNode.className) ? 'open' : 'close');
            id = ' id="qm-image-' + id + '-' + (qualityId ? qualityId : '') + '"';
        }

        return `<img${id} class="${imgNode.className}" data-t5qid="${(qualityId ?? '')}" data-comment="${comment ?? ''}" src="${src}" />`;
    }

    /**
     * Get data from the tags
     *
     * @param {HTMLDivElement} item
     * @param pixelMapping
     */
    _extractInternalTagsData(item, pixelMapping) {
        let data = this._getInitialData();

        const spanFull = item.querySelector('span.full');
        const spanShort = item.querySelector('span.short');

        data.text = spanFull.innerHTML.replace(/"/g, '&quot;');
        data.id = spanFull.getAttribute('data-originalid');
        data.qualityId = TagsConversion.getElementsQualityId(item);
        data.title = htmlEncode(spanShort.getAttribute('title'));
        data.length = spanFull.getAttribute('data-length');

        //old way is to use only the id attribute, new way is to use separate data fields
        // both way are currently used!
        if (!data.id) {
            let split = spanFull.getAttribute('id').split('-');
            data.id = split.shift();
        }

        data.nr = this.getInternalTagNumber(item);
        data = this._addTagType(item.className, data);

        if (data.numberTag) {
            data.source = spanFull.getAttribute('data-source');
            data.target = spanFull.getAttribute('data-target');
        }

        // if it is a whitespace tag we have to precalculate the pixel width of the tag (if possible)
        if ((data.whitespaceTag || data.placeableTag) && pixelMapping) {
            data.pixellength = pixelMapping.getPixelLengthFromTag(item);
        } else {
            data.pixellength = 0;
        }

        if (data.numberTag) {
            data.text = data.target ? data.target : data.source;
        }

        // get the dimensions of the inner spans
        data.fullWidth = this._ruler.measureWidth(data.text);
        data.shortWidth = this._ruler.measureWidth(data.shortTag);

        return data;
    }

    /**
     * Comapatibility function to retrieve the quality id from a DOM node
     * NOTE: historically the quality-id was encoded as "data-seq"
     *
     * @param {HTMLElement} element
     */
    static getElementsQualityId(element) {
        if (element.hasAttribute('data-t5qid')) {
            return element.getAttribute('data-t5qid');
        }

        if (element.hasAttribute('data-seq')) {
            return element.getAttribute('data-seq');
        }

        return null;
    }

    /**
     * Add type etc. to data according to tag-type.
     *
     * @param {string} className
     * @param {object} data
     *
     * @return {object} data
     */
    _addTagType(className, data) {
        data.type = 'internal-tag';

        //Fallunterscheidung Tag Typ
        switch (true) {
            case /open/.test(className):
                data.type += ' open';
                data.suffix = '-left';
                data.shortTag = data.nr;
                break;

            case /close/.test(className):
                data.type += ' close';
                data.suffix = '-right';
                data.shortTag = '/' + data.nr;
                break;

            case /single/.test(className):
                data.type += ' single';
                data.suffix = '-single';
                data.shortTag = data.nr + '/';
                break;
        }

        data.key = data.type + data.nr;
        data.shortTag = '&lt;' + data.shortTag + '&gt;';
        data.whitespaceTag = /nbsp|tab|space|newline|char|whitespace/.test(className);
        data.numberTag = /number/.test(className);
        data.placeableTag = /t5placeable/.test(className);

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
    }

    _getInitialData() {
        return {
            fullPath: Editor.data.segments.fullTagPath,
            shortPath: Editor.data.segments.shortTagPath
        };
    }

    _getSvg(text, width, editorElement) {
        let prefix = 'data:image/svg+xml;charset=utf-8,',
            svg = '',
            styles = this._getStyle(
                editorElement,
                [
                    'font-size',
                    'font-style',
                    'font-weight',
                    'font-family',
                    'line-height',
                    'text-transform',
                    'letter-spacing',
                    'word-break'
                ]
            ),
            lineHeight = parseInt(styles['line-height'].replace(/px/, ''));

        if (isNaN(lineHeight)) {
            lineHeight = Math.round(styles['font-size'].replace(/px/, ''));
        }

        svg += '<svg xmlns="http://www.w3.org/2000/svg" height="' + lineHeight + '" width="' + width + '">';
        svg += '<rect width="100%" height="100%" fill="rgb(207,207,207)" rx="3" ry="3"/>';
        svg += '<text x="1" y="' + (lineHeight - 5) + '" font-size="' + styles['font-size'] + '" font-weight="'
            + styles['font-weight'] + '" font-family="' + styles['font-family'].replace(/"/g, "'") + '">';
        svg += htmlEncode(text).replace('&amp;nbsp;', '&nbsp;') + '</text></svg>';

        return prefix + encodeURI(svg);
    }

    _getStyle(element, styleProps) {
        let out = [],
            defaultView = (element.ownerDocument || document).defaultView,
            computedStyle = defaultView.getComputedStyle(element, null);

        styleProps.forEach((prop) => {
            if (defaultView && defaultView.getComputedStyle) {
                // sanitize property name to css notation
                // (hyphen separated words eg. font-size)
                prop = prop.replace(/([A-Z])/g, "-$1").toLowerCase();

                out[prop] = computedStyle.getPropertyValue(prop);
            }
        });

        return out;
    }

    // getSingleCharacter(tagMatches) {
    //     let character = '';
    //
    //     switch (tagMatches[1]) {
    //         case 'hardReturn':
    //             character = '↵';
    //             break;
    //         case 'softReturn':
    //             character = '↵';
    //             break;
    //         case 'char':
    //             if (tagMatches[5] === 'c2a0') {
    //                 character = '⎵';
    //             }
    //             break;
    //         case 'space':
    //             character = '⎵';
    //             break;
    //         case 'tab':
    //             character = '→';
    //             break;
    //     }
    //
    //     return character;
    // }

    /**
     * Applies our templates to the given data by type
     *
     * @returns {string}
     */

    _applyTemplate(type, data) {
        switch (type) {
            case 'internalimg':
                return (this._hasQIdProp(data) ? this._templating.intImgTplQid.apply(data) : this._templating.intImgTpl.apply(data));

            case 'internalspans':
                return this._templating.intSpansTpl.apply(data);

            case 'numberspans':
                return this.intNumberSpansTpl.apply(data);

            case 'termspan':
                return (this._hasQIdProp(data) ? this._templating.termSpanTplQid.apply(data) : this._templating.termSpanTpl.apply(data));

            default:
                console.log('Invalid type "' + type + '" when using compileTemplate!');

                return '';
        }
    }

    _hasQIdProp(data) {
        return (data.qualityId && data.qualityId !== '');
    }

    /**
     * Render html for internal Tags displayed as div-Tags.
     * In case of changes, also check $htmlTagTpl in ImageTag.php
     *
     * @param {string} className
     * @param {object} data
     *
     * @return String
     */
    _renderInternalTags(className, data) {
        const type = data.numberTag ? 'numberspans' : 'internalspans';
        return '<div class="' + className + '">' + this._applyTemplate(type, data) + '</div>';
    }

    /**
     * returns a IMG tag with a segment identifier for "checkplausibilityofput" check in PHP
     *
     * @param {integer} segmentId
     * @param {String} fieldName
     * @return {String}
     */
    _getDuplicateCheckImg(segmentId, fieldName) {
        // TODO get rid of Ext dependency
        return '<img src="' + Ext.BLANK_IMAGE_URL + '" class="duplicatesavecheck" data-segmentid="' + segmentId + '" data-fieldname="' + fieldName + '">';
    }

    isTextNode(item) {
        return item.nodeName === "#text"
    }

    isTrackChangesNode(item) {
        return this.isTrackChangesInsNode(item) || this.isTrackChangesDelNode(item);
    }

    isTrackChangesDelNode(item) {
        return item.tagName === 'DEL';
    }

    isTrackChangesInsNode(item) {
        return item.tagName === 'INS';
    }

    isMQMNode(item) {
        return /(^|[\s])qmflag([\s]|$)/.test(item.className);
    }

    _isImageNode(item) {
        return item.tagName === 'IMG' && !TagsConversion.isDuplicateSaveTag(item);
    }

    _isIgnoredNode(item) {
        return /(^|[\s])ignoreInEditor([\s]|$)/.test(item.className);
    }

    _isSingleTagNode(item) {
        return this._isSingleTag(item.className);
    }

    _isOpenTagNode(item) {
        return this._isOpenTag(item.className);
    }

    _isCloseTagNode(item) {
        return this._isCloseTag(item.className);
    }

    _isSingleTag(className) {
        return /(^|[\s])single([\s]|$)/.test(className);
    }

    _isOpenTag(className) {
        return /(^|[\s])open([\s]|$)/.test(className);
    }

    _isCloseTag(className) {
        return /(^|[\s])close([\s]|$)/.test(className);
    }

    _isWhitespaceTag(className) {
        return /whitespace|nbsp|tab|space|newline|char/.test(className);
    }

    #getMqmFlagNumber(element) {
        const qmFlagClass = Array.from(element.classList).find(cls => cls.startsWith('qmflag-'));

        if (!qmFlagClass) {
            return null;
        }

        return qmFlagClass.replace('qmflag-', '');
    }
}
