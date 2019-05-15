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
		me.typeRouteMap['termAttribute']='';//TODO:
		
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
	},
	
	onEditableComponentClick:function(event){
		var me=event.data.scope,
			$el = $(this);
		
		me.addComponentEditor($el);
	},
	
	addComponentEditor:function($element){
		var me=this,
			$input= $('<textarea />').val($element.text());
			//$input = $('<input type="text" style="min-width: 150px;" onkeyup="this.size = Math.max(this.value.length, 1)"/>').val($element.text());
	  
		$element.replaceWith($input);
	  
		$input.focus();
		
		$input.one('blur', function(){
			me.saveComponentChange($element,$input);
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
