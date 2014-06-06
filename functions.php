<?php
function date_frequency($interval,$time)
{
	$date = new DateTime();

	// subtract number of days from now -- getting previous day date
	date_sub($date, date_interval_create_from_date_string('1 days'));

	// setting date format to "Y-m-d H:i:s"
	$previous_date = $date->format("Y-m-d").' '.$time;

	//getting date as defined in interval from $previous_date
	date_sub($date, date_interval_create_from_date_string($interval));

	// setting date format to "Y-m-d H:i:s"
	$interval_date = $date->format("Y-m-d").' '.$time;

	//return in array
	return array($previous_date,$interval_date);
}

function get_search_data($data)
{
	//generating array
	$parts = explode('|',$data);
	for($i=0;$i<count($parts);$i++)
	{
		$min_price_data = explode(',',$parts[1]);
		$min_price = end($min_price_data);

		$max_price_data = explode(',',$parts[2]);
		$max_price = end($max_price_data);

		$bed_data = explode(',',$parts[3]);
		$bed = end($bed_data);

		$town = explode(',',$parts[6]); //town
		array_shift($town);

		$locations = explode(',',$parts[7]); //locations
		array_shift($locations);
		is_array($locations);
		return array($min_price,$max_price,$bed,$town,$locations);
	}
}

function sql_array($array,$field,$pre_operator,$operator,$condition="")
{
	if(count($array)>0 && $array[0]!="")
	{
		$sql = " ".$pre_operator;
		$sql.=" ( ";
			foreach($array as $key)
			{
				if(!empty($key))
				{
					if($condition == "LIKE")
						$sql .= $field." LIKE '%$key%'  ".$operator." ";
					else
					$sql .= $field." = '$key'  ".$operator." ";	
				}
			}

		$sql=substr($sql,0,(strlen($sql)-3)); //this will eat the last OR
		$sql.=" ) ";
		return $sql;
	}
	else
		return false;
}

function generate_links($web_url,$parameter_array)
{
	$web_url.= strtolower(implode('-',$parameter_array));
	//http://adkalpha.com/warren-ee/index.php/sales/single/'.$obj->STREET_NUM.'-'.str_replace(" ", "-",strtolower($obj->STREET_NAME)).'-'.$obj->UNIT_NO.'-'.strtolower($obj->TOWN).'-ma-'.$obj->ZIP_CODE.'

	return $web_url;
}

function format_Neighborhoods($data)
{
	$new_data = array();
	$parts = explode(',',$data);

	/*for($i=0;$i<count($parts);$i++)
	{
		$sub_parts = explode('-',$parts[$i]);
		
		array_shift($sub_parts); // remove first element
		array_pop($sub_parts); // removes last element
		$new_data[$i] = implode(' ',$sub_parts);
	}

	return implode(', ',$new_data);*/

	$sub_parts = explode('-',$parts[0]);
	array_shift($sub_parts);
	array_pop($sub_parts);
	return implode(' ',$sub_parts);
}

function send_email($to,$subject,$message)
{
	//set content-type
	$headers = "MIME-Version: 1.0" . "\r\n";
	$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

	// More headers
	$headers .= 'From: <info@adkalpha.com>' . "\r\n";
	//$headers .= 'Cc: ' . "\r\n";

	if(mail($to,$subject,$message,$headers))
		echo "Email Sent to ".$to." Date: ".date("Y-m-d H:i:s");
	else
		echo "Error Occured!!";

	return true;
}

