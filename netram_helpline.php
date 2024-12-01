<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Netram Helpline</title>
</head>

<body>
<?php
//inputs to this file
$mobile_number = $_REQUEST['mb'];
$user_message = $_REQUEST['ms'];
$contact_type = $_REQUEST['type'];

//establishing persistence connection with the database
mysql_pconnect('localhost','root','') or die(mysql_error()); 
mysql_select_db('helpline') or die(mysql_error());

//check whether the same message is already queried 
//$lowercase_user_message = strtolower(trim($user_message));
$lowercase_user_message = strip_tags(strtolower(addslashes($user_message)));
$trim_user_message = trim($lowercase_user_message); //trim white spaces from both sides of the message
$s = start_timer();//start time recording
//*********************************************************************************************************************************************
//check the previous entry of this message and the phone number
$sql_check_previous_entry = "SELECT timestampdiff(minute,`timestamp`,now()) diff , `message` , `phone_number` , `query_quality` , `id` FROM `eyebank_query_log` WHERE `message` = '".$trim_user_message."' AND `phone_number` = '".$mobile_number."' AND timestampdiff(minute,`timestamp`,now()) <= 240 LIMIT 0,1";
$result_check_previous_entry = mysql_query($sql_check_previous_entry);
$num_rows_fetched = mysql_num_rows($result_check_previous_entry);//No. of rows fetched
//if above query had fetched some rows
if($num_rows_fetched >= 1){
	while($previous_entry = mysql_fetch_array($result_check_previous_entry)){
		$id = $previous_entry['id'];
	}
	$get_next =array();
	$get_next = fetch_next_eyebank($id);//call another function bby passing id and count that we fetched by above query
	if($get_next === false){
		$catch_error = array();
					$catch_error = throw_error("error_7");
					echo  $catch_error['error'];
	}
	else{
		//adding duplicate entry for above previous query with different time stamp
		$sql_add_duplicate_entry = "INSERT INTO `eyebank_query_log`(`message`, `prefix_id`, `query_string`, `query_area`, `phone_number`,`timestamp`, `response_time`, `query_quality`)  SELECT `message`,`prefix_id` , `query_string`,`query_area` , `phone_number` ,NOW(), `response_time` , `query_quality` FROM `eyebank_query_log` WHERE `id` = '$id'";
		$result__add_duplicate_entry = mysql_query($sql_add_duplicate_entry);
		$lastentry_id = mysql_insert_id();;
		$show_screen = array();
		$number = "";
		$show_screen['0'] = $get_next[1];
		$show_screen['1'] = $get_next[2];
		for($i=3; $i <= sizeof($get_next)-2; $i++){
			$number .= $get_next[$i] . ",";
		}
		$show_screen['2'] = $number;
		$show_screen['3'] = $get_next[sizeof($get_next)-1];
		display($show_screen , $lastentry_id , $get_next[0]);
	}
	$t = end_timer($s);//stop time recording and print it on the screen
	$end_timer = round($t,3);
}

//*********************************************************************************************************************************************

else{
	if(preg_match("(pin)",$trim_user_message)){
		search_eyebank_for_pincode($trim_user_message,$mobile_number,$s);
	}
	
	elseif(preg_match("(area)",$trim_user_message)){
		search_eyebank_for_area($trim_user_message,$mobile_number,$s);
	}
	
	else{
		match_pattern($trim_user_message,$mobile_number,$s);
	}
}

//***********************************************************Now Function***********************************************************************

