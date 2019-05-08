function jsonTest(events) {
	
	return events;

}

$(document).ready(function() {
		
		
		var date = new Date();
		var d = date.getDate();
		var m = date.getMonth();
		var y = date.getFullYear();
	
		/* initialize the external events
		-----------------------------------------------------------------*/
	
		$('#external-events div.external-event').each(function() {
		
			// create an Event Object (http://arshaw.com/fullcalendar/docs/event_data/Event_Object/)
			// it doesn't need to have a start or end
			var eventObject = {
				title: $.trim($(this).text()) // use the element's text as the event title
			};
			
			// store the Event Object in the DOM element so we can get to it later
			$(this).data('eventObject', eventObject);
			
			// make the event draggable using jQuery UI
			$(this).draggable({
				zIndex: 999,
				revert: true,      // will cause the event to go back to its
				revertDuration: 0  //  original position after the drag
			});
			
		});
	
	
		/* initialize the calendar
		-----------------------------------------------------------------*/
		
		var calendar = $('#calendar').fullCalendar({
			header: {
				left: 'prev,next today',
				center: 'title',
				right: 'year,month,agendaWeek,agendaDay'
			},
			selectable: true,
			selectHelper: true,
			editable: true,
			events:  {
		       
		       //console.debug(callback);
		      
	           url: '/secure/eventfeed',
	           type: 'POST',
	           async:true,
	           dataType: 'json',
	           data: {
	               // our hypothetical feed requires UNIX timestamps
	               action: 'getFeed'

	           },
	           error: function(e) {
	           		//console.debug(JSON.stringify(e));
	           },
	           success: function(jsonFeed) {
	                //console.debug(jsonFeed);
	                 //callback(jsonFeed);
	                 
	              
	           }
		       
		     
		    },
			eventClick: function(event, jsEvent, view, ui) {
				
				if (event.id!==undefined)
				{
					var view = $('#calendar').fullCalendar( 'getView' );
					var param = {'action':'getEvent', 'id': event.id, 'view': view.name, 'message': 'loading event'};
					var form = $(this).closest('form');
					form.ajaxForm('eventfeed',param,false,true);
				}
				

		
		    },
			eventDrop: function(event) {
				//console.debug('eventDrop');
				$.ajax({
				    url: '/secure/eventfeed',
				    type: 'POST',
				    async:true,
				    dataType: 'json',
				    data: {
				        // our hypothetical feed requires UNIX timestamps
				        action: 'moveEvent',
				  		event: event      
				    },
				    success : function(e) {
				    	//console.debug(e);				    
				    }
				    
				});
				
			},
			
			loading: function(bool) {
				if (bool) $('#loading').show();
				else $('#loading').hide();
			},
			eventResize: function(event,dayDelta,minuteDelta,revertFunc) {
		       //console.debug(event);
		       $.ajax({
		           url: '/secure/eventfeed',
		           type: 'POST',
		           async:true,
		           dataType: 'json',
		           data: {
		               // our hypothetical feed requires UNIX timestamps
		               action: 'eventResize',
		         		event: event   
		           },
		           error: function(e) {
		           	//console.debug(JSON.stringify(e));
		           },
		           success: function(jsonFeed) {
		            	//console.debug(JSON.stringify(jsonFeed));
		           	
		           }
		       });
		       
		
		    },
			droppable: true, // this allows things to be dropped onto the calendar !!!
			drop: function( date, allDay, jsEvent, ui ) { // this function is called when something is dropped
				 
				// retrieve the dropped element's stored Event Object
				var originalEventObject = $(this).data('eventObject');
				
				// we need to copy it, so that multiple events don't have a reference to the same object
				var copiedEventObject = $.extend({id:$(this).attr('id')}, originalEventObject);
				
				// assign it the date that was reported
				copiedEventObject.start = date;
				copiedEventObject.allDay = allDay;
				console.debug(copiedEventObject);
				
				$(this).remove();
				
				$.ajax({
				    url: '/secure/eventfeed',
				    type: 'POST',
				    async:true,
				    dataType: 'json',
				    data: {
				        // our hypothetical feed requires UNIX timestamps
				        action: 'dropEvent',
				  		event: copiedEventObject   
				    },
				    error: function(e) {
				    	//console.debug(JSON.stringify(e));
				    },
				    success: function(jsonFeed) {
				      //console.debug(JSON.stringify(jsonFeed));
				    	//var  = new date($('#calendar').fullCalendar('getDate'));
				    	var view = $('#calendar').fullCalendar( 'getView' );
				    	var d_formatted = date.getFullYear() + "-" + date.getMonth() + "-" + date.getDate();
				    	location.href = '?gotoDate='+d_formatted+'&view='+view.name;
				    }
				});
				
				
				
				// render the event on the calendar
				$('#calendar').fullCalendar('renderEvent', copiedEventObject, true);
				//alert(copiedEventObject.title + ' was places ' + copiedEventObject.start + ' \n' +
					//'(should probably update your database) ');
				// is the "remove after drop" checkbox checked?
				
				

			}
		});
		
		
		
		
	});