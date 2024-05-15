export default class PixelMapping {
    // Size-unit used for pixel-mapping
    static SIZE_UNIT_FOR_PIXEL_MAPPING = 'pixel';

    /**
     * @param {Font} font
     */
    constructor(font) {
        this.font = font;
    }

    /**
     * What's the length of the given internal tag according to the pixelMapping?
     * @param {HTMLElement} tagNode
     * @return {int}
     */
    getPixelLengthFromTag(tagNode) {
        if (!this.getPixelMappingSettings()
            || (this.font.sizeUnit !== PixelMapping.SIZE_UNIT_FOR_PIXEL_MAPPING)
        ) {
            return 0;
        }

        const matches = tagNode.className.match(/^([a-z]*)\s+([xA-Fa-g0-9]*)/),
            returns = {
                "hardReturn": "\r\n",
                "softReturn": "\n",
                "macReturn": "\r"
            };

        if (!matches) {
            return 0;
        }

        //convert stored data back to plain tag
        let tag = this.hexStreamToString(matches[2]);
        let plainTag = tag.replace(/[^a-zA-Z]*$/, '');

        //if it is a return, use the hardcoded replacements
        if (returns[plainTag]) {
            return this.getPixelLength(returns[plainTag]);
        }

        //get the real payload from the tag
        if (!(tag = tag.match(/ ts="([^"]+)"/))) {
            return 0;
        }

        //count the length of the real payload
        return this.getPixelLength(this.hexStreamToString(tag[1]));
    }

    /**
     * What's the length of the text according to the pixelMapping?
     * @param {String} text
     * @return {int}
     */
    getPixelLength(text) {
        const allCharsInText = this.stringToArray(text),
            pixelMapping = this.getPixelMappingForSegment();

        let unicodeCharNumeric,
            pixelLength = 0,
            pixelMappingForCharacter,
            charWidth;

        const getCharWidth = function (unicodeCharNumeric) {
            if (pixelMapping[unicodeCharNumeric] !== undefined) {
                pixelMappingForCharacter = pixelMapping[unicodeCharNumeric];
                if (pixelMappingForCharacter[this.font.fieldId] !== undefined) {
                    return pixelMappingForCharacter[this.font.fieldId];
                }
                if (pixelMappingForCharacter['default'] !== undefined) {
                    return pixelMappingForCharacter['default'];
                }
            }
            return pixelMapping['default'];
        };

        //console.dir(pixelMapping);
        //console.log(text);
        //console.dir(allCharsInText);
        let key = 0;
        allCharsInText.forEach(function (char) {
            unicodeCharNumeric = char.codePointAt(0);
            charWidth = getCharWidth(unicodeCharNumeric);
            key++;
            pixelLength += parseInt(charWidth);
            //console.log('['+key+'] ' + char + ' ('+ unicodeCharNumeric + '): ' + charWidth + ' => pixelLength: ' + pixelLength);
        });

        return pixelLength;
    }

    /**
     * Return the pixelMapping for a specific segment as already loaded for the task
     * (= the item from the array with all fonts for the task that matches the segment's
     * font-family and font-size).
     *
     * @return {Array}
     */
    getPixelMappingForSegment() {
        const pixelMapping = this.getPixelMappingSettings();

        return pixelMapping[this.font.fontFamily][this.font.fontSize];
    }

    getPixelMappingSettings() {
        // TODO think how we will handle global settings
        return Editor.data.task.get('pixelMapping');
    }

    // region Helpers

    // ---------------------------------------------------------------------------------------
    // tag content - Helpers
    // ---------------------------------------------------------------------------------------

    /**
     * implementation of PHPs pack('H*', data) function to get the tags real content
     */
    hexStreamToString(data) {
        return decodeURIComponent(data.replace(/(..)/g, '%$1'));
    }

    // ---------------------------------------------------------------------------------------
    // Unicode-Helpers
    // ---------------------------------------------------------------------------------------

    /**
     * https://stackoverflow.com/a/38901550
     */
    stringToArray(str) {
        const me = this,
            arr = [];
        let i = 0,
            codePoint;

        while (!isNaN(codePoint = me.knownCharCodeAt(str, i))) {
            arr.push(String.fromCodePoint(codePoint));
            i++;
        }

        return arr;
    }

    /**
     * https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/String/charCodeAt#Fixing_charCodeAt()_to_handle_non-Basic-Multilingual-Plane_characters_if_their_presence_earlier_in_the_string_is_known
     */
    knownCharCodeAt(str, idx) {
        str += '';

        const end = str.length;
        const surrogatePairs = /[\uD800-\uDBFF][\uDC00-\uDFFF]/g;

        while ((surrogatePairs.exec(str)) != null) {
            const li = surrogatePairs.lastIndex;
            if (li - 2 < idx) {
                idx++;
            } else {
                break;
            }
        }

        if (idx >= end || idx < 0) {
            return NaN;
        }

        let code = str.charCodeAt(idx);

        let hi,
            low;

        if (0xD800 <= code && code <= 0xDBFF) {
            hi = code;
            low = str.charCodeAt(idx + 1);
            // Go one further, since one of the "characters"
            // is part of a surrogate pair
            return ((hi - 0xD800) * 0x400) + (low - 0xDC00) + 0x10000;
        }

        return code;
    }

    // endregion Helpers
}
