"use strict";


// TODO: implement seperate class to handle the resizing when frame-contents are changed

(function(w, d, $) {

	// quick way of adding rotation-getter to jQuery ... a plugin seems overkill for now
	// see https://stackoverflow.com/questions/8270612/get-element-moz-transformrotate-value-in-jquery
	$.fn.rotation = function(){
		var el = $(this), tr = el.css("transform") || el.css("-webkit-transform") || el.css("-moz-transform") || el.css("-ms-transform") || el.css("-o-transform") || '';
		if (tr = tr.match('matrix\\((.*)\\)')) {
			tr = tr[1].split(',');
			if(typeof tr[0] != 'undefined' && typeof tr[1] != 'undefined') {
				var rad = Math.atan2(tr[1], tr[0]);
				return parseFloat((rad * 180 / Math.PI).toFixed(1));
			}
		}
		return 0;
	};
	// quick way of getting the parsed transformation in jQuery
	$.fn.transformationScale = function(){
		var el = $(this), tr = el.css("transform") || el.css("-webkit-transform") || el.css("-moz-transform") || el.css("-ms-transform") || el.css("-o-transform") || '';
		if (tr = tr.match('matrix\\((.*)\\)')) {
			tr = tr[1].split(',');
			if(typeof tr[0] != 'undefined' && typeof tr[3] != 'undefined') {
				return {"x":parseFloat(tr[0]),"y":parseFloat(tr[3])};
			}
		}
		return {"x":1,"y":1};
	};
	// Small extension of JQuery to get an API the retrieves the merged direct text-nodes of an element
	// and to have an api to check if an element has no absolute children
	function elementText(el, separator) {
		var textContents = [];
		for(var chld = el.firstChild; chld; chld = chld.nextSibling) {
			if (chld.nodeType == 3) {
				textContents.push(chld.nodeValue);
			}
		}
		return textContents.join(separator);
	}
	$.fn.directText = function(elementSeparator, nodeSeparator) {
		if (arguments.length<2){
			nodeSeparator = "";
		}
		if (arguments.length<1){
			elementSeparator = "";
		}
		return $.map(this, function(el){
			return elementText(el, nodeSeparator);
		}).join(elementSeparator);
	};
	// Shim for the outerHTML API that is missing in some browsers
	$.fn.outerHTML = function(){
		return (!this.length) ? this : (this[0].outerHTML || (
		function(el){
			var div = document.createElement('div');
			div.appendChild(el.cloneNode(true));
			var contents = div.innerHTML;
			div = null;
			return contents;
		})(this[0]));
	};


	/* *************************************************************************************************** */
	/* ********************************** Live Editing Parser ******************************************** */
	/*
		This parser is tailored to convert the output of pdf2htmlEX to a simpler structure that
		tries to keep the text-flow. There are lots of assumptions on how he output of pdf2htmlEX
		is structured. This parser is not meant to parse any non-pdf2htmlEX generated HTML-structure in any way ...
		Prequesites of the parsed HTML:
		
	
	*/
	
	/* *************************************************************************************************** */

	var LeParserSort = {
		numerical: function(a, b){
			return a - b;
		},
		byPos: function(a, b){
			return LeParserSort._byPropAsc(a, b, "pos");
		},
		byBottom: function(a, b){
			return LeParserSort._byPropAsc(a, b, "bottom");
		},
		byLeft: function(a, b){
			return LeParserSort._byPropAsc(a, b, "left");
		},
		byRight: function(a, b){
			return LeParserSort._byPropAsc(a, b, "right");
		},
		bySize: function(a, b){
			return LeParserSort._byPropAsc(a, b, "size");
		},
		byWeight: function(a, b){
			return LeParserSort._byPropDesc(a, b, "weight");
		},
		byChars: function(a, b){
			return LeParserSort._byPropDesc(a, b, "chars");
		},
		_byPropAsc: function(a, b, prop){
			if(a[prop] < b[prop]){
				return -1;
			}
			if (a[prop] > b[prop]){
				return 1;
			}
			return 0;
		},
		_byPropDesc: function(a, b, prop){
			if(a[prop] > b[prop]){
				return -1;
			}
			if (a[prop] < b[prop]){
				return 1;
			}
			return 0;
		}
	};
	
	/* *************************************************************************************************** */
	
	// represents the font-properties of an element
	var LeParserFont = function(size, lineheight, element, jNode, leParserFont){
		this.size = this._round(size);
		this.height = this._round(lineheight); // represents the line-height !
		this.renderedHeight = 0; // represents the calculated line-height, either by setting it explicitly or by calculation via LeParserFontMetrics
		this.frameIndex = -1; // indentifies the frame we belong to
		if(element && element != null && size == lineheight){
			// we can evaluate the character-height by what pdf2html sets for the height of the box
			this.charheight = this._round(element.getUnscaledHeight());
			if(this.charheight > this.size){
				this.charheight = this.size;
			}			
		} else {
			this.charheight = this.size;
		}
		if(jNode && jNode != null){
			// initialization from a real dom-node
			this.family = jNode.css('font-family');
			this.style = jNode.css('font-style');
			this.weight = jNode.css('font-weight');
			this.color = jNode.css('color');
			// pdf2html works with explicit whitespace-rendering at times !!
			this.whitespace = jNode.css('white-space');
		} else if(leParserFont && leParserFont != null) {
			// initialization from a clone
			this.family = leParserFont.family;
			this.style = leParserFont.style;
			this.weight = leParserFont.weight;
			this.color = leParserFont.color;
			this.whitespace = leParserFont.whitespace;
			this.charheight = leParserFont.charheight;
			this.frameIndex = leParserFont.frameIndex;
		} else {
			// DEFAULT initialization
			this.family = "sans-serif";
			this.style = "normal";
			this.weight = "400";
			this.color = "rgb(0,0,0)";
			this.whitespace = "normal";
		}		
		// DEBUG: console.log("INIT LeParserFont: family: "+this.family+" / size: "+this.size+" / style: "+this.style+" / weight: "+this.weight+" / height: "+this.height+" / color: "+this.color+" / charheight: "+this.charheight);
	};
	LeParserFont.prototype.clone = function(){
		return new LeParserFont(this.size, this.height, null, null, this);
	};
	LeParserFont.prototype.isEqual = function(font){
		return (this.family == font.family && this.size == font.size && this.style == font.style && this.weight == font.weight && this.height == font.height && this.color == font.color);
	};
	// forcedLineHeight is expected to be in PERCENT
	LeParserFont.prototype.getStyle = function(forcedLineHeight){
		var style = 'font-family:'+this.family+'; font-size:'+this.size+'px; font-style:'+this.style+'; font-weight:'+this.weight+'; color:'+this.color+';';
		if(this.whitespace != "normal"){
			// we convert pre to pre-wrap since pre will not allow properly breakable text if we optimize for editability
			var wp = (this.whitespace == "pre" && !leParser.config.joinMultilineWithBr) ? "pre-wrap" : this.whitespace;
			style += ' white-space:'+wp+';';
		}
		var lh = (forcedLineHeight && forcedLineHeight > 0) ? this._round(this.size * forcedLineHeight / 100) : this.getRenderedHeight();
		if(lh > 0){
			style += ' line-height:'+lh+'px;';
		}
		return style;
	};
	LeParserFont.prototype.toSpan = function(html){
		return '<span style="'+this.getStyle(0)+'">'+html+'</span>';
	};
	LeParserFont.prototype.setFrameIndex = function(index){
		this.frameIndex = index;
	};
	LeParserFont.prototype.setSize = function(size){
		size = this._round(size);
		if(size != this.size){
			if(this.height > 0){
				this.height = this._round(size / this.size * this.height);
			}
			if(this.renderedHeight > 0){
				this.renderedHeight = this._round(size / this.size * this.renderedHeight);
			}
			this.charheight = this._round(size / this.size * this.charheight);
			this.size = size;
		}
	};
	LeParserFont.prototype.setHeight = function(height){
		this.height = this._round(height);
		this.renderedHeight = 0;
	};
	LeParserFont.prototype.setRenderedHeight = function(height){
		this.renderedHeight = this._round(height);
	};
	LeParserFont.prototype.getSize = function(){
		return this.size;
	};
	LeParserFont.prototype.getLeading = function(){
		return this._round((this.getRenderedHeight() - this.charheight) / 2);
	};
	LeParserFont.prototype.getRenderedHeight = function(){
		// if our line-height is too small (usually all elements generated by pdf2htmlEx have 100% line-height) we calculate one
		if(this.renderedHeight < this.size){
			this.renderedHeight = leParser.getLineHeight(this);
		}
		return this.renderedHeight;
	};
	LeParserFont.prototype._round = function(val){
		return Math.round(val * 10) / 10;
	};
	
	/* *************************************************************************************************** */
	
	// evaluates the rendered font-heights to be able to apply useful line-heights for the single-lined textfields having no useful in pdf2Html (always 100%)
	var LeParserFontMetrics = function(){
		this.byFont = {};		
		this.bySize = {};
		this.addFont = function(font, textLength){
			// should not happen, but if it is tried to add an uncalculated non-multiline font ...
			if(font.renderedHeight < font.size){
				return;
			}
			var key = this._tokenize(font.family);
			if(!this.byFont.hasOwnProperty(key)){
				this.byFont[key] = {};
			}
			this._addToSize(this.byFont[key], font, textLength);
			this._addToSize(this.bySize, font, textLength);
		};
		this.getHeight = function(font){
			var key = this._tokenize(font.family);
			var fKey = (font.frameIndex > -1) ? "f"+font.frameIndex : "none";
			if(this.byFont.hasOwnProperty(key)){
				return this._findHeight(this.byFont[key], font.size, fKey);
			}
			return this._findHeight(this.bySize, font.size, fKey);
		};
		this._addToSize = function(target, font, chars){
			var height = font.getRenderedHeight(), sKey = this._numkey(font.size), hKey = this._numkey(height);
			if(!target.hasOwnProperty(sKey)){
				target[sKey] = { "size": font.size, "heights": {} };
			}
			if(!target[sKey].heights.hasOwnProperty(hKey)){
				target[sKey].heights[hKey] = { "height": height, "chars": chars, frames: {} };
			} else {
				target[sKey].heights[hKey].chars += chars;
			}
			if(font.frameIndex > -1){
				if(("f"+font.frameIndex) in target[sKey].heights[hKey].frames){
					target[sKey].heights[hKey].frames["f"+font.frameIndex]++;
				} else{
					target[sKey].heights[hKey].frames["f"+font.frameIndex] = 1;
				}
			}
		};
		this._findHeight = function(target, size, fKey){			
			var sKey = this._numkey(size);
			if(target.hasOwnProperty(sKey)){
				return this._findHeightInSize(target[sKey].heights, size, fKey);
			}
			sKey = this._findNearestInSizes(target, size);
			if(sKey != null){
				return this._findHeightInSize(target[sKey].heights, size, fKey);
			}
			return this._getDefault(size);
		};
		this._findHeightInSize = function(heights, size, fKey){
			var key, hits = [];
			// find heights with matching frames
			for(key in heights){
				if(heights[key].frames.hasOwnProperty(fKey)){
					hits.push({ "height": heights[key].height, "weight": heights[key].frames[fKey] });
				}
			}
			// if no matching frames found, add height with max. chars
			if(hits.length == 0){
				var hit = null;
				for(key in heights){
					if(hit == null || heights[key].chars > hit.chars){
						hit = { "height": heights[key].height, "chars": heights[key].chars, "weight": 0 };
					}
				}
				hits.push(hit);
			}
			// return hit, if multiple hits hit with the heighest weight (= occurances in frames)
			if(hits.length > 1){
				hits.sort(LeParserSort.byWeight);
			}
			return hits[0].height;
		};
		this._findNearestInSizes = function(target, size){
			var key, nearest = null;
			for(key in target){
				if(nearest == null || Math.abs(target[key].size - size) < nearest.dist){
					nearest = { "key":key, "size": target[key].size, "dist": Math.abs(target[key].size - size) };
				}
			}
			if(nearest == null || nearest.size > (size * leParser.config.fontHeightMatchThresh / 100) || (nearest.size * leParser.config.fontHeightMatchThresh / 100) < size){
				return null;
			}
			return nearest.key;
		};
		this._getDefault = function(size){
			return (size * leParser.config.defaultLineHeight / 100);
		};
		this._tokenize = function(key){
			return escape(key).split('@').join('at').split('.').join('dot').split('/').join('-').split('+').join('-').split('*').join('x').split('%').join('-');
		};
		this._numkey = function(num){
			return "s"+String(num).split('.').join('-');
		};
	};
	

	/* *************************************************************************************************** */

	var LeParserElement = function(top, left, width, height, rotation, scale){
		this.top = top;
		this.right = left + width;
		this.bottom = top + height;
		this.left = left;
		this.width = width;
		this.height = height;
		this.rotation = rotation;
		this.scale = scale;
		this.rendered = false;
		this.empty = false;
		this.seperated = false;
		this.multiline = false;
		this.colspan = 1;
		this.rowspan = 1;
		this.virtual = false;
		this.fixed = false;
		this.joinable = false;
		this.align = "left";
		this.font = null;
		this.html = "";
		this._subs = [];
	};
	LeParserElement.prototype.getScaleX = function(){
		return (this.scale.x > 0) ? this.scale.x : 1;
	};
	LeParserElement.prototype.getScaleY = function(){
		return (this.scale.y > 0) ? this.scale.y : 1;
	};
	LeParserElement.prototype.getUnscaledHeight = function(){
		return (this.height / this.getScaleY());
	};
	LeParserElement.prototype.getFont = function(){
		if(this.font == null){
			this.font = leParser.getDefaultFont();
		}
		return this.font;
	};
	LeParserElement.prototype.getFontStyle = function(forcedLineHeight){
		if(this.font == null){
			return "";
		}
		return this.font.getStyle(forcedLineHeight);
	};
	LeParserElement.prototype.appendFontStyle = function(style, forcedLineHeight){
		var fs = this.getFontStyle(forcedLineHeight);
		if(fs == "" || style == ""){
			return style+fs;
		}
		return style+" "+fs;
	};
	LeParserElement.prototype.getFontSize = function(){
		return this.getFont().size;
	};
	LeParserElement.prototype.setFontSize = function(size){
		return this.getFont().setSize(size);
	};
	LeParserElement.prototype.getLineHeight = function(){
		return this.getFont().height;
	};
	LeParserElement.prototype.setRenderedLineHeight = function(height){
		return this.getFont().setRenderedHeight(height);
	};
	LeParserElement.prototype.getRenderedHeight = function(){
		return Math.ceil(((this._getBottommost().bottom - this.top) / this.scale.y) + (2 * this.getFont().getLeading()));
	};
	LeParserElement.prototype.getTextClasses = function(){
		var classes = leParser.namespace+'-txt';
		if(this.multiline){
			classes += ' '+leParser.namespace+'-multiline';
		}
		return classes;
	};
	LeParserElement.prototype.appendTextClasses = function(classes){
		var tc = this.getTextClasses();
		if(tc == "" || classes == ""){
			return classes+tc;
		}
		return classes+" "+tc;
	};
	LeParserElement.prototype.cloneForSub = function(left, width){
		var ele = new LeParserElement(this.top, left, width, this.height, this.rotation, {"x":this.scale.x,"y":this.scale.y});
		ele. multiline= this.multiline;
		ele.align = this.align;
		ele.font = (this.font == null) ? null : this.font.clone();
		ele.virtual = true;
		return ele;
	};
	LeParserElement.prototype.isInLine = function(element){
		return(this.bottom < (element.bottom + leParser.config.linePosThresh) && this.bottom > (element.bottom - leParser.config.linePosThresh) && this.top < (element.top + leParser.config.linePosThresh) && this.top > (element.top - leParser.config.linePosThresh));
	};
	LeParserElement.prototype.isFontEqual = function(element){
		return(this.font != null && element.font != null && this.font.isEqual(element.font));
	};
	// we may introduce a threshhold here if the pdf2htmlEx fontsize calculation has inconsistencies
	LeParserElement.prototype.isFontSizeEqual = function(element){
		return(this.getFontSize() == element.getFontSize());
	};
	LeParserElement.prototype.isDirectlyLeftOf = function(element){
		return (this.right > element.left - leParser.config.subSuperJoinThresh && this.left < element.left);
	};
	LeParserElement.prototype.isDirectlyRightOf = function(element){
		return (this.left < element.right + leParser.config.subSuperJoinThresh && this.right > element.right);
	};
	LeParserElement.prototype.appendHorizontally = function(element, withoutWhitespace){
		if(!withoutWhitespace && this.html.length > 0 && this.html.charAt(this.html.length - 1) != " " && element.html.length > 0 && element.html.charAt(0) != " "){
			this.html += " ";
		}
		if(this.isFontEqual(element)){
			this.html += element.html;
		} else {
			this.html += element.getFontStyledHtml();
		}
		this.top = Math.min(this.top, element.top);
		this.bottom = Math.max(this.bottom, element.bottom);
		this.right = element.right;
		this.width = this.right - this.left;
		this.height = this.bottom - this.top;
		element.rendered = false;
	};
	// Checks, if an Element is a sup-or superscript relative to the passed element
	LeParserElement.prototype.isSubOrSuper = function(element){
		// font too big
		if(this.getFontSize() == 0 || this.getFontSize() > (element.getFontSize() * leParser.config.subSuperFontSizeMax)){
			return false;
		}
		// no vertical overlap
		if(this.top > element.bottom || this.bottom < element.top){
			return false;
		}
		return true;
	};
	// The element is added to the passed one as sub- or superscript
	LeParserElement.prototype.addAsSubOrSuper = function(element){
		if(this.isDirectlyRightOf(element)){
			element.html += this._renderAsSubOrSuperTag(element);
			element.right = this.right;
			element.width = element.right - element.left;
			this.rendered = false;
		} else if(this.isDirectlyLeftOf(element)){
			element.html = this._renderAsSubOrSuperTag(element) + element.html;
			element.left = this.left;
			element.width = element.right - element.left;
			this.rendered = false;
		}
	};
	LeParserElement.prototype.turnSubOrSuperToNormal = function(element){
		this.html = this._renderAsSubOrSuperTag(element);
		this.top = element.top;
		this.bottom = element.bottom;
		this.height = element.height;
		this.font = element.getFont().clone();
	};
	LeParserElement.prototype.isVerticallyAligned = function(element){
		if(this.align != element.align){
			return false;
		}
		if((this.align == "left" && this._ltab == element._ltab) || (this.align == "right" && this._rtab == element._rtab) || (this.align == "center" && this._ctab == element._ctab)){
			return true;
		}
		return false;
	};
	LeParserElement.prototype.isMultilineCapable = function(element, scaleY){
		if(this.isVerticallyAligned(element) && this.isFontSizeEqual(element)){
			// comparing the max-multiline height
			return (Math.abs(element._line - this._line) < ((this.getFontSize() * scaleY) * leParser.config.maxMultiLineHeight / 100));
		}
		return false;
	};
	LeParserElement.prototype.getMinLeft = function(){
		if(this.align == "left"){
			return this._ltab;
		}
		if(this.align == "right"){
			return this._minltab;
		}
		return (this._ltab - this._getMaxCenteredDist());
	};
	LeParserElement.prototype.getMaxRight = function(){
		if(this.align == "right"){
			return this._rtab;
		}
		if(this.align == "left"){
			return this._maxrtab;
		}
		return (this._rtab + this._getMaxCenteredDist());
	};
	// forces the adjustment of our tabs and adjust the neighbours accordingly
	LeParserElement.prototype.adjustTabs = function(left, right){
		this._setLeftTab(left);
		this._setRightTab(right);
	};
	// tries to adjust the tabs if possible
	LeParserElement.prototype.increaseTabs = function(left, right){
		if(left > -1 && left >= this._getPrevRight()){
			this._setLeftTab(left);
		}
		if(right > -1 && right <= this._getNextLeft()){
			this._setRightTab(right);
		}
	};
	// forces the setting of the tabs. For multiline-elements, will always be called on the holding element ...
	// API is only for error-corrections. Sets the tabs but for multiline-elements always on all subelements of the multiline element
	LeParserElement.prototype.setTabs = function(left, right){
		if(this._above != null && up){
			this._above.setTabs(left, right);
		} else {
			this._setRightTabVal(left);
			this._setLeftTabVal(right);
		}
	};
	// renders our html wrapped in a span with our font
	LeParserElement.prototype.getFontStyledHtml = function(){
		if(this.font == null){
			return this.html;
		}
		return this.font.toSpan(this.html);
	};
	// appends an element in the process of column-building
	LeParserElement.prototype.appendVertically = function(element, forceBreak){
		if(leParser.config.joinMultilineWithBr || forceBreak){
			if(this.html.length > 0 && element.html.substr(0,2) != "<p" && element.html.substr(0,4) != "<div"){
				this.html += "<br/>";
			}
		} else {
			if(this.html.length > 0 && this.html.substr(-1,1) != " "&& element.html.substr(0,1) != " "){
				this.html += " ";
			}
		}
		if(this.isFontEqual(element)){
			this.html += element.html;
		} else {
			this.html += element.getFontStyledHtml();
		}
		this.bottom = element.bottom;
		this.left = Math.min(this.left, element.left);
		this.right = Math.max(this.right, element.right);
		this.width = this.right - this.left;
		this.height = this.bottom - this.top;
		this.joinable = false;
		this.rowspan += element.rowspan;
		element._ltab = this._ltab;
		element._rtab = this._rtab;
		element.joinable = false;
		element.rendered = false;
		return true;
	};
	LeParserElement.prototype.render = function(asTable, styles, classes){
		styles = this.appendFontStyle(styles, 0);
		classes = this.appendTextClasses(classes);
		var attribs = (leParser.config.devMode) ? ' contenteditable="true"' : '';
		if(this.getFont().getLeading() > 0){
			styles += ' position:relative; top:-'+this.getFont().getLeading()+'px;';
		}
		if(this.align == "right" || this.align == "center"){
			styles += ' text-align:'+this.align+';';
		}
		return '<div style="'+styles+'" class="'+classes+'"'+attribs+'>'+this.html+'</div>';
	};
	LeParserElement.prototype._renderAsSubOrSuperTag = function(element){
		if(this.top >= element.top){
			// sub-text mostly look correct as indices and it seems not feasible to calculate that behaviour her ...
			return '<sub style="'+this.appendFontStyle("", 100)+'">' + $.trim(this.html) + '</sub>';
		}
		// sup-tags stick to the upper baseline while in printed text they are usually higher. we have to respect that ...
		var style = 'position:relative; bottom:' + String(element.bottom - this.bottom) + 'px;';
		return '<sup style="'+this.appendFontStyle(style, 100)+'">' + $.trim(this.html) + '</sup>';
	};
	LeParserElement.prototype._getMaxCenteredDist = function(){
		return Math.min((this._ltab - this._minltab), (this._maxrtab - this._rtab));
	};
	LeParserElement.prototype._setLeftTab = function(left){
		this._setLeftTabVal(left);
		if(this._prev != null){
			this._prev._maxrtab = left;
		}
	};
	LeParserElement.prototype._setRightTab = function(right){
		this._setRightTabVal(right);
		if(this._next != null){
			this._next._minltab = right;
		}
	};
	LeParserElement.prototype._setLeftTabVal = function(left){
		this._ltab = left;
		if(this._below != null){
			this._below._setLeftTabVal(left);
		}
	};
	LeParserElement.prototype._setRightTabVal = function(right){
		this._rtab = right;
		if(this._below != null){
			this._below._setRightTabVal(right);
		}
	};
	LeParserElement.prototype._getNextLeft = function(){
		var nextLeft = (this._next && this._next != null) ? this._next._ltab : leParser.maxX;
		if(this._below && this._below != null){
			return Math.min(nextLeft, this._below._getNextLeft());
		}
		return nextLeft;
	};
	LeParserElement.prototype._getPrevRight = function(){
		var prevRight = (this._prev && this._prev != null) ? this._prev._rtab : 0;
		if(this._below && this._below != null){
			return Math.max(prevRight, this._below._getPrevRight());
		}
		return prevRight;
	};
	LeParserElement.prototype._getBottommost = function(){
		if(!this._below || this._below == null){
			return this;
		}
		return this._below._getBottommost();
	};
	
	
	/* *************************************************************************************************** */

	var LeParserTab = function(pos, num){
		this.pos = pos;
		this.num = num;
	};

	/* *************************************************************************************************** */

	// represents an empty column in the rendering process (whitespace)
	var LeParserEmptyCol = function(ltab, rtab, top, bottom, colspan){
		this._ltab = this.left = ltab;
		this._rtab = this.right = rtab;
		this.top = top;
		this.bottom = bottom;
		this.height = this.bottom - this.top;
		this.width = this.right - this.left;
		this._above = this._below = null;
		this.colspan = colspan;
		this.rowspan = 1;
		this.rendered = true;
		this.empty = true;
	};
	LeParserEmptyCol.prototype.render = function(asTable, styles, attribs){
		return "";
	};

	/* *************************************************************************************************** */
	
	var LeParserRow = function(pos, num){
		this.pos = this.bottom = pos;
		this.top = 0;
		this.num = num;
		this.elements = [];
		this.lineHeight = 0;
		this.cols = [];
	};
	LeParserRow.prototype.addElement = function(ele){
		if(this.lineHeight < ele.getLineHeight()){
			this.lineHeight = ele.getLineHeight();
		}
		if(this.top == 0 || this.top > ele.top){
			this.top = ele.top;
		}
		this.elements.push(ele);
	};
	// defining the siblings of our elements and their max. tab boundries
	LeParserRow.prototype.build = function(frameLeft, frameRight){
		this.left = frameLeft;
		this.right = frameRight;
		if(this.elements.length == 0){
			return;
		}
		this.elements.sort(LeParserSort.byLeft);
		for(var i=0; i < this.elements.length; i++){
			var e = this.elements[i];
			e._prev = (i > 0) ? this.elements[i - 1] : null;
			e._next = (i < (this.elements.length - 1)) ? this.elements[i + 1] : null;
			e._above = e._below = null;
			e._minltab = (e._prev == null) ? this.left : e._prev._rtab;
			e._maxrtab = (e._next == null) ? this.right : e._next._ltab;
			// catching overlapping elements ...
			if(e._minltab > e._ltab){
				// if there is enough room we displace the element to the right
				if((e._minltab + e.width) <= frameRight){
					e._ltab = e.left = e._minltab;
					e._rtab = e.right = e._minltab + e.width;
					e.align = "left";
					e._maxrtab = Math.max(e._rtab, e._maxrtab);
				// we simply do not render elements that have no space to be shown, this is a rendering-bug in html2pdfEx as well ... we treat them as rendered as a layer
				} else {
					leParser.logError("Found overlapping element which will not be rendered: "+e.id);
					e.fixed = true;
					e.rendered = false;
					e.align = "left";
					e._ltab = Math.max(e._minltab, e._ltab);
					e._rtab = Math.min(e._maxrtab, e._rtab);
				}
			}
		}
	};
	LeParserRow.prototype.merge = function(line){
		for(var i=0; i < line.elements.length; i++){
			line.elements[i]._line = this.bottom;
			this.addElement(line.elements[i]);
		}
		this.build(Math.min(this.left, line.left), Math.max(this.right, line.right));
		line.lineHeight = 0;
		line.elements = [];
	};
	// finds the empty columns to cover the existing whitespace and evaluates the colspans for our columns
	// absolutely neccessary for this is, that all rendered elemens are expanded to a tab !
	LeParserRow.prototype.findColumns = function(tabs, indices, frame){
		var e, li, ri, last = 0;
		for(var i=0; i < this.elements.length; i++){
			e = this.elements[i];
			if(e.rendered || e._above != null){
				// must not happen normally, just to force a working rendering on errors
				if(!indices.hasOwnProperty("p"+e._ltab) || !indices.hasOwnProperty("p"+e._rtab)){
					frame.setTabBoundries(e);
				}
				li = indices["p"+e._ltab];
				ri = indices["p"+e._rtab];
				e.colspan = ri - li;
				if(last < li){
					this.cols.push(new LeParserEmptyCol(tabs[last], tabs[li], this.top, this.bottom, (li - last)));
				}
				if(e.rendered){
					this.cols.push(e);
				}
				// DEBUG: console.log(e.id+": index: "+(this.cols.length - 1)+" indices: ("+li+"/"+ri+") tabs: ("+e._ltab+"/"+e._rtab+") alltabs: ["+tabs.join(',')+"]");
				last = ri;
			}
		}
		if(last < (tabs.length - 1)){
			this.cols.push(new LeParserEmptyCol(tabs[last], tabs[tabs.length - 1], this.top, this.bottom, (tabs.length - 1 - last)));
		}
	};
	LeParserRow.prototype.findSections = function(frame){
		for(var i=0; i < this.elements.length; i++){
			if(this.elements[i].rendered){
				this.cols.push(this.elements[i]);
			}
		}
		return (this.cols.length > 0);
	};
	// we try to compensate for summing rounding-errors due to re-fractionalize already rounded values by always taking the overall-height of the element into account
	LeParserRow.prototype.evaluateRenderedHeight = function(frame, lines, index, numLines){
		if(index == 0){
			LeParserRow._currentHeight = 0;
		}
		this._renderedHeight = (index == (numLines - 1)) ?
			Math.floor((this.bottom - this.top) / frame.scaleY)
			: Math.floor((lines[index + 1].top / frame.scaleY) - frame.getRenderedTop() - LeParserRow._currentHeight);
			// uncompensated code:
			// : Math.floor((lines[index + 1].top - this.top) / frame.scaleY);
		this._relativeTop = LeParserRow._currentHeight;
		LeParserRow._currentHeight += this._renderedHeight;
		return this._renderedHeight;
	};
	LeParserRow.prototype.render = function(frame, index, numLines){
		if(frame.isTable){
			return this._renderAsTable(frame, index, numLines);
		}
		return this._renderAsSequence(frame, index, numLines);
	};
	LeParserRow.prototype._renderAsSequence = function(frame, index, numLines){
		var i, ele, pad, style, attribs, html = '';
		for(i = 0; i < this.cols.length; i++){
			ele = this.cols[i];
			if(this._renderedHeight > 0){
				// we add an padding but only half the bottom-padding to somehow keep the layout consistent (if configured)
				pad = (leParser.config.renderSequenceBotPads) ? Math.floor((this._renderedHeight - ele.getRenderedHeight()) / 100 * leParser.config.renderedCellPadShrink) : 0;
				style = (pad > 1 && index < (numLines - 1)) ? 'min-height:'+(this._renderedHeight - pad)+'px; padding-bottom:'+pad+'px;' : 'min-height:'+this._renderedHeight+'px;';
				attribs = (leParser.config.devMode) ? ' title="rendered height:'+this._renderedHeight+'px (scaled to '+(this._renderedHeight * frame.scaleY)+' px)"' : '';
				html += '<div style="'+style+'"'+attribs+'>';
			} else {
				attribs = (leParser.config.devMode) ? ' title="no rendered height could be evaluated"' : '';
				html += '<div'+attribs+'>';
			}
			html += ele.render(false, "", "");
			html += '</div>';
		}
		return html;
	};
	LeParserRow.prototype._renderAsTable = function(frame, index, numLines){
		var i, col, attribs, style, height, html = '';
		// we add an padding that is the min bottom bpadding of all elements in the row (multiline-fields count to the row they are rendered in) if configured
		var pad = (leParser.config.renderTableBotPads) ? this._getMinRenderedPad(frame, index) : 0;
		for(i = 0; i < this.cols.length; i++){
			col = this.cols[i];
			attribs = '';
			if(col.colspan > 1){
				attribs += (' colspan="'+col.colspan+'"');
			}
			if(col.rowspan > 1){
				attribs += (' rowspan="'+col.rowspan+'"');
			}
			style = (pad > 0) ? 'margin-bottom:'+pad+'px;' : '';
			html += ('<td'+attribs+'>'+col.render(true, style, "")+'</td>');
		}
		attribs = (this._renderedHeight > 0) ? ' style="height:'+this._renderedHeight+'px;"' : '';
		if(leParser.config.devMode) {
			attribs += ' title="rendered height:'+this._renderedHeight+'px (scaled to '+(this._renderedHeight * frame.scaleY)+' px)"';
		}
		return '<tr'+attribs+'>'+html+'</tr>';
	};
	LeParserRow.prototype._getMinRenderedPad = function(frame, index){
		var i, p, pad = -1;
		for(i = 0; i < this.cols.length; i++){
			if(!this.cols[i].empty){
				p = this._getRenderedElementPad(this.cols[i], frame, index);
				if(pad == -1 || p < pad){
					pad = p;
				}
			}
		}
		return (pad < 1) ? 0 : Math.floor(pad / 100 * leParser.config.renderedCellPadShrink);
	};
	LeParserRow.prototype._getRenderedElementPad = function(element, frame, rowIndex){
		if(!element.rendered || element.empty){
			return 0;
		}
		var h = element.getRenderedHeight();
		var rh = (element.rowspan > 1) ? (frame.getRelativeBottom(rowIndex + element.rowspan - 1) - this._relativeTop) : this._renderedHeight;
		return ((rh - h) > 1) ? (rh - h) : 0;
	};
	

	/* *************************************************************************************************** */

	var LeParserFrame = function(page, index){
		this.index = index;
		this.pageId = page.id;
		this.left = -1;
		this.top = -1;
		this.right = 0;
		this.bottom = 0;
		this.width = -1;
		this.height = -1;
		this.scaleX = 1;
		this.scaleY = 1;
		this.transform = "";
		this.fixed = false;
		this.rotated = false;
		this.rotation = 0;
		this.elements = [];
		this.rows = [];		
		this.tabs = [];
		this.isTable = true;
		this.jDomNode = null;
		// temporarily stores used in the build-phase to avoid excessive memory use
		this._nodes = {};
		this._lines = {};
		this._ctabs = {};
		this._ltabs = {};
		this._rtabs = {};
		this._tabs = {};
		this._fsizes = {};
	};
	LeParserFrame.prototype.getRenderedHeight = function(){
		return Math.ceil((this.bottom - this.top) / this.scaleY);
	};
	LeParserFrame.prototype.getRenderedTop = function(){
		return Math.floor(this.top / this.scaleY);
	};
	LeParserFrame.prototype.addElement = function(jNode, element){
		// add first element, inherit it's properties
		if(this.elements.length == 0){
			this.top = element.top;
			this.right = element.right;
			this.bottom = element.bottom;
			this.left = element.left;
			this.width = element.width;
			this.height = element.height;
			// DIRTY: the first Element sets the frames transform. this is obviously dependant on pdf2html logic
			this.transform = jNode.css("transform"); // retrieves smth. like matrix(0.25, 0, 0, 0.25, 0, 0)
			this.scaleX = element.getScaleX();
			this.scaleY = element.getScaleY();
		} else {
			if(this.top > element.top){
				this.top = element.top;
			}
			if(this.right < element.right){
				this.right = element.right;
			}
			if(this.bottom < element.bottom){
				this.bottom = element.bottom;
			}
			if(this.left > element.left){
				this.left = element.left;
			}
			this.width = this.right - this.left;
			this.height = this.bottom - this.top;
		}
		var lastElement = this.getLastRenderedElement();
		if(lastElement != null){
			// detection of sub-or-superscript, re-joining the parts
			// this depends on pdf2htmlEx rendering the sub/superscript elements directly after the text they belong to
			if(lastElement.isSubOrSuper(element) && !lastElement.joinable){
				// the element before is a sub/superscript
				this.removeElementProps(lastElement);
				if(lastElement.isDirectlyLeftOf(element)){
					lastElement.addAsSubOrSuper(element);
					element.joinable = true;
				} else {
					lastElement.turnSubOrSuperToNormal(element);
					this.addElementProps(lastElement);
				}
			} else if(element.isSubOrSuper(lastElement)){
				// the new element is a sub/superscript
				if(element.isDirectlyRightOf(lastElement)){
					this.removeElementProps(lastElement);
					element.addAsSubOrSuper(lastElement);
					lastElement.joinable = true;
					this.addElementProps(lastElement);
				} else {
					element.turnSubOrSuperToNormal(lastElement);
				}
			} else if(lastElement.joinable && element.isInLine(lastElement) && element.isFontSizeEqual(lastElement) && element.isDirectlyRightOf(lastElement)) {
				// the last element was a joined element and we now might can append the rest of the string
				this.removeElementProps(lastElement);
				lastElement.appendHorizontally(element, true);
				this.addElementProps(lastElement);
			}
		}
		this.elements.push(element);			
		if(!element.virtual){
			this._nodes[element.id] = jNode;
		}
		this.addElementProps(element);
		// bind the elements font to the frame. font-metrics are expected to be consistent within a frame
		element.getFont().setFrameIndex(this.index);
	};
	LeParserFrame.prototype.addElementProps = function(element){
		// add rounded element position to lines / tabs
		if(element.rendered){
			var roundedPos = Math.round(element.bottom * leParser.config.linePrecision) / leParser.config.linePrecision;
			element._line = this._addTo(roundedPos, "_lines", leParser.config.linePosThresh);
			roundedPos = Math.round(element.left * leParser.config.tabPrecision) / leParser.config.tabPrecision;
			element._ltab = this._addTo(roundedPos, "_ltabs", leParser.config.tabPosThresh);
			roundedPos = Math.round(element.right * leParser.config.tabPrecision) / leParser.config.tabPrecision;
			element._rtab = this._addTo(roundedPos, "_rtabs", leParser.config.tabPosThresh);
			// "center tab" to detect text-allign center elements
			roundedPos = Math.round(((element.right + element.left) / 2) * leParser.config.tabPrecision) / leParser.config.tabPrecision;
			element._ctab = this._addTo(roundedPos, "_ctabs", leParser.config.tabPosThresh);
		}
	};
	// can only be used to remove an element that already has been added !
	LeParserFrame.prototype.removeElementProps = function(element){
		if(element.hasOwnProperty("_line")){
			this._removeFrom(element._line, "_lines");
		}
		if(element.hasOwnProperty("_ltab")){
			this._removeFrom(element._ltab, "_ltabs");
		}
		if(element.hasOwnProperty("_rtab")){
			this._removeFrom(element._rtab, "_rtabs");
		}
		if(element.hasOwnProperty("_ctab")){
			this._removeFrom(element._ctab, "_ctabs");
		}
	};
	LeParserFrame.prototype.getLastRenderedElement = function(){
		if(this.elements.length == 0){
			return null;
		}
		for(var i = (this.elements.length - 1); i >= 0; i--){
			if(this.elements[i].rendered){
				return this.elements[i];
			}
		}
		return null;
	};
	// retrieves the number of times an element was on the provided line
	LeParserFrame.prototype.isInLines = function(bottom){
		return this._isIn(bottom, "_lines", leParser.config.linePosThresh);
	};
	// retrieves the number of times an element was on the provided left tab
	LeParserFrame.prototype.isInLeftTabs = function(left){
		return this._isIn(left, "_ltabs", leParser.config.tabPosThresh);
	};
	// retrieves the number of times an element was on the provided right tab
	LeParserFrame.prototype.isInRightTabs = function(right){
		return this._isIn(right, "_rtabs", leParser.config.tabPosThresh);
	};
	// the central function to generate the text-flow layout.
	// the idea is to generate a table holding all frame elements
	// problems are the detection of overlapping rows or columns, what is either by variations in positions or even "wrong" layout generated by pdf2htmlEx
	LeParserFrame.prototype.build = function(jPage, pageWidth){
		if(this.jDomNode != null){
			return;
		}
		var e, ea, el, es, i, j, k, l, la, r, s, t;
		// create our ID
		this.id = leParser.createFrameId();
		
		// round our values to match the general precision
		this.top = Math.floor(this.top * leParser.config.linePrecision) / leParser.config.linePrecision;
		this.bottom = Math.ceil(this.bottom * leParser.config.linePrecision) / leParser.config.linePrecision;
		this.left = Math.floor(this.left * leParser.config.tabPrecision) / leParser.config.tabPrecision;
		this.right = Math.ceil(this.right * leParser.config.tabPrecision) / leParser.config.tabPrecision;
		this.width = this.right - this.left;
		this.height = this.bottom - this.top;
		
		// evaluate the alignment for each element based on the tab-frequency, fill the per-line model
		for(i=0; i < this.elements.length; i++){
			e = this.elements[i];
			if(e.rendered){
				// evaluating the align by comparing the frequency of the tabs of all our elements
				t = Math.max(this._ltabs["p"+e._ltab].num, this._rtabs["p"+e._rtab].num, this._ctabs["p"+e._ctab].num);
				if(this._ltabs["p"+e._ltab].num == t){
					e.align = "left";
				} else if(this._rtabs["p"+e._rtab].num == t) {
					e.align = "right";
				} else {
					e.align = "center";
				}
				this._lines["p" + e._line].addElement(e);
			}
		}
		// generate sorted "real" array of lines with the lines built (elements sorted by their left position, fill element siblings)
		l = [];
		for(i in this._lines){
			l.push(this._lines[i]);
		}
		this._lines = l;		
		this._lines.sort(LeParserSort.byPos);
		for(i=0; i < this._lines.length; i++){
			this._lines[i].build(this.left, this.right);
		}
		// eliminate lines, that do overlap (closer together than a line-height) and merge them.
		// NOTE:
		// we may have two-column-layouts with multiline-text in each column within a table with different line-height we now bring to the higher line-height. 
		// This will be no problem here, since multiline-boxes will be rendered with their "real" lineheight. Only the whole multiline element line may be displaced if the first lines are not on the same baseline
		if(this._lines.length > 1){
			r = false;
			for(i=1; i < this._lines.length; i++){
				l = this._lines[i];
				la = this._lines[i - 1];
				if((l.bottom - la.bottom) < Math.floor(l.lineHeight * this.scaleY)){
					// check if line after has a smaller distance and take it for a merge instead
					if(((i + 1) < this._lines.length) && ((this._lines[i + 1].bottom - l.bottom) < (l.bottom - la.bottom))){
						this._lines[i + 1].merge(l);
					} else {
						la.merge(l);
					}
					r = true;
				}				
			}
			// if we merged lines we remove the now empty ones to not have to check for lines not being rendered
			if(r){
				l = [];
				for(i=0; i < this._lines.length; i++){
					if(this._lines[i].elements.length > 0)
						l.push(this._lines[i]);
				}
				this._lines = l;
			}
		}
		// find verticaly aligned & spanned elements:
		// we search the complete frame for vertically aligned matches and decide for a complete column of elements if they are joined afterwards
		// multiline-textfields are rendered immediately after each other by pdf2htmlEx and this information is crucial for our algorithm
		if(this._lines.length > 1 && this.elements.length > 1){
			ea = el = null;
			es = [];
			for(i=0; i < this.elements.length; i++){
				e = this.elements[i];
				if(e.rendered){
					if(ea != null && e.isMultilineCapable(el, this.scaleY)){
						es.push(e);
					} else {
						if(ea != null && es.length > 0){
							this.buildMultilineElements(ea, es);
							es = [];
						}
						ea = e;
					}
					el = e;
				}
			}
			if(ea != null && es.length > 0){
				this.buildMultilineElements(ea, es);
			}
		}
		// eliminate orphan centered textboxes, expand centered textboxes that are left or right to their max width
		this._tabs = {};
		if(this.elements.length > 1){
			// cluster the centered elements by their tab
			for(i=0; i < this.elements.length; i++){
				e = this.elements[i];
				if(e.rendered && e.align == "center"){
					k = "p"+e._ctab;
					if(!this._tabs.hasOwnProperty(k)){
						this._tabs[k] = [];
					}
					this._tabs[k].push(e);
				}
			}
			// then harmonize them
			for(k in this._tabs){
				this.harmonizeAlignedColumns("center", this._tabs[k], this.left, this.right);
			}
		}
		
		// eliminate orphan rightaligned textboxes, expand rightaligned textboxes to the left
		this._tabs = {};
		if(this.elements.length > 1){
			// cluster the centered elements by their tab
			for(i=0; i < this.elements.length; i++){
				e = this.elements[i];
				if(e.rendered && e.align == "right"){
					k = "p"+e._rtab;
					if(!this._tabs.hasOwnProperty(k)){
						this._tabs[k] = [];
					}
					this._tabs[k].push(e);
				}
			}
			// then harmonize them
			for(k in this._tabs){
				this.harmonizeAlignedColumns("right", this._tabs[k], this.left, this.right);
			}
		}
		
		// re-evaluate our tabs
		this._tabs = [];
		this.tabs = [];
		for(i=0; i < this.elements.length; i++){
			e = this.elements[i];
			if(e.rendered){
				this._addTo(e._ltab, "_tabs", 0);
				if(e.align != "left"){
					this._addTo(e._rtab, "_tabs", 0);
				}
			}
		}
		if(!this._isIn(this.left, "_tabs", leParser.config.tabPrecision)){
			this.tabs.push(this.left);
		}
		for(i in this._tabs){
			this.tabs.push(this._tabs[i].pos);
		}
		if(!this._isIn(this.right, "_tabs", leParser.config.tabPrecision)){
			this.tabs.push(this.right);
		}
		this.tabs.sort(LeParserSort.numerical);
		if(this.left != this.tabs[0]){
			this.left = this.tabs[0];
			this.width = this.right - this.left;
		}
		if(this.right != this.tabs[this.tabs.length - 1]){
			this.right = this.tabs[this.tabs.length - 1];
			this.width = this.right - this.left;
		}
		this.isTable = (this.tabs.length > 2);
		
		// expand left-aligned items to harmonize the layout further
		if(this.isTable ){
			for(i=0; i < this.elements.length; i++){
				e = this.elements[i];
				if(e.rendered){
					if(e.align == "right"){
						e.increaseTabs(this._findNearestLeftTab((e._rtab - e.width), false), -1);
					} else if(e.align == "left"){
						e.increaseTabs(-1, this._findNearestRightTab((e._ltab + e.width), true));
					}
				}
			}
		} else {
			for(i=0; i < this.elements.length; i++){
				if(this.elements[i].align == "left"){
					this.elements[i].increaseTabs(-1, this.right);
				}
			}
		}
		// if we will render a real html-table oure lines (=rows) will need to find & render empty rows to fill the whitespace and now the rowspan & colspan of each row ...
		if(this.isTable){
			// helper-structure to more efficently find tab-indexes
			this._tabs = {};
			for(i=0; i < this.tabs.length; i++){
				this._tabs["p"+this.tabs[i]] = i;
			}
			for(i=0; i < this._lines.length; i++){
				this._lines[i].findColumns(this.tabs, this._tabs, this);
			}
		} else {
			// for sequences / tables with just one column, we just need the rendered lines
			l = [];
			for(i=0; i < this._lines.length; i++){
				if(this._lines[i].findSections(this)){
					l.push(this._lines[i]);
				}
			}
			delete this._lines;
			this._lines = l;
		}
		// explicitly delete in-between models to trigger the garbage-collection
		delete  this._ctabs;
		delete  this._ltabs;
		delete  this._rtabs;
		delete  this._tabs;
		delete  this._fsizes;
		
		// renders our contents
		this.render(jPage, pageWidth);
	};
	// checks if the passed elements really can be merged to a textfield and evaluates the smallest bounding-box for the merged field
	// this process aditionally takes the differnce between the lineheights of the building line-elements into account (threshold -> maxMultiLineThresh)
	LeParserFrame.prototype.buildMultilineElements = function(last, elements){
		var merged = [last], next,
			minLeft = last.getMinLeft(), left = last._ltab, right = last._rtab, maxRight = last.getMaxRight(), 
			ml, mr, lh, lht, lastLh = 0;
		for(var i=0; i < elements.length; i++){
			next = elements[i];
			ml = next.getMinLeft();
			mr = next.getMaxRight();
			lh = next._line - last._line;
			lht = (lastLh == 0) ? 0 : (Math.abs(lh - lastLh) * 100 / lastLh);
			if(next._ltab >= minLeft && ml <= minLeft && next._rtab <= maxRight && mr >= maxRight && lht <= leParser.config.maxMultiLineThresh){
				merged.push(next);
				minLeft = Math.max(minLeft, ml);
				maxRight = Math.min(maxRight, mr);
				left = Math.min(left, next._ltab);
				right = Math.max(right, next._rtab);
				lastLh = lh;
			} else {
				if(merged.length > 1){
					this.createRowspan(merged, left, right);
				}
				merged = [];
				merged.push(next);
				minLeft = next.getMinLeft();
				left = next._ltab;
				right = next._rtab;
				maxRight = next.getMaxRight();
				lastLh = 0;
			}
			last = next;
		}
		if(merged.length > 1){
			this.createRowspan(merged, left, right);
		}
	};
	// really creates a multiline field out of the given line-elements
	LeParserFrame.prototype.createRowspan = function(elements, left, right){
		var nlThresh, maxW = elements[0].width, lh = Math.floor((elements[elements.length - 1].bottom - elements[0].bottom) / (elements.length - 1) / this.scaleY);
		elements[0].setRenderedLineHeight(lh);
		elements[0].multiline = true;
		elements[0].adjustTabs(left, right);
		elements[0]._below = elements[1];
		for(var i=1; i < elements.length; i++){
			if(elements[i].width > maxW){
				maxW = elements[i].width;
			}
		}
		nlThresh = maxW / 100 * leParser.config.multilineBreakThresh;
		for(var i=1; i < elements.length; i++){
			elements[i].adjustTabs(left, right);
			elements[i]._above = elements[i - 1];
			if(i < (elements.length - 1)){
				elements[i]._below = elements[i + 1];
			}
			elements[0].appendVertically(elements[i], (elements[i].width < nlThresh));
		}
		leParser.addMultilineFont(elements[0].getFont(), elements[0].html);
	};
	// tries to set shared tabs for centered elements in line
	LeParserFrame.prototype.harmonizeAlignedColumns = function(direction, elements, left, right){
		// just one element will be turned to left-align instead
		if(elements.length == 1){
			elements[0].align = "left";
			return;
		}
		this._harmonizeAligned(direction, elements, left, right);
	};
	// can only be used in the rendering-phase
	LeParserFrame.prototype.getRelativeBottom = function(index){
		if(index > this._lines.length - 1){
			// must not happen
			leParser.logError("Frame "+this.id+" LeParserFrame.prototype.getRelativeBottom: index out of bounds ("+index+")");
			return 0;
		}
		return this._lines[index]._relativeTop + this._lines[index]._renderedHeight;
	};
	// renders our evaluated layout
	LeParserFrame.prototype.render = function(jPage, pageWidth){
		var e, ea, i, j, l, t, w, rtabs = [];
		// to oevercome problems with unwanted line-breaks, we increase the size of the tabs according to a config-option and reverse proportionsl to their size (small tabs will grow more ...)
		if(this.tabs.length > 2){
			rtabs.push(this.tabs[0]);
			for(i=1; i < this.tabs.length; i++){
				w = this.tabs[i] - this.tabs[i - 1];
				t = ((leParser.config.renderedHorGrowth / 100) * w) - w; // growth of tab according to rendering-growth
				t = Math.pow(((pageWidth - w) / pageWidth), 3) * t; // fractional growth, smaller elements relative to the page grow more ...
				rtabs.push(rtabs[rtabs.length - 1] + Math.ceil(w + t));
			}
		} else {
			rtabs.push(this.tabs[0]);
			rtabs.push(Math.ceil(this.tabs[1] * (leParser.config.renderedHorGrowth / 100)));
		}
		this.renderedWidth = rtabs[rtabs.length - 1] - rtabs[0];
		// the next line will render the grown frame centered above the original one. currently, that produces more quirks tnan it solves ...
		// this.renderedLeft = Math.floor(this.left - ((this.renderedWidth - this.width) / 2));
		this.renderedLeft = this.left;
		
		// prepare the rows for rendering (neccessary to calculate proper cell-heights and a too big or too small layout due to accumulated rounding-errors)
		l = this._lines.length;
		this.renderedHeight = 0;
		for(i=0; i < l; i++){
			this.renderedHeight += this._lines[i].evaluateRenderedHeight(this, this._lines, i, l);			
		}
		// hide the original elements
		for(i in this._nodes){
			if(leParser.config.devMode){
				this._nodes[i].addClass(leParser.namespace+'-orig').hide();
			} else {
				this._nodes[i].remove();
			}
		}
		// render the table or sequence rows
		var html = '';
		for(i=0; i < l; i++){
			html += this._lines[i].render(this, i, l);			
		}
		// dev-info to check rendered layout
		t = (leParser.config.devMode) ? ' title="rendered height: '+this.renderedHeight+'px (scaled to '+(this.renderedHeight * this.scaleY)+' px)"' : '';
		if(this.isTable){
			var cols = '<colgroup>';
			for(i=1; i < rtabs.length; i++){
				w = rtabs[i] - rtabs[i - 1];
				cols += '<col style="width:'+String(w / this.renderedWidth * 100)+'%;">';
			}
			cols += '</colgroup>';
			html = '<table cellspacing="0" cellpadding="0" style="width:100%;"'+t+'>'+cols+'<tbody>'+html+'</tbody></table>';
			t = '';
		}
		html = '<div id="'+this.id+'" class="'+leParser.createFrameClasses()+'" style="'+leParser.createFrameStyles(this)+'"'+t+'>'+html+'</div>';
		
		this.jDomNode = $(html);
		jPage.append(this.jDomNode);		

		// in devmode we put layers above to make found elements visible
		if(leParser.config.devMode){
			if(leParser.config.showCellOverlays){
				// render visible elements including the invisible "fixed" elements (which are just error-corrections). Therefore we cannot use the _lines model ...
				for(i=0; i < this.elements.length; i++){
					e = this.elements[i];
					if(e.rendered || e.fixed){
						jPage.append(this.renderCellOverlay(e));
					}
				}
			}
			if(leParser.config.showCellOverlays && leParser.config.showEmptyCellOverlays){
				// render empty cells representing whitespace
				for(i=0; i < this._lines.length; i++){
					l = this._lines[i];
					for(j=0; j < l.cols.length; j++){
						if(l.cols[j].empty){
							jPage.append(this.renderCellOverlay(l.cols[j]));
						}
					}
				}
			}
		}
		delete  this._nodes;
		delete  this._lines;
	};
	LeParserFrame.prototype.renderCellOverlay = function(element){
		var title = '',
			styles = 'top:'+element.top+'px; left:'+element._ltab+'px; width:'+(element._rtab - element._ltab)+'px; height:'+element.height+'px;',
			classes = this.id+'-eolay '+leParser.namespace+'-eolay '+leParser.namespace+'-colorized '+leParser.namespace+'-';
		if(element.empty){
			classes += 'empty';
		} else if(element.fixed){
			classes += 'fixed';
		} else {
			classes += element.align;
		}
		if(element._prev && element._prev != null){
			title += ' prev:'+element._prev.id;
		}
		if(element._next && element._next != null){
			title += ' next:'+element._next.id;
		}
		if(element._above && element._above != null){
			title += ' above:'+element._above.id;
		}
		if(element._below && element._below != null){
			title += ' below:'+element._below.id;
		}
		if(element.colspan > 1){
			title += ' colspan:'+element.colspan;
		}
		if(element.rowspan > 1){
			title += ' rowspan:'+element.rowspan;
		}
		// reduced prop-set for empty elements ...
		if(element.empty){
			return ('<div class="'+classes+'" style="'+styles+'" title="line:'+element.bottom+title+'"></div>');
		}
		return (
			'<div id="'+element.id+'olay" class="'+classes+'" style="'+styles+'"'
			+' title="id:'+element.id+', align:'+element.align+', line:'+element._line
			+', ltab:'+element._ltab+', minltab:'+element._minltab+', rtab:'+element._rtab+', maxrtab:'+element._maxrtab+', ctab:'+element._ctab
			+', fontsize:'+element.getFontSize()+', lineheight:'+element.getLineHeight()+title+'"></div>');
	};
	// can only be called in dev-mode
	LeParserFrame.prototype.remove = function(jPage){
		$('.'+this.id+'-eolay').remove();
		if(this.jDomNode != null){
			this.jDomNode.remove();
		}
		this.jDomNode  = null;
		this.rows = [];
		this.elements = [];
		this.cols = [];
		this.tabs = [];
	};
	// this is only a "rescue"-function to avoid the rendering to fail. If the other layout-evaluation works properly, this method must must not be called
	// must not be called on non-rendered function !!
	LeParserFrame.prototype.setTabBoundries = function(element){
		leParser.logError("Frame "+this.id+" found element with tab-boundries not properly set: "+element.id);
		var ltab = element._ltab, rtab = element._rtab;
		if(!this._tabs.hasOwnProperty("p"+ltab)){
			if(element._prev != null){
				ltab = element._prev._rtab;
			} else {
				ltab = this._findNearestLeftTab(element.left);
			}
		}
		if(!this._tabs.hasOwnProperty("p"+rtab)){
			if(element._next != null){
				rtab = element._next._ltab;
			} else {
				rtab = this._findNearestRightTab(element.right);
			}
		}
		element.setTabs(ltab, rtab);
	};

	// internal helper

	LeParserFrame.prototype._isIn = function(pos, varName, thresh){
		for(var i in this[varName]){
			if(Math.abs(this[varName][i].pos - pos) < thresh){
				return this[varName][i].num;
			}
		}
		return 0;
	};
	LeParserFrame.prototype._addTo = function(pos, varName, thresh){
		for(var i in this[varName]){
			if(Math.abs(this[varName][i].pos - pos) <= thresh){
				this[varName][i].num++;
				return this[varName][i].pos;
			}
		}
		this[varName]["p"+String(pos)] = (varName == "_lines") ? new LeParserRow(pos, 1) : new LeParserTab(pos, 1);
		return pos;
	};
	LeParserFrame.prototype._removeFrom = function(pos, varName){
		if(this[varName].hasOwnProperty("p"+String(pos))){
			this[varName]["p"+String(pos)].num--;
			if(this[varName]["p"+String(pos)].num <= 0){
				delete this[varName]["p"+String(pos)];
			}
		}
	};
	LeParserFrame.prototype._findNearestRightTab = function(pos){
		for(var i=0; i < this.tabs.length; i++){
			if(this.tabs[i] >= pos){
				return this.tabs[i];
			}
		}
		return this.tabs[this.tabs.length - 1];
	};
	LeParserFrame.prototype._findNearestLeftTab = function(pos){
		for(var i = this.tabs.length - 1; i >= 0; i--){
			if(this.tabs[i] <= pos){
				return this.tabs[i];
			}
		}
		return this.tabs[0];
	};
	LeParserFrame.prototype._isPotentialMultiline = function(ele, eleAbove){
		return true;
	};
	LeParserFrame.prototype._harmonizeAligned = function(direction, elements, left, right){
		var e, i, ltab = left, rtab = right, minl = right, minr = left, remains = [];
		// evluate smallest available distance
		for(i = 0; i < elements.length; i++){
			e = elements[i];
			ltab = Math.max(ltab, e.getMinLeft());
			rtab = Math.min(rtab, e.getMaxRight());
			minl = Math.min(minl, e._ltab)
			minr = Math.max(minr, e._rtab);
		}
		// we want to harmonize the layout assignind boundries in the middle between items if possible
		if(direction == "right" && minl > (ltab + 2)){
			ltab = Math.floor((minl + ltab) / 2);
		} else if(direction == "left" && minr < (rtab - 2)){
			rtab = Math.ceil((minr + rtab) / 2);
		}
		// adjust elements that fit
		for(i = 0; i < elements.length; i++){
			e = elements[i];
			if(ltab <= e._ltab && rtab >= e._rtab){
				// if centered elements are adjusted to their existing size a center-align makes no sense ...
				if(elements.length == 1 && (ltab == e._ltab || rtab == e._rtab)){
					e.align = (ltab == e._ltab && rtab != right) ? "left" : "right";					
				} else {
					e.increaseTabs(ltab, rtab);
				}
			} else {
				remains.push(e);
			}
		}
		// second check is to prevent endless recursions !
		if(remains.length > 0 && remains.length < elements.length){
			this._harmonizeCentered(remains, left, right);
		}
	};
	LeParserFrame.prototype._addTab = function(t){
		if(!this._tabs.hasOwnProperty("p"+t)){
			this._tabs["p"+t] = t;
		}
	};

	/* *************************************************************************************************** */

	var LeParserRotatedFrame = function(page, index, element, jNode){
		this.index = index;
		this.pageId = page.id;
		this.left = element.left;
		this.top = element.top;
		this.right = element.right;
		this.bottom = element.bottom;
		this.width = element.width;
		this.height = element.height;
		this.fixed = true;
		this.rotated = true;
		this.rotation = element.rotation;
		this.jDomNode = jNode;
		this.transform = "";
		this.scaleX = 1;
		this.scaleY = 1;
	};
	LeParserRotatedFrame.prototype.build = function(jPage){
		if(this.jDomNode == null){
			return;
		}
		this.id = leParser.createFrameId();
		this.jDomNode.attr("id", this.id);
		// we add the coloration as a seperate layer to check the positions in dev-mode. therefore we do not scale & do not apply aour transformation-matrix
		if(leParser.config.devMode){
			this.jDomNode.addClass(leParser.createRotatedFrameClasses());
			this.jDomNode.prop('contenteditable', true);
		}
	};
	LeParserRotatedFrame.prototype.remove = function(jPage){
		if(this.jDomNode == null){
			return;
		}
		if(leParser.config.devMode){
			this.jDomNode.removeClass(leParser.createRotatedFrameClasses());
		}
		this.jDomNode  = null;
	};

	/* *************************************************************************************************** */

	var LeParserPage = function(index, id, width, height, left, top){
		this.index = index;
		this.id = id;
		this.width = width;
		this.height = height;
		// take care: these coords contain the scroll-positions and are only useful in the build-phase
		this.left = left;
		this.top = top;
		this.frames = [];
	};
	LeParserPage.prototype.numFrames = function(){
		return this.frames.length;
	};
	LeParserPage.prototype.addFrame = function(frame){
		this.frames.push(frame);
	};
	LeParserPage.prototype.build = function(jPage){
		if(!jPage || jPage == null){
			jPage = $("+"+this.id);
		}
		for(var i=0; i < this.frames.length; i++){
			this.frames[i].build(jPage, this.width);
		}
	};
	LeParserPage.prototype.remove = function(jPage){
		$("#"+this.id+" ."+leParser.namespace+"-orig").removeClass(leParser.namespace+"-orig").show();
		for(var i=0; i < this.frames.length; i++){
			this.frames[i].remove();
		}
		this.frames = [];
	};

	/* *************************** OPTIONAL: Configurator / Editor ************************************ */

	var LeParserConfigurator = function(){
		// has to be in sync with leParser config
		this.config = {
			"frameHorThreshOnLine": { "type":"int", "min":1, "max":100, "step":1, "unit":"px", "txt":"Horizontal threshhold detecting Elements belong to a frame that are horizontally aligned (bottomline)" },
			"frameHorizontalThresh": { "type":"int", "min":10, "max":500, "step":5, "unit":"px", "txt":"Horizontal threshhold detecting Elements belong to a frame" },
			"frameVerticalThreshForTab": { "type":"int", "min":1, "max":100, "step":1, "unit":"px", "txt":"Vertical threshhold detecting Elements belong to a frame that are vertically aligned (left or right)" },
			"frameVerticalThresh": { "type":"int", "min":10, "max":200, "step":1, "unit":"px", "txt":"Vertical threshhold detecting Elements belong to a frame" },
			"rotationThresh": { "type":"float", "min":0.1, "max":10, "step":0.1, "unit":"degree", "txt":"Threshhold detecting rotated Elements" },
			"linePrecision": { "type":"int", "min":1, "max":10, "step":1, "unit":"1/X", "txt":"Reciprocal precision of horizontal line calculations" },
			"tabPrecision": { "type":"int", "min":1, "max":10, "step":1, "unit":"1/X", "txt":"Reciprocal precision of vertical line calculations" },
			"linePosThresh": { "type":"float", "min":0.05, "max":10, "step":0.05, "unit":"px", "txt":"Threshhold to identify vertical positions to be on the same line" },
			"tabPosThresh": { "type":"float", "min":0.1, "max":10, "step":0.1, "unit":"px", "txt":"Threshhold to identify horizontal positions to be on the same line" },
			"maxMultiLineThresh": { "type":"int", "min":0, "max":25, "step":0.5, "unit":"percent", "txt":"Maximum line-height differnce two following textfields will be regarded as being one multiline-field" },
			"maxMultiLineHeight": { "type":"int", "min":100, "max":250, "step":1, "unit":"percent", "txt":"Maximum line-height a multiline-field can have" },
			"joinMultilineWithBr": { "type":"bool", "unit":"", "txt":"Join the lines of  multiline-fields with a line-break. If not, the text-flow may changes" },
			"multilineBreakThresh": { "type":"int", "min":50, "max":100, "step":1, "unit":"percent", "txt":"if a line of a multiline-field is that much shorter as the field, it will be added with a linebreak in any case" },
			"subSuperFontSizeMax": { "type":"float", "min":0.1, "max":1, "step":0.05, "unit":"percent", "txt":"Maximum percentage a sub- or superscript item can have in relation to the text it is part of" },
			"subSuperJoinThresh": { "type":"int", "min":0, "max":25, "step":1, "unit":"px", "txt":"Max distance between a sub/superscript item and a text to be rendered as belonging together" },
			"splitOnSeperatorSpans": { "type":"bool", "unit":"", "txt":"If set the empty spans used by pdf2html to generate horizontal whitespace are used to split the text into columns" },
			"renderSequenceBotPads": { "type":"bool", "unit":"", "txt":"If set it is tried to add a bottom padding to sequence-elements that have a distance to the next element in the sequence" },
			"renderTableBotPads": { "type":"bool", "unit":"", "txt":"If set it is tried to add a bottom padding to table-elements that have a distance to the next element in the column" },
			"renderedHorGrowth": { "type":"int", "min":100, "max":110, "step":0.5, "unit":"percent", "txt":"when rendering the frame-width will be increased with this percentage to avoid lines breaking due to browser rounding errors" },
			"renderedCellPadShrink": { "type":"int", "min":50, "max":100, "step":1, "unit":"percent", "txt":"vertical paddings of textboxes with a bottom-distance will be lowered to this percentage (as defined by renderSequenceBotPads and renderTableBotPads)" },
			"minRenderedLineHeight": { "type":"int", "min":101, "max":120, "step":0.5, "unit":"percent", "txt":"the min. lineheight an editable textfield will have" },
			"fontHeightMatchThresh": { "type":"int", "min":100, "max":200, "step":5, "unit":"percent", "txt":"Threshhold to find line-heights in the existing evaluated multiline-heights in percent" },
			"colorizeFrames": { "type":"bool", "unit":"", "txt":"Colorize all frames" },
			"showCellOverlays": { "type":"bool", "unit":"", "txt":"Shows the cells/cell-overlays as colored overlays over the rendered cells" },
			"showEmptyCellOverlays": { "type":"bool", "unit":"", "txt":"Colorize all empty (whitespace creating) columns in rows in the cell-overlays" }
		};
		this.build = function(){
			var lc = leParser.config, lp = leParser.namespace, item;
			var html =
				'<div id="'+lp+'-configurator">'
				+'<form onsubmit="return leParser.configurator.render(this);">'
				+'<table>';
			for(var prop in this.config){
				item = this.config[prop];
				html += '<tr><td><label title="'+item.txt+'">'+prop+':</label></td><td>';
				switch(item.type){
					case "bool":
						var checked = (lc[prop]) ? ' checked' : '';
						html += '<input type="checkbox" name="'+prop+'" value="1" data-type="bool"'+checked+'/>';
						break;
					case "int":
					case "float":
						html += '<input type="number" name="'+prop+'" value="'+lc[prop]+'" min="'+item.min+'" max="'+item.max+'" step="'+item.step+'" data-type="'+item.type+'"/>';
						break;
				}
				if(item.unit != ""){
					html += '<span>'+item.unit+'</span>';
				}
				html += '</td></tr>';
			}
			html +=
				'<tr><td colspan="2" class="'+lp+'-submit">'
				+'<input type="submit" value="Render" title="Render the adjusted configuration" />'
				+'<input type="reset" value="Reset" title="Revert to the initial values" />'
				+'<input type="button" onclick="return leParser.configurator.showConfig();" value="Config" title="Show the currently rendered config" />'
				+'<input id="'+lp+'-configurator-toggle" type="button" onclick="return leParser.configurator.toggle();" value="Toggle" title="Toggle between rendered and original layout" />'
				+'</td></tr>'
				+'</table></form></div>';
			$("body").addClass(lp+'-dev').append($(html));
		};
		this.showConfig = function(){
			var html =
				'<div id="'+leParser.namespace+'-configurator-config">'
				+'<a href="javascript:leParser.configurator.closeConfig();">x</a>'
				+'<pre>'+JSON.stringify(leParser.config, null, 5)+'</pre>'
				+'</div>';
			$("body").append($(html));
			return false;
		};
		this.closeConfig = function(){
			$("#"+leParser.namespace+"-configurator-config").remove();
		};
		this.render = function(form){
			// first remove all elements with the current props...
			leParser.removeAllPages();
			// now evaluate the new props
			var ele, name;
			$(form).find("input").each(function(){
				if(this.type != "submit" && this.type != "reset"){
					ele = $(this);
					switch(ele.data("type")){
						case "bool":
							leParser.config[this.name] = ele.prop("checked");
							break;
						case "float":
							leParser.config[this.name] = parseFloat(ele.val());
							break;
						case "int":
							leParser.config[this.name] = parseInt(ele.val());
							break;
					}
				}
			});
			this.isToggled = false;
			$("#"+leParser.namespace+"-configurator-toggle").removeClass(leParser.namespace+'-toggled');
			// and re-render the content with a delay
			window.setTimeout(function(){ leParser.buildAllPages(); }, 500);
			return false;
		};
		this.toggle = function(){
			if(this.isToggled){
				$("."+leParser.namespace+"-orig").fadeOut(leParser.config.configuratorFadeDuration);
				$("."+leParser.namespace+"-frm").fadeIn(leParser.config.configuratorFadeDuration);
				$("#"+leParser.namespace+"-configurator-toggle").removeClass(leParser.namespace+'-toggled');
				this.isToggled = false;
			} else {
				$("."+leParser.namespace+"-orig").fadeIn(leParser.config.configuratorFadeDuration);
				$("."+leParser.namespace+"-frm").fadeOut(leParser.config.configuratorFadeDuration);
				$("#"+leParser.namespace+"-configurator-toggle").addClass(leParser.namespace+'-toggled');
				this.isToggled = true;
			}			
			return false;
		};
		this.isToggled = false;
	};

	/* *************************************************************************************************** */

	w.leParser = {
		namespace: 't5lep', // CRUCIAL: this must match LeFrameWatcher.namespace
		maxX: 100000, // used as a fallback in calculations to mark elements to the absolute left
		maxY: 1000000, // used as a fallback in calculations to mark elements to the absolute bottom
		eleCounter: -1,
		frmCounter: -1,
		/* Explanation of config: see LeParserConfigurator.config */
		config: {
			layoutTags: ["div"],
			frameHorThreshOnLine: 25,
			frameHorizontalThresh: 150,
			frameVerticalThreshForTab: 11,
			frameVerticalThresh: 25,
			rotationThresh: 0.9,
			linePrecision: 2,
			tabPrecision: 2,
			linePosThresh: 0.5,
			tabPosThresh: 1.5,
			maxMultiLineThresh: 15,
			maxMultiLineHeight: 200,
			joinMultilineWithBr: true,
			multilineBreakThresh: 67,
			subSuperFontSizeMax: 0.75,
			subSuperJoinThresh: 2,
			splitOnSeperatorSpans: true,
			renderSequenceBotPads: true,
			renderTableBotPads: true,
			renderedHorGrowth: 102.5,
			renderedCellPadShrink: 95,
			minRenderedLineHeight: 105,
			fontHeightMatchThresh: 150,
			devMode: false,
			colorizeFrames: false,
			showCellOverlays: false,
			showEmptyCellOverlays: false,
			defaultFontSize: 11,
			defaultLineHeight: 125,
			configuratorFadeDuration: 1000
		},
		pages: {},
		/* a config-object can be set to manipulate the defaults */
		init: function(props){
			if(props){
				for(var prop in props){
					if(this.config.hasOwnProperty(prop)){
						this.config[prop] = props[prop];
					}
				}
			}
			if(this.config.devMode && LeParserConfigurator){
				this.configurator = new LeParserConfigurator();
				this.configurator.build();
			}
			this.fonts = new LeParserFontMetrics();
		},
		addMultilineFont: function(font, html){
			this.fonts.addFont(font, this.stripTags(html).length);
		},
		getLineHeight: function(font){
			return this.fonts.getHeight(font);
		},	
		getDefaultFont: function(){
			element.font = new LeParserFont(this.config.defaultFontSize, this.config.defaultFontSize, null, null, null);
		},
		buildAllPages: function(){
			leParser.pages = {};
			$("#page-container").children().each(function(index){
				leParser.parsePage(this, index);
			});
		},
		removeAllPages: function(){
			for(var id in this.pages){
				this.pages[id].remove();
			}
			this.pages = {};
		},
		parsePage: function(node, index){
			if(node.id && node.id != undefined){
				// did we already parse this page ?
				if(!this.pages.hasOwnProperty(node.id)){
					// retrieves the dimensions with transform matrix applied !!
					var pos = node.getBoundingClientRect();
					var page = new LeParserPage(index, node.id, pos.width, pos.height, pos.left, pos.top);
					var jPage = $(node);
					this.findFrames(page, jPage);
					page.build(jPage);
					this.pages[node.id]  = page;
				}
			} else {
				this.logError('Page without ID attribute found');
			}
		},
		findFrames: function(page, jPage){
			var positions = {}, fCount = 0, frame = null, i = 0, nn;
			jPage.find("*").each(function(index){
				// QUIRK: this is so crazy: the node-name is actally uppercase while leParser.config.layoutTags are lowercase. Still, this comparision works. If this.nodeName.toLowerCase() is used, it DOS NOT WORK anymore.
				// If this.nodeName.toUpperCase() is used, it also works. WHAT ???
				if($.inArray(this.nodeName, leParser.config.layoutTags)){
					var jNode = $(this);
					var element = leParser.createRenderedElement(jNode, this, page);
					if(element.rendered){
						// generate ID for the element if it does not have one
						element.id = leParser.getOrAddId(jNode);
						// Elements with a rotation will not be handled currently
						if(element.rotation != 0){
							page.addFrame(new LeParserRotatedFrame(page, fCount++, element, jNode));
						} else {
							// Logic is dependant on html2pdfEX not generating layouts with padding & margin ...
							// TODO: neccessary ???
							if(element.height >= (element.getLineHeight() * 2)){
								element.multiline = true;
							}
							if(leParser.doCreateNewFrame(frame, element)){
								frame = new LeParserFrame(page, fCount++);
								page.addFrame(frame);
							}
							frame.addElement(jNode, element);
							if(element._subs.length > 0){
								for(i=0; i < element._subs.length; i++){
									// TODO: neccessary ???
									element._subs[i].id = leParser.createId();
									element._subs[i].multiline = element.multiline;
									frame.addElement(jNode, element._subs[i]);
								}
							}
							delete element._subs;
						}
					}
				}
			});
		},
		// The logic to identify the need to create a new frame
		doCreateNewFrame: function(frame, pos){
			// we create a frame in any case if there is no frame already
			if(frame == null || pos == null){
				return true;
			}
			// if the element is too far away vertically (even for tab-detection) we crate a new frame in any case
			if(pos.top - frame.bottom > this.config.frameVerticalThresh || frame.top - pos.bottom > this.config.frameVerticalThresh){
				return true;
			}
			// if the element is in the distance to check for tab-detection we do just that
			if(pos.top - frame.bottom > this.config.frameVerticalThreshForTab || frame.top - pos.bottom > this.config.frameVerticalThreshForTab){
				return (frame.isInLeftTabs(pos.left) == 0 && frame.isInRightTabs(pos.right) == 0);
			}
			// if the element is too far away horizontally we crate a new frame
			if(pos.left - frame.right > this.config.frameHorizontalThresh || frame.left - pos.right > this.config.frameHorizontalThresh){
				return true;
			}
			// if the element is in the limit for line detection we do just that
			if(pos.left - frame.right > this.config.frameHorThreshOnLine || frame.left - pos.right > this.config.frameHorThreshOnLine){
				return (frame.isInLines(pos.bottom) == 0);
			}
			return false;
		},
		getOrAddId: function(jNode){
			if(jNode.get(0).hasAttribute("id")){
				return jNode.attr("id");
			}
			this.eleCounter++;
			jNode.attr("id", (this.namespace + this.eleCounter));
			return (this.namespace + this.eleCounter);
		},
		createId: function(){
			this.eleCounter++;
			return (this.namespace + this.eleCounter);
		},
		createFrameId: function(){
			this.frmCounter++;
			return this.namespace + "-frm" + this.frmCounter;
		},
		createFrameClasses: function(){
			return this.createClassesForType("frm", (this.config.devMode && this.config.colorizeFrames));
		},
		createRotatedFrameClasses: function(){
			return this.createClassesForType("rotfrm", (this.config.devMode && this.config.colorizeFrames));
		},
		createFrameStyles: function(frame){
			// re-applying the matrix, this is needed to apply a proper font-size in pixels (fractions do not work ...)
			var fWidth = frame.hasOwnProperty("renderedWidth") ? frame.renderedWidth : frame.width;
			var fLeft = frame.hasOwnProperty("renderedLeft") ? frame.renderedLeft : frame.left;
			if(frame.scaleX != 1 && frame.scaleY != 1 && frame.transform != ""){
				var width = fWidth / frame.scaleX;
				var height = frame.height / frame.scaleY;
				return 'top:'+String(frame.top - ((height - frame.height) / 2))+'px; left:'+String(fLeft - ((width - fWidth) / 2))+'px; width:'+String(Math.ceil(width))+'px; height:'+String(Math.ceil(height))+'px; transform:'+frame.transform+';';
			}
			return 'top:'+String(frame.top)+'px; left:'+String(fLeft)+'px; width:'+String(Math.ceil(fWidth))+'px; height:'+String(Math.ceil(frame.height))+'px;';
		},
		createClassesForType: function(type, addColorization){
			if(addColorization){
				return this.namespace + "-" + type + " "+this.namespace + "-colorized";
			}
			return this.namespace + "-" + type;
		},
		// jQuerys implementation does not work in all cases for unknown reasons
		getOffsetParent: function(jNode){
			var parent = jNode.parent();
			if(parent.length == 0){
				return parent;
			}
			var position = parent.css("position");
			if(position == "relative" || position == "absolute" || position == "fixed")
				return parent;
			return leParser.getOffsetParent(parent);
		},
		createRenderedElement: function(jNode, node, page){
			var clientRect = node.getBoundingClientRect();
			var element = new LeParserElement((clientRect.top - page.top), (clientRect.left - page.left), clientRect.width, clientRect.height, jNode.rotation(), jNode.transformationScale());
			if(jNode.css("position") != "absolute")
				return element;
			var children = jNode.contents();
			if(children.length == 0){
				return element;
			}
			// if under a thresh, the rotation will be reset to 0 (= horizontal)
			if(Math.abs(element.rotation) < leParser.config.rotationThresh){
				element.rotation = 0;
			}
			// fontSize. QUIRK: expects font-size to be set in pixels !
			var fs = jNode.css('font-size'), lh = jNode.css('line-height');
			fs = (fs.indexOf("px") > 0) ? parseFloat(fs.split("px").join("")) : leParser.config.defaultFontSize;
			if(lh.indexOf("px") > 0) {
				lh = parseFloat(lh.split("px").join(""))
			} else if(lh.indexOf("%") > 0) {
				lh = parseFloat(lh.split("%").join("")) / 100 * fs;
			} else if(/^\d*\.?\d+$/.test(lh)){
				lh = parseFloat(lh) *  fs;
			} else if(lh == "normal"){
				lh = 1.2 * fs; // DIRTY: 1.2 is the default-value according to MDN
			} else {
				// DIRTY: we assume 100% by default
				lh = fs;
			}
			// we limit the min lineHeight to 100%. It seems html2pdfEX uses lineheights < fontSize for unknown reason
			if(lh < fs){
				lh = fs;
			}
			// for font-calculations, dimensions must be passed with their unscaled value !
			element.font = new LeParserFont(fs, lh, element, jNode, null);
			
			// adding the html-nodes as strings, detect the ugly "seperator-spans"
			var html = "", sub = null, left, right, c;
			for(var i=0; i < children.length; i++){
				c = children[i];
				if((c.nodeType != 1 && c.nodeType != 3) || (c.nodeType == 3 && leParser.isOnlyWhitespace(String(c.nodeValue)))){
					// non-element-nodes (e.g. comments) & textnodes representing only whitespace do not need to be processed
					// console.log("DISMISS NODE TYPE "+c.nodeType);
				} else {
					if(c.nodeType == 3){
						element.rendered = true;
					} else if(c.nodeType == 1 && $(c).css("position") != "absolute"){
						element.rendered = true;
					}
					// identify seperating spans only for horizontal elements
					if(leParser.config.splitOnSeperatorSpans && element.rotation == 0 && c.nodeType == 1 && leParser.isSeperatorSpan(c)){
						// if we have a seperating span, we split the 
						clientRect = c.getBoundingClientRect();
						left = clientRect.left - page.left;
						if(sub == null){
							sub = element.cloneForSub((left + clientRect.width), (element.right - left - clientRect.width));
							element.right = left;
							element.width = element.right - element.left;
							element.html = html;
						} else {
							right = sub.right;
							sub.right = left;
							sub.width = sub.right - sub.left;
							sub.html = html;
							sub.rendered = true;
							element._subs.push(sub);
							sub = element.cloneForSub((left + clientRect.width), (right - left - clientRect.width));
						}
						html = "";
					} else {
						html += $(c).outerHTML();
					}
				}
			}
			if(sub == null){
				element.html = html;
			} else {
				sub.html = html;
				sub.rendered = true;
				element._subs.push(sub);
			}
			return element;
		},
		isSeperatorSpan: function(node){
			return (node.nodeName.toLowerCase() === "span" && $(node).hasClass("_") && leParser.isOnlyWhitespace(String(node.textContent)));
		},
		isOnlyWhitespace: function(text){
			return (/^\s+$/.test(text) == true);
		},
		stripTags: function(html){
			return html.toString().replace(/(<([^>]+)>)/ig,"");
		},
		rTrim: function(html, maxAmount){
			while(html.length > 0 && html.substr(-1,1) == " " && maxAmount > 0){
				html = html.substring(0, (html.length - 1));
				maxAmount--; 
			}
			return html;
		},
		lTrim: function(html, maxAmount){
			while(html.length > 0 && html.substr(0,1) == " " && maxAmount > 0){
				html = html.substr(1);
				maxAmount--; 
			}
			return html;
		},
		logError: function(msg){
			console.log('LiveEditingParser ERROR: '+msg);
		}
	};
	
	/* *************************************************************************************************** */
	
	var LeFrameWatcher = function(){
		// CRUCIAL: this must match leParser.namespace
		this.namespace = 't5lep';
		
		$("body").on('DOMSubtreeModified', "mydiv", function() {
		    alert('changed');
		});
		
	}

})(window, document, jQuery);

$(document).ready(function(){
	window.leParser.init({ "devMode":true, "colorizeFrames":true, "showCellOverlays":false });
	window.leParser.buildAllPages();
});