function search_eyebank_for_pincode($trim_user_message,$mobile_number,$s){
	$array_pincode_and_prefix = array();
	$aray_prefix_quality = array();
	$array_of_latlong = array();
	$first_eyebank_display =array();
	$lastentry_id ="";
	$array_pincode_and_prefix = extract_pincode_prefix($trim_user_message);
	if($array_pincode_and_prefix === "0"){
		$catch_error = array();
		$catch_error = throw_error("error_1");
		echo  $catch_error['error'];
		$lastentry_id = create_log($trim_user_message , "0" , "0" , $mobile_number , "0", "0", $catch_error['id']);
	}
	elseif($array_pincode_and_prefix === "1"){
		$catch_error = array();
		$catch_error = throw_error("error_2");
		echo  $catch_error['error'];
		$lastentry_id = create_log($trim_user_message , "0" , "0" , $mobile_number , "0", "0", $catch_error['id']);
	}
	else{
		$aray_prefix_quality = get_prefixid_quality($array_pincode_and_prefix['prefix'],"flagpin");
		$lastentry_id = create_log($trim_user_message , $aray_prefix_quality['prefix_id'] , "0" , "0" , $array_pincode_and_prefix['query_string'] , $mobile_number , $aray_prefix_quality['query_quality'],"0","0");
		$array_of_latlong =  get_lat_long($array_pincode_and_prefix['query_string']);
		if($array_of_latlong === false){
			$catch_error = array();
			$catch_error = throw_error("error_3");
			echo  $catch_error['error'];
		}
		else{
		// check $array_of_latlong is empty count() empty()
			$first_eyebank_display = get_nearest_eyebank($array_of_latlong[0],$array_of_latlong[1],$lastentry_id);
			if($first_eyebank_display === false){
				$catch_error = array();
				$catch_error = throw_error("error_4");
				echo  $catch_error['error'];
			}
			else{
				display($first_eyebank_display , $lastentry_id , "");
				
			}
		}
	}
	$t = end_timer($s);//stop time recording and print it on the screen
	$end_timer = round($t,3);
	$sql_insert_response_time = "UPDATE `helpline`.`eyebank_query_log` SET `response_time` = '".$end_timer."' where id = '".$lastentry_id."' ";
	$result_insert_response_time = mysql_query($sql_insert_response_time);
	//$lastentry_id = mysql_insert_id();
}

//****************************************************************************************************************************************

function search_eyebank_for_area($trim_user_message,$mobile_number,$s){
	$array_prefix_city_state = array();
	$aray_prefix_quality = array();
	$array_of_latlong = array();
	$pincode_for_city = "";
	$lastentry_id ="";
	$query_string = "";
	
	$array_prefix_city_state = extract_area_prefix($trim_user_message);
	if($array_prefix_city_state['flag'] === "incorrect"){
		$catch_error = array();
		$catch_error = throw_error("error_5");
		echo  $catch_error['error'];
		$lastentry_id = create_log($trim_user_message , "0" , "0" , $mobile_number , "0", "0", $catch_error['id']);
	}
	else{
		$pincode_for_city = get_pincode_for_city_state($array_prefix_city_state['city'],$array_prefix_city_state['state']);
		if($pincode_for_city === "0"){
		$catch_error = array();
		$catch_error = throw_error("error_6");
		echo  $catch_error['error'];
		$lastentry_id = create_log($trim_user_message , "0" , "0" , "0" , "0" , $mobile_number , "0", "0", $catch_error['id']);
		}
		else{
			$aray_prefix_quality = get_prefixid_quality($array_prefix_city_state['prefix'],"flagarea");
			$query_string = $array_prefix_city_state['city'].",".$array_prefix_city_state['state'];
			$query_string = $pincode_for_city;
			$lastentry_id = create_log($trim_user_message , $aray_prefix_quality['prefix_id'] , $array_prefix_city_state['city'] , $array_prefix_city_state['state'] , $query_string , $mobile_number , $aray_prefix_quality['query_quality'],"0","0");
			$array_of_latlong =  get_lat_long($pincode_for_city);
			if($array_of_latlong === false){
				$catch_error = array();
				$catch_error = throw_error("error_6");
				echo  $catch_error['error'];
			}
			else{
				// check $array_of_latlong is empty count() empty()
				$first_eyebank_display = get_nearest_eyebank($array_of_latlong[0],$array_of_latlong[1],$lastentry_id);
				if($first_eyebank_display === false){
					$catch_error = array();
					$catch_error = throw_error("error_4");
					echo  $catch_error['error'];
				}
				else{
					display($first_eyebank_display , $lastentry_id , "");
				}
			}
		}
	}
	$t = end_timer($s);//stop time recording and print it on the screen
	$sql_insert_response_time = "UPDATE `helpline`.`eyebank_query_log` SET `response_time` = '".$t."' where id = '".$lastentry_id."' ";
	$result_insert_response_time = mysql_query($sql_insert_response_time);
	//$lastentry_id = mysql_insert_id();
}

