const ComponentEditor={
		
	$_termTable:null,
	$_termAttributeTable:null,
	
	typeRouteMap:[],
	
	typeRequestDataKeyMap:[],
	
	init:function(){
		var me=this;
		
		me.cacheDom();
		me.initEvents();
		
		me.typeRouteMap['term']='term/{ID}/propose/operation';
		me.typeRouteMap['termEntryAttribute']='';//TODO:
		me.typeRouteMap['termAttribute']='';//TODO:
		
		me.typeRequestDataKeyMap['term']='term';//TODO:
		me.typeRequestDataKeyMap['termEntryAttribute']='value';//TODO:
		me.typeRequestDataKeyMap['termAttribute']='value';//TODO:
	},
	
	cacheDom:function(){
		this.$_termTable=$('#termTable');
		this.$_termAttributeTable=$('#termAttributeTable');
	},
	
	initEvents:function(){
		var me=this;
		me.$_termTable.on('click', '[data-editable]',{scope:me},me.addComponentEditor);
		me.$_termAttributeTable.on('click', '[data-editable]',{scope:me},me.addComponentEditor);
	},
	
	addComponentEditor:function(event){
		var me=event.data.scope,
			$el = $(this),
			$input = $('<input/>').val($el.text());
	  
		$el.replaceWith($input);
	  
		$input.one('blur', function(){
			me.saveComponentChange($el,$input);
		}).focus();
	},
	
	saveComponentChange:function($el,$input){
		var me=this,
			route=me.typeRouteMap[$el.data('type')],
			dataKey=me.typeRequestDataKeyMap[$el.data('type')],
			url=Editor.data.termportal.restPath+route.replace("{ID}",$el.data('id')),
			requestData={};
		
		//is the modefied text empty or the same as the initial one
		if($input.val()=='' || $el.text()==$input.val()){
			
			//get initial html for the component
			var dummyData={
					'attributeOriginType':$el.data('type'),
					'attributeId':$el.data('id')
			},
			componentRenderData=Attribute.getAttributeRenderData(dummyData,$el.text());
			$input.replaceWith(componentRenderData);
			return;
		}
		
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
	
	/***
	 * Update component html with the proposed result. The editor component also will be destroyed. 
	 */
	updateComponent:function($el,$input,result){
		var renderData='';
		if($el.data('type')=='term'){
			renderData=Term.getTermRenderData(result);
		}else{
			renderData=Attribute.getAttributeRenderData(result);
		}
		$input.replaceWith(renderData);
		//on the next term click, fatch the data from the server, and update the cache
		Term.reloadTermEntry=true;
	}
};

ComponentEditor.init();
