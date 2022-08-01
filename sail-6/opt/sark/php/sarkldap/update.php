<?php
  require_once $_SERVER["DOCUMENT_ROOT"] . "../php/srkDbClass";
  require_once $_SERVER["DOCUMENT_ROOT"] . "../php/srkHelperClass";
  require_once $_SERVER["DOCUMENT_ROOT"] . "../php/srkLDAPHelperClass";
  
  $helper = new helper;
  $ldap = new ldaphelper;
  
  $helper->removeLrCr($value); 

  
  $id = $_REQUEST['id'] ;
  $value = strip_tags($_REQUEST['value']) ;
  $column = $_REQUEST['columnName'] ;
  $argument=array();
  $dn = "uid=" . $_REQUEST['id'] . "," . $ldap->addressbook . "," . $ldap->base;

  if (!$ldap->Connect()) {
	echo  "LDAP ERROR 19 - " . ldap_error($ldap->ds);
  }

/*
	phone numbers
 */
    if ($column=='telephonenumber' || $column=='mobile' || $column=='homephone') {
		if (!is_numeric($value) && !empty($value)) {
			echo "LDAP ERROR 36 - phone numbers must be numeric or NULL (empty)";
			return;
		}
		else {			
			if (empty($value)) {
				$argument[$column]=array();
				doDelete($ldap,$dn,$argument);
			}
			else {
				$argument[$column] = $value;
				doModify($ldap,$dn,$argument);
			}
		}
		$ldap->Close();
		return; 		
	}
  
/*
	We should now only have sn and givenName to handle
	This means the cn will change also
 */  
  
	if (empty($value) && $column=='sn') {
		echo "LDAP ERROR 26 - Surname/Company name cannot be blank";
		return;		
	}	 

	
// get the existing sn,givenName usng the UID

	$search_arg = array("givenname", "sn");
	if (!$result = $ldap->Get("uid=" . $id, $search_arg)) {
			echo  "LDAP ERROR47 - Couldn't retrieve UID $value";
			$ldap->Close();
			return; 
	}

//  if target is sn then we need to replace sn and cn

	if ($column=='sn') {
		if (!empty($result[0]["givenname"][0])) {
			$argument["cn"] = $result[0]["givenname"][0] . ' ' . $value;
		}
		else {
			$argument["cn"] = $value;
		}
		$argument["sn"] = $value;
		doModify($ldap,$dn,$argument);
		$ldap->Close();
		return; 		
	}	

// finally, givenName.   We may need to delete it.
// in eithet case we will need to change cn

	if ($column=='givenname') {
		if (empty($value)) {
			$argument[$column]=array();
			doDelete($ldap,$dn,$argument);
			$argument["cn"] = $result[0]["sn"][0];
			doModify($ldap,$dn,$argument); 
		}
		else {
			$argument["cn"] = $value . ' ' . $result[0]["sn"][0];
			$argument["givenname"] = $value;
			doModify($ldap,$dn,$argument); 
		}			
	}
   
  	$ldap->Close();
  	return;   

/*
	functions
 */

  function doDelete($ldap, $dn,$argument) {
    if (ldap_mod_del($ldap->ds,$dn,$argument)) {
    	echo $_REQUEST['value'];
    }
    else {
        echo  "LDAP ERROR30 - " . ldap_error($ldap->ds);
        return -1;
    }
 }

 function doModify($ldap, $dn,$argument) {
	if (ldap_mod_replace($ldap->ds,$dn,$argument)) {  
		echo $_REQUEST['value'];
	}
	else { 
		echo  "LDAP ERROR 65 - " . ldap_error($ldap->ds);
		return -1;
	} 	
 }

 
