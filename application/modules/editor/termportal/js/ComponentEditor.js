const ComponentEditor={
    
    $_resultTermsHolder:null,
    $_termTable:null,
	
	typeRouteMap:[],
	
	typeRequestDataKeyMap:[],
	
	isNew: false,
	
	init:function(){
		var me=this;
		
		me.typeRouteMap['term']='term/{ID}/propose/operation';
		me.typeRouteMap['termEntryAttribute']='termattribute/{ID}/propose/operation';
		me.typeRouteMap['termAttribute']='termattribute/{ID}/propose/operation';
		
		me.typeRequestDataKeyMap['term']='term';//TODO:
		me.typeRequestDataKeyMap['termEntryAttribute']='value';//TODO:
		me.typeRequestDataKeyMap['termAttribute']='value';//TODO:
        
        this.cacheDom();
        this.initEvents();
	},
    
    cacheDom:function(){
        this.$_resultTermsHolder=$('#resultTermsHolder');
        this.$_termTable=$('#termTable');
    },
    
    initEvents:function(){
        var me = this;
        me.$_resultTermsHolder.on('focus', ".term-data.proposable.is-new textarea",{scope:me},me.onAddNewTermFocus);
    },
    
    onAddNewTermFocus: function(event) {
        var me = event.data.scope,
            $element = $(this);
        if ($(this).val().indexOf(proposalTranslations['addTermProposal']) != -1) {
            $(this).val('');
        }
    },
	
	/***
	 * Register term component editor for given term element
	 */
	addTermComponentEditor:function($element,$termAttributeHolder){
        console.log('addTermComponentEditor');
		var me=this,
			$input= $('<textarea />').val($element.text()),
			$commentPanel=$termAttributeHolder.find('[data-editable-comment]');
		
		//copy the collection id from the attribute holder data to the term element data
		$element.attr("data-collection-id",$termAttributeHolder.data('collectionId'));
		
		//the comment field does not exist for the term, create new
		if($commentPanel.length==0 && $element.data('id')>0){
			var dummyCommentAttribute=Attribute.renderNewCommentAttributes('termAttribute'),
			drawData=Attribute.handleAttributeDrawData(dummyCommentAttribute);
			
			$termAttributeHolder.prepend(drawData);
			$commentPanel=$termAttributeHolder.find('[data-editable-comment]');
		}
		
		if($commentPanel.prop('tagName')=='SPAN'){
			me.addCommentAttributeEditor($commentPanel);
		}
		
		$element.replaceWith($input);
        
        Term.drawProposalButtons('componentEditorOpened');
	  
		$input.one('blur', function(){
			me.saveComponentChange($element,$input);
		}).focus();
	},
	
	/***
	 * Register the component editor for given term or termentry attribute
	 * and return the component.
	 * @returns {Object}
	 */
	addAttributeComponentEditor:function($element){
        console.log('addAttributeComponentEditor');
		var me=this,
			$input= $('<textarea />').val($element.text());
		
		$element.replaceWith($input);
        
		$input.one('blur', function(){
			me.saveComponentChange($element,$input);
		});
        
        return $input;
	},
	
	/***
	 * Register component editor for term comment
     * and return the component.
     * @returns {Object}
	 */
	addCommentAttributeEditor:function($element){
        console.log('addCommentAttributeEditor');
		var me=this,
			$input= $('<textarea data-editable-comment />').val($element.text());
		
		$element.replaceWith($input);
        
		$input.focusout(function() {
			me.saveCommentChange($element,$input);
	    });
		
		return $input;
	},
	
	saveComponentChange:function($el,$input){
        console.log('saveComponentChange');
        var me=this,
            route,
            dataKey,
            url,
            requestData={};
        
        Term.drawProposalButtons('componentEditorClosed');
        
        // if id is not provided, this is a proposal on empty term entry // TODO: is this comment correct? We can also create a new Term WITHIN an existing TermEntry!
        me.isNew = (!$el.data('id') == undefined || $el.data('id') < 1); 
        
        // don't send the request? then reset component only.
        if (me.stopRequest($el,$input)){
            //get initial html for the component
            var dummyData={
                    'attributeOriginType':$el.data('type'),
                    'attributeId':$el.data('id'),
                    'proposable':true
            },
            componentRenderData=Attribute.getAttributeRenderData(dummyData,$el.text());

            $input.replaceWith(componentRenderData);
            return;
        }
		
		route=me.typeRouteMap[$el.data('type')];
		dataKey=me.typeRequestDataKeyMap[$el.data('type')];
		url=Editor.data.termportal.restPath+route.replace("{ID}",$el.data('id'));
		
		requestData[dataKey]=$input.val();
		
		if(me.isNew){
			url=Editor.data.termportal.restPath+'term',
			requestData={};
			requestData['collectionId']  =Term.newTermCollectionId;
			console.log('saveComponentChange => newTermCollectionId: ' + me.newTermCollectionId);
			requestData['language']      =Term.newTermLanguageId;
            requestData['termEntryId']   =Term.newTermTermEntryId;
			requestData[dataKey]=$input.val();
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
            groupId;

        // don't send the request? then update front-end only.
        if (me.stopRequest($element,$input)){
            
            Term.drawProposalButtons('commentAttributeEditorClosed');
            
            if (me.isNew) {
                // We completely render the front-end new to have a clear reset.
                // Otherwise things get buggy; too much work.
                groupId = $input.closest('.term-attributes').prev('.term-data').data('groupid');
                TermEntry.reloadTermEntry(groupId);
                return;
            }

            //when the comment does not exist, clean the editor 
            if($element.data('id')<1){
                //find the term holder and remove each unexisting comment attribute dom
                $termHolder=$input.parents('div[data-term-id]');
                $termHolder.children('p[data-id="-1"]').remove();
                $termHolder.children('h4[data-attribute-id="-1"]').remove();
                $input.replaceWith('');
                return;
            }

            
            //get initial html for the component
            var dummyData={
                    'attributeOriginType':$element.data('type'),
                    'attributeId':$element.data('id'),
                    'name':'note',
                    'proposable':true
            },
            componentRenderData=Attribute.getAttributeRenderData(dummyData,$element.text());

            $input.replaceWith(componentRenderData);
		}

		var me=this,
			$parent=$input.parents('div[data-term-id]'),//get the parrent div container and find the termid from there
			url=Editor.data.termportal.restPath+'term/{ID}/comment/operation'.replace("{ID}",$parent.data('term-id')),
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
		    isTerm=$element.data('type')=='term',
			renderData=isTerm ? Term.renderTermData(result) : Attribute.getAttributeRenderData(result,result.value),
			$elParent=isTerm ?  Term.getTermHeader($element.data('id')) : Attribute.getTermAttributeHeader($element.data('id'),$element.data('type')),
            $commentPanel,
            dummyCommentAttribute;
            
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
        
        //on the next term click, fatch the data from the server, and update the cache
		Term.reloadTermEntry=true;
		
		if(!isTerm){
		    // (= we come from editing an attribute, not a term)
			return;
		}
        
		//if it is comment, and the comment panel does not exist, add the comment panel after the proposed term is saved
		$commentPanel=$elParent.find('[data-editable-comment]');
		
		//the comment field does not exist for the term, create new
		if($commentPanel.length==0){
			dummyCommentAttribute=Attribute.renderNewCommentAttributes('termAttribute'),
			drawData=Attribute.handleAttributeDrawData(dummyCommentAttribute),
			$termAttributeHolder=me.$_termTable.find('div[data-term-id=-1]');//find the parent term holder (not saved term with termid -1)
			
			//update the term holder dom with the new temr id
			$termAttributeHolder.attr("data-term-id",result.termId);
			$termAttributeHolder.attr("data-groupid",result.groupId);
			
			//attach the comment attribute draw data to the term holder
			$termAttributeHolder.prepend(drawData);

            //find the comment panel and start the comment editor
            // (for existing terms, the comment editor is started by clicking it)
			if (me.isNew) {
	            $commentPanel=$termAttributeHolder.find('[data-editable-comment]');
	            if($commentPanel.length>0 && $commentPanel.prop('tagName')=='SPAN'){
	                this.addCommentAttributeEditor($commentPanel);
	            }
			}
		}
    },
	
	/***
	 * Update comment component in the table results.
	 */
	updateComponentComment:function($el,$input,result){
	    var me = this,
	        groupId;
	    
        console.log('updateComponentComment for isNew='+me.isNew);
        
        if (me.isNew) {
            // We render the front-end new to have a clear reset.
            // Otherwise things get buggy; too much work.
            groupId = $input.closest('.term-attributes').prev('.term-data').data('groupid');
            TermEntry.reloadTermEntry(groupId);
            return;
        }
        
		$input.replaceWith(Attribute.getProposalDefaultHtml(result.attributeOriginType,result.id,result.value,result));
		//on the next term click, fatch the data from the server, and update the cache
		Term.reloadTermEntry=true;
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
        if($input.val()=='' || $.trim($input.val())=='') {
            return true;
        }
        if ($el.text()==$input.val()) {
            if (Term.$_searchWarningNewSource.is(":hidden") && Term.$_searchErrorNoResults.is(":hidden")) {
                return true;
            }
        }
        return false;
    }
};

ComponentEditor.init();
