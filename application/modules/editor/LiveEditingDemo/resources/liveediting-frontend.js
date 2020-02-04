"use strict";


	// Frontend-Part, frame-watcher. Coded without jQuery
	
	var MutationObserver = window.MutationObserver || window.WebKitMutationObserver || window.MozMutationObserver;
	
	var LePageWatcherSort = {	
		byTop: function(a, b){
			if(a.top < b.top){
				return -1;
			}
			if (a.top > b.top){
				return 1;
			}
			return 0;
		}
	};
	
	/* *************************************************************************************************** */
	
	var LePageWatcher = function(node){
		this.frames = [];
		this.node = node;
		this.id = node.id;
		this.observations = { attributes: false, childList: true, characterData: true, subtree: true };
		this.evaluatePosition();
		this.bottomLine = this.height - (this.height / 100 * this.pageMinBottomDistance);
		var frms = node.getElementsByClassName(this.namespace+"-frm");
		for(var i=0; i < frms.length; i++){
			this.addFrame(new LePageWatcherFrame(this, frms[i], i));
		}
		this.frames.sort(LePageWatcherSort.byTop);
		this.initHeight = this.height;
		this.bottomDist = Math.floor(this.height - this.bottomLine);
	};
	// CRUCIAL: this must match leParser.namespace
	LePageWatcher.prototype.namespace = 't5lep';
	// the minimum bottom distance (equals 15mm on a DINA4 Paper)
	LePageWatcher.prototype.pageMinBottomDistance = 5;
	// the color the page-overshoot is marked with
	LePageWatcher.prototype.pageOvershootBkgColor = "rgb(0,255,255,.1)";
	// the amount a textbox can grow horizontally and we do not change it's textflow /seperately for sequence & table)
	LePageWatcher.prototype.sequenceHorizontalThresh = 105;
	LePageWatcher.prototype.tableHorizontalThresh = 101.5;
	// the minimum vertical distance a frame should have to the frame before
	LePageWatcher.prototype.frameMinVerticalDistance = 10;
	// Must match leParser.config.linePrecision
	LePageWatcher.prototype.linePrecision = 2;
	
	LePageWatcher.prototype.addFrame = function(frame){
		this.frames.push(frame);
		if(frame.bottom > this.bottomLine){
			this.bottomLine = frame.bottom;
		}
	};
	LePageWatcher.prototype.frameChanged = function(frame){
		this.evaluatePosition();
		var moved, bot = frame.bottom, frames = [frame];
		for(var i=0; i < this.frames.length; i++){
			if(frame.id != this.frames[i].id){
				moved = this.frames[i].move(frames);
				if(moved){
					frames.push(this.frames[i]);
				}
				bot = Math.max(bot, this.frames[i].bottom);
			}
		}
		this.checkHeight(bot);
	};
	LePageWatcher.prototype.checkHeight = function(newBottom){
		if((newBottom + this.bottomDist) > this.initHeight){
			this.height = newBottom + this.bottomDist;
			this.bottom = this.top + this.height;
			this.node.style.height = this.height + "px";
			if(!this.hasOwnProperty("overshoot")){
				this.overshoot = document.createElement("div");
				this.overshoot.className = this.namespace+"-overshoot";
				this.overshoot.style.position = "absolute";
				this.overshoot.style.left = "0";
				this.overshoot.style.right = "0";
				this.overshoot.style.bottom = "0";
				this.overshoot.style.top = this.initHeight+"px";
				this.overshoot.style.backgroundColor = this.pageOvershootBkgColor;
				this.node.insertBefore(this.overshoot, this.node.firstChild);
			}
		} else {
			if(this.hasOwnProperty("overshoot")){
				this.node.removeChild(this.overshoot);
				delete this.overshoot;
			}
		}
	};
	LePageWatcher.prototype.removeFrames = function(){
		for(var i=0; i < this.frames.length; i++){
			this.frames[i].remove();
		} 
		delete this.frames;
	};
	// static helper API to add the watcher for all pages
	LePageWatcher.prototype.init = function(){
		LePageWatcher.prototype.pages = [];
		var pages = document.body.getElementsByClassName(this.namespace+"-page");
		for(var i=0; i < pages.length; i++){
			LePageWatcher.prototype.pages.push(new LePageWatcher(pages[i]));
		}
	};
	// static helper API to remove the watcher from all pages
	LePageWatcher.prototype.remove = function(){
		for(var i=0; i < LePageWatcher.prototype.pages.length; i++){
			LePageWatcher.prototype.pages[i].removeFrames();
		}
		delete LePageWatcher.prototype.pages;
	};
	LePageWatcher.prototype.evaluatePosition = function(){
		var rect = this.node.getBoundingClientRect();
		this.left = rect.left;
		this.top = rect.top;
		this.width = rect.width;
		this.height = rect.height;
		this.bottom = rect.bottom;
	};

	/* *************************************************************************************************** */
	
	// HINT: The width-prop of a frame represents it's unscaled height while the height-property represents the scaled/rendered height.
	var LePageWatcherFrame = function(pageWatcher, node, index){
		this.page = pageWatcher;
		this.id = node.id;
		this.index = index;
		this.node = node;
		this.rotated = this._hasParserClass(this.node, "rotfrm");
		// when moving frames, we need the rendered height with the transformation applied
		var nodeRect = this.node.getBoundingClientRect();
		this.initHeight = this.height = nodeRect.height;
		this.initTop = this.top = nodeRect.top - this.page.top;
		this.left = nodeRect.left;
		this.width = nodeRect.width;
		this.right = this.left + this.width;
		this.initBottom = this.oldBottom = this.bottom = this.top + this.height;
		this.isTable = false;
		// we watch the first child (frame-box) as the frame itself has fixed width & height and will not change. this depends on the implementation in leParser ...
		// if we can not find this node (rotated frames e.g.), we do not observe our layout
		this.box = node.firstElementChild;
		if(!this.rotated && this.box != null && this._hasParserClass(this.box, "fbox")){
			this.isTable = (this.box.nodeName == "TABLE");
			// the inner box usually is bigger than the holding frame due to text-overshoot. This has to be distracted from the height to keep the observed dimensions consistent
			this.heightDiff = this.box.getBoundingClientRect().height - this.height;
			this.cssTop = String(this.node.style.top);
			this.cssTop = parseFloat(this.cssTop.substr(0, this.cssTop.length - 2));
			// wath the changes of the frame's contents
			var that = this;
			this.observer = new MutationObserver(function(mutations){
				that.changed(mutations);
			});
			this.observer.observe(this.box, pageWatcher.observations);
			//  since there is no way to reliably detect the text-growth within a box, we set the parent elements width (the holding container in a sequence or the holding td in a table) as a data-attribute
			// note: we can not set the width itself as this would affect the table-layout (no idea why though)
			var texts = this.box.getElementsByClassName(this.page.namespace+"-txt");
			for(var i=0; i < texts.length; i++){
				texts[i].setAttribute("data-width", texts[i].parentNode.offsetWidth);
			}	
		} else {
			this.box = null;
			this.observer = null;
		}
	};
	LePageWatcherFrame.prototype.remove = function(){
		if(this.observer != null){
			this.observer.disconnect();
		}
	};
	LePageWatcherFrame.prototype.calculateOffsets = function(frames){
		var off = 0, o, i;
		for(i = 0;  i < frames.length; i++){
			o = this.getNeededOffset(frames[i]);
			if(o != 0){
				off = (off == 0) ? o : Math.max(off, o);
			}
		}
		return off;
	};
	LePageWatcherFrame.prototype.getNeededOffset = function(frame){
		// rotated frames are not moved as they are regarded as fixed elements of the page
		if(this.rotated || this.initTop < frame.initBottom){
			return 0;
		}
		if((frame.bottom + this.page.frameMinVerticalDistance >= this.initTop) || (frame.oldBottom + this.page.frameMinVerticalDistance >= this.initTop)){
			// nested horizontal overlap detection if's for the sake of performance & understandability
			if(!(this.right < frame.left) && !(this.left > frame.right)){
				var minDist = Math.min(Math.abs(this.initTop - frame.initBottom), this.page.frameMinVerticalDistance);
				// we don't want to get more to the top than our initial position
				var newTop = Math.max((frame.bottom + minDist), this.initTop);
				if(newTop != this.top){
					return newTop - this.top;
				}
			}
		}
		return 0;
	};
	LePageWatcherFrame.prototype.moveVertically = function(offset){
		// rotated frames are not moved as they are regarded as fixed elements of the page
		this.top += offset;
		this.oldBottom = this.bottom; // needed do detect a frame moved back to a position "behind the boundries"
		this.bottom += offset;
		offset = (this.top - this.initTop) + this.cssTop;
		this.node.style.top = String(Math.round(offset * this.page.linePrecision) / this.page.linePrecision)+"px";
	};
	LePageWatcherFrame.prototype.move = function(frames){
		var offset = this.calculateOffsets(frames);
		if(offset != 0){
			this.moveVertically(offset);
			return true;
		}
		return false;
	};
	LePageWatcherFrame.prototype.changed = function(mutations){
		for(var i=0; i < mutations.length; i++){
			if(mutations[i].type == "characterData"){
				this._textChanged(this._getParentByParserClass(mutations[i].target,"txt"));
				break;
			}
		}
		var height = this.box.getBoundingClientRect().height - this.heightDiff;
		var hasChanged = (this.height != height && height >= this.initHeight);
		this.height = height;
		this.oldBottom = this.bottom; // needed do detect a frame moved back to a position "behind the boundries"
		this.bottom = (this.top + this.height);
		if(hasChanged){
			this.page.frameChanged(this);
		}
	};
	LePageWatcherFrame.prototype._textChanged = function(txtBox){
		if(txtBox != null && txtBox.offsetWidth && txtBox.scrollWidth){
			var initW = parseInt(txtBox.getAttribute("data-width")), maxQ = (this.isTable) ? this.page.tableHorizontalThresh : this.page.sequenceHorizontalThresh;
			if(txtBox.scrollWidth > (initW * maxQ / 100)){
				// When a text-box "overgrows", this is mostly due to the white-space settimgs. Let's change them to a wrapping setting & remove the breaks
				if(txtBox.style.whiteSpace == "pre" || txtBox.style.whiteSpace == "nowrap"){
					var breaks = txtBox.getElementsByTagName("BR");
					for(var i=0; i < breaks.length; i++){
						txtBox.removeChild(breaks[i]);
					}
					txtBox.style.whiteSpace = (txtBox.style.whiteSpace == "pre") ?  "pre-wrap" : "normal";
				}
			}
		}
	};
	LePageWatcherFrame.prototype._getParentByParserClass = function(node, name){
		if(!node || node == null){
			return null;
		}
		if(this._hasParserClass(node, name)){
			return node;
		}
		return this._getParentByParserClass(node.parentElement, name);
	};
	LePageWatcherFrame.prototype._hasParserClass = function(node, name){
		if(node && node.className && node.className != null){
			return (node.className.indexOf(this.page.namespace+"-"+name) > -1);
		}
		return false;
	};
