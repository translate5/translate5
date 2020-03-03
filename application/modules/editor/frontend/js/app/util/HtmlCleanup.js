
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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * @class HtmlCleanup: Cleans Markup used by the Segment-Editor from invisible characters and injected special tags to retrieve markup for use outside of the editor
 * TODO: this Code has overlappings with Editor.util.SearchReplaceUtils
 */
Ext.define('Editor.util.HtmlCleanup', {
	
	
	cleanAllUnwantedMarkup: function(html){
		// entfernt vom Editor / TrackChanges automatisch hinzugefügte unsichtbare Zeichen und alle internen Tags
		return this.cleanAllInternalTags(this.cleanInvisibleCharacters(html));
	},
	
	cleanInvisibleCharacters: function(html){
		// entfernt vom Editor / TrackChanges automatisch hinzugefügte unsichtbare Zeichen
		return html.replace(/\u200B|\uFEFF/g, '');
	},
	
	cleanAllInternalTags: function(html){
		html = this.cleanDuplicateSaveImgTags(html);
		html = this.cleanDeleteTags(html);
		html = this.cleanInsertTags(html);
		html = this.cleanMarkerTags(html);
		return html;
	},
	// TODO: used by RowEditorColumnParts. needed there or replacable with cleanAllInternalTags ??
	cleanAllInternalButMarkTags: function(html){
		html = this.cleanDuplicateSaveImgTags(html);
		html = this.cleanDeleteTags(html);
		html = this.cleanInsertTags(html);
		return html;
	},
	
	cleanDuplicateSaveImgTags: function(html){
		// (1) remove DEL-Tags and their content
		return html.replace(/<img[^>]* class="duplicatesavecheck"[^>]*>/,'');
		
	},
	
	cleanDeleteTags: function(html){
		// remove DEL-Tags and their content
		return html.replace(/<del[^>]*>.*?<\/del>/ig,'');
	},
	
	cleanInsertTags: function(html){
		 // remove INS-Tags and keep their content:
		return html.replace(/<ins[^>]*>/ig, '').replace(/<\/ins>/ig, '');
	},
	
	cleanMarkerTags: function(html){
		 // remove INS-Tags and keep their content:
		return html.replace(/<mark[^>]*>+|<\/mark>/ig, '');
	},
});