//*********************************************************************************************************************************************

function match_pattern($trim_user_message,$mobile_number,$s){
	$aray_prefix_quality = array();
	$pattern = "(\d\d\d\d\d\d)";
	$lastentry_id ="";
	$check_execution = preg_match($pattern,$trim_user_message,$matches);
	if(sizeof($matches) === 0){
		$catch_error = array();
		$catch_error = throw_error("error_7");
		echo  $catch_error['error'];
		$lastentry_id = create_log($trim_user_message , "0" , "0" , "0" , "0" , $mobile_number , "0", "0", $catch_error['id']);	
	}
	else{
		$trim_matched_pattern = trim($matches[0]);
		$aray_prefix_quality['prefix_id'] = 0;
		$aray_prefix_quality['query_quality'] = "50";
		$lastentry_id = create_log($trim_user_message , "0" , "0" , $aray_prefix_quality['prefix_id'] , $trim_matched_pattern , $mobile_number , $aray_prefix_quality['query_quality'] ,"0" , "0");
		$array_of_latlong =  get_lat_long($trim_matched_pattern);
		if($array_of_latlong === false){
			$catch_error = array();
			$catch_error = throw_error("error_3");
			echo  $catch_error['error'];
		}
		else{
			// check $array_of_latlong is empty count() empty()
			$first_eyebank_display = get_nearest_eyebank($array_of_latlong[0],$array_of_latlong[1],$lastentry_id);
			if($first_eyebank_display === false){
				$catch_error = array();
				$catch_error = throw_error("error_4");
				echo  $catch_error['error'];
			}
			else{
				display($first_eyebank_display , $lastentry_id , "");
			}
		}
	}
	$t = end_timer($s);//stop time recording and print it on the screen
	$sql_insert_response_time = "UPDATE `helpline`.`eyebank_query_log` SET `response_time` = '".$t."' where id = '".$lastentry_id."' ";
	$result_insert_response_time = mysql_query($sql_insert_response_time);
	//$lastentry_id = mysql_insert_id();
}

//*********************************************************Helper Function*********************************************************************

function get_prefixid_quality($prefix_string,$flag){
	$sql_get_prefix = "SELECT `id` FROM `prefixes` WHERE `prefix` = '".$prefix_string."' ";
	$array_prefixid_quality = array();
	$result_get_prefix = mysql_query($sql_get_prefix);
	$num_rows_fetched = mysql_num_rows($result_get_prefix);
	if($num_rows_fetched === 0){
		if($flag === "area"){
			$array_prefixid_quality['prefix_id'] = 3;
		}
		else{
			$array_prefixid_quality['prefix_id'] = 2;//$flag = pin
		}
		$array_prefixid_quality['query_quality'] = "50"; 
	}
	else{
		while($row = mysql_fetch_assoc($result_get_prefix)) {
			$array_prefixid_quality['prefix_id'] = $row['id'];
		}
		$array_prefixid_quality['query_quality'] = "100";
	}
	return $array_prefixid_quality;
}

//************************************************************************************************************************************************

