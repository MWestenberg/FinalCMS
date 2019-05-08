var pathArray = window.location.pathname.split('/'); // url
var parent = pathArray[1]; var child = pathArray[2];
var leftHandMargin = 300; 

(function($)
{
	$.extend( {
	    // scroll animation function
	    goToByScroll: function (id){
	       $('html,body').animate({scrollTop: $("#"+id).offset().top},'fast');
	       
	    },
	    getMessage: function() {
	    	return customMessages;
	    },
	    writeCookie: function()
	    {
	    	if ($.cookie('pixel_ratio') == null && window.devicePixelRatio>=2)
	    	{
	    		$.cookie('pixel_ratio', window.devicePixelRatio, { path: '/' });
	    	} 
	    },
	    //concat an object a with an object b
	    concat: function(a, b) {
            $.each(b, function(index, value) {
                if (a[index] !== undefined) {
                    if (!a[index.push]) {
                        a[index] = [a[index]];
                    }
                    a[index].push(value || '');
                } else {
                    a[index] = value || '';
                }
            });
            return a;
            
        },
        generateQuickGuid: function () {
            return Math.random().toString(36).substring(2, 15) +
                Math.random().toString(36).substring(2, 15);
        },
        resizePage: function(action) {
        		
			if (action=='close')
			{	 
					
				
				$("#closeMenu").hide();
				$("#lefthandMenu").children().hide("fast", function() {
					$("#main").animate({left:'10px'},50,function() {
					
						$("#lefthandMenu").animate({width:'10px'},50,function() {
							
							$("#closeMenu").animate({left:'15px'},50,function() {
								$("#closeMenu").show(50);						
							});
						
						});
					
					});
					
					

				});
				
				
				
			}
			else 
			{
				
				
				
				$("#closeMenu").hide();
			   			   
				$("#main").animate({left:leftHandMargin+'px'},50, function() {
				
					$("#lefthandMenu").animate({width:leftHandMargin+'px'},50, function() {
						$("#lefthandMenu").children().show("fast");
						
						$("#closeMenu").animate({left:(leftHandMargin+5)+'px'},50,function() {
							$("#closeMenu").show(50);
						
						});
						 
					});
				
				});
				
				
			}
    		
        }
        
       
        
       
	});
	
	$.fn.contextMenu = function(selectedItem,retVal,action,$element) {
	        
    	if (retVal!='')
		{
			
					
			$.ajax({
			  global: false,async:true,dataType: 'json',
			  type: 'POST',
			  url: "websitetree",
			  data: {'action' : action,'parentid':selectedItem.id.substr(1),'label':retVal,'level':selectedItem.level,'value':selectedItem.value},
			  success: function(data,status){
			  	if (status=='success' && data.result=='ok')
			  	{
			  		
			  		$('#jqxTree').jqxTree('addTo', { icon:data.json[0].icon, label: data.json[0].text,id:data.json[0].id},selectedItem.element);
			  		$('#jqxTree').jqxTree('render');
			  		$('#jqxTree').loadJqxTree($('#jqxTree'),"websitetree",{'action' : 'getPageTree'},'finalcms');
			  	}
			  	else {
			  		$('#jqxTree').jqxTree('render');
			  		$('#jqxTree').loadJqxTree($('#jqxTree'),"websitetree",{'action' : 'getPageTree'},'finalcms');
			  	}
			  
			  },
			  error: function(xhr,ajaxOptions) {
			  	//alert('error2');
			  	//console.debug(JSON.stringify(xhr));
			  }

			});
		}
    
    
    }
	
	$.fn.preload = function() {
	    this.each(function(){
	        $('<img/>')[0].src = this;
	    });
	}
	
	/* clear a form completely */
	$.fn.clearForm = function() {
	  return this.each(function() {
	    var type = this.type, tag = this.tagName.toLowerCase();
	    if (tag == 'form')
	      return $(':input',this).clearForm();
	    if (type == 'text' || type == 'password' || tag == 'textarea')
	      this.value = '';
	    else if (type == 'checkbox' || type == 'radio')
	      this.checked = false;
	    else if (tag == 'select')
	      this.selectedIndex = -1;
	  });
	};
	
	/* serialize an object like a form */
	$.fn.serializeObject = function()
	{
	    var o = {};
	    var a = this.serializeArray();
	    
	    $.each(a, function() {
	        if (o[this.name] !== undefined) {
	            if (!o[this.name].push) {
	                o[this.name] = [o[this.name]];
	            }
	            o[this.name].push(this.value || '');
	        } else {
	            o[this.name] = this.value || '';
	        }
	    });
	    return o;
	};
	
	/*
	* clears field when called and and removes the class grey
	*/
	
	$.fn.clearField = function() {
		if ($(this).val() == $(this).attr('title'))
		{
			$(this).val('');
			$(this).removeClass('grey');
			return false;
		}
		else if ($(this).attr('data-type')=='password')
		{
			$(this).get(0).type = 'password';	
		}
		return true;
			
	};
	
	
	
   $.fn.selectRange = function(start, end) {
      return this.each(function() {
        if (this.setSelectionRange) { this.setSelectionRange(start, end);
        } else if (this.createTextRange) {
            var range = this.createTextRange();
            range.collapse(true); 
            range.moveEnd('character', end); 
            range.moveStart('character', start); 
            range.select(); 
        }
      });
   };
	
	/*
	* Initializes field when called and sets the title text as text with class grey
	*/
	$.fn.initField = function() {
		if ($(this).val() == '')
		{
			$(this).val($(this).attr('title'));
			$(this).addClass('grey');
		}
				
	};
	
	/*
		serializes form and send's it to ajax file
		usage: 
				
		page: serverside page to load that returns json response.
		params: javascript object
		func: which function to load when call is succesful
		
		$("#myformID").ajaxForm('class.ajax.php',{'action':'bookOrder'},'loadMessage');
		
		A div #ajaxLoader is used to show progress. If non existent it will be created.
		
		This function uses $.fn.ajaxLoader()
		
	*/
	$.fn.ajaxForm = function(page,params,func,delay) {
		 //serialize the formobject
		var formval = $(this).serializeObject();
		
		if (params!='') { 
			// add extra params
			formval = jQuery.extend(formval, params);
		} 
		//alert(JSON.stringify(formval));
		//md5 all password values before sending to the server
		$.each(formval, function(key,val) {
			if (key.substr(0,8) == 'password')
			{
				if (val!=''){formval[key] = md5(val);}
			}		
		});
			
		//console.debug(JSON.stringify(formval));		
		page = "/"+parent+"/"+page;
		//console.debug(page);
			
		if (!$('#ajaxLoader').length){$(document.body).append('<div id="ajaxLoader"></div>');}
		var loader = $('#ajaxLoader');
		var intervalId = 0;var timeOutId = 0;
		
		$.ajax({
		      url: page, global: false,type: 'POST',data:formval,async:true,dataType: 'HTML',timeout: 30000,
		      beforeSend: function() {
		      	if (delay)
		      	{
		      		if (params.message)
		      		{
		      			loader.ajaxLoader(false,params.message,params.title);	
		      		}
		      		else 
		      		{
		      			loader.ajaxLoader('loader','');	
		      		}
		      	}
		      	timeOutId = setTimeout(function()
		      	{
		      		if (!delay)
		      		{
			      		if (params.message && params.title)
			      		{
			      			loader.ajaxLoader(false,params.message,params.title);	
			      		}
			      		else 
			      		{
			      			loader.ajaxLoader('loader','');	
			      		}
		      		}
		      		loader.append($.getMessage().ajaxForm.loading);
		      		intervalId = setInterval( function(){
		      			loader.append('.');
		      		},1000);
		      		
		      		
		      	},200);
		      	
		      },
		      complete: function() { clearInterval(intervalId);clearTimeout(timeOutId); },
		      success: function(msg){
		      	//console.debug(JSON.stringify(msg));
		      	clearInterval(intervalId);clearTimeout(timeOutId); 
		      	var response = jQuery.parseJSON(msg);
		      	
		      	if (!response || typeof(response)=='undefined')
		      	{
		      		clearInterval(intervalId);clearTimeout(timeOutId);
		      		loader.ajaxLoader('error',$.getMessage().ajaxForm.error);
		      	}
		      	else if (response.func)
		      	{
		      		eval(response.func)(response,loader);
		      	}
		      	else 
		      	{
		      		eval(func)(response,loader);	
		      	}
		      },
		      error: function(xhr,ajaxOptions) {loader.ajaxLoader('error',$.getMessage().ajaxForm.error+"<br /> "+xhr.statusText);}
		   });
		
	};
	
	
	/*
	*	function for creating a loading screen used for ajax calls in function $.fn.ajaxForm()
	*/
	$.fn.ajaxLoader = function (action,message) {
		$(this).dialog('destroy'); //destroy just to be sure
		//set html in current dialog
		$(this).dialog({ autoOpen: false,modal: true,hide: 'fade',position: ['center',200],closeOnEscape: false,resizable: false,dialogClass: "ajaxLoader"});
		
		//$(".ajaxLoader .ui-dialog-titlebar").css("background-color", "#000");
		//$(".ajaxLoader .ui-widget-header").css("display", "block");
		//$(".ajaxLoader .ui-widget-content").css("background-color", "#000");
		
		
		if (action == 'loader')
		{
			$(this).dialog("option","title",$.getMessage().ajaxLoader.loader.title);
			$(this).html($.getMessage().ajaxLoader.icon.circle + $.getMessage().ajaxLoader.loader.msg);
		}
		else if (action == 'error')
		{
			$(this).dialog("option","title",$.getMessage().ajaxLoader.error.title);
			$(this).dialog("option","buttons",[{ text: "Ok",click: function() { $(this).dialog('close');}}]);
			$(this).html(message);
		}
		else 
		{
			$(this).html($.getMessage().ajaxLoader.icon.circle);
			if (message!=''){$(this).append('<div style="width:100%;text-align:center">'+message+'</div>');}
		}
		//open the dialog
		$(this).dialog('open');
	};
	
	
	$.fn.imageRotate = function(angle) {
		$(this).rotate({
			angle:angle, 
			animateTo:720, 
			duration: 3000
		});	
	};
	
	
	$.fn.TableSearch = function(tblID){
			// When value of the input is not blank
		
		//alert(tblID);
		if( $(this).val() != "")
		{
			// Show only matching TR, hide rest of them
			$("."+tblID+" tbody>tr").hide();
			$("."+tblID+" td:contains-ci('" + $(this).val() + "')").parent("tr").show();
		}
		else
		{
			// When there is no input or clean again, show everything back
			$("."+tblID+" tbody>tr").show();
		}
	};
	
	$.extend($.expr[":"], 
	{
	    "contains-ci": function(elem, i, match, array) 
		{
			return (elem.textContent || elem.innerText || $(elem).text() || "").toLowerCase().indexOf((match[3] || "").toLowerCase()) >= 0;
		}
	});
	
	/*
	* validates the form and send its to the ajax file form.id with action form.id
	* form.id must be in userrights as lowercase and appointed to the appropriate file
	*/
	$.fn.sendToAjax = function(e) {
		
		e.preventDefault();
		$(".edit").each(function() {
			var text = $(this).attr('placeholder');
			if (text == $(this).val()) {$(this).val("");}	
		});
		
		var form = $(this).closest('form');
				
		if (form.validationEngine('validate'))
		{
			form.validationEngine('hideAll');
			var param = {'action':form.attr('id'), 'button': $(this).attr('id'),'message': form.attr('title')};
			form.ajaxForm(form.attr('id').toLowerCase(),param,false,true);
		}
	}
	
	
	/*
	* will load messages based on its action
	*/
	$.fn.showMessage = function(res) {
		
		$(this).dialog({ autoOpen: false,modal: true,hide:'fade',show:'fade',position: ['center',200],closeOnEscape: true,resizable: false});
		$(this).dialog("option","title",res.title);	
		
		if (res.action=='reload')
		{
			$(this).dialog("option","buttons",[{ text: $.getMessage().ok,click: function() { $(this).dialog('close');location.reload();}}]);
		}
		else if (res.action=='download')
		{
			$(this).dialog("option","buttons",[{ text: $.getMessage().close,click: function() { $(this).dialog('close');}},{ text: $.getMessage().download,click: function() { var newtab = window.open();newtab.location = res.location;}}    ]);
		}
		else if (res.action=='confirm')
		{
			$(this).dialog("option","buttons",[{ text: $.getMessage().yes,click: function() { $(this).dialog('close');$(res.formID).ajaxForm(res.page,res.params,res.func,true); }},{ text: $.getMessage().cancel,click: function() {  $(this).dialog('close');	 }}    ]);
		}
		else if (res.location)
		{
				$(this).dialog("option","buttons",[{ text: $.getMessage().ok,click: function() { $(this).dialog('close');location.href = res.location;}}]);
		}
		else if (res.action=='redirect')
		{
			$(this).dialog("option","buttons",[{ text: $.getMessage().ok,click: function() { $(this).dialog('close');eval(res.redirect)(res);}}]);
		}
		else 
		{
			$(this).dialog("option","buttons",[{ text: $.getMessage().ok,click: function() { $(this).dialog('close');}}]);
		}
		
		$(this).html(res.message);
		$(this).dialog('open');
		$('.ui-dialog :button').blur();
		$(".ui-dialog").focus();
			
	}
	
	/*
	* function called to load a message after an ajax call.
	* can be valid message or error message (valid will reload page)
	*/
	var loadMessage = function(res,loader) {
		loader.dialog("close");
		loader.dialog("destroy");
		$('#messageDialog').dialog('destroy'); //destroy just to be sure
		if (!$('#messageDialog').length){$(document.body).append('<div id="messageDialog"></div>');}
		
		$('#messageDialog').showMessage(res);
	};
	
	/*
	* used to refresh the page to @res.location 
	*/
	var refresh = function(res, loader) {
		location.href = res.location;
	};
	
	/*
	*	private functionss need to move these
	*/
	
	var downloadFile = function (res,loader) {
		
		$.ajax({
		  global: false,async:true,dataType: 'HTML',timeout: 30000,
		  type: 'POST',
		  url: "/stream/download.ajax.php",
		  data: {'action' : 'download','id' :did,'key': key},
		  success: function(msg){
		  	
		  	var response = jQuery.parseJSON(msg);showDownload(response);
		  	if (!response || typeof(response)=='undefined')
		  	{
		  		$('#dialog').html($.getMessage().ajaxForm.error + xhr.statusText);
		  		$('#dialog').dialog("option","buttons",[{ text: $.getMessage().ok,click: function() { $(this).dialog('close');}}]);
		  	}
		  
		  },
		  error: function(xhr,ajaxOptions) {
		  	$('#dialog').html($.getMessage().ajaxForm.error + xhr.statusText);
		  	$('#dialog').dialog("option","buttons",[{ text: $.getMessage().ok,click: function() { $(this).dialog('close');}}]);
		  }
		});
		
	}
	
	var updateElement = function (res,loader) {
		loader.dialog("close");
		$(res.element).html(res.html);	
		if (res.breadcrumbs!='')
		{
			if ($("#breadcrumbs-header").css('height')!='124px')
			{
				$("#breadcrumbs-header").animate( { height:"124px" }, { duration:500, complete: function(){$("#breadcrumbs").html(res.breadcrumbs);}});
			}
			else 
			{
				$("#breadcrumbs").html(res.breadcrumbs);	
			}
		}
		else 
		{
			$("#breadcrumbs").html(res.breadcrumbs);
			$("#breadcrumbs-header").animate( { height:"1px" }, { duration:500});
		}
		
		if (res.editUser)
		{
			if ($("#custCreateBut").length)
			{
				$("#custCreateBut").fadeOut('slow', function() {$("#userCreateBut").fadeIn("slow")});
				$("#userCreateBut > input").attr('id',res.editUser);
				
			}
			
		}
		
	}
	
	
	var loadPage = function (res,loader) {
		loader.dialog("close");
		$(res.target).html('');
		
		//console.debug(JSON.stringify(res));
		
		
		$(res.target).load(res.dialog, function() {
			if (res.formData)
			{
				
				$.each(res.formData[0], function (key, value) 
				{
					//alert(key);
					//check for each value if their is a form element and fill when valid
					
					if ($(res.target+" #"+key).is('img'))
					{
						//alert(value);	
						$(res.target+" #"+key).attr('src',value);
					}
					else if ($(res.target+' input[name="'+key+'"]').prop('type') =='radio') 
					{
						$(res.target+' input[name="'+key+'"]').filter('[value="'+value+'"]').attr('checked', true); //radio button
					}
					else if ($(res.target+' input[name="'+key+'"]').prop('type') =='checkbox') //checkbox
					{
						//checkbox button
						if (value==1)
						{
							$(res.target+' input[name="'+key+'"]').attr("checked", true);
						}
						else 
						{
							$(res.target+' input[name="'+key+'"]').attr("checked", false);
						}
					}
					else if (key=='fees' && value)
					{
						
						$.each(value, function (feekey,feevalue){
							
							var uniqueId = $.generateQuickGuid();
							
							$("#feeList").append('<li><fieldset><section><input type="text" name="feekey_'+uniqueId+'" id="feekey_'+uniqueId+'" class="edit validate[required]" value="'+feekey+'" placeholder="Fee name" /></section><input type="text" name="feevalue_'+uniqueId+'" id="feevalue_'+uniqueId+'" class="width-200px edit validate[custom[number]]" value="'+feevalue+'" placeholder="Value" /> <a href="" id="removeFee'+uniqueId+'" class="btn removeFee redBtn">X</a></fieldset></li>');
							
													
						});
					
					}
					else if (key=='regfee' && value)
					{
						
						var c= 0;
						
						$.each(value, function (feekey,feevalue){
							
							var uniqueId = $.generateQuickGuid();
							if (!c)
							{
								$("#regfeeList").append('<li><fieldset><section><input type="text" name="regkey_'+uniqueId+'" id="regkey_'+uniqueId+'" class="edit validate[required]" value="'+feekey+'" placeholder="Fee name" /></section><input type="text" name="regvalue_'+uniqueId+'" id="regvalue_'+uniqueId+'" class="width-200px edit validate[custom[number]]" value="'+feevalue+'" placeholder="Value" /></fieldset></li>');
							}
							else 
							{
								$("#regfeeList").append('<li><fieldset><section><input type="text" name="regkey_'+uniqueId+'" id="regkey_'+uniqueId+'" class="edit validate[required]" value="'+feekey+'" placeholder="Fee name" /></section><input type="text" name="regvalue_'+uniqueId+'" id="regvalue_'+uniqueId+'" class="width-200px edit validate[custom[number]]" value="'+feevalue+'" placeholder="Value" /> <a href="" id="removeFee'+uniqueId+'" class="btn removeFee redBtn">X</a></fieldset></li>');
							}
							
				
							c++;						
						});
					
					}
					else 
					{
						if (value!='')
						{	
							//<button  class="btn" onclick='ClickToSave()'>Save changes</button> <button class="btn">Reset to default</button>console.debug(key+':'+value+';');
							$(res.target+" #"+key).val(value); //normal text fields
							if (key=='brochure' && $("#thumb").length)
							{
								$("#thumb").attr('src', '/stream/streamPDFPreview.php?file='+value+'&type=event-brochures&action=thumb');
							}
							if (key=='preview_file' && $("#thumb").length)
							{
								$("#thumb").attr('src', '/stream/streamPDFPreview.php?file='+value+'&type=report-previews&action=thumb');
							}
						}
						else 
						{
							
							$(res.target+" #"+key).val('');//no value so clear
						}
						
						if ($(res.target+" #"+key).attr('readonly'))
						{
							//$('#dialogWindow '+res.formID+" #"+key).css({'border':'0'});
							
						}
					}
					
					
					
				});
			}
			
			if (res.disabledFields)
			{
				$.each(res.disabledFields, function (key, value) 
				{
					if ($(res.target+" #"+value).length)
					{
						$(res.target+" #"+value).prop('readonly',true);
						$(res.target+" #"+value).addClass('readOnly');
					}	
				
				});
			}
			
			
		});
	}
	
	var loadForm = function(res,loader) {
		loader.dialog("close");
		
		$(res.target).html('');
		$(res.target).load(res.dialog, function() {
			
			//console.debug(JSON.stringify(res));
			
			if (res.formData)
			{
				$.each(res.formData[0], function (key, value) 
				{
					
					//check for each value if their is a form element and fill when valid
					if ($(res.target+' input[name="'+key+'"]').prop('type') =='radio') 
					{
						$(res.target+' input[name="'+key+'"]').filter('[value="'+value+'"]').attr('checked', true); //radio button
					}
					else if ($(res.target+' input[name="'+key+'"]').prop('type') =='checkbox') //checkbox
					{
						//checkbox button
						if (value==1)
						{
							$(res.target+' input[name="'+key+'"]').attr("checked", true);
						}
						else 
						{
							$(res.target+' input[name="'+key+'"]').attr("checked", false);
						}
					}
					else 
					{
						if (value!='')
						{	
							
							$(res.target+" #"+key).val(value); //normal text fields
						}
						else 
						{
							$(res.target+" #"+key).val('');//no value so clear
						}
						
						if ($(res.target+" #"+key).attr('readonly'))
						{
							//$('#dialogWindow '+res.formID+" #"+key).css({'border':'0'});
							
						}
					}
				});
				
				$(res.target+' .formTitle').html(res.title);
			}
			
			/*
			Add toggle switch after each checkbox.  If checked, then toggle the switch.
			*/
			$('.fancy-checkbox').after(function(){
				if ($(this).is(":checked")) 
				{
					return "<a href='#' class='toggle checked' ref='"+$(this).attr("id")+"'></a>";
				}
				else
				{
					return "<a href='#' class='toggle' ref='"+$(this).attr("id")+"'></a>";
				}
				
			});
			
		});
		
		
		$(res.target).dialog({ autoOpen: true,modal: true,hide:'fade',position: ['center',100], width:"600px", closeOnEscape: true,resizable: false});
	
	}
	
	
	$(document).on("mouseover",".button",function(e){
		$(this).addClass('selected').css('cursor', 'pointer');	
	}).on("mouseout",".button",function(e){
		$(this).removeClass('selected');
	});
	
		
	var readDir = function(e) {
		if ($("#dialogWindow").dialog("isOpen")) {
		    $("#dialogWindow").dialog("close")
		}
		var t = {
		    action: "getDirList",
		    message: $.getMessage().ajaxLoader.loader.msg,
		    title: $.getMessage().ajaxLoader.loader.title,
		    dir: e.dir,
		    breadcrumbs: $("#breadcrumbs").html(),
		    rel: e.rel
		};
		e.form.ajaxForm("dirlist", t);
	}
	

	

	$(document).on("click",".close",function(e){
		e.preventDefault();
		$("#dialogWindow").dialog('close');
	});
	
	
	
	/*
	When the toggle switch is clicked, check off / de-select the associated checkbox
	*/
	$(document).on("click",'.toggle',function(e){
		var checkbox = $('#'+$(this).attr("ref"));
		if (checkbox.is(":checked")) 
		{
			checkbox.removeAttr("checked");
		}
		else
		{
			checkbox.attr("checked","true");
		}
		
		$(this).toggleClass("checked");
		e.preventDefault();
	});
	
	
	$(document).on("click",".send",function(e){
		$(this).sendToAjax(e);
	});
	
	
	$.fn.loadJqxTree = function(tree,page,params,theme) {
		
		if (!$('#ajaxLoader').length){$(document.body).append('<div id="ajaxLoader"></div>');}
		var loader = $('#ajaxLoader');
		loader.dialog({ autoOpen: false,modal: true,hide: 'fade',position: ['center',200],closeOnEscape: false,resizable: false,dialogClass: "ajaxLoader"});
		
		$.ajax({
			global: false,async:true,
			dataType: 'json',
			type: 'POST',
			url: "/"+parent+"/"+page,
			data: params,
			success: function(data,status){
			
				if (status=='success' && data.result=='ok')
				{
					//alert(data.json);
					
					// prepare the data
					var source =
					{
					datatype: "json",
					datafields: [
					{ name: 'id' },
					{ name: 'parentid' },
					{ name: 'text' },
					{ name: 'icon' },
					{ name: 'value' }
					],
					id: 'id',
					localdata: data.json
					};
					// create data adapter.
					var dataAdapter = new $.jqx.dataAdapter(source);
					// perform Data Binding.
					dataAdapter.dataBind();
					// get the tree items. The first parameter is the item's id. The second parameter is the parent item's id. The 'items' parameter represents 
					// the sub items collection name. Each jqxTree item has a 'label' property, but in the JSON data, we have a 'text' field. The last parameter 
					// specifies the mapping between the 'text' and 'label' fields.  
					var records = dataAdapter.getRecordsHierarchy('id', 'parentid', 'items', [{ name: 'text', map: 'label', icon: 'icon', level: 'level', value: 'value'}]);
					
					tree.jqxTree({ toggleMode: 'click',source: records, width: '250px',  theme: theme,allowDrag: true, allowDrop: true,
						dragEnd: function (item, dropItem, args, dropPosition, tree) {
							
							$.ajax({
							  global: false,async:true,dataType: 'json',
							  type: 'POST',
							  url: "/"+parent+"/"+page,
							  data: {'action' : 'moveItem','itemId':item.value,'dropPosition':dropPosition,'dropItemId':dropItem.value,'dropItemLevel':dropItem.level},
							  success: function(data,status)
							  {
							  	
							  	if (status=='success' && data.result=='ok')
							  	{
							  		$(this).loadJqxTree(tree,page,params,theme);//reload yourself
							  	}
							  	else 
							  	{
							  		$(this).loadJqxTree(tree,page,params,theme);//reload yourself
							  	}
							  
							  },
							  error: function(xhr,ajaxOptions) 
							  {
							  	$(this).loadJqxTree(tree,page,params,theme);//reload yourself
							  }
	
							});
						}
					
					
					});
					
					//define all menu's
					contextMenuItemAdd = $("#jqxMenuItemAdd").jqxMenu({ width: '120px', theme: theme, height: '25px', autoOpenPopup: false, mode: 'popup' });
					contextMenuItemAddRemove = $("#jqxMenuItemAddRemove").jqxMenu({ width: '120px', theme: theme, height: '86px', autoOpenPopup: false, mode: 'popup' });
					contextMenuAdd = $("#jqxMenuAdd").jqxMenu({ width: '120px', theme: theme, height: '43px', autoOpenPopup: false, mode: 'popup' });
					contextMenuAddRemove = $("#jqxMenuAddRemove").jqxMenu({ width: '120px', theme: theme, height: '43px', autoOpenPopup: false, mode: 'popup' });
					contextMenuRemove = $("#jqxMenuRemove").jqxMenu({ width: '120px', theme: theme, height: '43px', autoOpenPopup: false, mode: 'popup' });
					contextItemAdd = $("#jqxItemAdd").jqxMenu({ width: '120px', theme: theme, height: '43px', autoOpenPopup: false, mode: 'popup' });
					contextItemAddRemove = $("#jqxItemAddRemove").jqxMenu({ width: '120px', theme: theme, height: '43px', autoOpenPopup: false, mode: 'popup' });
					contextItemRemove = $("#jqxItemRemove").jqxMenu({ width: '120px', theme: theme, height: '43px',autoOpenPopup: false, mode: 'popup' });
					contextItemAddMenuRemove = $("#jqxItemAddMenuRemove").jqxMenu({ width: '120px', theme: theme, height: '84px',autoOpenPopup: false, mode: 'popup' });
					
					//disable contextuele menu in entire browser
					$(document).bind("contextmenu",function(e){
					   return false;
					});
					
					tree.live('initialized', function (event) {
					
						
					
					});
					//add the menu's
					tree.live('mousedown', function (event) {
					
						target = $(event.target).parents('li:first')[0];
						
						var item = $('#jqxTree').jqxTree('getItem', target);
						//console.debug(item.level);
						
						rightClick = isRightClick(event);
						
						if (rightClick && target != null) 
						{
							
							$(this).jqxTree('selectItem', target);
							scrollTop = $(window).scrollTop();
							scrollLeft = $(window).scrollLeft();
							if (item.level==0)
							{
								
								contextMenuAdd.jqxMenu('close');
								contextMenuRemove.jqxMenu('close');
								contextMenuAdd.jqxMenu('open', parseInt(event.clientX) + 5 + scrollLeft, parseInt(event.clientY) + 5 + scrollTop);
							}
							else if (item.level==1)
							{
								contextMenuAdd.jqxMenu('close');

								contextMenuRemove.jqxMenu('open', parseInt(event.clientX) + 5 + scrollLeft, parseInt(event.clientY) + 5 + scrollTop);
								//contextMenuItemAddRemove.jqxMenu('open', parseInt(event.clientX) + 5 + scrollLeft, parseInt(event.clientY) + 5 + scrollTop);
							}/*
							else if (item.level>=2 && item.id.substring(0,1)=='P')
							{
								contextMenuItemAddRemove.jqxMenu('close');
								contextMenuAdd.jqxMenu('close');
								contextItemRemove.jqxMenu('close');
								contextItemAddMenuRemove.jqxMenu('close');
								//contextMenuRemove('open', parseInt(event.clientX) + 5 + scrollLeft, parseInt(event.clientY) + 5 + scrollTop);
							}
							else if (item.level>=1 && item.id.substring(0,1)=='C')
							{
								contextMenuItemAddRemove.jqxMenu('close');
								contextMenuAdd.jqxMenu('close');	
								contextItemRemove.jqxMenu('close');
								contextItemAddMenuRemove.jqxMenu('close');
								contextItemRemove.jqxMenu('open', parseInt(event.clientX) + 5 + scrollLeft, parseInt(event.clientY) + 5 + scrollTop);
							}
							*/
							return false;
						}
					
					});
					
					
					
					tree.jqxTree('expandItem', $("li")[0]);
					tree.jqxTree('selectItem', $("li")[0]);
				}
			},
			
			error: function (xhr, status) {
			
				//alert(JSON.stringify(xhr));
			}
		});
		
		
		
		
	}
	
	
	
	
	$(document).on("click", "#editContent", function(e){ 
		e.preventDefault();
			
		var selectedItem = $("#jqxTree").jqxTree('selectedItem');
		var nextItem = $("#jqxTree").jqxTree('getNextItem', selectedItem.element);
		
		if (!selectedItem.isExpanded)
		{
			$("#jqxTree").jqxTree('expandItem', selectedItem.element);
		}
		
		
		if (selectedItem.hasItems)
		{
			var child = selectedItem.subtreeElement.firstChild;
			$("#jqxTree").jqxTree('selectItem', child);
		}
		
	
	
	
	
	});
	
	var refreshItem = function(res) {
	
		var selectedItem = $("#jqxTree").jqxTree('selectedItem');
		$("#jqxTree").jqxTree('selectItem', selectedItem);
		$('#jqxTree').jqxTree('refresh');
	
	};
	
	var refreshThumbnail = function(res,loader) {
		loader.dialog("close");
		loader.dialog("destroy");
		if ($("#thumb").length)
		{
			$("#thumb").attr('src', '/stream/streamPDFPreview.php?file='+res.thumb+'&type='+res.type+'&action=thumb');
		}
	}
	
	$(document).on("click", ".gotoPage",function(e) {
	
		e.preventDefault();
		var selectedItem = $("#jqxTree").jqxTree('selectedItem');
		$("#jqxTree").jqxTree('selectItem', selectedItem.parentElement);
		
	});
	
	
	$(document).on("click", ".saveContent",function(e) {
		e.preventDefault();
		var data = $("#contentwrapper").html();   
	   var params = {'action':'saveItem', 'message': $.getMessage().ajaxLoader.loader.msg,'title':$.getMessage().ajaxLoader.loader.title,
			'itemID':$("#itemID").val(), 'data' : data
		};
		$(this).ajaxForm('websitetree',params,true);
	});
	
	
	$(document).on("click", ".resetContent", function(e) {
		e.preventDefault();
		if (confirm($.getMessage().resetContent))
		{
			
			var params = {'action':'resetItem', 'message': $.getMessage().ajaxLoader.loader.msg,'title':$.getMessage().ajaxLoader.loader.title,
				'itemID':$("#itemID").val()};
			$(this).ajaxForm('websitetree',params,true);
		}
	
	});
	
	$(document).on("click", ".removeBtn", function(e) {
		e.preventDefault();
		if (confirm($.getMessage().removeItem))
		{
			
			$(this).sendToAjax(e);
		}
	
	});
	
	$(document).on("click", ".removeFee", function(e) {
		e.preventDefault();
		if (confirm($.getMessage().removeItem))
		{
			
			$(this).parents('li').remove();
		}
	
	});
	
	
	$(document).on("click","#closeMenu",function(e) {
		e.preventDefault();
			 
		
		if (parseInt($("#main").css('left'))< leftHandMargin)
		{
	
			$.resizePage('open');
		}
		else
	    {

	       $.resizePage('close');
	   	}
	
			
			
	});
	
	
	$(document).on("click","#addFee",function(e) {
		e.preventDefault();
		
		var uniqueId = $.generateQuickGuid();
		
		$("#feeList").append('<li><fieldset><section><input type="text" name="feekey_'+uniqueId+'" id="feekey_'+uniqueId+'" class="edit validate[required]" value="" placeholder="Fee name" /></section><input type="text" name="feevalue_'+uniqueId+'" id="feevalue_'+uniqueId+'" class="width-200px edit validate[custom[number]]" value="" placeholder="Value" /> <a href="" id="removeFee'+uniqueId+'" class="btn removeFee redBtn">X</a></fieldset></li>');

	});
	
	
	$(document).on("click","#addRegFee",function(e) {
			e.preventDefault();
			
			var uniqueId = $.generateQuickGuid();
			
			$("#regfeeList").append('<li><fieldset><section><input type="text" name="regkey_'+uniqueId+'" id="regkey_'+uniqueId+'" class="edit validate[required]" value="" placeholder="Fee name" /></section><input type="text" name="regvalue_'+uniqueId+'" id="regvalue_'+uniqueId+'" class="width-200px edit validate[custom[number]]" value="" placeholder="Value" /> <a href="" id="removeFee'+uniqueId+'" class="btn removeFee redBtn">X</a></fieldset></li>');
	
		});
	
	$(document).on("change","#brochure",function(e) {
		if ($("#thumb").length)
		{
			$("#thumb").attr('src', '/stream/streamPDFPreview.php?file='+$(this).val()+'&type=event-brochures&action=thumb');
		}
	});
	
	$(document).on("change","#preview_file",function(e) {
		
		if ($("#thumb").length)
		{
			$("#thumb").attr('src', '/stream/streamPDFPreview.php?file='+$(this).val()+'&type=report-previews&action=thumb');
		}
	});
	
	$(document).on("click","#refreshThumb",function(e) {
		e.preventDefault();
		
		if ($("#brochure").length)
		{
			var file = $("#brochure").val();
			var action = 'eventform';
		}
		else if ($("#preview_file").length)
		{
			var file = $("#preview_file").val();
			var action = 'reportform';
		}
		
		if (file)
		{
			var param = {'action':'refreshThumb', 'file': file};
			//console.debug(param);
			$("#refreshThumb").ajaxForm(action,param,false,true);	
		}
		
		
			
	});
	
})(jQuery);




