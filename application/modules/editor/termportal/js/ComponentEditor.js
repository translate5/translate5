const ComponentEditor={
    
    $_resultTermsHolder:null,
	
	typeRouteMap:[],
	
	typeRequestDataKeyMap:[],
	
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
			var dummyCommentAttribute={
					attributeId:-1,
					name:'note',
					attrValue:'',
					attrType:null,
					headerText:'',
					proposable:true
			},
			drawData=Attribute.handleAttributeDrawData(dummyCommentAttribute);
			
			$termAttributeHolder.prepend(drawData);
			$commentPanel=$termAttributeHolder.find('[data-editable-comment]');
		}
		
		if($commentPanel.prop('tagName')=='SPAN'){
			me.addCommentAttributeEditor($commentPanel);
		}
		
		$element.replaceWith($input);
	  
		$input.one('blur', function(){
			me.saveComponentChange($element,$input);
		}).focus();
	},
	
	/***
	 * Register the component editor for given term or termentry attribute
	 */
	addAttributeComponentEditor:function($element){
        console.log('addAttributeComponentEditor');
		var me=this,
			$input= $('<textarea />').val($element.text());
		
		$element.replaceWith($input);
		$input.one('blur', function(){
			me.saveComponentChange($element,$input);
		}).focus();
	},
	
	/***
	 * Register component editor for term comment
	 */
	addCommentAttributeEditor:function($element){
        console.log('addCommentAttributeEditor');
		var me=this,
			$input= $('<textarea data-editable-comment />').val($element.text());
		
		$element.replaceWith($input);
		
		$input.focusout(function() {
			me.saveCommentChange($element,$input);
	    });
	},
	
	saveComponentChange:function($el,$input){
        console.log('saveComponentChange');
        var me=this,
            route,
            dataKey,
            url,
            requestData={};
        
        // if id is not provided, this is a proposal on empty term entry // TODO: is this comment correct? We can also create a new Term WITHIN an existing TermEntry!
        isNew = (!$el.data('id') == undefined || $el.data('id') < 1); 
        
        // if not new: 
        if (!isNew) {
            // is the modified text empty or the same as the initial one?
            if($input.val()=='' || $.trim($input.val())=='' || $el.text()==$input.val()){
                
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
        }
		
		route=me.typeRouteMap[$el.data('type')];
		dataKey=me.typeRequestDataKeyMap[$el.data('type')];
		url=Editor.data.termportal.restPath+route.replace("{ID}",$el.data('id'));
		
		requestData[dataKey]=$input.val();
		
		if(isNew){
			url=Editor.data.termportal.restPath+'term',
			requestData={};
			requestData['collectionId']  =Term.newTermCollectionId;
			requestData['language']      =Term.newTermLanguageId;
            requestData['termEntryId']   =Term.newTermTermEntryId;
			requestData[dataKey]=$input.val();
			
	        console.log('saveComponentChange (isNew):');
	        console.log('- requestData: ' + JSON.stringify(requestData));
		}
		
		
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
		//is the modefied text empty or the same as the initial one
		if($input.val()=='' || $.trim($input.val())=='' || $element.text()==$input.val()){

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
			return;
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
	        	me.updateComponentComment($element,$input,result.rows);
	        }
	    });
	},
	
	/***
	 * Update component html with the proposed result. The editor component also will be destroyed. 
	 */
	updateComponent:function($element,$input,result){
		var isTerm=$element.data('type')=='term',
			renderData=isTerm ? Term.getTermRenderData(result) : Attribute.getAttributeRenderData(result,result.value),
			$elParent=isTerm ?  Term.getTermHeader($element.data('id')) : Attribute.getTermAttributeHeader($element.data('id'),$element.data('type'));
		
		$input.replaceWith(renderData);
		$elParent.switchClass('is-finalized','is-proposal');
        Term.drawProposalButtons($elParent);
		//on the next term click, fatch the data from the server, and update the cache
		Term.reloadTermEntry=true;
		
		if(!isTerm){
			return;
		}
		
		//if it is comment, and the comment panel does not exist, add the comment panel after the proposed term is saved
		var $commentPanel=$elParent.find('[data-editable-comment]');
		
		//the comment field does not exist for the term, create new
		if($commentPanel.length==0){
			var me=this,
				dummyCommentAttribute={
					attributeId:-1,
					name:'note',
					attrValue:'',
					attrType:null,
					headerText:'',
					proposable:true
			},
			drawData=Attribute.handleAttributeDrawData(dummyCommentAttribute),
			$termAttributeHolder=$('#termTable').find('div[data-term-id=-1]');//find the parent term holder (not saved term with termid -1)
			
			//update the term holder dom with the new temr id
			$termAttributeHolder.attr("data-term-id",result.termId);
			
			//attach the comment attribute draw data to the term holder
			$termAttributeHolder.prepend(drawData);

			//find the comment panel and start the comment editor
			$commentPanel=$termAttributeHolder.find('[data-editable-comment]');
			if($commentPanel.length>0 && $commentPanel.prop('tagName')=='SPAN'){
				this.addCommentAttributeEditor($commentPanel);
			}
		}
	},
	
	/***
	 * Update comment component in the table results
	 */
	updateComponentComment:function($el,$input,result){
		$input.replaceWith(Attribute.getProposalDefaultHtml(result.attributeOriginType,result.id,result.value,result));
		//on the next term click, fatch the data from the server, and update the cache
		Term.reloadTermEntry=true;
	}
};

ComponentEditor.init();
