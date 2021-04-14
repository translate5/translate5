var Attribute={
	$_termTable:null,
	$_termEntryAttributesTable:null,
	$_resultTermsHolder:null,
	
	languageDefinitionContent:[],
	
	init:function(){
		this.cacheDom();
		if(!Editor.data.app.user.isTermProposalAllowed){
			return;
		}
		this.initEvents();
	},
	
	cacheDom:function(){
        this.$_termTable=$('#termTable');
        this.$_termEntryAttributesTable = $('#termEntryAttributesTable');
        this.$_resultTermsHolder=$('#resultTermsHolder');
	},
	
	onAttributeEditingOpened: function() {
        Term.drawProposalButtons('attributeEditingOpened');
	},
	
	initEvents:function(){
		var me=this;

        me.$_termTable.on('focus input', ".term-attributes textarea",me.onAttributeEditingOpened);
        me.$_termEntryAttributesTable.on('focus input', "textarea",me.onAttributeEditingOpened);
        
        // ------------- TermEntries-Attributes -------------
        me.$_termEntryAttributesTable.on('click', ".proposal-add",{scope:me},me.onAddTermEntryAttributeClick);
        me.$_termEntryAttributesTable.on('click', ".proposal-delete",{scope:me,root:me.$_termEntryAttributesTable},me.onDeleteAttributeClick);
        me.$_termEntryAttributesTable.on('click', ".proposal-edit",{
            scope:me,
            type:'termEntryAttribute'
        },me.onEditAttributeClick);
        
        // ------------- Terms-Attributes -------------
        me.$_termTable.on('click', ".term-attributes .proposal-add",{scope:me},me.onAddTermAttributeClick);
        me.$_termTable.on('click', ".attribute-data.proposable .proposal-delete",{scope:me,root:me.$_termTable},me.onDeleteAttributeClick);
        me.$_termTable.on('click', ".attribute-data.proposable .proposal-edit",{
        	scope:me,
        	root:me.$_termTable,
        	type:'termAttribute'
    	},me.onEditAttributeClick);
	},
    
    /***
     * Render term attributes by given term
     * 
     * @param term
     * @param termLang
     * @returns {String}
     */
    renderTermAttributes:function(term,termLang){
        var me=this,
            html = [],
            commentAttribute=[],
            attributes=term.attributes;
        
        if(me.languageDefinitionContent[termLang]){
            html.push(me.languageDefinitionContent[termLang]);
        }
        
        for(var i=0;i<attributes.length;i++){
            //comment attribute should always appear as first
            if(attributes[i].name === 'note'){
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
    onAddTermEntryAttributeClick: function(){
        console.log('onAddTermEntryAttributeClick');
        // TODO
    },

    /***
     * On add term-attribute icon click handler
     */
    onAddTermAttributeClick: function(){
        console.log('onAddTermAttributeClick');
        // TODO
    },
	
    /***
     * On edit term-entry-attribute click handler
     * On edit term-attribute click handler
     */
    onEditAttributeClick: function(event){
    	var me=event.data.scope,
	    	type=event.data.type,
	    	targetAttribute= $(event.currentTarget),
	    	attributeHeader=targetAttribute.parents('h4.attribute-data'),
	    	attributeId = attributeHeader.data('attributeId'),
	    	$attributeBtn = attributeHeader.children('.proposal-edit'),
            $input=null,
            $editableComponent=null,
            termHolder=null;
            
        console.log('onEditAttributeClick');
        
        if (!ComponentEditor.isCommmentAttributeRequirementMet()) {
            return false;
        }
    	
        // In tbx, "definition" belongs to the <langSet> (= level between <termEntry> and <term>).
        // In the TermPoral, the user can edit definitions only in the termEntry-Attributes,
        // not on the level of each individual term.
        if (type === 'termAttribute' && attributeHeader.hasClass('is-definition')) {
            $('#editDefinitionMsg').dialog({
                position: { my: 'left top', at: 'left top', of: $attributeBtn }
            });
            $('#editDefinitionMsg').dialog('open');
            return;
        }
        
        if(!attributeId || attributeId<1 || attributeHeader.hasClass('is-definition')){
        	termHolder=targetAttribute.parents('div.term-attributes');
        }
        $editableComponent = me.getAttributeComponent(attributeId,type,termHolder);
        
        event.stopPropagation();
        
		if($editableComponent === null || $editableComponent === undefined || $editableComponent.length === 0){
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
            $parent,
            attributeId,
            url,
            $attribute,
            yesCallback,
            yesText=proposalTranslations['Ja'],
			noText=proposalTranslations['Nein'],
			buttons={};
        
        if (!ComponentEditor.isCommmentAttributeRequirementMet()) {
            return false;
        }
    	
        $parent=$element.parents('h4.attribute-data');//the button parrent
		if($parent.length === 0){
			return;
		}

		attributeId=$parent.attr('data-attribute-id');
        
		yesCallback=function(){
			//ajax call to the remove proposal action
			url=Editor.data.termportal.restPath+'termattribute/{ID}/removeproposal/operation'.replace("{ID}",attributeId);
				
			$.ajax({
		        url: url,
		        dataType: "json",	
		        type: "POST",
		        success: function(result){
		        	//on the next term click, fatch the data from the server, and update the cache
		    		Term.reloadTermEntry=true;
		        	//reload the termEntry when the attribute is deleted (not proposal)
		        	if(!result.rows || result.rows.length === 0){
		        		//TODO: if needed add also for termentry attributes
		        		$attribute=me.getAttributeComponent($parent.attr('data-attribute-id'),'termAttribute');
		        		//if no regular comment holder is found, check for the newly created
		        		if(!$attribute || $attribute.length === 0){
		        			$attribute=me.$_termTable.find('p[data-id~="-1"][data-type~="termAttribute"]');
		        		}
		        		if(!$attribute || $attribute.length<1){
		        			return;
		        		}
		        		$($attribute).remove();
		        		$($parent).remove();
		        		return;
		        	}
		        	
		        	var attributeData=result.rows;
		        	//the term attribute is definition, remove and update the content for the term and term entry attribute definition dom
		    		if(attributeData.attrType === 'definition'){
		    			me.checkAndUpdateDeffinition(attributeData);
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
		        }
		    });
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
            header,
            headerTagOpen,
            headerTagClose,
            headerText = '',
            attVal,
            flagContent = '',
            isDefinition='',
            isModificationTransacGroup='',
            childData=[],
            childDataText;
            
    	// "is-proposal" can be ... 
        // ... a proposal for a term that already existed (attribute.proposal = "xyz")
        // ... or a proposal for a new term (attribute.proposal = null, but attrProcessStatus is "unprocessed")
        isProposal = ' is-finalized'; 
        if (attribute.proposal || attribute.attrProcessStatus === "unprocessed") {
            isProposal = ' is-proposal';
        }
        
        if(attribute.attrType === "definition"){
        	isDefinition=' is-definition ';
        }
        
        if(me.isTransacModificationAttribute(attribute)){
        	isModificationTransacGroup=' is-transac-modification ';
        }
        
        headerTagOpen='<h4 class="ui-widget-header ui-corner-all attribute-data' +isModificationTransacGroup+ proposable + isProposal + isDefinition +'" data-attribute-id="'+attribute.attributeId+'">';
        headerTagClose='</h4>';
        
	    switch(attribute.name) {
	        case "transac":
	            
	            header=me.handleAttributeHeaderText(attribute,false);
	            
	            html += headerTagOpen + header + headerTagClose;
	            
	            if(attribute.children && attribute.children.length>0){
	                attribute.children.forEach(function(child) {
	                    //get the header text
	                    childDataText=me.handleAttributeHeaderText(child,true);
	                    
	                    attVal=me.getAttributeValue(child);
	                    
	                    //the data tag is displayed as first in this group
	                    if(child.name === "date"){
	                        childData.unshift(me.getAttributeContainerRender(child,(childDataText + ' ' + attVal)));
	                        return true;
	                    }
	                    childData.push(me.getAttributeContainerRender(child,(childDataText + ' ' + attVal)));
	                });
	                html+=childData.join('');
	            }
	            break;
	        case "descrip":
	            
	            attVal=me.getAttributeValue(attribute);
	            
	            //add the flag for the definition in the term entry attributes
	            if(attribute.attrType === "definition" && attribute.language){
	                flagContent=" "+getLanguageFlag(attribute.language);
	            }
	            
	            if(flagContent && flagContent!=""){
	                headerText =me.handleAttributeHeaderText(attribute,false)+flagContent;
	            }else{
	                headerText =me.handleAttributeHeaderText(attribute,false);
	            }
	            
	            html=headerTagOpen + headerText + headerTagClose +me.getAttributeContainerRender(attribute,attVal);
	            
	            //if it is definition on language level, get store the data in variable so it is displayed also on term language level
	            if(attribute.attrType === "definition" && attribute.language){
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
	            
	            attVal=me.getAttributeValue(attribute);
	            
	            headerText = me.handleAttributeHeaderText(attribute,false);
	            
	            html=headerTagOpen + headerText + headerTagClose +me.getAttributeContainerRender(attribute,attVal);
	            break;
	    }
	    return html;
	},
	
	/***
	 * Check if the attribute is of type deffinition. When it is deffinition, update the definitiona attribute 
	 * in the term and termentry area
	 */
	checkAndUpdateDeffinition:function(attribute){
		if(attribute.attrType !== 'definition' || !Editor.data.app.user.isTermProposalAllowed){
			return false;
		}
		
		if(!attribute.proposal){
			this.removeDomProposal(this.$_termTable,attribute);
			this.removeDomProposal(this.$_termEntryAttributesTable,attribute);
			//this will refresh the languageDefinitionContent definition holder 
			this.handleAttributeDrawData(attribute);
			return;
		}

		var me = this,
			renderData=me.getAttributeRenderData(attribute,attribute.value),
			$attributes = me.$_termTable.find('span[data-id="'+attribute.attributeId+'"]');
		
		$attributes.each(function(i,att){
			att=$(att);
			att.empty();
			att.replaceWith(renderData);
		});
		
		Term.drawProposalButtons('componentEditorClosed');
	    Term.reloadTermEntry=true;
	    return true;
	},
	
	/***
	 * Remove attribute dom poroposal and replace the new values
	 */
	removeDomProposal:function(root,attributeData){
		//the term proposal is removed, find the attribute holder and render the initial term proposable content
    	var me=this,
    		renderData=me.getAttributeRenderData(attributeData,attributeData.value),
    		$proposalHolder=root.find('p[data-type="'+attributeData.attributeOriginType+'"][data-id="'+attributeData.attributeId+'"]'),
    		$ins=$proposalHolder.find('ins'),
    		$parrent=root.find('h4[data-attribute-id="'+attributeData.attributeId+'"]');
    		
		$ins.replaceWith(renderData);
    	$proposalHolder.find('del').remove();
    	$parrent.switchClass('is-proposal','is-finalized');
    	$parrent.each(function(i,att){
			Term.drawProposalButtons($(att));
		});
	},
	
	/***
	 * Build the attribute text, based on if headerText (db translation for the attribute) is provided
	 * @param attribute: entry or term attribute
	 * @param addColon: add colon on the end of the header text
	 * @returns
	 */
	handleAttributeHeaderText:function(attribute,addColon){
	    var noHeaderName = attribute.attrType ? attribute.attrType : attribute.name,//use attribute type as fallback header TRANSLATE-2325
	    	headerText=attribute.headerText ? attribute.headerText :  noHeaderName,//if no headerText use attribute name + if exist attribute type
            headerTextTranslated = translations[headerText] ? translations[headerText] : headerText;
        return headerTextTranslated + (addColon ? ":" : "");
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
	    if(attribute.name === "date"){
	        var dateFormat='dd.mm.yy';
	        if(SESSION_LOCALE === "en"){
	            dateFormat='mm/dd/yy';
	        }
	        attVal=$.datepicker.formatDate(dateFormat, new Date(attVal*1000));
	    }
	    
	    //for the processStatus attribute render icon or translated text
	    if(attribute.name === "termNote" && attribute.attrType === "processStatus"){
	    	var tooltip=Editor.data.apps.termportal.allProcessstatus[attVal] ? Editor.data.apps.termportal.allProcessstatus[attVal] : attVal;
	    	//when the attribute is processStatus, translate the given processStatus value
	    	switch(attVal) {
	    	  case "finalized":
	    		  attVal='<img src="' + moduleFolder + 'images/tick.png" alt="'+tooltip+'" title="'+tooltip+'">';
	    	    break;
	    	  case "provisionallyProcessed":
	    		  attVal="-";
	    	    break;
	    	  default:
	    		  //for all other processStatus values use the translated value string
	    		  attVal=tooltip;
	    		  attVal=attVal.replace(/$/mg,'<br>');
	    	}
	    }
	    if(attribute.name === "termNote" && attribute.attrType === "normativeAuthorization"){
	        attVal=Editor.data.apps.termportal.allPreferredTerm[attVal] ? Editor.data.apps.termportal.allPreferredTerm[attVal] : attVal;
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
		if(attributeData.proposal && attributeData.proposal !== undefined){
			htmlCollection.push('<del class="proposal-value-content">'+attValue+'</del>');
			htmlCollection.push('<ins class="proposal-value-content">'+attributeData.proposal.value+'</ins>');
			return htmlCollection.join(' ');
		}
		
		//the user has proposal rights -> init attribute proposal span
		return me.getProposalDefaultHtml(attributeData.attributeOriginType,attributeData.attributeId,attValue,attributeData);
	},
	
	/***
	 * Return the term attribute container render data.
	 * If addHolder is set, the html will be surrounded with termAttributeHolder div
	 */
	getTermAttributeContainerRenderData:function(term,addHolder){
		var me=this,
			termRflLang = (term.attributes[0] !== undefined) ? term.attributes[0].language : '',
			renderData=[];	
		
		if(addHolder){
			renderData.push('<div data-term-id="'+term.termId+'" data-collection-id="'+term.collectionId+'" class="term-attributes">');
		}
		
        if (term.termId !== -1) {
            renderData.push(Term.renderInstantTranslateIntegrationForTerm(termRflLang));
        }
        //draw term attributes
        renderData.push(me.renderTermAttributes(term,termRflLang));
		
        if(addHolder){
			renderData.push('</div>');
        }
        return renderData.join(' ');
	},

	/***
	 * Get the proposalable component default html. 
	 * This will init the component as editable and set the type,id and value so thay can be used by the ComponentEditor
	 */
	getProposalDefaultHtml:function(type,id,value,attributeData){
		var data='data-editable';
		if(attributeData && attributeData.name === 'note'){
			data='data-editable-comment';
		}
		return '<span '+data+' data-type="'+type+'" data-id="'+id+'">'+value+'</span>';
	},
	
	/***
	 * The attribute container holder. All attributes and attribute proposals must be surrounded with this container.
	 */
	getAttributeContainerRender:function(attribute,html){
		var me=this,
			addClass='';
		if(me.isNoteAttribute(attribute)){
			addClass='class="isAttributeComment"';
		}
		if(me.isDateAttribute(attribute)){
			addClass='class="isAttributeDate"';
		}
		if(me.isResponsiblePersonAttribute(attribute)){
			addClass='class="isResponsiblePerson"';
		}
		return '<p '+addClass+' data-type="'+attribute.attributeOriginType+'" data-id="'+attribute.attributeId+'">'+html+'</p>';
	},
	
	/***
	 * Return the jquery component of the term/termentry attribute header(h4)
	 */
	getTermAttributeHeader:function(attributeId,type){
		var me=this,
			$selector=null;
		if(type === 'termEntryAttribute'){
			$selector=me.$_termEntryAttributesTable;
		}
		if(type === 'termAttribute'){
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
	getAttributeComponent:function(attributeId,type,selector){
		var me=this,
			$el;
		if((!selector || selector.length==0) && type === 'termEntryAttribute'){
			selector=me.$_termEntryAttributesTable;
		}
		if((!selector || selector.length==0) && type === 'termAttribute'){
			selector=me.$_termTable;
		}
		if(!selector){
			return null;
		}
		// sometimes the attribute is still in span, sometimes in p > textarea already
		$el = selector.find('span[data-id="'+attributeId+'"]');
		if ($el.length === 0) {
		    $el = selector.find('p[data-id="'+attributeId+'"]');
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
     * @param: attrValue comment initial value
     * @returns {Array}
     */
    renderNewCommentAttributes: function(attributeOriginType,attrValue) {
        return {
            attributeId:-1,
            name:'note',
            headerText:this.findTranslatedAttributeLabel('note',null),
            attrValue:attrValue ? attrValue : '',
            attrType:null,
            proposable:true,
            attributeOriginType:attributeOriginType
        };
    },
    

    /***
     * Find the label translation
     * @param labelName
     * @param labelType
     * @returns
     */
    findTranslatedAttributeLabel:function(labelName,labelType){
    	for(var i=0;i<attributeLabels.length;i++){
    		if(attributeLabels[i].label === labelName && attributeLabels[i].type === labelType){
    			return attributeLabels[i].labelText;
    		}
    	}
    	//if label type exist, return the label type, otherwize return the label name
    	return labelType ? labelType : labelName;
    },
    
    /***
     * Remove the complete attribute from the term attribute holder by given term attribute component editor
     */
    removeNewInputAttribute:function($componentEditor){
    	 //find the term holder and remove each unexisting comment attribute dom
        var $termHolder=$componentEditor.parents('div[data-term-id]');
        $termHolder.children('p[data-id="-1"]').remove();
        $termHolder.children('h4[data-attribute-id="-1"]').remove();
        $componentEditor.replaceWith('');
    },
    
    /***
     * Reset the comment attribute component to its initial state
     * @param {Object} $$componentEditor   = the textarea(component editor)
     * @param {Object} attributeData = the comment attribute data. If no comment attribute data is provided, the function will try to find it in the cache
     */
    resetCommentAttributeComponent:function($componentEditor,attributeData){
    	//if no attribute data is provided, try to find the data from the cache
    	if(!attributeData){
    		var $termHolder=$componentEditor.parents('div[data-term-id]'),
	    		termId=$termHolder.data('term-id'),
	    		termData=Term.getTermDataFromCache(Term.newTermTermEntryId,termId);
    		
    		for(var i=0;i<termData.attributes.length;i++){
    			var attribute=termData.attributes[i];
    			if(attribute.name=='note'){
    				attributeData=attribute;
    				break;
    			}
    		}
    	}
    	//if still no attribute data, it is a new comment -> remove the attribute
    	if(!attributeData){
    		this.removeNewInputAttribute($componentEditor);
    		return;
    	}
    	//the comment attribute data exist, render the attribute from the data
        var componentRenderData=Attribute.getAttributeRenderData(attributeData,attributeData.attrValue);
        $componentEditor.replaceWith(componentRenderData);
    },
    
    /***
     * Check if the given attribute is od type transac date
     */
    isDateAttribute:function(attribute){
    	return attribute && attribute.name=='date';
    },
    
    /***
     * Check if the given attribute is of type note (comment)
     */
    isNoteAttribute:function(attribute){
    	return attribute && attribute.name=='note';
    },
    
    /***
     * Check if the given attribute is for resposible person (the person name in the transac group)
     */
    isResponsiblePersonAttribute:function(attribute){
    	if(!attribute){
    		return false;
    	}
    	return attribute.name=='transacNote' && (attribute.attrType=='responsiblePerson' || attribute.attrType=='responsibility');
    },
    
    /***
     * Check if the given attribute is of type transac modification
     */
    isTransacModificationAttribute:function(attribute){
    	if(!attribute){
    		return false;
    	}
    	return attribute.name=='transac' && attribute.attrType=='modification';
    }
};

Attribute.init();
