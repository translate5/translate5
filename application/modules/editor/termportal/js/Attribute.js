const Attribute={
	$_termTable:null,
	$_termEntryAttributesTable:null,
	
	languageDefinitionContent:[],
	
	init:function(){
		this.cacheDom();
		this.initEvents();
	},
	
	cacheDom:function(){
        this.$_termTable=$('#termTable');
        this.$_termEntryAttributesTable = $('#termEntryAttributesTable');
	},
	
	onAttributeEditingOpened: function() {
        Term.drawProposalButtons('attributeEditingOpened');
	},
	
	initEvents:function(){
		var me=this;

        me.$_termTable.on('focus input', ".term-attributes textarea",me.onAttributeEditingOpened);
        me.$_termEntryAttributesTable.on('focus input', "textarea",me.onAttributeEditingOpened);
        
        // ------------- TermEntries-Attributes -------------
        // - Icons
        me.$_termEntryAttributesTable.on('click', ".proposal-add",{scope:me},me.onAddTermEntryAttributeClick);
        me.$_termEntryAttributesTable.on('click', ".attribute-data.proposable .proposal-delete",{scope:me,root:me.$_termEntryAttributesTable},me.onDeleteAttributeClick);
        me.$_termEntryAttributesTable.on('click', ".attribute-data.proposable .proposal-edit",{
            scope:me,
            type:'termEntryAttribute',
            reference: 'icon'
        },me.onEditAttributeClick);
        // - Content
        me.$_termEntryAttributesTable.on('click', '.attribute-data.proposable ~ [data-type="termEntryAttribute"] [data-editable][data-type="termEntryAttribute"]',{
            scope:me,
            type:'termEntryAttribute',
            reference: 'content'
        },me.onEditAttributeClick);
        
        // ------------- Terms-Attributes -------------
        // - Icons
        me.$_termTable.on('click', ".term-attributes .proposal-add",{scope:me},me.onAddTermAttributeClick);
        me.$_termTable.on('click', ".attribute-data.proposable .proposal-delete",{scope:me,root:me.$_termTable},me.onDeleteAttributeClick);
        me.$_termTable.on('click', ".attribute-data.proposable .proposal-edit",{
        	scope:me,
        	root:me.$_termTable,
        	type:'termAttribute',
            reference: 'icon'
    	},me.onEditAttributeClick);
        // - Content
        me.$_termTable.on('click', '.attribute-data.proposable ~ [data-type="termAttribute"] [data-editable][data-type="termAttribute"]',{
            scope:me,
            type:'termAttribute',
            reference: 'content'
        },me.onEditAttributeClick);
        // - Comment
        me.$_termTable.on('click', '.attribute-data.proposable ~ [data-type="termAttribute"] [data-editable-comment][data-type="termAttribute"]',{
            scope:me,
            type:'termAttribute',
            reference: 'comment'
        },me.onEditAttributeClick);
	},
    
    /***
     * Render term attributes by given term
     * 
     * @param attributes
     * @param termLang
     * @returns {String}
     */
    renderTermAttributes:function(attributes,termLang){
        var me=this,
            html = [],
            commentAttribute=[];
        
        if(me.languageDefinitionContent[termLang]){
            html.push(me.languageDefinitionContent[termLang]);
        }
        
        for(var i=0;i<attributes.length;i++){
            //comment attribute should always appear as first
            if(attributes[i].name=='note'){
                commentAttribute.push(me.handleAttributeDrawData(attributes[i]));
                continue;
            }
            html.push(me.handleAttributeDrawData(attributes[i]));
        }
        html=commentAttribute.concat(html);
        
        return html.join('');
    },

    /***
     * On add term-entry-attribute icon click handler
     */
    onAddTermEntryAttributeClick: function(event){
        var me = event.data.scope;
        console.log('onAddTermEntryAttributeClick');
        // TODO
    },

    /***
     * On add term-attribute icon click handler
     */
    onAddTermAttributeClick: function(event){
        var me = event.data.scope;
        console.log('onAddTermAttributeClick');
        // TODO
    },
	
    /***
     * On edit term-entry-attribute click handler
     * On edit term-attribute click handler
     */
    onEditAttributeClick: function(event){
        console.log('onEditAttributeClick');
    	var me=event.data.scope,
	    	type=event.data.type,
            reference = event.data.reference,
	        $element=$(this),
			attributeId,
            $editableComponent,
            $input;
        console.log('onEditAttributeClick ('+reference+')');
        
        event.stopPropagation();
        
        switch(reference) {
            case "comment":
                $editableComponent = $element;
                break;
            case "content":
                $editableComponent = $element;
                break;
            case "icon":
                attributeId = $element.parents('h4.attribute-data').data('attributeId');
                $editableComponent = me.getAttributeComponent(attributeId,type);
                break;
    	}
	
		if($editableComponent.length==0){
			return;
		}
		
		// if the textarea exists already
		if ($editableComponent.children('textarea').length === 1) {
	        $input = $editableComponent.children('textarea');
	        $input.focus();
	        return;
		}
		
		//is attribute component
		if(typeof $editableComponent.data('editable') !== 'undefined'){
		    $input = ComponentEditor.addAttributeComponentEditor($editableComponent);
            $input.focus();
            return;
		}
		
		//is comment attribute component
		if(typeof $editableComponent.data('editableComment') !== 'undefined'){
		    $input = ComponentEditor.addCommentAttributeEditor($editableComponent);
            $input.focus();
            return;
		}
    },
    
	
	/***
     * On delete term and term entry attribute icon click handler
     */
    onDeleteAttributeClick: function(event){
        var me=event.data.scope,
        	root=event.data.root,
            $element=$(this),
			$parent=$element.parents('h4.attribute-data'),//the button parrent
			attributeId=$parent.data('attributeId');
		
		if($parent.length==0){
			return;
		}
		var yesCallback=function(){
			//ajax call to the remove proposal action
			var me=event.data.scope,
				url=Editor.data.termportal.restPath+'termattribute/{ID}/removeproposal/operation'.replace("{ID}",attributeId);
				
			$.ajax({
		        url: url,
		        dataType: "json",	
		        type: "POST",
		        success: function(result){
		        	//reload the termEntry when the attribute is deleted (not proposal)
		        	if(!result.rows || result.rows.length==0){
		        		Term.reloadTermEntry=true;
		        		Term.findTermsAndAttributes(Term.newTermGroupId);
		        		return;
		        	}
		        	
		        	
		        	var attributeData=result.rows;
		        	
		        	//the term attribute is definition, remove and update the content for the term and term entry attribute definition dom
		    		if(attributeData.attrType=='definition'){
		    			me.checkAndUpdateDeffinition(attributeData,'termEntryAttribute')
		    			me.checkAndUpdateDeffinition(attributeData,'termAttribute')
		    			return;
		    		}
		    		
		        	//the term proposal is removed, find the attribute holder and render the initial term proposable content
		        	var renderData=me.getAttributeRenderData(attributeData,attributeData.value),
		        		$proposalHolder=root.find('p[data-type="'+attributeData.attributeOriginType+'"][data-id="'+attributeData.attributeId+'"]'),
		        		$ins=$proposalHolder.find('ins');
		        		
	        		$ins.replaceWith(renderData);
		        	$proposalHolder.find('del').remove();
		        	$parent.switchClass('is-proposal','is-finalized');
		            Term.drawProposalButtons($parent);
		    		//on the next term click, fatch the data from the server, and update the cache
		    		Term.reloadTermEntry=true;
		        }
		    });
		};
		var yesText=proposalTranslations['Ja'],
			noText=proposalTranslations['Nein'],
			buttons={
			};
		
		buttons[yesText]=function(){
            $(this).dialog('close');
            yesCallback();
		};
		buttons[noText]=function(){
			$(this).dialog('close');
		};
		// Define the Dialog and its properties.
	    $("<div></div>").dialog({
	        resizable: false,
	        modal: true,
	        title: proposalTranslations['deleteAttributeProposal'],
	        height: 250,
	        width: 400,
	        buttons:buttons
	    }).text(proposalTranslations['deleteAttributeProposalMessage']);
    },
    
	/***
	 * Find child's for the attribute, and build the data in needed structure
	 *  
	 * @param attribute
	 * @returns html
	 */
	handleAttributeDrawData:function(attribute){
	    
	    if(!attribute.attributeId){
	        return translations['noExistingAttributes']; // see /application/modules/editor/views/scripts/termportal/index.phtml
	    }
	    
	    var me=this,
	    	html='',
            isProposal,
            proposable = attribute.proposable ? ' proposable' : '', // = the user can handle proposals for this attribute: (1) suer has the rights (2) attribute is editable
	    	headerTagOpen,
	    	headerTagClose;
            
    	// "is-proposal" can be ... 
        // ... a proposal for a term that already existed (attribute.proposal = "xyz")
        // ... or a proposal for a new term (attribute.proposal = null, but attrProcessStatus is "unprocessed")
        isProposal = ' is-finalized'; 
        if (attribute.proposal !== null || attribute.attrProcessStatus === "unprocessed") {
            isProposal = ' is-proposal';
        }
        
        headerTagOpen='<h4 class="ui-widget-header ui-corner-all attribute-data' + proposable + isProposal + '" data-attribute-id="'+attribute.attributeId+'">';
        headerTagClose='</h4>';
        
	    switch(attribute.name) {
	        case "transac":
	            
	            
	            var header=me.handleAttributeHeaderText(attribute,true);
	            
	            html += headerTagOpen + header + headerTagClose;
	            
	            if(attribute.children && attribute.children.length>0){
	                var childData=[];
	                attribute.children.forEach(function(child) {
	                    //get the header text
	                    childDataText=me.handleAttributeHeaderText(child,true);
	                    
	                    var attVal=me.getAttributeValue(child);
	                    
	                    //the data tag is displayed as first in this group
	                    if(child.name ==="date"){
	                        childData.unshift(me.getAttributeContainerRender(attribute,(childDataText + ' ' + attVal)))
	                        return true;
	                    }
	                    childData.push(me.getAttributeContainerRender(attribute,(childDataText + ' ' + attVal)));
	                });
	                html+=childData.join('');
	            }
	            break;
	        case "descrip":
	            
	            var attVal=me.getAttributeValue(attribute);
	
	            var flagContent="";
	            //add the flag for the definition in the term entry attributes
	            if(attribute.attrType=="definition" && attribute.language){
	                flagContent=" "+getLanguageFlag(attribute.language);
	            }
	
	            var headerText="";
	            
	            if(flagContent && flagContent!=""){
	                headerText =me.handleAttributeHeaderText(attribute,false)+flagContent;
	            }else{
	                headerText =me.handleAttributeHeaderText(attribute)+":";
	            }
	            
	            html=headerTagOpen + headerText + headerTagClose +me.getAttributeContainerRender(attribute,attVal);
	            
	            //if it is definition on language level, get store the data in variable so it is displayed also on term language level
	            if(attribute.attrType=="definition" && attribute.language){
	                me.languageDefinitionContent[attribute.language]="";
	                if(attribute.children && attribute.children.length>0){
	                    attribute.children.forEach(function(child) {
	                        html+=me.handleAttributeDrawData(child);
	                    });
	                }
	                
	                //remove the flag from the html which will be displayed in the term
	                me.languageDefinitionContent[attribute.language]=html.replace(flagContent,'');
	            }
	            
	            break;
	        default:
	            
	            var attVal=me.getAttributeValue(attribute);
	            
	            var headerText = me.handleAttributeHeaderText(attribute,true);
	            
	            html=headerTagOpen + headerText + headerTagClose +me.getAttributeContainerRender(attribute,attVal);
	            break;
	    }
	    return html;
	},
	
	/***
	 * Check if the attribute is of type deffinition. When it is deffinition, update the definitiona attribute 
	 * in the term or termentry area deppending on attrType param
	 */
	checkAndUpdateDeffinition:function(attribute,attrType){
		
		if(attribute.attrType!='definition'){
			return false;
		}
		
		
		var me = this,
			renderData=me.getAttributeRenderData(attribute,attribute.value),
			$elParent=me.getTermAttributeHeader(attribute.attributeId,attrType),
			$input=me.getAttributeComponent(attribute.attributeId,attrType);
	        
		
		//check for proposal and update the classes
		if(!attribute.proposal){
			$elParent.switchClass('is-proposal','is-finalized');
		}else{
			// update term-data
			$elParent.removeClass('is-finalized').removeClass('is-new').addClass('is-proposal');
			$elParent.removeClass('in-editing');
		}
	    
		if($input.children('ins').length==1){
			renderData=me.getAttributeContainerRender(attribute,renderData);
		}
		
	    $input.replaceWith(renderData);
	    
	    Term.drawProposalButtons($elParent);
	    return true;
	},
	
	/***
	 * Build the attribute text, based on if headerText (db translation for the attribute) is provided
	 * @param attribute: entry or term attribute
	 * @param addColon: add colon on the end of the header text
	 * @returns
	 */
	handleAttributeHeaderText:function(attribute,addColon){
	    var me=this,
	    	noHeaderName=attribute.name + (attribute.attrType ? (" "+attribute.attrType) : ""),
	    	headerText=attribute.headerText ? attribute.headerText :  noHeaderName;//if no headerText use attribute name + if exist attribute type
	    
	    return headerText+ (addColon ? ":" : "");
	},
	
	/***
	 * Get the value from the attribute. Replace the line break with br tag.
	 * @param attribute
	 * @returns
	 */
	getAttributeValue:function(attribute){
	    var me=this,
	    	attVal=attribute.attrValue ? attribute.attrValue : "";
	    
	    //if it is a date attribute, handle the date format
	    if(attribute.name=="date"){
	        var dateFormat='dd.mm.yy';
	        if(SESSION_LOCALE=="en"){
	            dateFormat='mm/dd/yy';
	        }
	        attVal=$.datepicker.formatDate(dateFormat, new Date(attVal*1000));
	    }
	    if (attribute.attrType == "processStatus" && attVal == "finalized") {
	    	attVal='<img src="' + moduleFolder + 'images/tick.png" alt="finalized" title="finalized">';
	    }else if(attribute.attrType == "processStatus" && attVal == "provisionallyProcessed"){
	    	attVal="-";
	    } else {
	    	attVal=attVal.replace(/$/mg,'<br>');
	    }
	    
	    return me.getAttributeRenderData(attribute,attVal);
	},
	
	getAttributeRenderData:function(attributeData,attValue){
		var me=this,
			htmlCollection=[];
		
		if (attributeData.attrProcessStatus === "unprocessed" && !attributeData.proposal) {
			htmlCollection.push('<ins class="proposal-value-content">'+attributeData.attrValue+'</ins>');
			return htmlCollection.join(' ');
		}
		
		if(!attributeData.proposable){
			return attValue;
		}
		
		
		//the proposal is allready defined, render the proposal
		if(attributeData.proposal && attributeData.proposal!=undefined){
			htmlCollection.push('<del class="proposal-value-content">'+attValue+'</del>');
			htmlCollection.push('<ins class="proposal-value-content">'+attributeData.proposal.value+'</ins>');
			return htmlCollection.join(' ');
		}
		
		//the user has proposal rights -> init attribute proposal span
		return me.getProposalDefaultHtml(attributeData.attributeOriginType,attributeData.attributeId,attValue,attributeData);
	},

	/***
	 * Get the proposalable component default html. 
	 * This will init the component as editable and set the type,id and value so thay can be used by the ComponentEditor
	 */
	getProposalDefaultHtml:function(type,id,value,attributeData){
		var data='data-editable';
		if(attributeData && attributeData.name=='note'){
			data='data-editable-comment';
		}
		return '<span '+data+' data-type="'+type+'" data-id="'+id+'">'+value+'</span>';
	},
	
	/***
	 * The attribute container holder. All attributes and attribute proposals must be surrounded with this container.
	 */
	getAttributeContainerRender:function(attribute,html){
		return '<p data-type="'+attribute.attributeOriginType+'" data-id="'+attribute.attributeId+'">'+html+'</p>';
	},
	
	/***
	 * Return the jquery component of the term/termentry attribute header(h4)
	 */
	getTermAttributeHeader:function(attributeId,type){
		var me=this,
			$selector=null;
		if(type=='termEntryAttribute'){
			$selector=me.$_termEntryAttributesTable;
		}
		if(type=='termAttribute'){
			$selector=me.$_termTable;
		}
		if(!$selector){
			return null;
		}
		return $selector.find('h4[data-attribute-id="'+attributeId+'"]');
	},
	
	/***
	 * Return jquery component of the term/termenty attribute
	 */
	getAttributeComponent:function(attributeId,type){
		var me=this,
			$selector=null,
			$el;
		if(type=='termEntryAttribute'){
			$selector=me.$_termEntryAttributesTable;
		}
		if(type=='termAttribute'){
			$selector=me.$_termTable;
		}
		if(!$selector){
			return null;
		}
		// sometimes the attribute is still in span, sometimes in p > textarea already
		$el = $selector.find('span[data-id="'+attributeId+'"]');
		if ($el.length === 0) {
		    $el = $selector.find('p[data-id="'+attributeId+'"]');
		}
		return $el;
	},
    
    /**
     * Returns term entry attributes for creating a new term entry.
     * @returns {Object}
     */
    renderNewTermEntryAttributes: function() {
        return {};
    },
    
    /**
     * Returns term attributes for creating a new term.
     * @returns {Array}
     */
    renderNewTermAttributes: function() {
        return [];
    },
    
    /**
     * Returns comment "dummy" attributes for creating a new comment.
     * @param: attributeOriginType origin type of the attribute
     * @returns {Array}
     */
    renderNewCommentAttributes: function(attributeOriginType) {
        return {
            attributeId:-1,
            name:'note',
            attrValue:'',
            attrType:null,
            headerText:'',
            proposable:true,
            attributeOriginType:attributeOriginType
        };
    }
};

Attribute.init();
