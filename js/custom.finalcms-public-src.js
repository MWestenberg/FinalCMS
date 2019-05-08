var pathArray = window.location.pathname.split('/'); // url
var parent = pathArray[1]; var child = pathArray[2]; var grandchild = pathArray[3];

(function($)
{
	$.extend({goToByScroll:function(e){$("html,body").animate({scrollTop:$("#"+e).offset().top},"fast")},getMessage:function(){return customMessages},resizeIframes:function(){$("iframe").each(function(value){var width=$(this).attr('width')+'px';var height=$(this).attr('height')+'px';$(this).css({'width':width,'height':height})})},writeCookie:function(){if($.cookie("pixel_ratio")==null&&window.devicePixelRatio>=2){$.cookie("pixel_ratio",window.devicePixelRatio,{path:"/"})}},concat:function(e,t){$.each(t,function(t,n){if(e[t]!==undefined){if(!e[t.push]){e[t]=[e[t]]}e[t].push(n||"")}else{e[t]=n||""}});return e}});$.fn.preload=function(){this.each(function(){$("<img/>")[0].src=this})};$.fn.clearForm=function(){return this.each(function(){var e=this.type,t=this.tagName.toLowerCase();if(t=="form")return $(":input",this).clearForm();if(e=="text"||e=="password"||t=="textarea")this.value="";else if(e=="checkbox"||e=="radio")this.checked=false;else if(t=="select")this.selectedIndex=-1})};$.fn.serializeObject=function(){var e={};var t=this.serializeArray();$.each(t,function(){if(e[this.name]!==undefined){if(!e[this.name].push){e[this.name]=[e[this.name]]}e[this.name].push(this.value||"")}else{e[this.name]=this.value||""}});return e};$.fn.clearField=function(){if($(this).val()==$(this).attr("title")){$(this).val("");$(this).removeClass("grey");return false}return true};$.fn.ajaxForm=function(page,params,func){var formval=$(this).serializeObject();if(params!=""){formval=jQuery.extend(formval,params)}$.each(formval,function(e,t){if(e.substr(0,8)=="password"){formval[e]=md5(t)}});if(!$("#ajaxLoader").length){$(document.body).append('<div id="ajaxLoader"></div>')}var loader=$("#ajaxLoader");var intervalId=0;var timeOutId=0;$.ajax({url:"/"+page,global:false,type:"POST",data:formval,async:true,dataType:"HTML",timeout:3e4,beforeSend:function(){if(params.message){loader.ajaxLoader(false,params.message,params.title)}else{loader.ajaxLoader("loader","")}timeOutId=setTimeout(function(){loader.append($.getMessage().ajaxForm.loading);intervalId=setInterval(function(){loader.append(".")},1e3)},3e3)},complete:function(){clearInterval(intervalId);clearTimeout(timeOutId)},success:function(msg){clearInterval(intervalId);clearTimeout(timeOutId);var response=jQuery.parseJSON(msg);if(!response||typeof response=="undefined"){clearInterval(intervalId);clearTimeout(timeOutId);loader.ajaxLoader("error",$.getMessage().ajaxForm.error)}else if(response.func){eval(response.func)(response,loader)}else{eval(func)(response,loader)}},error:function(e,t){loader.ajaxLoader("error",$.getMessage().ajaxForm.error+"<br />"+e.statusText)}})};$.fn.ajaxLoader=function(e,t){$(this).dialog("destroy");$(this).dialog({autoOpen:false,modal:true,hide:"fade",position:["center",200],closeOnEscape:false,resizable:false,dialogClass:"ajaxLoader"});if(e=="loader"){$(this).dialog("option","title",$.getMessage().ajaxLoader.loader.title);$(this).html($.getMessage().ajaxLoader.icon.circle+$.getMessage().ajaxLoader.loader.msg)}else if(e=="error"){$(this).dialog("option","title",$.getMessage().ajaxLoader.error.title);$(this).dialog("option","buttons",[{text:"Ok",click:function(){$(this).dialog("close")}}]);$(this).html(t)}else{$(this).html($.getMessage().ajaxLoader.icon.circle);if(t!=""){$(this).append('<div style="width:100%;text-align:center">'+t+"</div>")}}$(this).dialog("open")};$.fn.showMessage=function(e){$(this).dialog({autoOpen:false,modal:true,width:"450px",hide:"fade",position:["center",200],closeOnEscape:false,resizable:false});$(this).dialog("option","title",e.title);if(e.action=="reload"){$(this).dialog("option","buttons",[{text:$.getMessage().ok,click:function(){$(this).dialog("close");location.reload()}}])}else if(e.action=="download"){$(this).dialog("option","buttons",[{text:$.getMessage().close,click:function(){$(this).dialog("close")}},{text:$.getMessage().download,click:function(){var t=window.open();t.location=e.location}}])}else if(e.location){$(this).dialog("option","buttons",[{text:$.getMessage().ok,click:function(){$(this).dialog("close");location.href=e.location}}])}else{$(this).dialog("option","buttons",[{text:$.getMessage().ok,click:function(){$(this).dialog("close")}}])}$(this).html(e.message);$(this).dialog("open")};var loadMessage=function(e,t){t.dialog("close");$("#messageDialog").dialog("destroy");if(!$("#messageDialog").length){$(document.body).append('<div id="messageDialog"></div>')}$("#messageDialog").showMessage(e)};var refresh=function(e,t){location.href=e.location}
	
	var loadForm = function(res,loader) {
		loader.dialog("close");
		
		if (!$('#'+res.target).length){$(document.body).append('<div id="'+res.target+'"></div>');}
		$('#'+res.target).html('');
		$('#'+res.target).load(res.dialog, function() {
			
			//console.debug(JSON.stringify(res));
			
			if (res.formData)
			{
				$.each(res.formData[0], function (key, value) 
				{
					
					//console.debug(key+' '+$('#dialogWindow #'+key).prop('tagName')+': '+value);
					if ($('#'+res.target+' #'+key).prop('tagName')=='SPAN' || $('#'+res.target+' #'+key).prop('tagName')=='P')
					{
						//alert(value);
						if ($.trim(value)!='')
						{
							$('#'+res.target+' #'+key).html(value);
						}
						
						
					}
					else if ($('#'+res.target+'input[name="'+key+'"]').prop('type') =='radio' )
					{	
						alert(value);
					}
					else if ($('#'+res.target+' input[name="'+key+'"]').prop('type') =='checkbox') //checkbox
					{
						if (value==1)
						{
							$('#'+res.target+' input[name="'+key+'"]').attr("checked", true);
						}
						else 
						{
							$('#'+res.target+' input[name="'+key+'"]').attr("checked", false);
						}
					}
					else 
					{	
						
						if (value!='')
						{	
							$('#'+res.target+' #'+key).val(value); //normal text fields
						}
						else 
						{
						
							$('#'+res.target+' #'+key).val('');//no value so clear
						}	
					}
					
					if (key=='filename' && value!=null){
						$('#'+res.target+' .downloadButton').css('display','block');
					}
					
				});
				
				$('#'+res.target+' .formTitle').html(res.title);
			}
		});
		
		
		
		
		$('#'+res.target).dialog({ autoOpen: true,modal: true,hide:'fade',position: ['center',100], width:"600px", closeOnEscape: true,resizable: false});
		
	};
	
	var showCart =  function(res,loader) {
		loader.dialog('close');
		
		if (res.valid == 'ok')
		{
			
			$("#cart").show();
		}
		else 
		{
			$("#cart").hide();	
		}
		
		
		
	};
	
	var refreshShoppingList = function(res,loader) {
		loader.dialog("close");
		$("#"+res.formData).hide("slow", function(){ 
			
			$(this).remove();
		
			if (!$("#shoppinglist tbody tr").length)
			{
				$("#totalprice").hide();
				$("#reportbuyform").slideUp('slow');
				$("#proceed").hide();
				$("#cart").hide();
			}
			else 
			{
				$("#totalprice").children('td').eq(1).html(res.totalprice);	
				
			}
		
		});
		
		
		
	
	};	
	
	
	
		
})(jQuery);

