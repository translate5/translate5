
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
  markupImages: null,
  //prefix der img Tag ids im HTML Editor
  idPrefix: 'tag-image-',
  requires: [
      'Editor.view.segments.HtmlEditorLayout'
  ],
  componentLayout: 'htmleditorlayout',

  //Konfiguration der parent Klasse
  enableFormat: false,
  enableFontSize : false,
  enableColors : false,
  enableAlignments : false,
  enableLists : false,
  enableLinks : false,
  enableFont : false,
  isTagOrderClean: true,
  isRtl: false,
  missingContentTags: [],
  duplicatedContentTags: [],
  contentEdited: false, //is set to true if text content or content tags were modified
  disableErrorCheck: false,
  segmentLengthInRange:false,//is segment number of characters in defined range

  strings: {
      tagOrderErrorText: '#UT# Einige der im Segment verwendeten Tags sind in der falschen Reihenfolgen (schließender vor öffnendem Tag).',
      tagMissingText: '#UT# Die nachfolgenden Tags wurden noch nicht hinzugefügt oder beim Editieren gelöscht, das Segment kann nicht gespeichert werden. <br /><br />Die Tastenkombination<br /><ul><li>STRG + EINFG (alternativ STRG + . (Punkt)) fügt den kompletten Quelltext in den Zieltext ein.</li><li>STRG + , (Komma) + &gt;Nummer&lt; fügt den entsprechenden Tag in den Zieltext (Null entspricht Tag Nr. 10) ein.</li><li>STRG + SHIFT + , (Komma) + &gt;Nummer&lt; fügt die Tags mit den Nummern 11 bis 20 in den Zieltext ein.</li></ul>Fehlende Tags:',
      tagDuplicatedText: '#UT# Die nachfolgenden Tags wurden beim Editieren dupliziert, das Segment kann nicht gespeichert werden. Löschen Sie die duplizierten Tags. <br />Duplizierte Tags:',
      tagRemovedText: '#UT# Es wurden Tags mit fehlendem Partner entfernt!',
      cantEditContents: '#UT#Es ist Ihnen nicht erlaubt, den Segmentinhalt zu bearbeiten. Bitte verwenden Sie STRG+Z um Ihre Änderungen zurückzusetzen oder brechen Sie das Bearbeiten des Segments ab.'
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
   */
  //***********************************************************************************
  //End Events
  //***********************************************************************************
  
  initComponent: function() {
    var me = this;
    me.viewModesController = Editor.controller.ViewModes;
    me.metaPanelController = Editor.app.getController('Editor');
    me.segmentsController = Editor.app.getController('Segments');
    me.imageTemplate = new Ext.Template([
      '<img id="'+me.idPrefix+'{key}" class="{type}" title="{text}" alt="{text}" src="{path}"/>'
    ]);
    me.imageTemplate.compile();
    me.spanTemplate = new Ext.Template([
      '<span title="{text}" class="short">&lt;{shortTag}&gt;</span>',
      '<span data-originalid="{id}" data-filename="{md5}" class="full">{text}</span>'
    ]);
    me.spanTemplate.compile();
    me.callParent(arguments);
  },
  initFrameDoc: function() {
	  this.callParent(arguments);
	  this.fireEvent('afterinitframedoc', this);
  },

  /**
   * Überschreibt die Methode um den Editor Iframe mit eigenem CSS ausstatten
   * @returns string
   */
  getDocMarkup: function() {
    var me = this,
        dir = (me.isRtl ? 'rtl' : 'ltr'),
        //ursprünglich wurde ein body style height gesetzt. Das führte aber zu Problemen beim wechsel zwischen den unterschiedlich großen Segmente, daher wurde die Höhe entfernt.
        body = '<html><head><style type="text/css">body{border:0;margin:0;padding:{0}px;}</style>{1}</head><body dir="{2}" style="direction:{2};font-size:12px;line-height:14px;"></body></html>',
        additionalCss = '<link type="text/css" rel="stylesheet" href="'+Editor.data.moduleFolder+'/css/htmleditor.css?v=12" />'; //disable Img resizing
    return Ext.String.format(body, me.iframePad, additionalCss, dir);
  },
  /**
   * overriding default method since under some circumstances this.getWin() returns null which gives an error in original code
   */
  getDoc: function() {
  	  //it is possible that dom is not initialized
  	  if(!this.iframeEl || !this.iframeEl.dom) {
  	  	return null;
  	  }
      return this.iframeEl.dom.contentDocument || (this.getWin() && this.getWin().document);
  },
    
  /**
   * reintroduce our body tag check here, 
   * we have to wait with editor initialzation until body tag is ready
   */
  getEditorBody: function() {
      var doc = this.getDoc(),
          body = doc && (doc.body || doc.documentElement);
      if(body && body.tagName == 'BODY'){
          return body;
      }
      return false;
  },
  
  /**
   * Setzt Daten im HtmlEditor und fügt markup hinzu
   * @param value String
   */
  setValueAndMarkup: function(value, segmentId, fieldName){
      //check tag is needed for the checkplausibilityofput feature on server side 
      var me = this,
          checkTag = me.getDuplicateCheckImg(segmentId, fieldName);
      
      me.setValue(me.markupForEditor(value)+checkTag);
  },
  /**
   * Fixing focus issues EXT6UPD-105 and EXT6UPD-137
   */
  privates: {
      getFocusEl: function() {
          return Ext.isGecko ? this.iframeEl : Ext.fly(this.getEditorBody());
      }
  },
  /**
   * Fixing focus issues EXT6UPD-105
   */
  pushValue: function() {
      this.callParent();
      //do toggle on off not only on gecko, but also on IE
      if(!Ext.isGecko && Ext.isIE11) {
          this.setDesignMode(false);  //toggle off first
          this.setDesignMode(true);
      }
  },
  
  /**
   * Holt Daten aus dem HtmlEditor und entfernt das markup
   * @return String
   */
  getValueAndUnMarkup: function(){
    var me = this,
    	result,
    	body = me.getEditorBody();
    me.checkTags(body);
    me.setSegmentLengthInRange(body.textContent || body.innerText || "");
    result = me.unMarkup(body);
    me.contentEdited = me.plainContent.join('') !== result.replace(/<img[^>]+>/g, '');
    return result;
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
  markupForEditor: function(value) {
    var me = this,
        tempNode = document.createElement('DIV'),
        plainContent = [],
        result;
    me.contentEdited = false;
    me.markupImages = {};
    
    result = me.markup(value, plainContent);
    me.plainContent = plainContent; //stores only the text content and content tags for "original content has changed" comparsion
    return result.join('');
  },
  
  /**
   * Inserts the given string (containing div/span internal tags) at the cursor position in the editor
   * If second parameter is true, the content is not set in the editor, only the internal tags are stored in an internal markup table (for missing tag check for example)
   * @param {String} value
   * @param {Boolean} initMarkupMapOnly optional, default false/omitted
   */
  insertMarkup: function(value, initMarkupMapOnly) {
      var html = this.markup(value).join(''),
          doc = this.getDoc(),
          sel, range, frag, node, el, lastNode, rangeForNode,
          termTags, termTageNode, arrLength, i;
      
      //if that parameter is true, no html is inserted into the target column
      if(initMarkupMapOnly) {
          return;
      }
      
      if (!window.getSelection) {
          //FIXME Not supported by your browser message!
          return;
      }
      sel = doc.getSelection();
      if(sel.getRangeAt) {
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
        for( i=0; i < arrLength; i++ ) {
            termTageNode = termTags[i];
            while(termTageNode.firstChild) {
                termTageNode.parentNode.insertBefore(termTageNode.firstChild,termTageNode);
            }
            termTageNode.parentNode.removeChild(termTageNode);
        }
        // insert
        range.insertNode(frag);
        rangeForNode = range.cloneRange();
        range.setStartAfter(lastNode);
        range.setEndAfter(lastNode); 
        this.fireEvent('afterInsertMarkup', rangeForNode);
      }
  },
  /**
   * converts the given HTML String to HTML ready for the Editor (div>span to img tags)
   * Each call adds the found internal tags to the markupImages map.
   * @param value {String}
   * @param plainContent {Array} optional, needed for markupForEditor only
   */
  markup: function(value, plainContent) {
    var me = this,
        tempNode = document.createElement('DIV'),
        plainContent = plainContent || [];
    me.result = [];
    //tempnode mit inhalt füllen => Browser HTML Parsing
    value = value.replace(/ </g, Editor.TRANSTILDE+'<');
    value = value.replace(/> /g, '>'+Editor.TRANSTILDE);
    Ext.fly(tempNode).update(value);
    me.replaceTagToImage(tempNode, plainContent);
    return me.result;
  },

  replaceTagToImage: function(rootnode, plainContent) {
    var me = this,
        data = {
            fullPath: Editor.data.segments.fullTagPath,
            shortPath: Editor.data.segments.shortTagPath
        };
    
    Ext.each(rootnode.childNodes, function(item){
      var termFoundCls;
      if(Ext.isTextNode(item)){
        var text = item.data.replace(new RegExp(Editor.TRANSTILDE, "g"), ' ');
        me.result.push(Ext.htmlEncode(text));
        plainContent.push(Ext.htmlEncode(text));
        return;
      }
      // Keep nodes from TrackChanges, but replace their images etc.
      if( (item.tagName.toLowerCase() == 'ins' || item.tagName.toLowerCase() == 'del')  && /(^|[\s])trackchanges([\s]|$)/.test(item.className)){
          // TrackChange-Node might include images: 
          // - add the special id to the img
          // - replace the given divs and spans with their image
          // TrackChange-Node might include TermTag: 
          // - replace TermTag-divs with their corresponding span
          var allImagesInItem = item.getElementsByTagName('IMG');
              allDivsInItem = item.getElementsByTagName('DIV');
          if (allImagesInItem.length > 0) {
              for (var i = allImagesInItem.length; i--; ) { // backwards because we might remove items
                  var imgItem = allImagesInItem[i];
                  if (!me.isDuplicateSaveTag(imgItem)) {
                      var htmlForItemImg = me.imgNodeToString(imgItem, true),
                      templateEl = document.createElement('template');
                      templateEl.innerHTML = htmlForItemImg;
                      item.insertBefore(templateEl.content.firstChild,imgItem);
                      item.removeChild(imgItem);
                  }
              }
          }
          if (allDivsInItem.length > 0) {
              for (var i = allDivsInItem.length; i--; ) { // backwards because we might remove items
                  var divItem = allDivsInItem[i];
                  if (divItem == null) {
                      continue; // item might have been removed alreday
                  }
                  if(/(^|[\s])term([\s]|$)/.test(divItem.className)){
                      var htmlForDivItem = '',
                          termFoundCls = divItem.className;
                      // TODO: is the same as below for "// Span für Terminologie"
                      if(me.fieldTypeToEdit) {
                          var replacement = me.fieldTypeToEdit+'-$1';
                          termFoundCls = termFoundCls.replace(/(transFound|transNotFound|transNotDefined)/, replacement);
                      }
                      htmlForDivItem += Ext.String.format('<span class="{0}" title="{1}">', termFoundCls, divItem.title);
                      htmlForDivItem += divItem.innerHTML;
                      htmlForDivItem += '</span>';
                      var templateEl = document.createElement('template');
                      templateEl.innerHTML = htmlForDivItem;
                      item.insertBefore(templateEl.content.firstChild,divItem.parentNode);
                      item.removeChild(divItem.parentNode);
                  } else {
                      var divItem = allDivsInItem[i],
                          dataOfItem,
                          htmlForItemImg;
                      dataOfItem = me.getData(divItem,data),
                      htmlForItemImg = me.imageTemplate.apply(dataOfItem);
                      var templateEl = document.createElement('template');
                      templateEl.innerHTML = htmlForItemImg;
                      item.insertBefore(templateEl.content.firstChild,divItem);
                      item.removeChild(divItem);
                  }
              }
          }
          item.innerHTML = item.innerHTML.replace(new RegExp(Editor.TRANSTILDE, "g"), ' ');
          me.result.push(item.outerHTML);
          plainContent.push(item.outerHTML);
          return;
      }
      if(item.tagName == 'IMG' && !me.isDuplicateSaveTag(item)){
          me.result.push(me.imgNodeToString(item, true));
          return;
      }
      // Span für Terminologie
      if(item.tagName == 'DIV' && /(^|[\s])term([\s]|$)/.test(item.className)){
        termFoundCls = item.className
        if(me.fieldTypeToEdit) {
            var replacement = me.fieldTypeToEdit+'-$1';
            termFoundCls = termFoundCls.replace(/(transFound|transNotFound|transNotDefined)/, replacement);
        }
        me.result.push(Ext.String.format('<span class="{0}" title="{1}">', termFoundCls, item.title));
        me.replaceTagToImage(item, plainContent);
        me.result.push('</span>');
        return;
      }
      //some tags are marked as to be igored in the editor, so we ignore them
      if(item.tagName == 'DIV' && /(^|[\s])ignoreInEditor([\s]|$)/.test(item.className)){
          return;
      }
      if(item.tagName != 'DIV'){
        return;
      }
      data = me.getData(item,data);
      
      me.result.push(me.imageTemplate.apply(data));
      plainContent.push(me.markupImages[data.key].html);
    });
  },
  /**
   * daten aus den tags holen
   */
  getData: function (item,data) {
      var me = this,
          divItem, spanFull, spanShort, split,
          sp, fp, //[short|full]Path shortcuts;
          shortTagContent;
      divItem = Ext.fly(item);
      spanFull = divItem.down('span.full');
      spanShort = divItem.down('span.short');
      data.text = spanFull.dom.innerHTML.replace(/"/g, '&quot;');
      data.id = spanFull.getAttribute('data-originalid');
      //old way is to use only the id attribute, new way is to use separate data fields
      // both way are currently used!
      if(data.id) {
          //new way
          data.md5 = spanFull.getAttribute('data-filename');
      }
      else {
          split = spanFull.getAttribute('id').split('-');
          data.id = split.shift();
          data.md5 = split.pop();
      }
      shortTagContent = spanShort.dom.innerHTML;
	  data.nr = shortTagContent.replace(/[^0-9]/g, '');
      if(shortTagContent.search(/locked/)!==-1){
          data.nr = 'locked'+data.nr;
      }
      //Fallunterscheidung Tag Typ
      switch(true){
        case /open/.test(item.className):
          data.type = 'open';
          data.suffix = '-left';
          data.shortTag = data.nr;
          break;
        case /close/.test(item.className):
          data.type = 'close';
          data.suffix = '-right';
          data.shortTag = '/'+data.nr;
          break;
        case /single/.test(item.className):
          data.type = 'single';
          data.suffix = '-single';
          data.shortTag = data.nr+'/';
          break;
      }
      data.key = data.type+data.nr;

      //zusammengesetzte img Pfade:
      sp = data.shortPath+data.nr+data.suffix+'.png';
      fp = data.fullPath+data.md5+data.suffix+'.png';
      //caching der Pfade und den zugehörigen divs fürs unmarkup 
      me.markupImages[data.key] = {
          shortPath: sp,
          fullPath: fp,
          html: '<div class="'+item.className+'">'+me.spanTemplate.apply(data)+'</div>'
      };

      if(me.viewModesController.isFullTag()){
        data.path = fp;
      }
      else {
        data.path = sp;
      }
      return data;
  },
  /**
   * ersetzt die images durch div und spans im string 
   * @private
   * @param node dom-node
   * @return String
   */
  unMarkup: function(node){
      var me = this,
          result = [];
    
      if(!node.hasChildNodes()){
          return "";
      }
    
      Ext.each(node.childNodes, function(item){
          var markupImage,
              text, img;
          if(Ext.isTextNode(item)){
              text = item.data;
              result.push(Ext.htmlEncode(text));
              return;
          }
          // Keep nodes from TrackChanges
          if( (item.tagName.toLowerCase() == 'ins' || item.tagName.toLowerCase() == 'del')  && /(^|[\s])trackchanges([\s]|$)/.test(item.className)){
              // TrackChange-Node might include images => replace the images with their divs and spans:
              var allImagesInItem = item.getElementsByTagName('img');
              if( allImagesInItem.length > 0) {
                  for (var i=allImagesInItem.length; i--; ) { // backwards because we might remove items
                      var imgItem = allImagesInItem[i],
                          imgHtml = me.unmarkImage(imgItem);
                      if (imgHtml != '') {
                          var template = document.createElement('template');
                          template.innerHTML = imgHtml;
                          imgItem.parentNode.insertBefore(template.content.firstChild,imgItem);
                          imgItem.parentNode.removeChild(imgItem);
                      }
                  }
              }
              result.push(item.outerHTML);
              return;
          }
          // recursive processing of Terminologie spans, removes the term span
          //@todo die Term Spans fliegen hier richtigerweise raus (wg. Umsortierung des Textes)
          //Allerdings muss danach die Terminologie anhand der Begriffe im Text wiederhergestellt werden.
          if(item.tagName == 'SPAN' && item.hasChildNodes()){
              result.push(me.unMarkup(item));
              return;
          }
          if(item.tagName == 'IMG'){
              result.push(me.unmarkImage(item));
              return;
          }
          if(item.hasChildNodes()){
              result.push(me.unMarkup(item));
              return;
          }
          result.push(item.textContent || item.innerText);
      });
      return result.join('');
  },
  unmarkImage: function(item) {
      var me = this;
      if(me.isDuplicateSaveTag(item)){
          img = Ext.fly(item);
          return me.getDuplicateCheckImg(img.getAttribute('data-segmentid'), img.getAttribute('data-fieldname'));
      }
      else if(markupImage = me.getMarkupImage(item.id)){
          return markupImage.html;
      }
      else if(/^qm-image-/.test(item.id)){
          return me.imgNodeToString(item, false);
      }
  },
  /**
   * generates a img tag string
   * @param {Image} imgNode
   * @param {Boolean} markup flag whether markup or unmarkup process
   * @returns {String}
   */
  imgNodeToString: function(imgNode, markup) {
	  var id = '', 
	  	  src = imgNode.src.replace(/^.*\/\/[^\/]+/, ''),
	  	  img = Ext.fly(imgNode),
	  	  comment = img.getAttribute('data-comment');
	  	  seq = img.getAttribute('data-seq');
	  if(markup) { //on markup an id is needed for remove orphaned tags
		  //qm-image-open-#
		  //qm-image-close-#
		  id = (/open/.test(imgNode.className) ? 'open' : 'close');
		  id = 'id="qm-image-'+id+'-'+seq+'"';
	  }
	  return Ext.String.format('<img {0} class="{1}" data-seq="{2}" data-comment="{3}" src="{4}" />', id, imgNode.className, seq, comment ? comment : '', src);
  },
  /**
   * returns a IMG tag with a segment identifier for "checkplausibilityofput" check in PHP
   * @param {Integer} segmentId
   * @param {String} fieldName
   * @return {String}
   */
  getDuplicateCheckImg: function(segmentId, fieldName) {
      return '<img src="'+Ext.BLANK_IMAGE_URL+'" class="duplicatesavecheck" data-segmentid="'+segmentId+'" data-fieldname="'+fieldName+'">';
  },
  /**
   * returns true if given html node is a duplicatesavecheck img tag
   * @param {HtmlNode} img
   * @return {Boolean}
   */
  isDuplicateSaveTag: function(img) {
      return img.tagName == 'IMG' && img.className && /duplicatesavecheck/.test(img.className);
  },
  /**
   * disables the hasAndDisplayErrors method on its next call, used for save and ignore the tag checks
   */
  disableContentErrorCheckOnce: function() {
      this.disableErrorCheck = true;
  },
  /**
   * used by the row editor for content validation
   * @return {Boolean}
   */
  hasAndDisplayErrors: function() {
      var me = this;
      //if we are running a second time into this method triggered by callback, 
      //  the callback can disable a second error check
      if(me.disableErrorCheck){
          me.fireEvent('contentErrors', me, null);
          me.disableErrorCheck = false;
          return false;
      }

	  //since this error can't be handled somehow, we don't fire an event but show the message and stop immediatelly
      if(Editor.data.task.get('notEditContent') && me.contentEdited){
          Editor.MessageBox.addError(me.strings.cantEditContents);
          return true;
      }

      //if the segment characters length is not in the defined range, add the message
      if(!me.segmentLengthInRange) {
          //fire the event, and get the message from the segmentminmaxlength component
          me.fireEvent('contentErrors', me, me.getSegmentMinMaxLength().activeErroMessage);
          return true;
      }
      
      if(me.missingContentTags.length > 0 || me.duplicatedContentTags.length > 0){
          var msg = '', 
              //first item the field to check, second item: the error text:
              todo = [['missingContentTags', 'tagMissingText'],['duplicatedContentTags','tagDuplicatedText']];
          for(var i = 0;i<todo.length;i++) {
              if(me[todo[i][0]].length > 0) {
                  msg += me.strings[todo[i][1]];
                  Ext.each(me[todo[i][0]], function(tag) {
                      msg += '<img src="'+tag.shortPath+'"> ';
                  })
                  msg += '<br /><br />';
              }
          }
          me.fireEvent('contentErrors', me, msg);
          return true;
      }
      if(!me.isTagOrderClean){
          me.fireEvent('contentErrors', me, me.strings.tagOrderErrorText);
          return true;
      }
      me.fireEvent('contentErrors', me, null);
      return false;
  },
  /**
   * check and fix tags
   * @param node
   */
  checkTags: function(node) {
      var nodelist = node.getElementsByTagName('img');
      this.fixDuplicateImgIds(nodelist);
      if(!this.checkContentTags(nodelist)) {
          return; //no more checks if missing tags found
      }
      this.removeOrphanedTags(nodelist);
      this.checkTagOrder(nodelist);
  },
  /**
   * returns true if all tags are OK
   * @param {Array} nodelist
   * @return {Boolean}
   */
  checkContentTags: function(nodelist) {
      var me = this,
          foundIds = [];
      me.missingContentTags = [];
      me.duplicatedContentTags = [];
      
      //FIXME ignore deleted tags!
      Ext.each(nodelist, function(img) {
          if(Ext.Array.contains(foundIds, img.id)) {
              me.duplicatedContentTags.push(me.markupImages[img.id.replace(new RegExp('^'+me.idPrefix), '')]);
          }
          else {
              foundIds.push(img.id);
          }
      });
      Ext.Object.each(this.markupImages, function(key, item){
          if(!Ext.Array.contains(foundIds, me.idPrefix+key)) {
              me.missingContentTags.push(item);
          }
      });
      return me.missingContentTags.length == 0 && me.duplicatedContentTags.length == 0;
  },
  /**
   * Tag Order Check (MQM and content tags)
   * assumes that img tag contains an id with substring "-open" or "-close"
   * ids starting with "remove" are ignored, because they are marked to be removed by removeOrphanedTags   
   * @param {Array} nodelist
   */
  checkTagOrder: function(nodelist) {
	  var me = this, open = {}, clean = true;
	  Ext.each(nodelist, function(img) {
		  if(me.isDuplicateSaveTag(img) || /^remove/.test(img.id) || /-single/.test(img.id)){
			  //ignore tags marked to remove
			  return;
		  }
		  if(/-open/.test(img.id)){
			  open[img.id] = true;
			  return;
		  }
		  var o = img.id.replace(/-close/, '-open');
		  if(! open[o]) {
			  clean = false;
			  return false; //break each
		  }
	  });
	  this.isTagOrderClean = clean;
  },
  /**
   * Fixes duplicate img ids in the opened editor on unmarkup (MQM tags)
   * Works with <img> tags with the following specifications: 
   * IMG needs an id Attribute. Assuming that the id contains the strings "-open" or "-close". The rest of the id string is identical.
   * Needs also an attribute "data-seq" which is containing the plain ID of the tag pair.
   * If a duplicated img tag is found, the "123" of the id will be replaced with a generated Ext.id()
   * 
   * example, tag with needed infos:
   * <img id="foo-open-123" data-seq="123"/> open tag 
   * <img id="foo-close-123" data-seq="123"/> close tag
   * 
   * copying this tags will result in
   * <img id="foo-open-ext-456" data-seq="ext-456"/> 
   * <img id="foo-close-ext-456" data-seq="ext-456"/>
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
   * @param {Array} nodelist
   */
  fixDuplicateImgIds: function(nodelist) {
	  var me = this, 
	      ids = {}, 
	      stackList = {}, 
	      updateId = function(img, newSeq, oldSeq) {
	          //dieses img mit der neuen seq versorgen.
	          img.id = img.id.replace(new RegExp(oldSeq+'$'), newSeq);
	          img.setAttribute('data-seq', newSeq);
	      };
	    //duplicate id fix vor removeOrphanedLogik, da diese auf eindeutigkeit der IDs baut
	    //dupl id fix benötigt checkTagOrder, welcher sich aber mit removeOrphanedLogik beißt
	    Ext.each(nodelist, function(img) {
	    	var newSeq, oldSeq = img.getAttribute('data-seq'), id = img.id, pid, open;
	    	if(! id || me.isDuplicateSaveTag(img)) {
	    		return;
	    	}
	    	if(! ids[id]) {
	    		//id noch nicht vorhanden, dann ist sie nicht doppelt => raus
	    		ids[id] = true;
	    		return;
	    	}

	    	//gibt es einen Stack mit inhalten für meine ID, dann hole die Seq vom Stack und verwende diese
	    	if(stackList[id] && stackList[id].length > 0) {
	    		newSeq = stackList[id].shift();
	    		updateId(img, newSeq, oldSeq);
	    		return;
	    	}
    		//wenn nein, dann:
    		//partner id erzeugen
	    	open = new RegExp("-open");
    		if(open.test(id)) {
    			pid = id.replace(open, '-close');
    		}
    		else {
    			pid = id.replace(/-close/, '-open');
    		}
    		//bei bedarf partner stack erzeugen
    		if(!stackList[pid]) {
    			stackList[pid] = [];
    		}
    		newSeq = Ext.id();
    		//die neue seq auf den Stack der PartnerId legen
    		stackList[pid].push(newSeq);
	    	updateId(img, newSeq, oldSeq);
	    });
  },
  
  /**
   * removes orphaned tags (MQM only)
   * assuming same id for open and close tag. Each Tag ID contains the string "-open" or "-close"
   * prepends "remove-" to the id of an orphaned tag
   * @see fixDuplicateImgIds
   * @param {Array} nodelist
   */
  removeOrphanedTags: function(nodelist) {
	var me = this, openers = {}, closers = {}, hasRemoves = false;
    Ext.each(nodelist, function(img) {
        if(me.isDuplicateSaveTag(img)) {
            return;
        }
      if(/-open/.test(img.id)){
        openers[img.id] = img;
      }
      if(/-close/.test(img.id)){
        closers[img.id] = img;
      }
    });
    Ext.iterate(openers, function(id, img){
      var closeId = img.id.replace(/-open/, '-close');
      if(closers[closeId]) {
        //closer zum opener => aus "closer entfern" liste raus
        delete closers[closeId];
      }
      else {
        //kein closer zum opener => opener zum entfernen markieren
    	hasRemoves = true;
        img.id = 'remove-'+img.id;
      }
    });
    Ext.iterate(closers, function(id, img){
	  hasRemoves = true;
      img.id = 'remove-'+img.id;
    });
    if(hasRemoves) {
    	Editor.MessageBox.addInfo(this.strings.tagRemovedText);
    }
  },
  /**
   * returns img tags contained in the currently edited field as img nodelist
   */
  getTags: function(compareList) {
      var me = this,
          body = me.getEditorBody();
      return node.getElementsByTagName('img');
  },
  showShortTags: function() {
    this.rendered && this.setImagePath('shortPath');
  },
  showFullTags: function() {
    this.rendered && this.setImagePath('fullPath');
  },
  setImagePath: function(target){
    var me = this;
    me.getEditorBody().className = '';
    Ext.each(Ext.query('img', true, me.getEditorBody()), function(item){
      var markupImage;
      if(markupImage = me.getMarkupImage(item.id)){
        item.src = markupImage[target];
      }
    });
  },
  /**
   * @param imgHtml string containing the MarkupImageId ([open|close|single][0-9]+) prefixed by this.idPrefix
   * @returns this.markupImages item
   */
  getMarkupImage: function(imgHtml) {
    var matches = imgHtml.match(new RegExp('^'+this.idPrefix+'((open|close|single)([0-9]+|locked[0-9]+))'));
    if (!matches || matches.length != 4) {
      return null;
    }
    if(this.markupImages[matches[1]]){
      return this.markupImages[matches[1]];
    }
    return null;
  },
  /**
   * überschreibt die Methode um die leere Toolbar ausblenden
   */
  createToolbar: function() {
    this.callParent(arguments);
    this.toolbar.hide();
  },
  /**
   * überschreibt die Methode um HtmlEditor Funktionen (bold etc. pp.) zu deaktivieren
   */
  execCmd: function() {
  },
  hasSelection: function() {
	  var doc = this.getDoc();
	  if(doc.selection) {
		  return doc.selection.type.toLowerCase() != 'none'; 
	  }
	  if(! doc.getSelection){
		  return false;
	  }
	  var sel = doc.getSelection();
	  return !(sel.isCollapsed || sel.rangeCount > 0 && sel.getRangeAt(0).collapsed);
  },
  destroyEditor: function() {
      //do nothing, here since the getWin().un('unload',...); in the original method throws an exception
      // since our htmlEditor is only destroyed once at page reload we just do nothing here
      // the comments in the original method about leaked IE6/7 can be ignored so far
  },
  setDirectionRtl: function(isRtl) {
      var me = this;
      me.isRtl = isRtl;
      if(!me.getDoc().editorInitialized) {
          return;
      }
      var body = Ext.fly(me.getEditorBody()),
          dir = isRtl ? 'rtl' : 'ltr';
      body.set({"dir": dir});
      body.setStyle('direction', dir);
  },
  
  /***
   * Set the internal flag segmentLengthInRange based on if the currently edited segment number of characters is within the defined range.
   */
  setSegmentLengthInRange:function(bodyText){
      var me=this,
          minMaxLength=me.getSegmentMinMaxLength();
      //check if the the min/max length is supplied
      if(minMaxLength){
          me.segmentLengthInRange=minMaxLength.isSegmentLengthInRange(bodyText);
          return;
      }
      me.segmentLengthInRange=true;
  },
  
  /***
   * Return segmentMinMaxLength component instance
   */
  getSegmentMinMaxLength:function(){
      var me=this,
          minMaxLength=Ext.ComponentQuery.query('#segmentMinMaxLength');
      return minMaxLength.length>0 ? minMaxLength[0] : null;
  }
  
});