$(document).ready( function() {
	
		
	$(document).keypress(function(e) {
	  if(e.which == 13) {
	    e.preventDefault();
	    if ($(".send").length)
	    {
	    	$(".send").click();
	    }
	    
	    
	  }
	});
	
	$('input, textarea').placeholder();
	
	
	$(".fileManager").click(function(e) {
		e.preventDefault();
		
		$(this).parent().parent().children().removeClass('selected');
		$(this).parent().addClass('selected');
		
		if (!$('#kcfinder_div').length){$("#main").append('<div id="kcfinder_div"></div>');}
		$(".pageMargin").hide();
		
		if ($(this).prop('id')=='browseImages')
		{
		 	var browseType = 'images';
		}
		else 
		{
			var browseType = 'files';	
		}
		
		target = $('#kcfinder_div');
		  
		target.html('<iframe name="kcfinder_iframe" id="kcfinder_iframe" src="/plugins/kcfinder-2.51/browse.php?type='+browseType+'" frameborder="0" width="100%" height="100%" marginwidth="0" marginheight="0" scrolling="no" />');
		target.css('display','block');
		
		
		//$("#main .pageMargin").load('https://dev.finalmedia.nl/plugins/kcfinder-2.51/browse.php?type=images&CKEditor=column3&CKEditorFuncNum=728&langCode=nl');
		
	});
	
	
	

	
	$("#search").keyup(function() {
		$(this).TableSearch("searchTable");	
	});
/*	
	
	if ($("#search").length)
	{
		$("#search").TableSearch("searchTable");
	}
	
*/	
	
	
	$("#report-year").change(function(e) {
		location.href  = '?year='+$("#report-year option:selected").val()
	});
	
	$(".loginform").show("drop", { direction: "up" }, 1500);
	$(".resetform").hide().show("fade", "slow");
	
	$(".search").click(function(e) {
		
		e.preventDefault();
		var form = $(this).closest('form');
		var param = {'action':form.attr('id'), 'message': form.attr('title'),'title':$.getMessage().ajaxLoader.loader.title, 'rel':$(this).attr('rel')};
		form.ajaxForm(form.attr('id').toLowerCase(),param);
	});
	
	/* click eventHandler: reset button */
	$(".reset").click(function(e) {
		e.preventDefault();
		var form = $(this).closest('form');
		form.validationEngine('hideAll');
		form.clearForm();
		$(".edit").each(function() {
			$(this).initField();		
		});
		return false;
	});
	
	$("#signout").click(function(e) {
		e.preventDefault();
		var params = {'action':$(this).attr('id'), 'message': $.getMessage().signout.msg};
		$(this).ajaxForm('signout',params,false,true);
		
	});
	
	$("#dialog").keypress(function(e) {
	  if(e.which == 13) {
	    $("#dialog .send").click();
	  }
	});
	
	$(".closeButton").click(function(e) {
		e.preventDefault();
		var form = $(this).closest('form');
		form.validationEngine('hideAll');
		$("#"+$(this).parent().attr('id')).dialog('close');
	});
	
	/* transform captcha code entered to uppercase */	
	$("#user_code").keydown(function(event) {
		$(this).css({'text-transform':'uppercase'});
		if (event.keyCode == 32){ event.preventDefault();}
		
		if ((event.keyCode < 48 || event.keyCode > 57) && (event.keyCode < 96 || event.keyCode > 105 )) {
			// do nothing		  	 
		} 
		else {
			event.preventDefault();
		}
		
	});
	
	
	/*  click eventHandler: captcha code refresh */
	$("#refresh").click(function(e) {
		e.preventDefault();
		d = new Date();
		$("#captcha").attr("src", "/stream/visual-captcha.php?"+d.getTime());
								
	});
	
		
	if ($(".wheel").length)
	{
		$(".wheel").imageRotate();
	}
	
	
	var placeholder = 'placeholder' in document.createElement('input');  
	if (!placeholder) { 
		$(":input").each(function(){   // this will work for all input fields
			if ($(this).val=='')
			{
				$(this).placeHolder();
			}
		});
	}
	
	
	
	
	$(['/images/finalcms/ajax-loader.gif']).preload();
	
	
	$("#reportManagement tbody tr, #eventManagement tbody tr").click(function(e) {
		var form = $(this).closest('form');
		if ($(this).attr('id')!==undefined)
		{	
			var param = {'action':form.attr('id'), 'id': $(this).attr('id'), 'message': form.attr('title')};
			form.ajaxForm(form.attr('id').toLowerCase(),param,false,true);
		}
	});
	
		
	$(window).resize(function() {
	
	
		if ($(window).width()<1050)
		{
			if (parseInt($("#main").css('left')) >= leftHandMargin)
			{
				$.resizePage('close');
			}
		}
		else 
		{
			if (parseInt($("#main").css('left')) < leftHandMargin)
			{
				$.resizePage('open');	
			}
		}
		
	
	});
	
	
	if ($(window).width()<1050)
	{
		if (parseInt($("#main").css('left')) >= leftHandMargin)
		{
			$.resizePage('close');
		}
	}
	else 
	{
		if (parseInt($("#main").css('left')) < leftHandMargin)
		{
			$.resizePage('open');	
		}
	}
		
		
	
		
});



	