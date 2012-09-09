<?php

// define variables
$mail_box = '{mail.blackswandev.net/novalidate-cert}'; //imap example
$mail_user = 'mailuser@blackswandev.net'; //mail username
$mail_pass = 'admin123'; //mail password
$delete = '1'; //deletes emails with at least this number of failures

// include database connection settings
include('conn.inc.php');

// query table emailid
$query = mysql_query("SELECT * FROM emailid WHERE statuscode = '';");

while ($DBdata = mysql_fetch_assoc($query)) 
{
	$email .= $DBdata['email']. ", ";
}

$email_list = explode(',', $email);

// count how many emails there are.
$total_emails = count($email_list);

// go through the list and trim off the newline character.
for ($counter=0; $counter<$total_emails; $counter++)
{
$email_list[$counter] = trim($email_list[$counter]);
}

$to = $email_list;
$mail_subject = 'testing bounce email';
$mail_msg = 'Mail succedded';
$headers = 'From: mailuser@blackswandev.net' . "\r\n" . 'Reply-To: mailuser@blackswandev.net' . "\r\n" . 'Return-Path: mailuser@blackswandev.net' . "\r\n" . 'X-Mailer: PHP/' . phpversion();

foreach ($to as $mail_to) 
{
	// send mail via php smtp function
	$send_mail = mail($mail_to,$mail_subject,$mail_msg, $headers, "-f $mail_user");
	echo 'mailed to: '. $mail_to.'<br />';
}
  
// connect to mailbox using imap
$conn = imap_open ($mail_box, $mail_user, $mail_pass) or die(imap_last_error());
//read messages
$num_msgs = imap_num_msg($conn);

//start bounce class
require_once('bounce_driver.class.php');
$bouncehandler = new Bouncehandler();

// get the failures
$email_addresses = array();
$delete_addresses = array();

for ($n=1;$n<=$num_msgs;$n++) 
{
	$bounce = imap_fetchheader($conn, $n).imap_body($conn, $n); //entire message
	$multiArray = $bouncehandler->get_the_facts($bounce);
	$statusmsg = $bouncehandler->fetch_status_messages($bounce);
	$statuscode = $bouncehandler->format_status_code($bounce);
  
	echo '<br />multiArray: ';
	var_dump ($multiArray);
	echo '<br />statusmsg: ';
	var_dump ($statusmsg);
	echo '<br /> statuscode: ';
		var_dump ($statuscode);
	echo '<br />------------------------------------------------------------------------------------------';
	if (!empty($multiArray[0]['action']) && !empty($multiArray[0]['status']) && !empty($multiArray[0]['recipient']) ) 
	{
		if ($multiArray[0]['action']=='failed') 
		{
			$email_addresses[$multiArray[0]['recipient']]++; //increment number of failures
			$delete_addresses[$multiArray[0]['recipient']][] = $n; //add message to delete array
		} //if delivery failed
	} //if passed parsing as bounce
} //for loop

// process the failures
foreach ($email_addresses as $key => $value)	//trim($key) is email address, $value is number of failures 
{ 
    if ($value>=$delete) 
	{
    /*
    do whatever you need to do here, e.g. unsubscribe email address
    */
		// mark for deletion
		foreach ($delete_addresses[$key] as $delnum)
		{
			echo 'bounce email found: '. $value.'<br />';
		}
    } //if failed more than $delete times
} //foreach

// delete messages
imap_expunge($conn);

// close
imap_close($conn);

?>