<?php

function format_date($input_date) {
	$date_sep = '/';
	$output_date = $input_date;
	if(mb_strlen($input_date, 'UTF-8') <= 8) {
		$parts = preg_split("#(\/|\-)#", $input_date);
		$day = $parts[0];
		$month = $parts[1];
		$year = $parts[2];
		if(strlen($day) === 1) $day = '0' . $day;
		if(strlen($month) === 1) $month = '0' . $month;
		if($year > date('y') + 10) {
			$year = '19' . $year;
		} else {
			$year = '20' . $year;
		}
		$output_date = $day . $date_sep . $month . $date_sep . $year;
	}
	return $output_date;
}

function format_mail_date($input_date)
{
    $timestamp = @strtotime($input_date);
    if(!$timestamp)
        return $input_date;
        
    $output_date = date("Y-m-d H:i:s", $timestamp);
    if(!$output_date)
        return $input_date; 

    return $output_date;
}

function format_amounts($input_amount) {
	
	$output_amount = str_replace(',', '.', $input_amount);
    
	return $output_amount;
}

function format_currency($input_string, $ReaderObject) {
    $OutputString = $ReaderObject->translate($input_string, 'devises');
    return $OutputString;
}

function format_mail_address($input_mail) {
	$explode = explode(" ", $input_mail);
	$mail = end($explode);
	$Output_mail = str_replace("<", "", $mail);
	$Output_mail = str_replace(">", "", $Output_mail);
	
	return $Output_mail;

}

?>