function create_log($message , $prefix_id , $city , $state , $query_string , $mobile_number , $query_quality , $responded_eyebank_id , $error_id){
$last_entered_id = "";
$query_area = "";
if($city === "0" || $state === "0"){
	$query_area = "0";
}
else{
	$query_area = $city . "," . $state;
}

$sql_insert_query ="INSERT INTO `helpline`.`eyebank_query_log` (`id`, `message`, `prefix_id`, `query_string`, `query_area`, `phone_number`, `timestamp`, `query_quality` , `responded_eyebank_id` , `error_id`) VALUES (NULL, '".$message."', '".$prefix_id."', '".$query_string."', '".$query_area."', '".$mobile_number."', NOW(), '".$query_quality."', '".$responded_eyebank_id."', '".$error_id."');";
$result_insert_query = mysql_query($sql_insert_query);
$last_entered_id = mysql_insert_id();
return $last_entered_id;
}

//************************************************************************************************************************************************

function throw_error($code){
	$fetched_error = array();
	$sql_error = "SELECT `id` , `error` FROM errors WHERE `error_code` = '".$code."' ";
	$result_error = mysql_query($sql_error);
	$result_set = mysql_fetch_assoc($result_error);
	$fetched_error['id'] = $result_set['id'];
	$fetched_error['error'] = "@success@ @autoreply@ " . $result_set['error'];
	return $fetched_error;	
}

//************************************************************************************************************************************************

function update_responded_eyebank($eyebank_query_log_id , $eyebank_id){
	 $sql = "UPDATE `eyebank_query_log` SET`responded_eyebank_id`= '".$eyebank_id."' WHERE `id` = '".$eyebank_query_log_id."'";
	 mysql_query($sql);	
}

//************************************************************************************************************************************************

function extract_pincode_prefix($trim_user_message){
	$user_message_array = explode('pin',$trim_user_message);
	$pincode_index = "";
	$array_eliminate = array(""," ");
	$flag = "";
	$prefix = "";
	$extract_string = "";
	
	$user_message_array = array_diff($user_message_array, $array_eliminate);// remove the elements who's values are ZERO
	$user_message_array = array_values($user_message_array);//reindexing the whole array
	if(!sizeof($user_message_array) == "0"){
		for($i =0; $i <= sizeof($user_message_array) - 1; $i++){
			$pattern = "(\d\d\d\d\d\d)";
			$user_message_array[$i];
			if(preg_match($pattern,$user_message_array[$i],$matches)){
				$pincode_index = $i;
				$extract_string = trim($matches[0]);
				$flag = "2";
				break;
			}
			else{
				$flag = "0";
			}
		}
		if($pincode_index === 0){
			$array_pincode_prefix['prefix'] = "PIN";
		}
		else{
			$prefix = str_replace(" ","",$user_message_array[0],$count);
			
			$complete_prefix = $prefix . " " . "pin";
			$array_pincode_prefix['prefix'] = trim(strtoupper($complete_prefix));
		}
		$array_pincode_prefix['query_string'] = $extract_string;
	}
	else{$flag = "1";}
	if($flag === "2"){return $array_pincode_prefix;}
	else{return $flag;}

}

//****************************************************************************************************************************