$(document).ready( function() {
	
	//init retina cookie
	$.writeCookie();
	
	$(".focus").focus();
	
	$("#cancel, .cancel").click(function(e) {
		e.preventDefault();
		if ($(this).attr('rel'))
		{
			if ($(this).attr('rel')=='goBack')
			{
				history.go(-1);
			}
			else if ($(this).attr('rel')=='reload')
			{
				location.href = location.href; //to itself
			}
			else  
			{
				location.href = $(this).attr('rel');	
			}
			
		}
				
	});	
	
	/* click eventHandler: send button */
	$(".send").click(function(e) {
		e.preventDefault();
		//$(".edit").clearField();
		var form = $(this).closest('form');
				
		if (form.validationEngine('validate'))
		{
			form.validationEngine('hideAll');
			var param = {'action':form.attr('id'), 'message': form.attr('title')};
			form.ajaxForm(form.attr('id').toLowerCase(),param);
		}
		
		return false;
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
	
	/*
	When the toggle switch is clicked, check off / de-select the associated checkbox
	*/
	$('.toggle').click(function(e) {
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
	
	
	$(".inputbg input").focus(function() {
		$(this).parent().addClass('selected');
	}).focusout(function(){
		$(this).parent().removeClass('selected');	
	});
	
	//hide small event calender 1
	if ($(".calendar-small1").length)
	{
		$(".calendar-small1").each(function() {
			
			if ($(this).children(".calendar-number-small").html() =='')
			{
				$(this).hide();
			}
			else 
			{
				$(this).show();
			}
			
		});
	}
	
	//hide small event calender 2
	if ($(".calendar-small2").length)
	{
		$(".calendar-small2").each(function() {
			
			if ($(this).children(".calendar-number-small").html() =='')
			{
				$(this).hide();
			}
			else 
			{
				$(this).show();
			}
			
		});
	}
	
	
	
	$('input, textarea').placeholder();
		
	$(['/images/finalcms/ajax-loader.gif']).preload();
	
	$.resizeIframes();
	
	
	// Set pixelRatio to 1 if the browser doesn't offer it up.
	var pixelRatio = !!window.devicePixelRatio ? window.devicePixelRatio : 1;
	 
	// Rather than waiting for document ready, where the images
	// have already loaded, we'll jump in as soon as possible.
	$(window).on("load", function() {
	    if (pixelRatio > 1) {
	        $('img').each(function() {
	 
	            // Very naive replacement that assumes no dots in file names.
	            $(this).attr('src', $(this).attr('src').replace(".","@2x."));
	        });
	    }
	});
	
	
});



	