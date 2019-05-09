const ComponentEditor={
		
	$_termTable:null,
	$_termAttributeTable:null,
	
	init:function(){
		this.cacheDom();
		this.initEvents();
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
			var $p = $el.text($input.val());
			$input.replaceWith($p);
			me.saveComponentChange($el.data('id'),$el.data('type'),$input.val());
		}).focus();
	},
	
	saveComponentChange:function(id,type,value){
		//TODO: ajax request to the neede api
		console.log("ajax request",id,type,value);
	}
};

ComponentEditor.init();
