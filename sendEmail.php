<?php

// override PHP limits for memory and timeout
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 1800); //1800 seconds = 30 minutes
// clean up strict input mode
ini_set('sql-mode', 'NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION');

date_default_timezone_set('America/New_York');
// Error log for tracking succes/failure and errors
 $errorLog = "error_log";
 
// database connection
$databasehost = "198.61.136.72";
$databasename = "rets_mlspin";
$databaseusername = "mlspin_warren";
$databasepassword = "BeaconSt@1";


 //echo "+ Connecting to $databasename<br>\n";

// Create connection and check result
$mysqli = new mysqli($databasehost,$databaseusername,$databasepassword, $databasename) 
 or die('+ Failed to connect to MySQL: Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);

 if ($mysqli->connect_error) {
    exit(1);
}
 
 //echo '+ Success... ' . $mysqli->host_info . "<br>\n";

include 'functions.php';

$limit = 5; // limit of the listing

$sales_url = "http://adkalpha.com/warren-ee/index.php/sales/single/";

//available town/city
//$town_array = array("Boston","Brookline","Cambridge","Newton","Weston","Wellesley","Somerville","Arlington","Belmont","Lexington","Needham","Watertown","Winchester");

//availabel status
$status_array = array("New","Back on Market","Reactivated","Price Changed");

//users whose status is yes for daily email
$users = array(
 			1=>array('id'=>1, 'name'=>"Mailtest Warren", 'email'=>'warrenemail1@gmail.com'), 
 			2=>array('id'=>2, 'name'=>"Warren Emailtest", 'email'=>'warren2email@gmail.com')
 		);

 //saved search by user id
 //SELECT field_id_252 as search_criteria, field_id_253 as type, field_id_254 as member id FROM `exp_channel_data` where channel_id='20' and field_id_254=',112,' limit 10 -- member id format ",112,"
 $saved_search_1 = array(
 					1=>array('keys'=>'keywords,|min-price,500000|max-price,1500000|min-beds,2|min-baths,2|category,sale|town,boston|location(s),south-boston,waterfront|', 'type'=>'sales'),
 					2=>array('keys'=>'keywords,|min-price,500000|max-price,1500000|min-beds,2|min-baths,2|category,sale|town,somerville|location(s),|', 'type'=>'sales')
 				);

 $saved_search_2 = array(
 					1=>array('keys'=>'keywords,|min-price,1000|max-price,5000|min-beds,2|min-baths,2|category,sale|town,boston|location(s),|location(s),brookline', 'type'=>'rentals')
 				);

list($first_date,$second_date) = date_frequency('1 days','23:59:59');


foreach($users as $key)
{
	$sales="";
	$rentals="";
	$html="";
	$search_results = false;
	//query db to retrive saved search by user

	if($key['id']==2)
		$saved_search_data = $saved_search_2;
	else
		$saved_search_data = $saved_search_1;

	if(count($saved_search_data)>0)
	{
		$j=1;
		$k=1;
		foreach($saved_search_data as $search_data)
		{
			//get search data
			list($min_price,$max_price,$bed,$town_array,$neighborhoods_array)=get_search_data($search_data['keys']);
			
			//for sales
			if($search_data['type']=='sales')
			{
				//create the query
				$sql = "SELECT cc.LIST_NO, NO_BEDROOMS, NO_ROOMS, STATUS, LIST_PRICE, STREET_NUM, STREET_NAME, UNIT_NO, NO_UNITS, UNIT_LEVEL, TOWN, STATE, ZIP_CODE, Neighborhoods
					FROM resi_cc cc
					left join resi_extras e on cc.LIST_NO=e.LIST_NO
					where ";
				$sql .="(UPDATE_DATE BETWEEN '$second_date' AND '$first_date')
					and (LIST_PRICE BETWEEN '$min_price' and '$max_price')
					and NO_BEDROOMS='$bed'";

				$sql.=sql_array($town_array,"TOWN","AND","OR");
				$sql.=sql_array($status_array,"STATUS","AND","OR");
				$sql.=sql_array($neighborhoods_array,"Neighborhoods","AND","OR","LIKE");


				/*$sql .=") and ";
						foreach($status_array as $status)
						{
							if(!empty($status))
								$sql .= " STATUS='".$status."'  OR";	
						}

					$sql=substr($sql,0,(strlen($sql)-3)); //this will eat the last OR
					*/

				$sql .=" order by UPDATE_DATE desc";

				// execute the query and check result
					if (!$result = $mysqli->query($sql))
					{
						 echo "+ $sql<br>\n\n";
						die("+ DB Error: ".$mysqli->error."<br>\n\n");

					}

				$total_data = $result->num_rows;
				echo $sql;
				if($total_data>$limit)
				{
						
					$sql .=" limit 0,$limit";
					 
					// execute the query and check result
					if (!$result = $mysqli->query($sql))
					{
						 echo "+ $sql<br>\n\n";
						die("+ DB Error: ".$mysqli->error."<br>\n\n");

					}

					$total_data = $total_data - $limit;
				}

					if($result->num_rows>0)
					{
						$search_results = true;
						// fetch object array  
						$sales.='<tr><td colspan="2" style="color:#663333; font-weight:bold; font-size:16px; border-bottom:1px solid #636466; padding-bottom:3px;">Sales Search #'.$j.': '.$result->num_rows.' Properties</td></tr>';
					    while ($obj = $result->fetch_object()) 
					    {
					        $sales.='<tr><td style="padding-top:10px;" width"="40" valign="top">';
					        $sales.='<img src="http://media.mlspin.com/photo.aspx?mls='.$obj->LIST_NO.'&n=0" width="40" height="40" align="left" style="border:0px;" border="0">';
					        $sales.='</td><td style="padding-top:10px; padding-left:16px;">';
					        $sales.='<a href="'.generate_links($sales_url,array($obj->STREET_NUM,str_replace(" ", "-",$obj->STREET_NAME),$obj->UNIT_NO,$obj->TOWN,'ma',$obj->ZIP_CODE)).'" target="_blank" style="text-decoration:none; color:#663333;">';
					        $sales.='<span style="text-transform:uppercase; text-decoration:none; color:#663333;">'.$obj->STATUS.'</span> - $'.number_format($obj->LIST_PRICE,'0','',',');
					        $sales.=' - '.$obj->STREET_NUM.' '.$obj->STREET_NAME.'. in '.$obj->TOWN.', '.format_Neighborhoods($obj->Neighborhoods).' - <span style="color:#222; font-weight:normal;">2 unit, '.$obj->NO_ROOMS.' total room, '.$obj->NO_BEDROOMS.' total bedroom</span>';
					        $sales.='</a></td></tr>';
					    }

					    $sales.='<tr><td colspan="2"><div style="border-top:1px solid #636466; margin-top:15px;';
					    if($total_data<$limit)
					    	$sales.='margin-bottom:20px';
					    $sales.='">';

					    if($total_data>$limit)
					    {
					    	$sales.='<span style="float:right; text-align:right; background-color:#222222; font-size:18px; font-weight:normal; padding:5px 15px;">
					    				<a href="#" style="text-decoration:none; color:#fff;">VIEW ALL ('.$total_data.')</a></span>';
					    }

					    $sales.='</div></td></tr>';
						$j++;
					}
					// free result set 
    				$result->close();	
			}

			//for rent
			if($search_data['type']=='rentals')
			{
				//zillow only has rentals
				//create the query
				/*$sql = "SELECT cc.LIST_NO, NO_BEDROOMS, NO_ROOMS, STATUS, LIST_PRICE, STREET_NUM, STREET_NAME, UNIT_NO, TOWN, STATE, ZIP_CODE, Neighborhoods
					FROM resi_rn cc
					left join resi_extras e on cc.LIST_NO=e.LIST_NO
					where (";
						foreach($town_array as $town)
						{
							if(!empty($town))
								$sql .= " TOWN='".$town."'  OR";	
						}
					
					$sql=substr($sql,0,(strlen($sql)-3)); //this will eat the last OR

				$sql .=") and (";
						foreach($status_array as $status)
						{
							if(!empty($town))
								$sql .= " STATUS='".$status."'  OR";	
						}

					$sql=substr($sql,0,(strlen($sql)-3)); //this will eat the last OR

				$sql .=") and (UPDATE_DATE BETWEEN '$second_date' AND '$first_date')
					and (LIST_PRICE BETWEEN '$min_price' and '$max_price')
					and NO_BEDROOMS='$bed'
					order by UPDATE_DATE desc
					limit 0,$limit
					";
		
					// execute the query and check result
					if (!$result = $mysqli->query($sql))
					{
						 echo "+ $sql<br>\n\n";
						die("+ DB Error: ".$mysqli->error."<br>\n\n");

					}

					if($result->num_rows>0)
					{
						$search_results = true;

						// fetch object array 
						$rentals.='<tr><td colspan="2" style="color:#663333; font-weight:bold; font-size:16px; border-bottom:1px solid #636466; padding-bottom:3px;">Rentals Search #'.$j.': '.$result->num_rows.' Properties</td></tr>';
					    while ($obj = $result->fetch_object()) 
					    { 
					        $rentals.='<tr><td style="padding-top:10px;" width"="40" valign="top">';
					        $rentals.='<img src="http://media.mlspin.com/photo.aspx?mls='.$obj->LIST_NO.'&n=0" width="40" height="40" align="left" style="border:0px;" border="0">';
					        $rentals.='</td><td style="padding-top:10px; padding-left:16px;">';
					        $rentals.='<a href="'.generate_links($sales_url,array($obj->STREET_NUM,str_replace(" ", "-",$obj->STREET_NAME),$obj->UNIT_NO,$obj->TOWN,'ma',$obj->ZIP_CODE)).'" target="_blank" style="text-decoration:none; color:#663333;">';
					        $rentals.='<span style="text-transform:uppercase; text-decoration:none; color:#663333;">'.$obj->STATUS.'</span> - $'.number_format($obj->LIST_PRICE,'0','',',');
					        $rentals.=' - '.$obj->STREET_NUM.' '.$obj->STREET_NAME.'. in '.$obj->TOWN.', '.format_Neighborhoods($obj->Neighborhoods).' - <span style="color:#222; font-weight:normal;">2 unit, '.$obj->NO_ROOMS.' total room, '.$obj->NO_BEDROOMS.' total bedroom</span>';
					        $rentals.='</a></td></tr>';
					    }

					    $rentals.='<tr><td colspan="2"><div style="border-top:1px solid #636466; margin-top:15px;"><span style="float:right; text-align:right; background-color:#222222; font-size:18px; font-weight:normal; padding:5px 15px;"><a href="#" style="text-decoration:none; color:#fff;">VIEW ALL</a></span></div></td></tr>';
					$k++;
					}*/
			}

			echo "<br>search by: <br> Town : ";
			print_r($town_array);
			echo '<br> Neighborhoods : ';
    		print_r($neighborhoods_array).'<br>';
		}

    	if($search_results===true)
    	{
    		

    		echo email_body("Daily","http://adkalpha.com/mlsdata/email/images",$sales,$rentals); exit;
    		//echo 'email sent to user '. $key['name'].'<br>';
    		//send_email($key['email'],"Property Listing Updates",email_body("Daily","http://adkalpha.com/mlsdata/email/images",$sales,$rentals));
    	}
		
	}

}

//echo "<br><br>+ Disconnecting from $databasename";

$mysqli->close();

?>
