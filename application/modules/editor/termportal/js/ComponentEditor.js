const ComponentEditor={
		
	$_termTable:null,
	$_termAttributeTable:null,
	$_commentEditor:null,
	
	typeRouteMap:[],
	
	typeRequestDataKeyMap:[],
	
	init:function(){
		var me=this;
		
		me.cacheDom();
		me.initEvents();
		
		me.typeRouteMap['term']='term/{ID}/propose/operation';
		me.typeRouteMap['termEntryAttribute']='';//TODO:
		me.typeRouteMap['termAttribute']='termattribute/{ID}/propose/operation';
		
		me.typeRequestDataKeyMap['term']='term';//TODO:
		me.typeRequestDataKeyMap['termEntryAttribute']='value';//TODO:
		me.typeRequestDataKeyMap['termAttribute']='value';//TODO:
	},
	
	cacheDom:function(){
		var me=this;
		me.$_termTable=$('#termTable');
		me.$_termAttributeTable=$('#termAttributeTable');
		me.$_commentEditor=$('<input />')
	},
	
	initEvents:function(){
		var me=this;
		me.$_termTable.on('click', '[data-editable]',{scope:me},me.onEditableComponentClick);
		me.$_termAttributeTable.on('click', '[data-editable]',{scope:me},me.onEditableComponentClick);
		me.$_termTable.on('click', 'span[data-editable-comment]',{scope:me},me.onEditableCommentComponentClick);
	},
	
	onEditableCommentComponentClick:function(event){
		var me=event.data.scope,
			$el = $(this);
		me.addCommentAttributeEditor($el);
	},
	
	onEditableComponentClick:function(event){
		var me=event.data.scope,
			$el = $(this);
		me.addComponentEditor($el);
	},
	
	addComponentEditor:function($element){
		var me=this,
			$input= $('<textarea />').val($element.text()),
			$parent=me.$_termTable.find('[data-term-id="' + $element.data('id') + '"]'),
			$commentPanel=$parent.find('[data-editable-comment]');
		
			//$input = $('<input type="text" style="min-width: 150px;" onkeyup="this.size = Math.max(this.value.length, 1)"/>').val($element.text());
	  
		//the comment field does not exist for the term, create new
		if($commentPanel.length==0){
			var dummyCommentAttribute={
					attributeId:-1,
					name:'note',
					attrValue:'',
					attrType:null,
					headerText:'',
					proposable:true
			},
			drawData=Attribute.handleAttributeDrawData(dummyCommentAttribute);
			
			$parent.prepend(drawData);
			$commentPanel=$parent.find('[data-editable-comment]');
		}
		
		if($commentPanel.prop('tagName')=='SPAN'){
			me.addCommentAttributeEditor($commentPanel);
		}
		
		$element.replaceWith($input);
	  
		$input.one('blur', function(){
			me.saveComponentChange($element,$input);
		}).focus();
	},
	
	addCommentAttributeEditor:function($element){
		var me=this,
			$input= $('<textarea data-editable-comment />').val($element.text());
		
		$element.replaceWith($input);
		
		$input.focusout(function() {
			me.saveCommentChange($element,$input);
	    });
	},
	
	saveComponentChange:function($el,$input){
		//is the modefied text empty or the same as the initial one
		if($input.val()=='' || $el.text()==$input.val()){
			
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
		
		var me=this,
			route=me.typeRouteMap[$el.data('type')],
			dataKey=me.typeRequestDataKeyMap[$el.data('type')],
			url=Editor.data.termportal.restPath+route.replace("{ID}",$el.data('id')),
			requestData={};
		
		requestData[dataKey]=$input.val();
		
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
		if($input.val()=='' || $element.text()==$input.val()){
			
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
	updateComponent:function($el,$input,result){
		var renderData='';
		if($el.data('type')=='term'){
			renderData=Term.getTermRenderData(result);
		}else{
			renderData=Attribute.getAttributeRenderData(result,result.value);
		}
		$input.replaceWith(renderData);
		//on the next term click, fatch the data from the server, and update the cache
		Term.reloadTermEntry=true;
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
