<?php
// 
// Developed by CoCo
// Copyright (C) 2016 CoCoSoFt
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//

  
//	syslog(LOG_WARNING, "channel reader running");

// memory jogger ToDo
  require_once $_SERVER["DOCUMENT_ROOT"] . "../php/srkAmiHelperClass";
  require_once $_SERVER["DOCUMENT_ROOT"] . "../php/srkHelperClass";

  $amiHelper = new amiHelper;
  $channels = build_channel_array($amiHelper->get_coreShowChannels());

  if (empty($channels)) {
    return;
  }

/*
	$result = `sudo /usr/sbin/asterisk -rx 'core show channels concise'`;
  $data = explode("\n", $result);
*/

  $stream = null;
 
    $stream .= '<table class="w3-table w3-card-4 w3-text-gray w3-striped w3-hoverable" id="chantable">';
    $stream .= '<thead class="w3-deep-orange w3-text-white">'; 
    $stream .= '<tr>';
    $stream .= '<th>Duration</th>';
    $stream .= '<th>Source</th>';
    $stream .= '<th></th>';
    $stream .= '<th>Destination</th>';
    $stream .= '<th></th>';
    $stream .= '<th>State</th>';			
		$stream .= '</tr>';
		$stream .= '</thead>';
		$stream .= '<tbody>';     


  foreach($channels as $key=>$chan) {

/*
    if ($chan['Application'] == 'AppDial') {
        continue;
    }
*/
    switch ($chan['Application']) {

      case "Dial":
          buildCommon($chan,$stream,$key);
          $destination = $chan['Exten'];
          $target =  $chan['ChannelStateDesc'];
//Call groups simply have a status of Up.   Change it to Ring to be consistent.
          if ($target = "Up") {
            $target = "Ring";
          }
          $linked = findLinked($channels,$chan['CallerIDNum'],$chan['BridgeId']);
          if ($linked == $chan['Exten']) {
            $target = "In Call";
          }
          elseif (!empty($linked)) {
            $destination = $linked . " (Via $destination)";
            $target = "In Call";
          }
          else {

          }
          $stream .= "<td>$destination</td>";
          $stream .= '<td class="icons"><img src="/sark-common/icons/arrowright.png" border=0 title = "Direction of call"></td>';          
          $stream .= "<td>" . $target . "</td>";
          break;
      case "Queue":
          buildCommon($chan,$stream,$key);
          $queueName = explode(',',$chan['ApplicationData']);
          $target =  "Queue Wait";
          $destination = $queueName[0];
          $linked = findLinked($channels,$chan['CallerIDNum'],$chan['BridgeId']);
          if ($linked) {
            $destination = "$linked (Via $destination)";
            $target = "In Call";
          }
          $stream .= "<td>$destination</td>";
          $stream .= '<td class="icons"><img src="/sark-common/icons/arrowright.png" border=0 title = "Direction of call"></td>';          
          $stream .= "<td>" . $target . "</td>";
          break;
      case "ConfBridge":
          buildCommon($chan,$stream,$key);
          $confName = explode(',',$chan['ApplicationData']);
          $stream .= "<td> $confName[0]</td>";
          $stream .= '<td class="icons"><img src="/sark-common/icons/arrowright.png" border=0 title = "Direction of call"></td>';
          $stream .= "<td>Conf Room</td>";
          break;
      case "VoiceMail":
          buildCommon($chan,$stream,$key);          
          $vName = explode(',',$chan['ApplicationData']);
          $stream .= "<td>" . $vName[0] . "</td>";
          $stream .= '<td class="icons"><img src="/sark-common/icons/arrowright.png" border=0 title = "Direction of call"></td>';
          $stream .= "<td>Leaving Voicemail</td>";
          break;
      case "VoiceMailMain":
          buildCommon($chan,$stream,$key); 
          $destination = $chan['ApplicationData'];
          if (empty($chan['ApplicationData'])) {
              $destination = $chan['Exten'];
          }       
          $stream .= "<td>$destination</td>";
          $stream .= '<td class="icons"><img src="/sark-common/icons/arrowright.png" border=0 title = "Direction of call"></td>';
          $stream .= "<td>Retrieving Voicemail</td>";         
          break;  
      case "Wait":
          buildCommon($chan,$stream,$key);
          $destination =  $chan['Context'];
          if  ($chan['Context'] == 'mainmenu')  {
              $destination = "IVR";
          }
          $stream .= "<td>$destination</td>";
          $stream .= '<td class="icons"><img src="/sark-common/icons/arrowright.png" border=0 title = "Direction of call"></td>';
          $stream .= "<td>Wait</td>";         
          break;                  
    }


    $stream .= "</td>"; 
    $stream .= '</tbody>';
    $stream .= "</table>";  

  }  
  echo $stream;
    

function buildCommon($chan, &$stream,$key) {
// TOC and CLID are common to all
    $stream .= "<tr>";  
    // time on call 
    $stream .= "<td>" . $chan['Duration'] . "</td>";
    // CLID
//    $shortChannel = explode("-",$key);
//    $stream .= "<td>" . $shortChannel[0] . "</td>";
    preg_match(' /^\w+\/([\w\@]+)-.*$/ ',$key,$matches);
    if (strlen($matches[1]) < 6) {
      $stream .= "<td>" . $matches[1] . "</td>";
    }
    else {
      $stream .= "<td>" . $chan['CallerIDNum'] . "</td>";
    }
// nice little arrow
    $stream .= '<td class="icons"><img src="/sark-common/icons/arrowright.png" border=0 title = "Direction of call"></td>';
}

function build_channel_array($amirets) {
/*
 * build an array of active channels by cleaning up the AMI output
 * (which contains stuff we don't want).
 */ 
  $channel_array=array();
  $lines = explode("\r\n",$amirets);  
  $channel = 0;
  foreach ($lines as $line) {
    // ignore lines that aren't couplets
    if (!preg_match(' /:/ ',$line)) { 
        continue;
    }
    
    // parse the couplet  
    $couplet = explode(': ', $line);
    
    // ignore events and ListItems
    if ($couplet[0] == 'Event' || $couplet[0] == 'EventList' || $couplet[0] == 'ListItems' || $couplet[0] == 'Response' || $couplet[0] == 'Message' ) {
      continue;
    }
    
    //check for a new channel and set a new key if we have one
    if ($couplet[0] == 'Channel') {
      $channel = $couplet[1];
    }
    else {
      if (!$channel) {
        continue;
      }
      else {
        $channel_array [$channel][$couplet[0]] = $couplet[1];
      }
    }
  }
  return $channel_array; 
}

function findLinked($channels,$CallerIDNum,$BridgeId) {

  if (empty($BridgeId)) {
    return null;
  }
  foreach ($channels as $candidate) {
    if ($candidate['CallerIDNum'] != $CallerIDNum && $candidate['BridgeId'] == $BridgeId) {
      return $candidate['CallerIDNum'];
    }
  }
  return null;
}
   	
?>