function extract_area_prefix($trim_user_message){
	$prefix = "";
	$extract_area = "";
	$array_area_index = "";
	$flag = "0";
	$array_city_prefix = array();
	$extract_city_state = array();
	$array_prefix_city_state = array();
	$array_user_message = array();
	
	$array_user_message = explode(' ',$trim_user_message);
	$array_area_index = array_search("area",$array_user_message);
	if($array_area_index === false){
		$pattern = ".[eye]*\s*[bank]*\s*area.";
		$matches = array();
		preg_match($pattern,$trim_user_message,$matches);
		$string_length = strpos($trim_user_message,'area') + 4;
		$query_string = substr($trim_user_message,$string_length);
		
		if($string_length != strlen($matches[0]) ){
			$len = strpos($trim_user_message,'area');
			$complete_prefix = substr($trim_user_message,0,$len) . "area";
		}
		else{
			$prefix = str_replace(" ","",$matches[0],$count);
			$prefix = str_replace("area","",$matches[0],$count);
			$complete_prefix = $prefix . " " . "area";
		}
		$array_prefix_city_state['prefix'] = trim(strtoupper($complete_prefix)); 
		$extract_city_state = explode(",",$query_string);
		$array_prefix_city_state['city'] = trim($extract_city_state[0]);
		$array_prefix_city_state['state'] = trim($extract_city_state[1]);
		if($array_prefix_city_state['state']=== "" || $array_prefix_city_state['city'] === ""){
			$array_prefix_city_state['flag'] = "incorrect";
		}
		else{
			//print_r($array_city_prefix);
			$array_prefix_city_state['flag'] = "correct";
		}
	}
	
	else{
		if($array_area_index === 0){
			//$prefix = "CITY";
			$array_prefix_city_state['prefix'] = "AREA";
		}
		else{
			for($i=0;$i <= $array_area_index-1;$i++){
				$prefix .=trim($array_user_message[$i]);
			}
			$complete_prefix = $prefix . " " . "area";
			$array_prefix_city_state['prefix'] = trim(strtoupper($complete_prefix));
		}
		for($i=$array_area_index+1;$i <= sizeof($array_user_message)-1;$i++){
			$extract_area .= $array_user_message[$i] . "#";
		}
		$extract_area = trim($extract_area);
		$extract_city_state = explode(",",$extract_area);
		if(sizeof($extract_city_state)>1){
			$mediate_string_city = $extract_city_state[0];
			$array_prefix_city_state['city'] = trim(str_replace("#"," ",$mediate_string_city,$count));
			$mediate_string_state = $extract_city_state[1];
			$array_prefix_city_state['state'] = trim(str_replace("#"," ",$mediate_string_state,$count));
			if($array_prefix_city_state['state']=== "" || $array_prefix_city_state['city'] === ""){
				$array_prefix_city_state['flag'] = "incorrect";
			}
			else{
				//print_r($array_city_prefix);
				$array_prefix_city_state['flag'] = "correct";
			}
		}
		else{ $array_prefix_city_state['flag'] = "incorrect";} 
	}
	return $array_prefix_city_state;
}

//*****************************************************************************************************************************************

function get_pincode_for_city_state($city,$state){
	echo $str_pincodes = "";
	$flag = "0";
	$sql_fetch_pincode = " SELECT distinct `pincode` FROM `pincodes` AS p , (SELECT `pincode_id` FROM `geocodes_map` WHERE (`city` = '".ucwords($city)."' || `city` = '".strtolower($city)."'  || `city` = '".strtoupper($city)."') AND `state_id` = (SELECT `id` FROM `states` WHERE `state` = '".ucwords($state)."' || `state` = '".strtolower($state)."'  || `state` = '".strtoupper($state)."')) AS g WHERE p.`id` = g.`pincode_id` ";
	$result_fetch_pincode = mysql_query($sql_fetch_pincode);
	$num_rows_fetched = mysql_num_rows($result_fetch_pincode);
	if($num_rows_fetched == 0){
		return $flag;
		//echo $flag;	
	}
	else{
		for($i=0;$i <= $num_rows_fetched-1;$i++){
			$fetched_pincodes = mysql_fetch_array($result_fetch_pincode);
			$str_pincodes.= " " . $fetched_pincodes['pincode'];
		}
		$str_pincodes = trim($str_pincodes);
		$array_pincodes = explode(" " ,$str_pincodes);
		$random_element_key = array_rand($array_pincodes,1);
		$final_pincode = $array_pincodes[$random_element_key];
		return  $final_pincode;
		//echo $final_pincode;
	}
}

//*******************************************************************function used by every case************************************************

