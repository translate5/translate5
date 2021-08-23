
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

Ext.define('Editor.view.segments.PixelMapping', {
    alias: 'widget.segment.pixelmapping',
    itemId:'segmentPixelMapping',
    statics: {
        
        SIZE_UNIT_FOR_PIXELMAPPING: 'pixel', // Size-unit used for pixel-mapping
        
        /**
         * What's the length of the text according to the pixelMapping?
         * @param {String} text
         * @param {Object} segmentMeta (from currentSegment.get('metaCache'))
         * @param {Integer} segmentFileId (from currentSegment.get('fileId'))
         * @return {Integer}
         */
        getPixelLength: function (text, segmentMeta, segmentFileId) {
            var me = this,
                pixelLength = 0,
                allCharsInText = me.stringToArray(text),
                pixelMapping = me.getPixelMappingForSegment(segmentMeta),
                unicodeCharNumeric,
                pixelMappingForCharacter,
                charWidth,
                getCharWidth = function(unicodeCharNumeric) {
                    if (pixelMapping[unicodeCharNumeric] !== undefined) {
                        pixelMappingForCharacter = pixelMapping[unicodeCharNumeric];
                        if (pixelMappingForCharacter[segmentFileId] !== undefined) {
                            return pixelMappingForCharacter[segmentFileId];
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
           var key = 0;
           Ext.each(allCharsInText, function(char){
               unicodeCharNumeric = char.codePointAt(0);
               charWidth = getCharWidth(unicodeCharNumeric);
               key++;
               pixelLength += parseInt(charWidth);
               //console.log('['+key+'] ' + char + ' ('+ unicodeCharNumeric + '): ' + charWidth + ' => pixelLength: ' + pixelLength);
           });

           return pixelLength;
        },
        
        /**
         * What's the length of the given internal tag according to the pixelMapping?
         * @param {String} tagHtml
         * @param {Object} segmentMeta (from currentSegment.get('metaCache'))
         * @param {Integer} segmentFileId (from currentSegment.get('fileId'))
         * @return {Integer}
         */
        getPixelLengthFromTag: function (tagNode, segmentMeta, segmentFileId) {
            if(!Editor.data.task.get('pixelMapping') || (segmentMeta &&  segmentMeta.sizeUnit !== this.SIZE_UNIT_FOR_PIXELMAPPING)) {
                return 0;
            }
            
            var me = this, 
                matches = tagNode.className.match(/^([a-z]*)\s+([gxA-Fa-f0-9]*)/), 
                plainTag, tag, 
                returns = {
                    "hardReturn": "\r\n",
                    "softReturn": "\n",
                    "macReturn": "\r"
                };
            if(! matches) {
                return 0;
            }
            //convert storaged data back to plain tag
            tag = me.hexStreamToString(matches[2]);
            plainTag = tag.replace(/[^a-zA-Z]*$/, '');
            //if it is a return, use the hardcoded replacements
            if(returns[plainTag]){
                return me.getPixelLength(returns[plainTag], segmentMeta, segmentFileId);
            }
            //get the real payload from the tag
            if(!(tag = tag.match(/ ts="([^"]+)"/))) {
                return 0;
            }
            
            //count the length of the real payload
            return me.getPixelLength(me.hexStreamToString(tag[1]), segmentMeta, segmentFileId);
        },
        
        /**
         * Return the pixelMapping for a specific segment as already loaded for the task
         * (= the item from the array with all fonts for the task that matches the segment's
         * font-family and font-size).
         * @param {Object} segmentMeta (from currentSegment.get('metaCache'))
         * @return {Array}
         */
        getPixelMappingForSegment: function(segmentMeta) {
            var me = this,
                pixelMapping = Editor.data.task.get('pixelMapping'),
                fontFamily = segmentMeta.font.toLowerCase(),
                fontSize = segmentMeta.fontSize,
                pixelMappingForFontfamily = pixelMapping[fontFamily];
            return pixelMappingForFontfamily[fontSize];
        },
        
        // ---------------------------------------------------------------------------------------
        // tag content - Helpers
        // ---------------------------------------------------------------------------------------

        /**
         * implementation of PHPs pack('H*', data) function to get the tags real content
         */
        hexStreamToString: function(data) {
            return decodeURIComponent(data.replace(/(..)/g,'%$1'));
        },
        
        // ---------------------------------------------------------------------------------------
        // Unicode-Helpers
        // ---------------------------------------------------------------------------------------
        
        /**
         * https://stackoverflow.com/a/38901550
         */
        stringToArray: function (str) {
            var me = this,
                i = 0,
                arr = [],
                codePoint;
            while (!isNaN(codePoint = me.knownCharCodeAt(str, i))) {
                arr.push(String.fromCodePoint(codePoint));
                i++;
            }
            return arr;
        },
        
        /**
         * https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/String/charCodeAt#Fixing_charCodeAt()_to_handle_non-Basic-Multilingual-Plane_characters_if_their_presence_earlier_in_the_string_is_known
         */
        knownCharCodeAt: function (str, idx) {
            str += '';
            var code,
                end = str.length;
            
            var surrogatePairs = /[\uD800-\uDBFF][\uDC00-\uDFFF]/g;
            while ((surrogatePairs.exec(str)) != null) {
              var li = surrogatePairs.lastIndex;
              if (li - 2 < idx) {
                idx++;
              }
              else {
                break;
              }
            }
            
            if (idx >= end || idx < 0) {
              return NaN;
            }
            
            code = str.charCodeAt(idx);
            
            var hi, low;
            if (0xD800 <= code && code <= 0xDBFF) {
              hi = code;
              low = str.charCodeAt(idx + 1);
              // Go one further, since one of the "characters"
              // is part of a surrogate pair
              return ((hi - 0xD800) * 0x400) +
                (low - 0xDC00) + 0x10000;
            }
            return code;
          }
    }
});