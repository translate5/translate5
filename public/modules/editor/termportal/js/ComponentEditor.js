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

        if (!this.isCommmentAttributeRequirementMet()) {
            return false;
        }

        console.log('addTermComponentEditor');
		var me=this,
			$input= $('<textarea />').val($element.text());

		//add comment component editor
		me.onEditTermCommentComponentHandler($termAttributeHolder,$element.data('id') > 0);

		//reset the flag
		me.isNew=false;
		//copy the collection id from the attribute holder data to the term element data
		$element.attr("data-collection-id",$termAttributeHolder.data('collectionId'));

		$element.replaceWith($input);

        Term.drawProposalButtons('componentEditorOpened');

        me.addKeyboardShortcuts($element,$input);

        me.isComponentEditorActive();

        me.$_termTable.off( "mouseup", ".term-data.proposable .proposal-save");
        me.$_termTable.off( "mouseup", ".term-data.proposable .proposal-cancel");

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

        if (!this.isCommmentAttributeRequirementMet()) {
            return false;
        }

        console.log('addAttributeComponentEditor');
		var me=this,
			$input= $('<textarea />').val($element.text());

		//unregister the events since thay are registered below
		$input.off('change keyup keydown paste cut');
        me.$_termEntryAttributesTable.off('mouseup', '.proposal-save');
        me.$_termEntryAttributesTable.off('mouseup', '.proposal-cancel');
        me.$_termTable.off('mouseup', '.proposal-save');
        me.$_termTable.off('mouseup', '.proposal-cancel');

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
			$input= $('<textarea data-editable-comment />').val($element.text());


		$input.off('change keyup keydown paste cut');
		me.$_termTable.off('mouseup', '.term-attributes .proposal-save');
        me.$_termTable.off('mouseup', '.term-attributes .proposal-cancel');

		$input.on('change keyup keydown paste cut', function () {
			//resize the input when there is more than 50 characters in it
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
        	me.cancelCommentComponentChange($element,$input);
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
    	$input.val('');// this will force saveComponentChange() to stop the saving.
        this.saveComponentChange($element,$input);
    },

    /**
     * Cancel editing the comment component
     * @param {Object} $element = the original span[data-editable]
     * @param {Object} $input   = the textarea with the proposed content
     */
    cancelCommentComponentChange:function($element,$input){
    	var isFromTm=$element.text() == proposalTranslations['acceptedFromTmComment'];
    	//if it is proposalFrom tm, save the default comment text for tm proposal comment
    	if(isFromTm){
    		$input.val(proposalTranslations['acceptedFromTmComment']);
    		//reset the element value, so it is not ignored by cancel request
    		$element.val('');
    		$element.text('');
    	}else{
    		$input.val($element.text());
    	}
    	this.saveCommentChange($element,$input);
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
            isTerm=$el.data('type')=='term',
            isCancelRequest=me.stopRequest($el,$input);

        // if id is not provided, this is a proposal on empty term entry // TODO: is this comment correct? We can also create a new Term WITHIN an existing TermEntry!
        me.isNew = (!$el.data('id') == undefined || $el.data('id') < 1);

        //if the cancel component is for a term, cancel the comment component editor to
        if(isTerm && isCancelRequest){
        	me.closeCommentComponentEditor($el.data('id'));
        }

        Term.drawProposalButtons('componentEditorClosed');

        // don't send the request? then reset component only.
        if (!me.isNew && isCancelRequest){
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
        	//the new term proposal is canceled, reset all new proposal data and reload the term entry
        	var termEntryIdReload=Term.newTermTermEntryId;
        	//reset the instanttranslate termproposal flag
        	isTermProposalFromInstantTranslate=false;
            Term.resetNewTermData();
            //reload the term entry
            Term.findTermsAndAttributes(termEntryIdReload);
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
			console.log('saveComponentChange => newTermCollectionId: ' + Term.newTermCollectionId);
			requestData['languageId']      =Term.newTermLanguageId;
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
	        },
			 error: function (error) {
	        	console.log(error);
	        	console.log(requestData);
			 }
	    });
	},

	saveCommentChange:function($element,$input){
	    var me = this,
            $parent,
            url,
            requestData,
            isNewComment=$element.data('id')==-1,//is the comment attribute new or already exist for the term
            isNewCommentFromTm=$input.val()==proposalTranslations['acceptedFromTmComment'] && isNewComment;//if the comment is new autogenerated comment from tm, do not ignore the saveing


        // don't send the request? then update front-end only.
        if (me.stopRequest($element,$input) && !isNewCommentFromTm){

        	if (!me.isCommmentAttributeRequirementMet()) {
                return;
            }

            Term.drawProposalButtons('commentAttributeEditorClosed');

            //if it is a new term(in new term case the comment attribute does not exist in the db)
            //or if it is a new attribute
            if (me.isNew || $element.data('id')<1) {
            	//remove the comment attribute from the dom
                Attribute.removeNewInputAttribute($input);
                me.isComponentEditorActive();
                return;
            }

            //get the inital data for the comment attribute component
            var dummyData={
                    'attributeOriginType':$element.data('type'),
                    'attributeId':$element.data('id'),
                    'name':'note',
                    'headerText:':Attribute.findTranslatedAttributeLabel('note',null),
                    'attrValue':$element.text(),
                    'proposable':true
            };

            //the comment attribute exist, rerender the initial attribute data
            //reset the comment attribute editor with the inital data
            Attribute.resetCommentAttributeComponent($input,dummyData);
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
		    $elParent=null,
            $termAttributeHolder,
            activeTabSelector,
            activeTab;


		if(isTerm){
			renderData=Term.renderTermData(result);
			$elParent= Term.getTermHeader($element.data('id'));

			// (if necessary:) add language to select
			addLanguageToSelect(result.language, result.languageRfc5646);
			 // update term-data
	        $elParent.attr('data-term-value', result.term);
	        $elParent.attr('data-term-id', result.termId);
	        $elParent.attr('data-groupid', result.groupId);
	        $elParent.attr('data-termEntryId', result.termEntryId);

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
			$elParent.attr('data-attribute-id', result.attributeId);
		}

		$input.replaceWith(renderData);

		//reload the term entry data if term entry results are available
		if(result[TermEntry.KEY_TERM_ENTRY_ATTRIBUTES]){
			TermEntry.drawTermEntryAttributes(result[TermEntry.KEY_TERM_ENTRY_ATTRIBUTES]);
		}

        $elParent.removeClass('is-finalized').removeClass('is-new').addClass('is-proposal');
        $elParent.removeClass('in-editing');

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

		//reload the term attribute data
		Term.refreshTermAttributeContent(result);

		$termAttributeHolder = me.$_termTable.find('div[data-term-id="' + result.termId + '"]');
		//handle the term comment attribute
		me.onEditTermCommentComponentHandler($termAttributeHolder,true);

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
			Term.drawProposalButtons('terms');
		}

		Term.drawProposalButtons('attribute');

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
                if($element.data('editableComment')!=undefined){
                	me.cancelCommentComponentChange($element,$input);
                }else{
                	me.cancelComponentChange($element,$input);
                }
            }
        });
    },

    /***
     * Check and hide the InstantTranslate combo if the component editor is active
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
    	if(commentEditors.length>0){
    		commentEditors.focus();
    		return true;
    	}
		return false;
    },

    /**
     * Are the requirements for the comment-attribute met? If not, show a message and return false.
     * Otherwise return true.
     * @return bool
     */
    isCommmentAttributeRequirementMet: function() {
    	//if the config is not set to mendatory do not do the other validations
    	if(!Editor.data.apps.termportal.commentAttributeMandatory){
    		return true;
    	}
    	var me=this,
    		activeTerm=Term.findActiveTermHeader();

    	if(!activeTerm || activeTerm.length<1){
    		return true;
    	}

		var isProposal=activeTerm.hasClass('is-proposal'),
    		commentEditor=me.getTermCommentComponentEditor(activeTerm.attr("data-term-id"));

    	if(commentEditor.length<1){
    		return true;
    	}
    	var editorHolder=commentEditor.parent('p.isAttributeComment'),
    		isNewComment=editorHolder.data('id')==-1
        // If the comment attribute mandatory flag is set, check if there is unclosed comment editor.
        if (isProposal && isNewComment) {
            showInfoMessage(proposalTranslations['commentAttributeMandatoryMessage'], proposalTranslations['commentAttributeMandatoryTitle']);
            return false;
        }
        return true;
    },

    /***
     * Close coment component editor for given termId
     * The function will search for comment component editor within the term attributes
     */
    closeCommentComponentEditor:function(termId){
    	var me=this;
    	//check if there is an active component editor
    	if(!me.isCommentComponentEditorActive()){
    		return;
    	}
    	//find the term attribute holder and the comment component editor inside
    	var commentEditor=me.getTermCommentComponentEditor(termId);

    	if(commentEditor.length<1){
    		return;
		}
    	//reset the comment attribute
    	//this will remove(the comment attribute does not exist in the db) or rerender(the attribute exist in db) the comment attribute
    	Attribute.resetCommentAttributeComponent(commentEditor);
    },

    /***
     * Get the component editor of the comment for a term
     */
    getTermCommentComponentEditor:function(termId){
    	//find the term attribute holder and the comment component editor inside
    	var $termAttributeHolder = this.$_termTable.find('div[data-term-id="'+termId+ '"]');
    	return $termAttributeHolder.find('textarea[data-editable-comment]');
    },

    /***
     * Find/render and open the comment component editor in the term attribute holder area.
     */
    onEditTermCommentComponentHandler:function($termAttributeHolder,isExistingElement){
    	var me=this,
			$commentPanel=$termAttributeHolder.find('[data-editable-comment]');

		//check if it is new comment attribute
		if($commentPanel.length === 0 && isExistingElement){
			$commentPanel=$termAttributeHolder.find('.isAttributeComment');
		}

		//the comment field does not exist for the term, create new
		if($commentPanel.length === 0 && isExistingElement){
			var dummyCommentAttribute=Attribute.renderNewCommentAttributes('termAttribute'),
				drawData=Attribute.handleAttributeDrawData(dummyCommentAttribute),
				$instantTranslateComponent=$termAttributeHolder.find('div.instanttranslate-integration');

			//if the InstantTranslate component exist, add the comment editor always after it
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
    }
};

ComponentEditor.init();