function get_lat_long($pincode) {
  // This function pulls just the lattitude and longitude from the
  // database for a given zip code.
  //die($pincode);
	$sql = "SELECT g.`Lat` , g.`Lng` , g.`id`  FROM `geocodes_map`  AS g,  (SELECT `id` FROM `pincodes` WHERE `pincode` = '".$pincode."' ) AS p WHERE g.`pincode_id` = p.`id`";
	$result = mysql_query($sql);
	if (!$result) {
		return false;
	} 
	else {
		if(mysql_num_rows($result) === 0){
			return false;
		}
		else{
			$row = mysql_fetch_array($result);
			mysql_free_result($result);
			$row = array_unique($row);//removing duplicate values from an array 
			return $row;
		}
	}      
}

//***************************************************************************************************************************************

function get_nearest_eyebank($latitude,$longitude,$lastentry_id){
	//die($try_id);
	$sql_eyebank ="SELECT i_databank_id,v_hospital_name , ( 6371 * ACOS( COS( RADIANS( $latitude ) ) * COS( RADIANS( v_lat ) ) * COS( RADIANS( v_lang ) - RADIANS($longitude ) ) + SIN( RADIANS($latitude ) ) * SIN( RADIANS( v_lat ) ) ) ) AS distance FROM eyebanks HAVING distance between 0 and 100 ORDER BY distance LIMIT 0 , 15 ";
	$result_fetched_eyebanks = mysql_query($sql_eyebank);
	$fetched_rows_count = mysql_num_rows($result_fetched_eyebanks);
	while($fetched_eyebanks = mysql_fetch_assoc($result_fetched_eyebanks)){
		$sql_insert_cache = "INSERT INTO `helpline`.`eyebank_response_cache` (`id`, `eyebank_query_log_id`, `eyebank_id`, `is_served`, `timestamp`, `distance`) VALUES (NULL, '".$lastentry_id."', '".$fetched_eyebanks['i_databank_id']."', '0', NOW(), '".$fetched_eyebanks['distance']."');";
		mysql_query($sql_insert_cache);
	}
	$sql_first_eyebank = "SELECT c.`id` , e.`i_databank_id` , e.`v_hospital_name` , e.`i_phone_code1` , e.`i_phone_code2`  , e.`i_mobile1` , e.`i_mobile2` , c.`distance`  FROM `eyebanks` AS e , (SELECT `eyebank_id` ,`distance` , `id`  FROM `eyebank_response_cache` WHERE `eyebank_query_log_id` = '".$lastentry_id."' LIMIT 0 , 1) AS c WHERE e.`i_databank_id` = c.`eyebank_id`";
	$result_first_eyebank = mysql_query($sql_first_eyebank);
	if(mysql_num_rows($result_first_eyebank) === 0){
		//echo "there is no eyebank in your city";
		return false;
	}
	else{
		
		$first_eyebank_detail = array();
		$phone_number = "";
		while($first_eyebank = mysql_fetch_assoc($result_first_eyebank)){
			$distance = $first_eyebank['distance'];
			//print_r($first_eyebank);
			$first_eyebank = array_unique($first_eyebank);//removing duplicate values from an array 
			
			$arr = Array("0");// initialize an array
			$first_eyebank = array_diff($first_eyebank, $arr);// remove the elements who's values are ZERO
			$first_eyebank = array_values($first_eyebank);//reindexing the whole array
			//print_r($first_eyebank);
			for($i=3; $i <= sizeof($first_eyebank)-1;$i++){
				$phone_number .= $first_eyebank[$i] . ",";	
			}
			$first_eyebank_detail[0] = $first_eyebank[1];
			$first_eyebank_detail[1] = $first_eyebank[2];
			$first_eyebank_detail[2] = $phone_number;
			$first_eyebank_detail[3] = $distance;
			$first_eyebank_detail[4] = $first_eyebank[0];
		}
		$sql_update_is_served = "UPDATE `eyebank_response_cache`SET `is_served` = '1' where id = '".$first_eyebank_detail[4]."' ";
		mysql_query($sql_update_is_served);
		return $first_eyebank_detail;
		//print_r($first_eyebank_detail);
		//die();
	}
}

