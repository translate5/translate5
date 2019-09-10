var ComponentEditor={
    
    $_resultTermsHolder:null,
    $_termTable:null,
    $_termEntryAttributesTable:null,
    
	typeRouteMap:[],
	
	typeRequestDataKeyMap:[],
	
	isNew: false,
	
	init:function(){
		var me=this;
		
		me.typeRouteMap['term']='term/{ID}/propose/operation';
		me.typeRouteMap['termEntryAttribute']='termattribute/{ID}/propose/operation';
		me.typeRouteMap['termAttribute']='termattribute/{ID}/propose/operation';
		
		me.typeRequestDataKeyMap['term']='term';
		me.typeRequestDataKeyMap['termEntryAttribute']='value';
		me.typeRequestDataKeyMap['termAttribute']='value';
        
		me.cacheDom();
		
		if(!Editor.data.app.user.isTermProposalAllowed){
			return;
		}
		me.initEvents();
	},
    
    cacheDom:function(){
        this.$_resultTermsHolder=$('#resultTermsHolder');
        this.$_termTable=$('#termTable');
        this.$_termEntryAttributesTable=$('#termEntryAttributesTable');
    },
    
    initEvents:function(){
        var me = this;
        me.$_resultTermsHolder.on('focus', ".term-data.proposable.is-new textarea",{scope:me},me.onAddNewTermFocus);
    },
    
    onAddNewTermFocus: function() {
        if ($(this).val().indexOf(proposalTranslations['addTermProposal']) !== -1) {
            $(this).val('');
        }
    },
	
	/***
	 * Register term component editor for given term element
	 */
	addTermComponentEditor:function($element,$termAttributeHolder){
		if(!Editor.data.app.user.isTermProposalAllowed){
			return;
		}
		
		//show info message if the comment attribute mandatory flag is set and the comment component editor is active
    	if(Editor.data.apps.termportal.commentAttributeMandatory && this.isCommentComponentEditorActive()){
			showInfoMessage(proposalTranslations['commentAttributeMandatoryMessage'],proposalTranslations['commentAttributeMandatoryTitle']);
			return false;
    	}
    	
        console.log('addTermComponentEditor');
		var me=this,
			$input= $('<textarea />').val($element.text()),
			$commentPanel=$termAttributeHolder.find('[data-editable-comment]');
		
		//reset the flag
		me.isNew=false;
		
		//check if it is new comment attribute
		if($commentPanel.length === 0 && $element.data('id') > 0){
			$commentPanel=$termAttributeHolder.find('.isAttributeComment');
		}
		
		//copy the collection id from the attribute holder data to the term element data
		$element.attr("data-collection-id",$termAttributeHolder.data('collectionId'));
		
		//the comment field does not exist for the term, create new
		if($commentPanel.length === 0 && $element.data('id') > 0){
			var dummyCommentAttribute=Attribute.renderNewCommentAttributes('termAttribute'),
				drawData=Attribute.handleAttributeDrawData(dummyCommentAttribute),
				$instantTranslateComponent=$termAttributeHolder.find('div.instanttranslate-integration');
			
			//if the instant translate component exist, add the comment editor always after it
			if($instantTranslateComponent.length>0){
				$instantTranslateComponent.after(drawData);
			}else{
				$termAttributeHolder.prepend(drawData);
			}
			
			$commentPanel=$termAttributeHolder.find('[data-editable-comment]');
		}
		
		if($commentPanel.prop('tagName') === 'SPAN'){
			me.addCommentAttributeEditor($commentPanel);
		}
		
		$element.replaceWith($input);
        
        Term.drawProposalButtons('componentEditorOpened');
        
        me.addKeyboardShortcuts($element,$input);
        
        me.isComponentEditorActive();
        
        me.$_termTable.one('mouseup', '.term-data.proposable .proposal-save',function() {
            me.saveComponentChange($element,$input);
        });
        me.$_termTable.one('mouseup', '.term-data.proposable .proposal-cancel',function() {
            me.cancelComponentChange($element,$input);
        });
        
        $input.focus();
    },
	
	/***
	 * Register the component editor for given term or termentry attribute
	 * and return the component.
	 * @returns {Object}
	 */
	addAttributeComponentEditor:function($element){
		if(!Editor.data.app.user.isTermProposalAllowed){
			return;
		}
		
		//if the comment attribute mandatory flag is set, check if there is unclosed comment editor,
    	if(Editor.data.apps.termportal.commentAttributeMandatory && ComponentEditor.isCommentComponentEditorActive()){
			showInfoMessage(proposalTranslations['commentAttributeMandatoryMessage'],proposalTranslations['commentAttributeMandatoryTitle']);
			return false;
    	}
    	
        console.log('addAttributeComponentEditor');
		var me=this,
			$input= $('<textarea />').val($element.text());
		
		
		$input.on('change keyup keydown paste cut', function () {
			//resite the input when there is more than 50 characters in it
			if($(this).val().length>50){
				$(this).height(150);
				$(this).width(350);
			}
	    }).change();
		
		$element.replaceWith($input);
        
        me.addKeyboardShortcuts($element,$input);
        
        me.isComponentEditorActive();
        
        //the attibute can be term attribute or term entry attribute
        //register the event listeners for both tables because of the deffinition
        me.$_termEntryAttributesTable.one('mouseup', '.proposal-save',function() {
            me.saveComponentChange($element,$input);
        });
        me.$_termEntryAttributesTable.one('mouseup', '.proposal-cancel',function() {
    		me.cancelComponentChange($element,$input);
        });
        me.$_termTable.one('mouseup', '.proposal-save',function() {
            me.saveComponentChange($element,$input);
        });
        me.$_termTable.one('mouseup', '.proposal-cancel',function() {
    		me.cancelComponentChange($element,$input);
        });
        
        $input.focus();
        return $input;
	},
	
	/***
	 * Register component editor for term comment
     * and return the component.
     * @returns {Object}
	 */
	addCommentAttributeEditor:function($element){
		if(!Editor.data.app.user.isTermProposalAllowed){
			return;
		}
        console.log('addCommentAttributeEditor');
		var me=this,
			$input= $('<textarea data-editable-comment />').val($element.text()),
			isFromTm=$element.text() == proposalTranslations['acceptedFromTmComment'];
		
		
		$input.on('change keyup keydown paste cut', function () {
			//resite the input when there is more than 50 characters in it
			if($(this).val().length>50){
				$(this).height(150);
				$(this).width(350);
			}
	    }).change();
		
		$element.replaceWith($input);
        
        me.addKeyboardShortcuts($element,$input);

        me.isComponentEditorActive();
        
        me.$_termTable.on('mouseup', '.term-attributes .proposal-save',function() {
            me.saveCommentChange($element,$input);
        });
        me.$_termTable.on('mouseup', '.term-attributes .proposal-cancel',function() {
        	//if it is proposalFrom tm, save the default comment text for tm proposal comment
        	isFromTm ? $input.val(proposalTranslations['acceptedFromTmComment']) : $input.val(''); 
        	me.saveCommentChange($element,$input);
        });
        $input.focus();
		return $input;
	},
    
	/**
     * Cancel editing the component; don't save the changes.
     * @param {Object} $element = the original span[data-editable]
     * @param {Object} $input   = the textarea with the proposed content
     */
    cancelComponentChange:function($element,$input){
        $input.val(''); // this will force saveComponentChange() to stop the saving.
        this.saveComponentChange($element,$input);
    },

    
    /**
     * Save the proposed changes (+ close the editor, update buttons etc).
     * @param {Object} $element = the original span[data-editable]
     * @param {Object} $input   = the textarea with the proposed content
     */
	saveComponentChange:function($el,$input){
        console.log('saveComponentChange');
        var me=this,
            route,
            dataKey,
            url,
            requestData={},
            isTerm=$el.data('type')=='term';
        
        Term.drawProposalButtons('componentEditorClosed');
        
        // if id is not provided, this is a proposal on empty term entry // TODO: is this comment correct? We can also create a new Term WITHIN an existing TermEntry!
        me.isNew = (!$el.data('id') == undefined || $el.data('id') < 1); 
        
        // don't send the request? then reset component only.
        if (!me.isNew && me.stopRequest($el,$input)){
            //get initial html for the component
            var dummyData={
                    'attributeOriginType':$el.data('type'),
                    'attributeId':$el.data('id'),
                    'proposable':true
            },
            componentRenderData=Attribute.getAttributeRenderData(dummyData,$el.text());

            $input.replaceWith(componentRenderData);
            me.isComponentEditorActive();
            return;
        }
        
        //check if the new term request should be canceled (empty value)
        if($input.val() === '' || $.trim($input.val()) === ''){
        	Term.newTermLanguageId=null;
        	Term.newTermRfcLanguage=null;
    		Term.findTermsAndAttributes(Term.newTermGroupId);
    		return;
        }
		
		route=me.typeRouteMap[$el.data('type')];
		dataKey=me.typeRequestDataKeyMap[$el.data('type')];
		url=Editor.data.termportal.restPath+route.replace("{ID}",$el.data('id'));
		
		requestData[dataKey]=$input.val();
		
		if(me.isNew && isTerm){
			url=Editor.data.termportal.restPath+'term';
			requestData={};
			requestData['collectionId']  =Term.newTermCollectionId;
			console.log('saveComponentChange => newTermCollectionId: ' + me.newTermCollectionId);
			requestData['language']      =Term.newTermLanguageId;
            requestData['termEntryId']   =Term.newTermTermEntryId;
			requestData[dataKey]=$input.val();
			requestData['isTermProposalFromInstantTranslate']=isTermProposalFromInstantTranslate;
		}
        
        if (Term.$_searchWarningNewSource.is(":visible")) {
            requestData['termSource']=instanttranslate.textSource;
            requestData['termSourceLanguage']=instanttranslate.langSource;
        }
        
        console.log('saveComponentChange :' + JSON.stringify(requestData));
		
		//send proposal request
		 $.ajax({
	        url: url,
	        dataType: "json",
	        type: "POST",
	    	data:{
	    		'data':JSON.stringify(requestData)
	    	},
	        success: function(result){
	        	me.updateComponent($el,$input,result.rows);
	        }
	    });
	},
	
	saveCommentChange:function($element,$input){
	    var me = this,
            $termHolder,
            $parent,
            url,
            requestData;

	    
    	//if the comment panel is mandatory, display the info message
		if($input.val()=='' && Editor.data.apps.termportal.commentAttributeMandatory){
			var dialog=showInfoMessage(proposalTranslations['commentAttributeMandatoryMessage'],proposalTranslations['commentAttributeMandatoryTitle']);
			dialog.on('dialogclose', function(event) {
				$input.focus();
			});
			return;
		}
		
        // don't send the request? then update front-end only.
        if (me.stopRequest($element,$input)){
            
            Term.drawProposalButtons('commentAttributeEditorClosed');
            
            if (me.isNew || $element.data('id')<1) {
                //find the term holder and remove each unexisting comment attribute dom
                $termHolder=$input.parents('div[data-term-id]');
                $termHolder.children('p[data-id="-1"]').remove();
                $termHolder.children('h4[data-attribute-id="-1"]').remove();
                $input.replaceWith('');
                me.isComponentEditorActive();
                return;
            }

            //get initial html for the component
            var dummyData={
                    'attributeOriginType':$element.data('type'),
                    'attributeId':$element.data('id'),
                    'name':'note',
                    'headerText:':Attribute.findTranslatedAttributeLabel('note',null),
                    'proposable':true
            },
            componentRenderData=Attribute.getAttributeRenderData(dummyData,$element.text());

            $input.replaceWith(componentRenderData);
            me.isComponentEditorActive();
            return;
		}

		$parent=$input.parents('div[data-term-id]');//get the parrent div container and find the termid from there
		url=Editor.data.termportal.restPath+'term/{ID}/comment/operation'.replace("{ID}",$parent.data('term-id'));
		requestData={};
		
		requestData['comment']=$input.val();
		
		$.ajax({
	        url: url,
	        dataType: "json",	
	        type: "POST",
	    	data:{
	    		'data':JSON.stringify(requestData)
	    	},
	        success: function(result){
	            me.updateComponent($element,$input,result.rows);
	        }
	    });
	},
	
	/***
	 * Update component html with the proposed result. The editor component also will be destroyed. 
	 */
	updateComponent:function($element,$input,result){
		var me = this,
			attrType=$element.data('type'),
		    isTerm=attrType=='term',
		    renderData=null,
		    attributeRenderData=null,
		    $elParent=null,
            $commentPanel,
            dummyCommentAttribute,
            drawData='',
            $termAttributeHolder,
            instantTranslateInto,
            activeTabSelector,
            activeTab;
			
		
		if(isTerm){
			renderData=Term.renderTermData(result);
			$elParent= Term.getTermHeader($element.data('id'));
			
			// (if necessary:) add language to select
			addLanguageToSelect(result.language, result.languageRfc5646);

			//for the new term, term attribute render data is required
			if (me.isNew) {
				var termRflLang=(result.attributes && result.attributes[0].language!=undefined) ? result.attributes[0].language : '';
				attributeRenderData=Attribute.renderTermAttributes(result,termRflLang);
			}
			//reload the term entry data when new term is created
			if(result[TermEntry.KEY_TERM_ENTRY_ATTRIBUTES]){
				TermEntry.drawTermEntryAttributes(result[TermEntry.KEY_TERM_ENTRY_ATTRIBUTES]);
			}
			
		}else{
			renderData=Attribute.getAttributeRenderData(result,result.value);
			
			//if the type is definition, update the definition attribute in the current selected tab
			if(result.attrType === 'definition'){
                activeTabSelector=$("#resultTermsHolder ul>.ui-tabs-active").attr('aria-controls');
        		activeTab= $('#'+activeTabSelector);

    			//the selected tab is the term tab
            	attrType=activeTab.data('type');
        		
			}
			$elParent=Attribute.getTermAttributeHeader($element.data('id'),attrType);
		}
            
        // update term-data
        $elParent.attr('data-term-value', result.term);
        $elParent.attr('data-term-id', result.termId);
        $elParent.attr('data-groupid', result.groupId);
        
        if(!isTerm){
        	$elParent.attr('data-attribute-id', result.attributeId);
        }
        
        $elParent.removeClass('is-finalized').removeClass('is-new').addClass('is-proposal');
        $elParent.removeClass('in-editing');
        $input.replaceWith(renderData);
        Term.drawProposalButtons($elParent);
        
        me.isComponentEditorActive();
        
        //on the next term click, fatch the data from the server, and update the cache
		Term.reloadTermEntry=true;
		
		if(!isTerm){
		    // (= we come from editing an attribute, not a term)
			//check and update if the attribute is deffinition
			Attribute.checkAndUpdateDeffinition(result);
			return;
		}
		$termAttributeHolder = me.$_termTable.find('div[data-term-id="' + result.termId + '"]');
		//if it is comment, and the comment panel does not exist, add the comment panel after the proposed term is saved
		$commentPanel=$termAttributeHolder.find('[data-editable-comment]');
		//the comment field does not exist for the term, create new
		if($commentPanel.length === 0){
			
			$termAttributeHolder=me.$_termTable.find('div[data-term-id=-1]');//find the parent term holder (not saved term with termid -1)
			instantTranslateInto=Term.renderInstantTranslateIntegrationForTerm(result.language);
			
			//update the term holder dom with the new term id
			$termAttributeHolder.attr("data-term-id",result.termId);
			$termAttributeHolder.attr("data-groupid",result.groupId);
			
			//for the new term, render the attributes to
			if(me.isNew && attributeRenderData && attributeRenderData!=''){
				drawData+=attributeRenderData;
	        }

			//attach the comment attribute draw data to the term holder
			$termAttributeHolder.prepend(drawData);
			
			$commentPanel=$termAttributeHolder.find('.isAttributeComment');
			if($commentPanel.length === 0){
				var commentValue=isTermProposalFromInstantTranslate ? proposalTranslations['acceptedFromTmComment'] : null;

				dummyCommentAttribute=Attribute.renderNewCommentAttributes('termAttribute',commentValue);
				
				$termAttributeHolder.prepend(Attribute.handleAttributeDrawData(dummyCommentAttribute));
			}
			

			//render the instant translate into select
			if(instantTranslateInto){
				$termAttributeHolder.prepend(instantTranslateInto);
				Term.initInstantTranslateSelect();
			}
			
			//find the comment panel and start the comment editor
            // (for existing terms, the comment editor is started by clicking it)
			if (me.isNew) {
				//reset the data for the propose term component
				//only the term specific data is reset, the other data is loaded from the newly saved term
				Term.newTermName=proposalTranslations['addTermProposal'] + '...';
				Term.newTermRfcLanguage=null;
				Term.newTermLanguageId=null;
				Term.newTermAttributes=[];
				Term.newTermCollectionId=result.collectionId;
				Term.newTermGroupId=result.groupId;
				Term.newTermTermEntryId=result.termEntryId;
				
				me.$_termTable.prepend(Term.renderNewTermSkeleton(result));
				me.$_termTable.accordion('refresh');
				
				Term.drawProposalButtons('attribute');
				Term.drawProposalButtons('terms');
            
	            $commentPanel=$termAttributeHolder.find('[data-editable-comment]');
	            if($commentPanel.length>0 && $commentPanel.prop('tagName') === 'SPAN'){
	                me.addCommentAttributeEditor($commentPanel);
	            }
			}
		}
		
        // "reset", is valid only once (= when coming from TermPortal)
        if(isTermProposalFromInstantTranslate) {
            isTermProposalFromInstantTranslate = false;
        }
    },
	
    /**
     * Is the request to be send?
     * Don't send the request if the modified text is
     * - empty 
     * or 
     * - the same as the initial one (= does not apply to proposals that use a prefilled term-string, e.g. from InstantTranslate or after empty search)
     * @returns {Boolean}
     */
    stopRequest: function($el,$input) {
        if($input.val() === '' || $.trim($input.val()) === '') {
            return true;
        }
        if ($el.text() === $input.val()) {
            if (Term.$_searchWarningNewSource.is(":hidden") && Term.$_searchErrorNoResults.is(":hidden")) {
                return true;
            }
        }
        return false;
    },
    
    /**
     * Add keyboard-shortcuts for the component.
     * @param {Object} $element = the original span[data-editable]
     * @param {Object} $input   = the textarea with the proposed content
     */
    addKeyboardShortcuts: function($element, $input) {
        var me = this;
        $input.keydown(function(e){
        	if (e.ctrlKey && e.which === 83) { // CTRL+S
                event.preventDefault();
                if($element.data('editableComment')!=undefined){
                	me.saveCommentChange($element,$input);
                }else{
                	me.saveComponentChange($element,$input);
                }
            }
            if (e.which === 27) {              // ESCAPE
                event.preventDefault();
                me.cancelComponentChange($element,$input);
            }
        });
    },
    
    /***
     * Check and hide the instant translate combo if the component editor is active
     */
    isComponentEditorActive:function(){
    	var me=this,
    		editorExist=me.$_termTable.find('textarea').length>0,
    		translateToCombos=me.$_termTable.find('.instanttranslate-integration');
		translateToCombos.each(function(index,cmp){
			editorExist ? $(cmp).hide() :$(cmp).show(); 
		});
    },
    
    /***
     * Check if the comment component editor is active
     */
    isCommentComponentEditorActive:function(){
    	var commentEditors=this.$_termTable.find('textarea[data-editable-comment]');
		return commentEditors.length>0;
    }
};

ComponentEditor.init();
