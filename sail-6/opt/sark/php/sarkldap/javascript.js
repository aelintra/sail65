
  $(document).ready(function() {

	$('#upimg').click(function() {
		$('#file').click();
	});
	
	$('#file').change(function() {
		$('#upimgclick').val("TRUE");
		$('#sarkldapForm').submit();
	});
		
	$.validator.addMethod("alpha",function(value,element) {
		return this.optional(element) || /^[ A-Za-z0-9_-]{1,50}$/i.test(value); 
	},"field can only contain alphanumerics, underscores, hyphens and spaces (no special characters)");			
		  
	$("#sarkldapForm").validate ( {
	   rules: {
			sn: "required alpha",
			givenname: "alpha",
			telephonenumber: "digits",
			mobile: "digits",
			homephone: "digits"
	   },
	   messages: {
	   }					
	});

	if ( $('#userext').val() == 'none' ) {
		$('.ddialcls').hide();
	}

	var scrollPosition;

	$('#ldaptable').dataTable ( {
		"bPaginate": false,
		"bAutoWidth": true,
		"bStateSave": true,
		"sDom": 'fti',
		"bSort" : true,
		"aoColumnDefs" : [{
			"bSortable" : false,
			"aTargets" : [7]
		}],
		"aoColumns": [ 
			{ "sName": "cluster" },
			{ "sName": "sn" },
			{ "sName": "givenname" },
			{ "sName": "telephonenumber" },
			{ "sName": "mobile" },
			{ "sName": "homephone" },
			{ "sName": "o" },
			{ "sName": "del" }
		],
        "drawCallback": function() {
			$(".dataTables_scrollBody").scrollTop(scrollPosition);
		}  

	} ).makeEditable({
			sUpdateURL: "/php/sarkldap/update.php",		
			sReadOnlyCellClass: "read_only",
			"aoColumns": [
				null, 		// cluster	
				{
					type: 'text',
					event: 'click',
					onblur: 'submit'			
				}, 		// givenname													
				{
					type: 'text',
					event: 'click',
					onblur: 'submit',
					placeholder: '-'			
				}, 		// givenname													
				{
					type: 'text',
					event: 'click',
					onblur: 'submit',	
					placeholder: '-'				
				}, 		// phone1				
				{
					type: 'text',
					event: 'click',
					onblur: 'submit',	
					placeholder: '-',				
				}, 		// phone 2
				{
					type: 'text',
					event: 'click',
					onblur: 'submit',	
					placeholder: '-'				
				}, 		// phone 3
				{
					type: 'text',
					event: 'click',
					onblur: 'submit',	
					placeholder: '-'				
				}, 		// organization				
				null	// delete col
            ]
    }); 


    	$('#readldaptable').dataTable ( {
		"bPaginate": false,
		"bAutoWidth": true,
		"bStateSave": true,
		"sDom": 'fti',
		"aoColumnDefs" : [{
			"bSortable" : false,
			"aTargets" : [7]
		}],
        "drawCallback": function() {
			$(".dataTables_scrollBody").scrollTop(scrollPosition);
		}  

		})  
    	if ( $('#perms').val() == 'view' ) { 
    		$('.editcol').hide();
    	}

//    	srkPerms('readldaptable');
		
// save scroll for redraw	
	
	$(".dataTables_scrollBody").mousedown(function(){
		scrollPosition = $(".dataTables_scrollBody").scrollTop();
	})	 	
          
 });
 

      