//*******************************************************************************************************************************************

function display($show_screen , $eyebank_query_log_id , $eyebank_id){
$sent_reply = "";
update_responded_eyebank($eyebank_query_log_id , $show_screen[0]);
$sent_reply = "@success@ @autoreply@ " . "Name $show_screen[1]" ." ". "Ph. No. $show_screen[2]" ." " . "Distance $show_screen[3]Kms" ." " ;
echo $sent_reply ;
}

//*********************************************************************************************************************************************

//funtion to fetch next eyebank for a particular query
function fetch_next_eyebank($eyebank_query_log_id){
	$sql_fetch_previous_entry = " SELECT r.`id` ,e.`i_databank_id`, e.`v_hospital_name` , e.`i_phone_code1` , e.`i_phone_code2` , e.`i_mobile1` , e.`i_mobile2` , r.`distance`  FROM `eyebanks` AS e ,  (SELECT `id`, `eyebank_id` , `distance` FROM `eyebank_response_cache` AS e WHERE `eyebank_query_log_id` = '".$eyebank_query_log_id."' and `is_served` = \"0\" LIMIT 0 , 1)  AS r  WHERE e.`i_databank_id` = r.`eyebank_id` " ;
	$result_fetch_previous_entry = mysql_query($sql_fetch_previous_entry);
	if(mysql_num_rows($result_fetch_previous_entry) === 0){
		return false;
	}
	else{
		for($i=1; $i <= 1; $i++){
			$next_row = mysql_fetch_array($result_fetch_previous_entry);
			$next_row = array_unique($next_row);//removing duplicate values from an array 
			$arr = Array("0");// initialize an array
			$next_row = array_diff($next_row, $arr);// remove the elements who's values are ZERO
			$next_row = array_values($next_row);//reindexing the whole array
			//print_r($next_row);
		}
		$sql_update_is_served = "UPDATE `eyebank_response_cache`SET `is_served` = '1' where id = '".$next_row[0]."' ";
		mysql_query($sql_update_is_served);
		return $next_row;
	}
}

//**********************************************************************************************************************************************

function start_timer(){
	$starttime = "";
	$mtime = microtime();
	$mtime = explode(" ",$mtime);
	$mtime = $mtime[1] + $mtime[0];
	$starttime = $mtime;
	return $starttime;
}

//*********************************************************************************************************************************************

function end_timer($starttime){
	$mtime = microtime();
	$mtime = explode(" ",$mtime);
	$mtime = $mtime[1] + $mtime[0];
	$endtime = $mtime;
	$totaltime = ($endtime - $starttime);
	//echo "<br />This page was created in ".$totaltime." seconds";
	return $totaltime;
}
//echo "<br />";
//echo "<a href=\"javascript: history.go(-1)\">Back</a>";

//*****************************************************************END OF FILE****************************************************************


//echo $t = end_timer($s);//stop time tecording and print it on the screen
/*$s = start_timer();
mysql_pconnect('localhost','root','') or die(mysql_error()); 
mysql_select_db('helpline') or die(mysql_error());
$sql = "SELECT * FROM `timezone` WHERE `id` = 1";
$result = mysql_query($sql);
echo $result;
while($rw = mysql_fetch_assoc($result)){
	echo $rw['TZ'];
}
while($row = mysql_fetch_assoc($result_check_previous_entry)) {
  var_dump($row);
}
 // our initial array
    $arr = Array("blue", "green", "red", "yellow", "green", "orange", "yellow", "indigo", "red");
    print_r($arr);
	echo "<br />";
     
    // remove the elements who's values are yellow or red
    $arr = array_diff($arr, array("yellow", "red"));
    print_r($arr);
		echo "<br />";
    // optionally you could reindex the array
    $arr = array_values($arr);
    print_r($arr);
		echo "<br />";
*/
?>
</body>
</html>