(function($)
{
	$.extend( {
	    // resize menu based on window width
	    fillSubmenu: function (init){
	      	
	      	if ($(window).width() >1020)
	      	{
	      		var max = 7;
	      	}
			else if ($(window).width() >900)
			{
				var max = 6;
			}
			else if ($(window).width() >890)
			{
				var max = 7;	
			}
			else if ($(window).width() >880)
			{
				var max = 6;	
			}
			else if ($(window).width() >760)
			{
				var max = 5;	
			}
			else if ($(window).width() >730)
			{
				var max = 4;	
			}
			else if ($(window).width() >636)
			{
				var max = 3;	
			}
			else if ($(window).width() >560)
			{
				var max = 2;	
			}
			else if ($(window).width() >520)
			{
				var max = 1;	
			}
			else 
			{
				var max = 0;	
				$("#menuList").hide();
				$(".awalogo").css({width:'120px','margin-top':'20px'});
				$(".more > span").html('Menu');
				
			}
			
			if (max >0)
			{
				$("#menuList").show();
				$(".awalogo").css({width:'184px','margin-top':'10px'});
				$(".more > span").html('More');
			}
			
			
			//console.debug(max+' '+$(window).width());
	      
	      	var c = 1;
	      	var e = $("#menuList > li").length;
		      	
	      	$("#menuList > li").each(function(index,value) {
	      		if (c >max)
	      		{
	      			
	      			$(".dropdownContainer").css({display:'block'});
	      			if (!init)
	      			{
	      				$("#submenuList").prepend('<li>'+$(this).html()+'</li>');
	      			}
	      			else 
	      			{
	      				$("#submenuList").append('<li>'+$(this).html()+'</li>');	
	      			}
	      			$(this).remove();
	      			
	      		}
	      		c++;
	      	});
	      	c--;
	 
	      	var d=max;
	      	
	      	$("#submenuList > li").each(function(index,value) {
	      		if (d>e)
	      		{
	      			$("#menuList").append('<li>'+$(this).html()+'</li>');
	      			$(this).remove();
	      		}
	      		d--;
	      	});	
	      	
	      
	       
	    }
	});
		
	$.fn.divSearch = function(divId){
			// When value of the input is not blank
		
		//alert(tblID);
		if( $(this).val() != "")
		{
			// Show only matching TR, hide rest of them
			$("."+divId+" .row>div").hide();
			$("."+divId+" .row>div:contains-ci('" + $(this).val() + "')").show();
		}
		else
		{
			// When there is no input or clean again, show everything back
			$("."+divId+" .row>div").show();
		}
	};
	
	$.extend($.expr[":"], 
	{
	    "contains-ci": function(elem, i, match, array) 
		{
			return (elem.textContent || elem.innerText || $(elem).text() || "").toLowerCase().indexOf((match[3] || "").toLowerCase()) >= 0;
		}
	});
	
	
	$.fn.gotoItem = function(speed,offSet) {
		
		!speed ? speed = 3000:false;
		$('html,body').animate({ 
			scrollTop: this.offset().top - offSet
		 }, speed);	
	};
	
	
	$(".close").live("click",function(e){
		e.preventDefault();
		var dialogId = '#'+$(this).closest('.ui-dialog-content').attr('id');
		$(dialogId).dialog('close');
	});

})(jQuery);

