<?php

	include_once('/usr/local/etc/USF_connections.php');
	include_once('RaveRestAPI.php');

	$USFrave = new RaveRestAPI($RAVE['user'],$RAVE['password'],$RAVE['REST_URL']);

	$firstName='Eric';
	$lastName='Pierce';
	$mobileNumber1='8633700498';
	$mobileCarrier1='15';
	$mobile1Confirmed='false';
	$useMobile1ForVoice='true';
	$NetID='epierce';
	$Email = 'epierce@mail.usf.edu';
	$Unum = 'U22944104';
	$GroupID = '7193';
	$ListID = '6066';
	$ConfCode = '8503';
	$ListMembers = array('epierce@usf.edu','chance@usf.edu','fdhsfjkdsfhjk@hjk.edu');
	$ListName = 'CIMS_TEST';
	
	$USFrave->setDebug();
	
	$ravetest = $argv[1];
	
	switch($ravetest) {
		case 'createUserList' :
			echo 'Create new List:'."\n";	
			$response =  $USFrave->createUserList($ListName,$ListMembers);
			print_r($response);
		break;		
		case 'deleteUserList' :
			echo 'Delete List:'."\n";	
			$response =  $USFrave->deleteUserList($ListID);
			print_r($response);
		break;		
		case 'getUserLists' :
			echo 'Get all Lists:'."\n";	
			$response =  $USFrave->getUserLists();
			print_r($response);
		break;
		case 'getUserListDetails' :
			echo 'Get Members of list '."$ListID \n";	
			$response =  $USFrave->getUserListDetails($ListID);
			print_r($response);
		break;
		case 'finduserbysisid' :
			echo 'Find User by sisID:'."\n";	
			$response =  $USFrave->findUserBySisId($Unum);
			print_r($response);
		break;
		case 'finduserbyemail' :
			echo 'Find User by Email:'."\n";
			$response =  $USFrave->findUserByEmail($Email);
			print_r($response);
		break;
		case 'lookupcarrier' :
			echo 'Find Carrier:'."\n";	
			$response =  $USFrave->lookupCarrier($mobileNumber1);
			print_r($response);
		break;
		case 'sendconfcode' :
			echo 'Send Confirmation Code:'."\n";	
			$response =  $USFrave->sendConfCode($Email);
			print_r($response);	
		break;
		case 'confirmphone' :
			echo 'Confirm Phone:'."\n";	
			$response =  $USFrave->confirmPhone($Email,$ConfCode);
			print_r($response);
		break;	
		case 'deleteuser' :
			echo 'Delete User:'."\n";
			$response =  $USFrave->deleteUser($Email);
			print_r($response);
		break;
		case 'updateprimaryemail' :
			echo 'Update Primary Email:'."\n";
			$response = $USFrave->updatePrimaryEmail($Unum,$Email);
			print_r($response);
		break;
		case 'getsubscribedgroupsforuser' :
			echo 'Get Subscribed Groups:'."\n";
			$response =  $USFrave->getSubscribedGroupsForUser($Email);
			print_r($response);
		break;
		case 'subscribetogroup' :
			echo 'Subscribe to Group:'."\n";
			$response = $USFrave->subscribeToGroup($GroupID,$Email);
			print_r($response);
		break;
		case 'unsubscribetogroup' :
			echo 'Unsubscribe from Group:'."\n";
			$response = $USFrave->unsubscribeToGroup($GroupID,$Email);
			print_r($response);
		break;
		case 'updateGroupSubscription' :
			echo 'Update Subscribe to Group:'."\n";
			$response = $USFrave->updateGroupSubscription($GroupID,$Email,'false','false');
			print_r($response);
		break;
		case 'registeruser' :
			echo 'Register User:'."\n";
			$response =  $USFrave->registerUser($firstName,
												$lastName,
												$Email,
												$mobileNumber1,
												$mobileCarrier1,
												$mobile1Confirmed,
												$useMobile1ForVoice,
												$Unum,
												$NetID);
			print_r($response);
		break;
		case 'updateuser' :
			echo 'Update User:'."\n";
			$response =  $USFrave->updateUser($firstName,
												$lastName,
												$Email,
												$mobileNumber1,
												$mobileCarrier1,
												$mobile1Confirmed,
												$useMobile1ForVoice,
												$Unum,
												$NetID);
			print_r($response);
		break;
		default:
			usage();
		break;
	}
		
		function usage() {
			echo "USAGE: ravetest.php FUNCTION\n";
			echo 'Valid Functions:
			
			createUserList
			deleteUserList
			getUserLists
			getUserListDetails
				
			finduserbyemail
			finduserbysisid
			deleteuser
			updateuser
			registeruser
			updateprimaryemail
				
			updateGroupSubscription
			unsubscribetogroup
			unsubscribetogroup
			subscribetogroup
			getsubscribedgroupsforuser
				
			lookupcarrier
			sendconfcode
			confirmphone'."\n\n";
			
		}

?>