function email_body($interval,$image_path,$sales,$rentals="")
{
	$html='
<table width="600" cellspacing="0" cellpadding="0" border="0" style="background-color:#fff; padding:20px; font-family: Open Sans; font-style: normal; font-size:16px;">
	<tr>
		<td style="border-bottom:1px solid #000;" valign="top">
			<div style="float:left; font-size:28px; color:#222; font-weight:bold; display:inline-block;">LISTING <span style="font-weight:normal;">UPDATES</span></div>
            <div style="float:right;"><img src="'.$image_path.'/logo.png" width="164" height="18" style="border:0px;" border="0" align="right"></div>
			<div style="float:left; font-size:16px; width:100%; display:block; padding-bottom:3px;">A ['.$interval.'] List of Available Properties, Selected just for You</div>
		</td>
	</tr>
	<tr>
		<td style="padding-top:18px;">
            <table width="560" cellspacing="0" cellpadding="0" border="0" style="background-color:#f0f0f0;">
                <tr>
                    <td width="100"><img src="'.$image_path.'/nick-warren.jpg" width="100" height="100" style="border:0px;" border="0"></td>
                    <td valign="top"><div style="float:left; display:inline-block; font-size:24px; font-weight:bold; color:#222; margin:16px 0 0 18px;">NICK WARREN</div>
                    <div style="float:left; width:100%; display:block; color:#222; font-weight:bold; font-style:italic; margin:0 0 10px 18px;">Your Agent</div>
                    <div style="float:left; width:100%; display:block; color:#663333; font-weight:bold; margin:0 0 10px 18px;">(617) 848-9616 | <a href="mailto:nwarren@warrenre.com" style="color:#663333; text-decoration:none;"><font color="#663333" style="text-decoration:none;">nwarren@warrenre.com</font></a></div>
                    </td>
                </tr>
            </table>
		</td>
	</tr>
	<tr>
		<td style="font-size:28px; color:#222; padding-top:24px; border-bottom:1px solid #636466;">
			YOUR NEW LISTINGS
		</td>
	</tr>';
	if(!empty($sales))
	{
	$html.='<tr>
		<td style="padding-top:8px;"><div style="color:#222; padding:6px 12px; background-color:#f0f0f0; font-weight:bold">SALES</div></td>
	</tr>
	<tr>
		<td>
			<table width="560" cellspacing="0" cellpadding="0" border="0" style="padding:10px 20px 0 20px; color:#663333; font-weight:bold; font-size:16px;">
                '.$sales.'							
			</table>
		</td>
	</tr>';
	}
	if(!empty($rentals))
	{

	$html.='
	<tr>
		<td style="padding-top:8px;"><div style="color:#222; padding:6px 12px; background-color:#f0f0f0; font-weight:bold">RENTALS</div></td>
	</tr>
	<tr>
		<td>
			<table width="560" cellspacing="0" cellpadding="0" border="0" style="padding:10px 20px 0 20px; color:#663333; font-weight:bold; font-size:16px;">
                '.$rentals.'							
			</table>
		</td>
	</tr>';
	}
	$html.='
    <!-- footer -->
    <tr>
        <td style="padding-top:0px;">&nbsp;</td>
    </tr>
    <tr>
		<td style="padding-top:8px; background-color:#f0f0f0;">
            <table cellspacing="0" cellpadding="0" border="0">
                <tr>
                    <td width="360" style="font-size:11px; text-align:justify; padding:17px 20px 25px 20px;">
                    <a href="#">View it online</a><br>
                You are receiving this email because you have done business with Warren Residential and or one of its independent contractors. We value this relationship and look forward to doing more business in the future! <br>If you no longer wish to receive emails please <a href="#">unsubscribe</a><br>
                &copy; 2014 Warren Residential, All rights reserved
                    </td>
                    <td width="140" style="padding-left:20px; padding-top:10px;" valign="top">
                        <table width="140" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td style="padding-bottom:15px;">
                                    <a href="#"><img src="'.$image_path.'/twitter.png" border="0" style"border:0px;"></a>
                                </td>
                                <td style="padding-bottom:15px;">
                                    <a href="#"><img src="'.$image_path.'/twitter.png" border="0" style"border:0px;"></a>
                                </td>
                                <td style="padding-bottom:15px;">
                                    <a href="#"><img src="'.$image_path.'/twitter.png" border="0" style"border:0px;"></a>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding-bottom:15px;">
                                    <a href="#"><img src="'.$image_path.'/twitter.png" border="0" style"border:0px;"></a>
                                </td>
                                <td style="padding-bottom:15px;">
                                    <a href="#"><img src="'.$image_path.'/twitter.png" border="0" style"border:0px;"></a>
                                </td>
                                <td style="padding-bottom:15px;">
                                    <a href="#"><img src="'.$image_path.'/twitter.png" border="0" style"border:0px;"></a>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding-bottom:15px;">
                                    <a href="#"><img src="'.$image_path.'/twitter.png" border="0" style"border:0px;"></a>
                                </td>
                                <td style="padding-bottom:15px;">
                                    <a href="#"><img src="'.$image_path.'/twitter.png" border="0" style"border:0px;"></a>
                                </td>
                                <td style="padding-bottom:15px;">
                                    <a href="#"><img src="'.$image_path.'/twitter.png" border="0" style"border:0px;"></a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
         </td>
	</tr>
</table>';

return $html;
}
?>
