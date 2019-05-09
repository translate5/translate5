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
	  
		$el.replaceWith( $input );
	  
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
		
		requestData[dataKey]=$input.val();
		 $.ajax({
	        url: url,
	        dataType: "json",
	        type: "POST",
	    	data:{
	    		'data':JSON.stringify(requestData)
	    	},
	        success: function(result){
	        	console.log(result);
	        	me.updateComponent($el,$input,result.rows);
	        }
	    });
	},
	
	updateComponent:function($el,$input,result){
		var renderData=Attribute.getAttributeRenderData(result);
		//var $p = $el.text($input.val());
		$input.replaceWith(renderData);
	}
	
};

ComponentEditor.init();
