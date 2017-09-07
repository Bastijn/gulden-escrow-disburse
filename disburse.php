<?php
/* Disburse script for NLG miner earnings
 * 
 * By: Bastijn Koopmans
 * Version: 1
 * Date: 07/09/2017
 * 
 * Summary:
 * This script checks the balance of the escrow account
 * used to collect miner earnings, and transfers a
 * pre-defined percentage to the other parties who
 * contributed to the miners. A log file is created
 * after the script is finished disbursing.
 * 
 * How to use:
 * This script can for example run every day using
 * a cronjob on the same machine that runs the wallet
 * with the escrow account, or can be used by
 * implementing it in a web page (like G-DASH) to
 * disburse the earnings manually.
 * 
*/


/* SETTINGS */
$easygulden_path = "EasyGulden/easygulden.php";
$walletpassword = "";
$escrowaccount = "";
$minimum_nlg = 10;
$minconf = 1;
$txcomments = "";

$rpcUser = "";
$rpcPass = "";
$rpcHost = "";
$rpcPort = "";
/* SETTINGS */


//Include the EasyGulden library to connect to GuldenD
require_once($easygulden_path);

//Create a connection to GuldenD RPC
$gulden = new Gulden($rpcUser,$rpcPass,$rpcHost,$rpcPort);

//Get the current balance
$escrowbalance = round($gulden->getbalance($escrowaccount), 6);

//Create a return message with the start of the log file
$returnmessage = "#### Running disburse script at ".date("Y-m-d H:i:s")." ####\r\n";

//Continue if the balance > $minimum_nlg
if($escrowbalance > $minimum_nlg) {

	//Create a list of people, their address and their payout proportion
	$partylist = array(
		array('name' => 'Harry',
			  'payout' => round(0.1 * $escrowbalance, 6),
			  'address' => "xxxx"),
		array('name' => 'Jane',
			  'payout' => round(0.4 * $escrowbalance, 6),
			  'address' => "yyyy"),
		array('name' => 'John',
			  'payout' => round(0.2 * $escrowbalance, 6),
			  'address' => "zzzz"),
		array('name' => 'PartyPooper',
			  'payout' => round(0.3 * $escrowbalance, 6),
			  'address' => "qqqq")
		);
	
	//Create an array for sending to all in 1 go
	$sendtomany = array();
	
	//Unlock the wallet for 60 seconds max
	$gulden->walletpassphrase($walletpassword, 60);
	
	//For each participant, transfer the funds
	foreach ($partylist as $participant) {
		//Get the details per participant
		$sendtoname = $participant['name'];
		$sendtopayout = $participant['payout'];
		$sendtoaddress = $participant['address'];
		
		//Fill the sendtomany array
		$sendtomany[$sendtoaddress] = $sendtopayout;
		
		//OPTIONAL: Create the transaction per person instead of the sendmany command below
		/*
		 * 
		$dotransaction = $gulden->sendtoaddressfromaccount($escrowaccount, $sendtoaddress, $sendtopayout);
		
		//Write the return message to a string
		$returnmessage .= date("Y-m-d H:i:s")." # Sent $sendtopayout to $sendtoname ($sendtoaddress). TXID: $dotransaction\r\n";
		
		//Wait 1 second to complete the transaction before creating the next one
		sleep(1);
		* 
		*/
	}
	
	//Create an array with only the addresses for fee payments
	$addresslist = array_column($partylist, 'address');
	
	//Create a sendmany transaction from the escrow account, to many and the receiving addresses pay the fee
	$dotransaction = $gulden->sendmany($escrowaccount, $sendtomany, $minconf, $txcomments, $addresslist);
	
	$returnmessage .= date("Y-m-d H:i:s")." # Transaction completed with TXID $dotransaction\r\n";
	
	//Lock the wallet
	$gulden->walletlock();

} else {
	//Return a message when there is not enough funds available
	$returnmessage .= date("Y-m-d H:i:s")." # Not enough funds available to disburse: $escrowbalance NLG\r\n";
}

//Write the end of the return message
$returnmessage .= "#### Finished running disburse script at ".date("Y-m-d H:i:s")." ####\r\n\r\n";

//Append the returnmessage to the log file
$logfile = "disburse.log";
$fh = fopen($logfile, 'a+') or die("can't open file");
fwrite($fh, $returnmessage);
fclose($fh);
?>