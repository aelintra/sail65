
  $(document).ready(function() {

 /*
 * hide/reveal logic for trunk create
 */         
       $('#divtrunkname').hide();	
       $('#divusername').hide();
       $('#divpassword').hide();
       $('#divhost').hide();
       $('#divpeername').hide();
       $('#divregister').hide();
       $('#divprivileged').hide();

				
	$('#chooser').change(function(){
			$('#chooser').attr('disabled', true);
			$('#chooser').css('background-color','lightgrey');
			if(this.value=='SIP (send registration)') {
				$('#divtrunkname').show();	
				$('#divhost').show();		
				$('#divpassword').show();
				$('#divprivileged').show();									
			}
			if(this.value=='SIP (accept registration)') {
				$('#divtrunkname').show();			
				$('#divpassword').show();
				$('#divprivileged').show();									
			}
			if(this.value=='SIP (trusted peer)') {
				$('#divtrunkname').show();
				$('#divhost').show();
				$('#divprivileged').show();											
			}
			if(this.value=='GeneralIAX2' ) {
				$('#divtrunkname').show();			
				$('#divpassword').show();
				$('#divhost').show();
				$('#divregister').show();										
			}
			if(this.value=='InterSARK') {
				$('#divtrunkname').show();
				$('#divpeername').show();
				$('#divhost').show();
				$('#divpassword').show();
				$('#divpeername').show();
				$('#divprivileged').show();						
			}

			$("#carrier").val($(this).val());			
					
	}); 

	var scrollPosition;
	
	$('#trunktable').dataTable ( {
//		"sScrollY": auto,
		"bPaginate": false,
		"bAutoWidth": true,
//		"bStateSave": true,
		"sDom": 'fti',
		"aoColumnDefs" : [{
			"bSortable" : false,
			"aTargets" : [3,5,6,7]
		}],
		"aoColumns": [ 		
			{ "sName": "pkey" },
			{ "sName": "cluster" },
			{ "sName": "description" },
			{ "sName": "carriertype" },
			{ "sName": "ipaddr" },					
			{ "sName": "active" },	
			{ "sName": "connect"},
			{ "sName": "edit" },
			{ "sName": "del" }
		],
/*		
		"fnRowCallback": function( nRow, aData, iDisplayIndex, iDisplayIndexFull ) {
          $('td:eq(2),td:eq(5)', nRow).addClass( "bluetags" );         
        },
*/
        "drawCallback": function() {
			$(".dataTables_scrollBody").scrollTop(scrollPosition);
		}           

	} ) .makeEditable({
			sUpdateURL: "/php/sarktrunk/update.php",
				fnOnEdited: function(status)
				{ 	
					$("#commit").attr("src", "/sark-common/buttons/commitClick.png");
				},			
			sReadOnlyCellClass: "read_only",
			"aoColumns": [
/*			
				null,  	// pkey
				null,   // tenant
				{
					type: 'text',
					event: 'click',
                    onblur: 'cancel',
                    submit: 'Save',			
					tooltip: 'Click to set trunkname',
					onblur: 'cancel',
					placeholder: 'None',
				}, 		// description
				null,	// carriertype
				null,	// ipaddr
				{
					tooltip: 'Click to activate/deactivate',
					event: 'click',
					type: 'select',
                    onblur: 'cancel',
                    submit: 'Save',
					data: "{ 'YES':'YES','NO':'NO' }"
				}, 		// act				
				null, 	// connect	
				null,	// edit col	
				null	// delete col
*/									
            ]
        })
        
//        $(".dataTables_scrollBody").find("tr").find('td:eq(6):not(:contains("OK"))').css('color', 'Red') ; 
//		$(".dataTables_scrollBody").find("tr").find('td:eq(5):contains(NO)').parent().css('color', 'Gray') ;
		
// save scroll for redraw	
	$(".dataTables_scrollBody").mousedown(function(){
		scrollPosition = $(".dataTables_scrollBody").scrollTop();
	})        




		
      });
      
