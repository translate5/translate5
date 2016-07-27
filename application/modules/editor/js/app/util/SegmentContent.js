
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
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
 * @class Editor.util.SegmentContent
 */
Ext.define('Editor.util.SegmentContent', {
    content: '',
    /**
     * The given segment content is the base for the operations provided by this method
     * @param {String} content
     */
    constructor: function(content) {
        this.contentString = content;
        this.contentAsDom = Ext.dom.Helper.createDom('<div class="faked-body">'+content+'</div>');
    },
    /**
     * returns the content tags as a list of dom nodes
     * @return [{HTMLDivElement}]
     */
    getContentTagNodes: function() {
        return Ext.dom.Query.select("div.open, div.close, div.single", this.contentAsDom);
    },
    /**
     * returns the content tags as a list of Strings
     * @return [{String}]
     */
    getContentTags: function() {
        var nodes = this.getContentTagNodes(),
            detachedDiv = document.createElement("div"),
            result = [];
        
        Ext.Array.each(nodes, function(node) {
            detachedDiv.innerHTML = '';
            detachedDiv.appendChild(node);
            result.push(detachedDiv.innerHTML);
            node = null;
        });
        detachedDiv = null;
        return result;
    }
});