$(document).ready( function() {
	
	
	
	if( /Android|webOS|iPhone|iPad|iPod|BlackBerry/i.test(navigator.userAgent) ) {
	 	
	 	$("#banner").replaceWith('<div class="row smallbanner"></div>');
	 	//depends on the menu
	 	if ($(".dropdownContainer").length)
	 	{
	 		var offSet = 85;
	 	}
	 	else 
	 	{
	 		var offSet = 0;
	 	}
	 	
	}
	else 
	{
		var offSet = 85;	
	}
		
	//dropdown of readmore boxes
	$(".readmore").click(function(e) {
		e.preventDefault();
		var blockId = $(this).attr("href");
		var relay = $(this);
		if (!$(blockId).attr('data-toggled') || $(blockId).attr('data-toggled') == 'off')
		{
			
			var down = false
			$(".dropBlock").each(function(index,val) {
				if ($(this).attr('data-toggled') == 'on')
				{
					$(this).attr('data-toggled','off');	
					down = 	$(this);			
				}
			});
			
			if (down)
			{
				
				
				$(".featureBox").css({'padding-bottom':'20px'});
				down.slideUp('fast',function() {
					$(blockId).attr('data-toggled','on');
					$(blockId).slideDown(500,function() {
						
						$(blockId).gotoItem(500,offSet);
					});
				
				});
			}
			else 
			{
				
				$(".featureBox").css({'padding-bottom':'0px'});
				$(blockId).attr('data-toggled','on');
				$(blockId).slideDown(500,function() {
					$(blockId).gotoItem(500,offSet);
				
				});
				
			}
			
	    }
	    else if ($(blockId).attr('data-toggled') == 'on')
	    {
	    	
	    	$(".featureBox").css({'padding-bottom':'20px'});
	    	$(blockId).attr('data-toggled','off'); 
	       	$(blockId).slideUp(500,function() {
	       		$(blockId).gotoItem(500,offSet);
	       	
	       	});
	   	}
		
			
	});
	
	
	//closing of readmore boxes by cross
	$(".boxCross A").click(function(e) {
		e.preventDefault();
		var relay = $(this).attr('href');
		var blockId = $(this).closest('.dropBlock');
		$(blockId).attr('data-toggled','off'); 
		$(".featureBox").css({'padding-bottom':'20px'});
		$(blockId).slideUp(500,function() {
			$(relay).gotoItem(500,(offSet+80));			

		});
		
	
	});

	$("#search").keyup(function() {
		$(this).divSearch("searchDiv");	
	});
	
	
	$("#search-report").keyup(function(e) {
		e.preventDefault();
		
		if(e.which == 13) {
		  location.href = '?search='+$(this).val();
		  
		}
	
	});
	

	
	if ($(window).width() <1080)
	{
		
		$.fillSubmenu(true);
		
	}
	
	$(window).resize(function() {
		
		if ($(window).width() <1080)
		{
			$.fillSubmenu(false);
		}
		else 
		{
			$("#submenuList > li").each(function(index,value) {
				$("#menuList").append('<li>'+$(this).html()+'</li>');
				$(this).remove();
			});	
			$(".dropdownContainer").hide();
		}
		
		
		
	});
	

	
	$(".more").click(function()
	{
		var X=$(this).attr('id');
	
		if(X==1)
		{
			$(".submenu").hide();
			$(this).attr('id', '0');	
		}
		else
		{
			$(".submenu").show();
			$(this).attr('id', '1');
		}
		
	});
	

	$(".more").click(function(e)
	{
		return false;
	});
	

	//Textarea without editing.
	$(document).click(function()
	{
		$(".submenu").hide();
		$(".more").attr('id', '');
	});
	
	$(".addToCart").click(function(e) {
		e.preventDefault();
		$("#cart").show();
		var param = {'action':'addToCart', 'reportID': $(this).attr('id'),'message': 'adding report to cart'};
		$(this).ajaxForm('addToCart',param,false,true);
	});
	
	
	$(".deleteFromShoppingList").click(function(e) {
		e.preventDefault();
		var param = {'action':'removeFromCart', 'reportID': $(this).parents("tr").attr('id'),'message': 'Removing report from cart'};
		$(this).ajaxForm('addToCart',param,false,true);
		
	});
	
	/*$('#currency').change(function() {
		var param = {'action':'updateCurrency', 'currency': $(this).val(),'message': 'Changing currency to '+ $(this).val()};
		$(this).ajaxForm('addToCart',param,false,true);
		
	});
	*/
	
	if ($("#shoppinglist tbody  > tr").length)
	{
		$("#proceed").show();
		
	}
	else 
	{
		$("#proceed").hide();	
	}
	
	
	$("#proceed").click(function(e) {
		e.preventDefault();
		
		
		if ($("#shoppinglist tbody  > tr").length)
		{
			$("#reportbuyform").slideDown('slow');
		}
		
		$(this).hide();
		
	});
	
	$("#closebuyform").click(function(e) {
		e.preventDefault();
		$("#reportbuyform").slideUp('slow');
		$("#proceed").show();
	});
	
	$('input[name=paymentmethod]:radio').change(function() {
		if ($(this).val() == 'creditcard')
		{
			$(".creditcardInfo").show();
		}
		else 
		{
			$(".creditcardInfo").hide();	
		}
	
	});
	
	
	if (!$("#nocart").length)
	{
		$("#cart").ajaxForm('addToCart',{'action':'checkCart'},false,true);
	}
	
	if (child=='report-shop')
	{
		$(".btn-report").each(function() {
			
			if ($(this).attr('href') == window.location.pathname)
			{
				$(this).addClass('selected');
			}
			else 
			{
				$(this).removeClass('selected');	
			}
		
		});
		
	}
});