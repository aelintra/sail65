<!DOCTYPE html >
<?php
ob_start();
if (!strpos($_SERVER['SCRIPT_URL'],'sarklogin')) {
	require_once $_SERVER["DOCUMENT_ROOT"] . "../php/srksessions/session.php";
}
else {
	require_once $_SERVER["DOCUMENT_ROOT"] . "../php/srksessions/common.php";
}
?>

<html lang="en">
<head>
<title>SARK PBX</title>
<meta name="copyright" content="Copyright 2018 Aelintra Telecom Limited" />
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" type="image/png" href="/sark-common/Sark_favicon.png">
<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.9/css/all.css" integrity="sha384-5SOiIsAziJl6AWe0HWRKTXlfcSHKmYV4RBF18PPJ173Kzn7jzMyFuTtk8JA7QQG1" crossorigin="anonymous">
<link rel="stylesheet" href="/sark-common/js/jQuery-autoComplete-master/jquery.auto-complete.css">
<link rel="stylesheet" href="/sark-common/css/dist/toggle-switch.css">
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.css">

<!-- <link rel="stylesheet" type="text/css" href="/sark-common/css/sark.css" /> -->
<link rel="stylesheet" type="text/css" href="/sark-common/js/DataTables-1.10.15/datatables.css" media="screen" /> 

<script  src="/sark-common/js/jquery-1.11.0.min.js" ></script>
<script  src="/sark-common/js/DataTables-1.10.15/datatables.min.js" ></script>
<script  src="/sark-common/jquery-datatables-editable-master/media/js/jquery.dataTables.editable.js" ></script>
<script  src="/sark-common/js/jquery.jeditable.js" ></script>
<script  src="/sark-common/js/jquery.validate.js" ></script>
<script  src="/sark-common/js/jQuery-autoComplete-master/jquery.auto-complete.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.js"></script>


<!--<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.touchswipe/1.6.18/jquery.touchSwipe.min.js"></script>-->
<script>

$(document).ready(function() {

  $('.message').delay(3000).fadeOut(400);
  $('.emessage').delay(10000).fadeOut(400);

});


function srkMenuFunction(id) {
    var x = document.getElementById(id);
    if (x.className.indexOf(" w3-show") == -1) {
        window.scrollTo(0, 0)
        x.className += " w3-show";
    } else { 
        x.className = x.className.replace(" w3-show", "");
    }

}

function checkDec(el){
 var ex = /^[0-9]+\.?[0-9]*$/;
 if(ex.test(el.value)==false){
   el.value = el.value.substring(0,el.value.length - 1);
  }
}

function backsnap(id) {
    var i;
    var x = document.getElementsByClassName("bkupsnap");
    for (i = 0; i < x.length; i++) {
        x[i].style.display = "none"; 
    }
    document.getElementById(id).style.display = "block"; 
}

function srkPerms(dtab) {
//  console.log(dtab);

  if ( $('#perms').val() == 'view' ) { 
    $('.buttonUpdate').hide();
    $('.buttonCreate').hide();
  }
  if ( $('#perms').val() == 'update' ) {
      $('.buttonCreate').hide();
  }; 

  if (dtab) {
    var mytable = $('#' + dtab).DataTable(); 
    if ( $('#cosflag').val() == 'OFF' || $('#sysuser').val() == 'NO' ) {
      mytable.columns('.cluster').visible(false);
      $('#cluster').hide();
      $('.cluster').hide();
    };
    if ( $('#perms').val() == 'view' ) {    
      mytable.columns('.delcol').visible(false);
      mytable.columns('.editcol').visible(false);
    };
    if ( $('#perms').val() == 'update' ) {
      mytable.columns('.delcol').visible(false);
    };  
  };
}

function srkOpenTab(evt, myTabName) {
    var i;
    var x = document.getElementsByClassName("srktab");
    for (i = 0; i < x.length; i++) {
        x[i].style.display = "none"; 
    }
    tablinks = document.getElementsByClassName("tablink");
  	for (i = 0; i < x.length; i++) {
      tablinks[i].className = tablinks[i].className.replace(" w3-red", "");
  	}
    document.getElementById(myTabName).style.display = "block"; 
    evt.currentTarget.className += " w3-red";
}

  function dialBack(number) {
//    console.log(number);
//    console.log(pkey);
    $(".myspinner").show();
    var pkey = $("#userext").val();

    $.post('../dialler.php', { number:number, pkey:pkey },
      function (response) {
        var obj = JSON.parse(response);
//        console.log('RC=' + obj.msg);
//        console.log('dialled ' + number + ' for ' + pkey);  
      });
    setTimeout(hideSpinner, 2000);
  };

  function hideSpinner(){
      $(".myspinner").hide();
  }
	

function confirmOK(myMsg) {
	return window.confirm(myMsg);
}

/*

document.onkeypress = keyPress;

function keyPress(e){
  var x = e || window.event;
  var key = (x.keyCode || x.which);
  if(key == 13 || key == 3){
   
   e.preventDefault();
// these two will disable "enter"   
   $(this).blur();
   return false;
// This will translate enter to save
//   document.getElementById("save").click()
  }
}
*/
$(window).load(function() {
	$(".loader").fadeOut("slow");
})



</script>
<style>

.myspinner {
    position: fixed;
    top: 50%;
    left: 50%;
    margin-left: -50px; /* half width of the spinner gif */
    margin-top: -50px; /* half height of the spinner gif */
    text-align:center;
    z-index:1234;
/*    overflow: auto; */
    width: 100px; /* width of the spinner gif */
    height: 102px; /*hight of the spinner gif +2px to fix IE8 issue */
}

.fluidMedia {
    position: relative;
    padding-bottom: 56.25%; /* proportion value to aspect ratio 16:9 (9 / 16 = 0.5625 or 56.25%) */
    padding-top: 30px;
    height: 0;
    overflow: hidden;
}

.fluidMedia iframe {
    position: absolute;
    top: 0; 
    left: 0;
    width: 100%;
    height: 100%;
}
    .longdatabox {
/*      
    margin: auto;
    margin-right: 2em;
    font-size: 1em;
*/
    width: 100%;
    height: 26em;
    resize: none;
//    border-style: none;
    
/*
    border-width: 0;
 
    border-style: solid;
    border-width: 2px;
    margin-top: 5px;
    box-sizing: border-box;
    -moz-box-sizing: border-box;
*/
    }

    .intBoxBackground {
      background-color: WhiteSmoke;
    }
    </style> 

<?php
	$url = explode('/', $_SERVER['SCRIPT_URL']);
	if (file_exists($_SERVER["DOCUMENT_ROOT"] . "../php/" . $url[2] . '/javascript.js')) {
		echo '<script  src="/php/' . $url[2] . '/javascript.js" ></script>' . PHP_EOL;
	}

  if ($url[2] == 'sarkholiday') {
       echo ' <link rel="stylesheet" type="text/css" href="/sark-common/js/jquery-ui-1.12.1.custom/jquery-ui.css" media="screen" />'; 
       echo ' <script  src="/sark-common/js/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>'; 
  }
  if ($url[2] == 'sarksplash') {
       echo ' <script  type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>'; 
  }

 
?>
</head>
<body>   
<div id="myspinner" class="myspinner" style="display:none">
  <img class="w3-display-middle" src="/sark-common/Spinner-1s-150px.gif" alt="Loading"/>
</div>
