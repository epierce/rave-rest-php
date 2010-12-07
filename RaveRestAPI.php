<?php

/**
 * PHP/curl client for the RAVE wireless (http://www.getrave.com) User Management REST API
 * Please see the Rave User Management API Reference Guide for details on the API
 *
 * @package RaveRestAPI
 * @author epierce
 * @version 1.1
 *
 */
class RaveRestAPI
{

	/**
	 * Stores an error string for the last request if one occurred
	 *
	 * @var string
	 * @access protected
	 */
	protected $error = '';

	/**
	 * Curl object used to connect to web service
	 * @var string
	 * @access protected
	 */
	protected $curl;

	/**
	 * Turn on debug output
	 *
	 * @var boolean
	 * @access protected
	 */
	protected $debug;


	/**
	 * Intializes a RaveRestAPI object
	 *
	 * @param string $user
	 * @param string $password
	 * @param string $REST_URL
	 */
	public function __construct($user,$password,$REST_URL)
	{
		$this->curl = new Curl;

		$this->debug = false;

		$this->user = $user;
		$this->password = $password;
		$this->REST_URL = $REST_URL;
		
		$this->setCommonCurlOptions();
	}


	/**
	 * Turns on/off debugging output
	 *
	 * @param boolean $value
	 */
	public function setDebug($value = true)
	{
		$this->debug = $value;
	}

	/**
	 * get error string from last web service request
	 *  @return string
	 */
	public function getError()
	{
		return $this->error;
	}

	/**
	 * Prints debugging information from the Curl object
	 *
	 * @param string $function
	 * @param string $request_URL
	 * @param string $payload
	 * @param object $response
	 */
	private function printResponseDebug($function,$request_URL,$payload,$response)
	{
		echo "=======================\n";
		echo "   $function \n";
		echo "=======================\n";
		echo "Connecting to $request_URL \n";
		echo "Payload: $payload\n";
		echo "Response: Headers\n";
		print_r($response->headers);
		echo "Response: Body\n";
		print_r($response->body);
		echo "\n=======================\n";

		return;
	}

	/**
	 * Handle HTTP error codes from the Curl object
	 *
	 * @param object $response
	 */
	private function errorHandler($response)
	{
		switch ($response->headers['Status-Code']) {
			case 401:	$this->error = 'Authentication Failure: Username or Password Incorrect';
			break;

			case 406:	$xml = simplexml_load_string($response->body);
			$this->error = 'Input data error: '.$xml->errorMessage;
			break;

			case 500:	$xml = simplexml_load_string($response->body);
			$this->error = 'Internal Server Error: '.$xml->errorMessage;
			break;

		}

		if($this->debug) echo $this->error." \n";

		return;
	}

	/**
	 * Set the associated CURL options for a RAVE API request
	 */
	private function setCommonCurlOptions()
	{
		$USERPWD = $this->user.':'.$this->password;

		$this->curl->options['CURLOPT_RETURNTRANSFER'] = 1;
		//This didn't work for POST/PUT - forced the correct value in curl->request()
		$this->curl->options['CURLOPT_HTTPHEADER'] = array('Content-type: application/xml; charset=utf-8');
		$this->curl->options['CURLOPT_HTTPAUTH'] = 'CURLAUTH_BASIC';
		$this->curl->options['CURLOPT_USERPWD'] = $USERPWD;
	}

	/**
	 * Gets the Cell Phone Carrier for a given phone number
	 *
	 * $PhoneNumber must be 10 digits
	 *
	 * Returns an assoc. array with elements:
	 *  Name : Carrier Name
	 *  ID : Carrier ID number - use this number when interacting with other RAVE services
	 *
	 *  Returns 0 on failure
	 *
	 * @param number $PhoneNumber
	 * @return number|array
	 */
	public function lookupCarrier($PhoneNumber)
	{

		$request_URL = $this->REST_URL."siteadmin/mobilecarriers/phonecarrier/".$PhoneNumber;

		$this->setCommonCurlOptions();

		$response = $this->curl->get($request_URL);

		if($this->debug) $this->printResponseDebug('lookupCarrier',$request_URL,'',$response);

		//Valid Return Codes for REST API are 200 or 202
		if (($response->headers['Status-Code'] != 200) && ($response->headers['Status-Code'] != 202)) {
			$this->errorHandler($response);
			return 0;
		}

		$xml = simplexml_load_string($response);

		$carrier['ID'] = (int) $xml->attributes()->id;
		$carrier['Name'] = (String) $xml->name;

		return $carrier;
	}



	/**
	 * Sends a SMS confirmation code to a given RAVE subscriber
	 *
	 * $Email must already be registered with RAVE and have a Mobile Phone Number set
	 *
	 * @param string $Email
	 * @return boolean
	 */
	public function sendConfCode($Email)
	{

		$request_URL = $this->REST_URL."user/".$Email."/sendconfcode";

		$this->setCommonCurlOptions();

		$response = $this->curl->post($request_URL);

		if($this->debug) $this->printResponseDebug('sendConfCode',$request_URL,'',$response);


		//Valid Return Codes for REST API are 200 or 202
		if (($response->headers['Status-Code'] != 200) && ($response->headers['Status-Code'] != 202)) {
			$this->errorHandler($response);
			return 0;
		}

		return 1;
	}


	/**
	 * Sends confirmation code back to RAVE to confirm the mobile phone number for a subscriber
	 *
	 * @param string $Email
	 * @param number $ConfCode
	 * @return boolean
	 */
	public function confirmPhone($Email,$ConfCode)
	{

		$request_URL = $this->REST_URL."user/".$Email."/confirmphone";

		$this->setCommonCurlOptions();

		$response = $this->curl->post($request_URL,$ConfCode);

		if($this->debug) $this->printResponseDebug('confirmPhone',$request_URL,$ConfCode,$response);


		//Valid Return Codes for REST API are 200 or 202
		if (($response->headers['Status-Code'] != 200) && ($response->headers['Status-Code'] != 202)) {
			$this->errorHandler($response);
			return 0;
		}

		return 1;

	}

	/**
	 * Gets Rave User Data using Student Information System ID (sisID)
	 *
	 * @param string $sisID
	 * @return array|number
	 */
	public function findUserBySisId($sisID)
	{

		$request_URL = $this->REST_URL."user/findbysisid?sisid=".$sisID;

		$this->setCommonCurlOptions();

		$response = $this->curl->get($request_URL);

		if($this->debug) $this->printResponseDebug('findUserBySisId',$request_URL,'',$response);

		//Valid Return Codes for REST API are 200 or 202
		if (($response->headers['Status-Code'] != 200) && ($response->headers['Status-Code'] != 202)) {
			$this->errorHandler($response);
			return 0;
		}

		return (array) simplexml_load_string($response);

	}


	/**
	 * Gets Rave User Data using Email
	 *
	 * @param string $Email
	 * @return array|number
	 */
	public function findUserByEmail($Email)
	{

		$request_URL = $this->REST_URL."user/".$Email;

		$this->setCommonCurlOptions();

		$response = $this->curl->get($request_URL);

		if($this->debug) $this->printResponseDebug('findUserByEmail',$request_URL,'',$response);


		//Valid Return Codes for REST API are 200 or 202
		if (($response->headers['Status-Code'] != 200) && ($response->headers['Status-Code'] != 202)) {
			$this->errorHandler($response);
			return 0;
		}

		return (array) simplexml_load_string($response);

	}



	/**
	 * Updates Primary Email on a RAVE subscriber using the SISid as the identifier
	 *
	 * @param string $sisid
	 * @param string $Email
	 * @return boolean
	 */
	public function updatePrimaryEmail($sisid,$Email)
	{

		$request_URL = $this->REST_URL."user/updateprimaryemail?sisid=".$sisid."&email=".$Email;

		$this->setCommonCurlOptions();

		$response = $this->curl->post($request_URL, $vars);

		if($this->debug) $this->printResponseDebug('updateUserPrimaryEmail',$request_URL,'',$response);

		//Valid Return Codes for REST API are 200 or 202
		if (($response->headers['Status-Code'] != 200) && ($response->headers['Status-Code'] != 202)) {
			$this->errorHandler($response);
			return 0;
		}

		return 1;

	}


	/**
	 * Creates a new RAVE subscriber
	 *
	 * @param string $firstName Subscriber's first name
	 * @param string $lastName Subscriber's last name
	 * @param string $Email Subscriber's primary Email address
	 * @param integer $mobileNumber1 Subscriber's cell phone number
	 * @param integer $mobileCarrier1 RAVE wireless CarrierID from lookupCarrier()
	 * @param boolean $mobile1Confirmed Indicates the number has been verified ('true'/'false' only - other forms of boolean not accepted)
	 * @param boolean $useMobile1ForVoice Indicates the number should be used for voice alerts ('true'/'false' only - other forms of boolean not accepted)
	 * @param string $sisId Alternate identifier for subscriber
	 * @param string $ssoId username for CAS/LDAP authentication
	 * @param string $languagePreference Preferred Language (default: 'en')
	 * @param string $administrationRole Supported Values: 'USER','BROADCAST_ADMIN','SITE_ADMIN' (default: 'USER')
	 * @param string $institutionRole
	 * @param string $userAttribute1
	 * @param string $userAttribute2
	 * @param string $userAttribute3
	 * @param string $userAttribute4
	 * @return boolean
	 */
	public function registerUser($firstName, $lastName, $Email, $mobileNumber1, $mobileCarrier1, $mobile1Confirmed, $useMobile1ForVoice, $sisId, $ssoId,
	$languagePreference='en', $administrationRole='USER', $institutionRole='', $userAttribute1='', $userAttribute2='', $userAttribute3='', $userAttribute4='')
	{

		$request_URL = $this->REST_URL."user";
		
		$this->setCommonCurlOptions();

		$raveUser = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ?><raveUser/>');
		$raveUser->addChild('firstName', $firstName);
		$raveUser->addChild('lastName', $lastName);
		$raveUser->addChild('email', $Email);
		$raveUser->addChild('sisId', $sisId);
		$raveUser->addChild('ssoId', $ssoId);
		$raveUser->addChild('languagePreference', $languagePreference);
		$raveUser->addChild('administrationRole', $administrationRole);
		$raveUser->addChild('mobileNumber1', $mobileNumber1);
		$raveUser->addChild('mobileCarrier1', $mobileCarrier1);
		$raveUser->addChild('mobile1Confirmed', $mobile1Confirmed);
		$raveUser->addChild('useMobile1ForVoice', $useMobile1ForVoice);
		$raveUser->addChild('institutionRole', $institutionRole);
		$raveUser->addChild('userAttribute1', $userAttribute1);
		$raveUser->addChild('userAttribute2', $userAttribute2);
		$raveUser->addChild('userAttribute3', $userAttribute3);
		$raveUser->addChild('userAttribute4', $userAttribute4);

		$POST_Payload = $raveUser->asXML();

		$response = $this->curl->post($request_URL, $POST_Payload);
		
		if($this->debug) $this->printResponseDebug('registerUser',$request_URL,$POST_Payload,$response);

		//Valid Return Codes for REST API are 200 or 202
		if (($response->headers['Status-Code'] != 200) && ($response->headers['Status-Code'] != 202)) {
			$this->errorHandler($response);
			return 0;
		}

		return 1;
	}

	/**
	 * Updates RAVE subscriber record
	 *
	 * @param string $firstName Subscriber's first name
	 * @param string $lastName Subscriber's last name
	 * @param string $Email Subscriber's primary Email address
	 * @param integer $mobileNumber1 Subscriber's cell phone number
	 * @param integer $mobileCarrier1 RAVE wireless CarrierID from lookupCarrier()
	 * @param boolean $mobile1Confirmed Indicates the number has been verified ('true'/'false' only - other forms of boolean not accepted)
	 * @param boolean $useMobile1ForVoice Indicates the number should be used for voice alerts ('true'/'false' only - other forms of boolean not accepted)
	 * @param string $sisId Alternate identifier for subscriber
	 * @param string $ssoId username for CAS/LDAP authentication
	 * @param string $languagePreference Preferred Language (default: 'en')
	 * @param string $administrationRole Supported Values: 'USER','BROADCAST_ADMIN','SITE_ADMIN' (default: 'USER')
	 * @param string $institutionRole
	 * @param string $userAttribute1
	 * @param string $userAttribute2
	 * @param string $userAttribute3
	 * @param string $userAttribute4
	 * @return boolean
	 */
	public function updateUser($firstName, $lastName, $Email, $mobileNumber1, $mobileCarrier1, $mobile1Confirmed, $useMobile1ForVoice, $sisId, $ssoId,
	$languagePreference='en', $administrationRole='USER', $institutionRole='', $userAttribute1='', $userAttribute2='', $userAttribute3='', $userAttribute4='')
	{

		$request_URL = $this->REST_URL."user";

		$this->setCommonCurlOptions();

		$raveUser = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ?><raveUser/>');
		$raveUser->addChild('firstName', $firstName);
		$raveUser->addChild('lastName', $lastName);
		$raveUser->addChild('email', $Email);
		$raveUser->addChild('sisId', $sisId);
		$raveUser->addChild('ssoId', $ssoId);
		$raveUser->addChild('languagePreference', $languagePreference);
		$raveUser->addChild('administrationRole', $administrationRole);
		$raveUser->addChild('mobileNumber1', $mobileNumber1);
		$raveUser->addChild('mobileCarrier1', $mobileCarrier1);
		$raveUser->addChild('mobile1Confirmed', $mobile1Confirmed);
		$raveUser->addChild('useMobile1ForVoice', $useMobile1ForVoice);
		$raveUser->addChild('institutionRole', $institutionRole);
		$raveUser->addChild('userAttribute1', $userAttribute1);
		$raveUser->addChild('userAttribute2', $userAttribute2);
		$raveUser->addChild('userAttribute3', $userAttribute3);
		$raveUser->addChild('userAttribute4', $userAttribute4);

		$PUT_Payload = $raveUser->asXML();
			
		$response = $this->curl->put($request_URL, $PUT_Payload);

		if($this->debug) $this->printResponseDebug('updateUser',$request_URL,$PUT_Payload,$response);

		//Valid Return Codes for REST API are 200 or 202
		if (($response->headers['Status-Code'] != 200) && ($response->headers['Status-Code'] != 202)) {
			$this->errorHandler($response);
			return 0;
		}

		return 1;
	}


	/**
	 * Updates RAVE password -- Not needed for CAS/LDAP authentication
	 *
	 * @param string $Email
	 * @param string $Password
	 * @return boolean
	 */
	public function updatePassword($Email,$Password)
	{

		$request_URL = $this->REST_URL."user/".$Email."/resetpassword";

		$this->setCommonCurlOptions();

		$response = $this->curl->post($request_URL, $Password);

		if($this->debug) $this->printResponseDebug('updatePassword',$request_URL,$Password,$response);

		//Valid Return Codes for REST API are 200 or 202
		if (($response->headers['Status-Code'] != 200) && ($response->headers['Status-Code'] != 202)) {
			$this->errorHandler($response);
			return 0;
		}

		return 1;

	}

	/**
	 * Removes RAVE subscriber
	 *
	 * @param string $Email
	 * @return boolean
	 */
	public function deleteUser($Email)
	{

		$request_URL = $this->REST_URL."user/".$Email;

		$this->setCommonCurlOptions();

		$response = $this->curl->delete($request_URL);

		if($this->debug) $this->printResponseDebug('deleteUser',$request_URL,'',$response);

		//Valid Return Codes for REST API are 200 or 202
		if (($response->headers['Status-Code'] != 200) && ($response->headers['Status-Code'] != 202)) {
			$this->errorHandler($response);
			return 0;
		}

		return 1;

	}

	/**
	 * Get all subscribed groups for a single RAVE user
	 *
	 * returns assoc.array with elements:
	 *
	 * $membership[0]['groupID'] : RAVE GroupId number
	 * $membership[0]['groupURL'] : Web Service URL to get details/modify group
	 * $membership[0]['role'] : Subscriber's role in this group
	 *
	 * Returns 0 on failure
	 *
	 * @param string $Email
	 * @return number|array
	 */
	public function getSubscribedGroupsForUser($Email)
	{

		$request_URL = $this->REST_URL."user/".$Email."/groups";

		$this->setCommonCurlOptions();

		$response = $this->curl->get($request_URL);

		if($this->debug) $this->printResponseDebug('getSubscribedGroupsForUser',$request_URL,'',$response);

		//Valid Return Codes for REST API are 200 or 202
		if (($response->headers['Status-Code'] != 200) && ($response->headers['Status-Code'] != 202)) {
			$this->errorHandler($response);
			return 0;
		}

		$xml = simplexml_load_string($response);

		foreach ($xml->raveGroupMembership as $Membership) {

			$groupID = $Membership->attributes()->groupId;
			$groupURL = $Membership->attributes()->groupDetailsURL;
			$role = $Membership->role;

			$membership[] = array('groupID' => (int) $groupID,'groupURL' => (string) $groupURL,'role' => (string) $role);

		}

		return $membership;
	}

	/**
	 * Subscribe User to a RAVE Group
	 *
	 * @param integer $groupId RAVE groupID
	 * @param string $Email Subscriber's primary email address
	 * @param boolean $alertByPhone Indicates if the subscriber should be notified via voice ('true'/'false' only - other forms of boolean not accepted)
	 * @param boolean $alertByEmail Indicates if the subscriber should be notified via email ('true'/'false' only - other forms of boolean not accepted)
	 * @param string $role Supported Values: 'ADMIN', 'CONTRIBUTOR', 'MEMBER' (default: 'MEMBER')
	 * @return boolean
	 */
	public function subscribeToGroup($groupId,$Email,$alertByPhone='true',$alertByEmail='true',$role='MEMBER')
	{

		$request_URL = $this->REST_URL."user/".$Email."/groups";

		$this->setCommonCurlOptions();

		$raveGroupMembership = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ?><raveGroupMembership/>');
		$raveGroupMembership->addAttribute('groupId',$groupId);
		$raveGroupMembership->addChild('alertByPhone',$alertByPhone);
		$raveGroupMembership->addChild('alertByEmail',$alertByEmail);
		$raveGroupMembership->addChild('role',$role);
			
		$POST_Payload = $raveGroupMembership->asXML();
			
		$response = $this->curl->post($request_URL, $POST_Payload);

		if($this->debug) $this->printResponseDebug('subscribeToGroup',$request_URL,$POST_Payload,$response);

		//Valid Return Codes for REST API are 200 or 202
		if (($response->headers['Status-Code'] != 200) && ($response->headers['Status-Code'] != 202)) {
			$this->errorHandler($response);
			return 0;
		}

		return 1;
	}


	/**
	 * Update user subscription to a RAVE Group
	 *
	 * @param integer $groupId RAVE groupID
	 * @param string $Email Subscriber's primary email address
	 * @param boolean $alertByPhone Indicates if the subscriber should be notified via voice ('true'/'false' only - other forms of boolean not accepted)
	 * @param boolean $alertByEmail Indicates if the subscriber should be notified via email ('true'/'false' only - other forms of boolean not accepted)
	 * @param string $role Supported Values: 'ADMIN', 'CONTRIBUTOR', 'MEMBER' (default: 'MEMBER')
	 * @return boolean
	 */
	public function updateGroupSubscription($groupId,$Email,$alertByPhone='true',$alertByEmail='true',$role='MEMBER')
	{

		$request_URL = $this->REST_URL."user/".$Email."/groups";

		$this->setCommonCurlOptions();

		$raveGroupMembership = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ?><raveGroupMembership/>');
		$raveGroupMembership->addAttribute('groupId',$groupId);
		$raveGroupMembership->addChild('alertByPhone',$alertByPhone);
		$raveGroupMembership->addChild('alertByEmail',$alertByEmail);
		$raveGroupMembership->addChild('role',$role);
			
		$PUT_Payload = $raveGroupMembership->asXML();

		$response = $this->curl->put($request_URL, $PUT_Payload);

		if($this->debug) $this->printResponseDebug('updateGroupSubscription',$request_URL,$PUT_Payload,$response);

		//Valid Return Codes for REST API are 200 or 202
		if (($response->headers['Status-Code'] != 200) && ($response->headers['Status-Code'] != 202)) {
			$this->errorHandler($response);
			return 0;
		}

		return 1;
	}

	/**
	 * Unsubscribe from a RAVE group
	 *
	 * @param integer $groupId RAVE GroupID
	 * @param string $Email Subscriber's primary email address
	 * @return boolean
	 */
	public function unsubscribeToGroup($groupId,$Email)
	{

		$request_URL = $this->REST_URL."user/".$Email."/groups/".$groupId;

		$this->setCommonCurlOptions();

		$response = $this->curl->delete($request_URL);

		if($this->debug) $this->printResponseDebug('unsubscribeToGroup',$request_URL,'',$response);

		//Valid Return Codes for REST API are 200 or 202
		if (($response->headers['Status-Code'] != 200) && ($response->headers['Status-Code'] != 202)) {
			$this->errorHandler($response);
			return 0;
		}

		return 1;
	}

	/**
	 * Subscribe user to a RAVE List
	 * TODO: find out the difference between 'group' & 'list' in RAVE
	 *
	 * @param integer $listId RAVE List ID number
	 * @param string $Email Subscriber's primary email address
	 * @return boolean
	 */
	public function subscribeToList($listId,$Email)
	{

		$request_URL = $this->REST_URL."user/".$Email."/userlists/".$listId;

		$this->setCommonCurlOptions();

		$response = $this->curl->post($request_URL);


		if($this->debug) $this->printResponseDebug('subscribeToList',$request_URL,'',$response);

		//Valid Return Codes for REST API are 200 or 202
		if (($response->headers['Status-Code'] != 200) && ($response->headers['Status-Code'] != 202)) {
			$this->errorHandler($response);
			return 0;
		}

		return 1;
	}

	/**
	 * Unsubscribe from a RAVE list
	 * @param integer $listId RAVE list ID number
	 * @param string $Email Subscriber's primary email address
	 * @return boolean
	 */
	public function unsubscribeToList($listId,$Email)
	{

		$request_URL = $this->REST_URL."user/".$Email."/userlists/".$listId;

		$this->setCommonCurlOptions();

		$response = $this->curl->delete($request_URL);


		if($this->debug) $this->printResponseDebug('unsubscribeToList',$request_URL,'',$response);

		//Valid Return Codes for REST API are 200 or 202
		if (($response->headers['Status-Code'] != 200) && ($response->headers['Status-Code'] != 202)) {
			$this->errorHandler($response);
			return 0;
		}

		return 1;
	}
	
	/**
	 * List Administration
	 */
	
	/**
	 * Creates a new RAVE list
	 *
	 * @param string $listName List's name
	 * @param array $listMembers Array of members for this list
	 * @return array Array of members that were rejected by RAVE (i.e. email address do not exist)
	 */
	public function createUserList($listName, $listMembers)
	{

		$request_URL = $this->REST_URL."siteadmin/userlists";
		
		$this->setCommonCurlOptions();

		$raveList = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ?><userList/>');
		$raveList->addChild('name', $listName);
		$raveListMembers = $raveList->addChild('memberList');
		foreach($listMembers as $member){
			$raveListMembers->addChild('listMember', $member);
		}
		
		$POST_Payload = $raveList->asXML();

		$response = $this->curl->post($request_URL, $POST_Payload);
		
		if($this->debug) $this->printResponseDebug('createUserList',$request_URL,$POST_Payload,$response);

		//Valid Return Codes for REST API are 200 or 202
		if (($response->headers['Status-Code'] != 200) && ($response->headers['Status-Code'] != 202)) {
			$this->errorHandler($response);
			return 0;
		}
		
		$xml = simplexml_load_string($response);

		$rejectedMembers = array();
		foreach ($xml->xpath('//listMember') as $rejectedMember) {
			$rejectedMembers[] = (string) $rejectedMember;
		}
		
		return $rejectedMembers;
	}

	
	/**
	 * Remove RAVE list
	 *
	 * @param int $listID
	 * @return boolean
	 */
	public function deleteUserList($listID)
	{

		$request_URL = $this->REST_URL."siteadmin/userlists/".$listID;

		$this->setCommonCurlOptions();

		$response = $this->curl->delete($request_URL);

		if($this->debug) $this->printResponseDebug('deleteUserList',$request_URL,'',$response);

		//Valid Return Codes for REST API are 200 or 202
		if (($response->headers['Status-Code'] != 200) && ($response->headers['Status-Code'] != 202)) {
			$this->errorHandler($response);
			return 0;
		}

		return 1;
	}
	
	/**
	 * Get all RAVE lists
	 *
	 * returns assoc.array with elements:
	 *
	 * $raveList[0]['listID'] : RAVE ListId number
	 * $raveList[0]['listURL'] : Web Service URL to get all subscribed memebers
	 * $raveList[0]['name'] : List name
	 *
	 * Returns 0 on failure
	 *
	 * @return number|array
	 */
	public function getUserLists()
	{

		$request_URL = $this->REST_URL."siteadmin/userlists";

		$this->setCommonCurlOptions();

		$response = $this->curl->get($request_URL);

		if($this->debug) $this->printResponseDebug('getUserLists',$request_URL,'',$response);

		//Valid Return Codes for REST API are 200 or 202
		if (($response->headers['Status-Code'] != 200) && ($response->headers['Status-Code'] != 202)) {
			$this->errorHandler($response);
			return 0;
		}

		$xml = simplexml_load_string($response);

		foreach ($xml->userList as $userList) {

			$listID = $userList->attributes()->id;
			$listURL = $userList->attributes()->userListDetailsURL;
			$name = $userList->name;

			$raveList[] = array('listID' => (int) $listID,'listURL' => (string) $listURL,'name' => (string) $name);

		}

		return $raveList;
	}
	
	/**
	 * Get members of a RAVE list
	 *
	 * Returns 0 on failure
	 * 
	 * @param int $listID Rave ListID number
	 * @return number|array Member list
	 */
	public function getUserListDetails($listID)
	{

		$request_URL = $this->REST_URL."siteadmin/userlists/".$listID;

		$this->setCommonCurlOptions();

		$response = $this->curl->get($request_URL);

		if($this->debug) $this->printResponseDebug('getUserListDetails',$request_URL,'',$response);

		//Valid Return Codes for REST API are 200 or 202
		if (($response->headers['Status-Code'] != 200) && ($response->headers['Status-Code'] != 202)) {
			$this->errorHandler($response);
			return 0;
		}

		$xml = simplexml_load_string($response);		
		foreach ($xml->memberList->listMember as $listMember) {
			$memberList[] = (string) $listMember;			
		}

		return $memberList;
	}
}


/**
 * A basic CURL wrapper
 *
 * See the README for documentation/examples or http://php.net/curl for more information about the libcurl extension for PHP
 *
 * @package curl
 * @author Sean Huber <shuber@huberry.com>
 **/
class Curl {

	/**
	 * The file to read and write cookies to for requests
	 *
	 * @var string
	 **/
	public $cookie_file;

	/**
	 * Determines whether or not requests should follow redirects
	 *
	 * @var boolean
	 **/
	public $follow_redirects = true;

	/**
	 * An associative array of headers to send along with requests
	 *
	 * @var array
	 **/
	public $headers = array();

	/**
	 * An associative array of CURLOPT options to send along with requests
	 *
	 * @var array
	 **/
	public $options = array();

	/**
	 * The referer header to send along with requests
	 *
	 * @var string
	 **/
	public $referer;

	/**
	 * The user agent to send along with requests
	 *
	 * @var string
	 **/
	public $user_agent;

	/**
	 * Stores an error string for the last request if one occurred
	 *
	 * @var string
	 * @access protected
	 **/
	protected $error = '';

	/**
	 * Stores resource handle for the current CURL request
	 *
	 * @var resource
	 * @access protected
	 **/
	protected $request;

	/**
	 * Initializes a Curl object
	 *
	 * Sets the $cookie_file to "curl_cookie.txt" in the current directory
	 * Also sets the $user_agent to $_SERVER['HTTP_USER_AGENT'] if it exists, 'Curl/PHP '.PHP_VERSION.' (http://github.com/shuber/curl)' otherwise
	 **/
	function __construct() {
		$this->cookie_file = dirname(__FILE__).DIRECTORY_SEPARATOR.'curl_cookie.txt';
		$this->user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Curl/PHP '.PHP_VERSION.' (http://github.com/shuber/curl)';
	}

	/**
	 * Makes an HTTP DELETE request to the specified $url with an optional array or string of $vars
	 *
	 * Returns a CurlResponse object if the request was successful, false otherwise
	 *
	 * @param string $url
	 * @param array|string $vars
	 * @return CurlResponse object
	 **/
	function delete($url, $vars = array()) {
		return $this->request('DELETE', $url, $vars);
	}

	/**
	 * Returns the error string of the current request if one occurred
	 *
	 * @return string
	 **/
	function error() {
		return $this->error;
	}

	/**
	 * Makes an HTTP GET request to the specified $url with an optional array or string of $vars
	 *
	 * Returns a CurlResponse object if the request was successful, false otherwise
	 *
	 * @param string $url
	 * @param array|string $vars
	 * @return CurlResponse
	 **/
	function get($url, $vars = array()) {
		if (!empty($vars)) {
			$url .= (stripos($url, '?') !== false) ? '&' : '?';
			$url .= (is_string($vars)) ? $vars : http_build_query($vars, '', '&');
		}
		return $this->request('GET', $url);
	}

	/**
	 * Makes an HTTP HEAD request to the specified $url with an optional array or string of $vars
	 *
	 * Returns a CurlResponse object if the request was successful, false otherwise
	 *
	 * @param string $url
	 * @param array|string $vars
	 * @return CurlResponse
	 **/
	function head($url, $vars = array()) {
		return $this->request('HEAD', $url, $vars);
	}

	/**
	 * Makes an HTTP POST request to the specified $url with an optional array or string of $vars
	 *
	 * @param string $url
	 * @param array|string $vars
	 * @return CurlResponse|boolean
	 **/
	function post($url, $vars = array()) {
		return $this->request('POST', $url, $vars);
	}

	/**
	 * Makes an HTTP PUT request to the specified $url with an optional array or string of $vars
	 *
	 * Returns a CurlResponse object if the request was successful, false otherwise
	 *
	 * @param string $url
	 * @param array|string $vars
	 * @return CurlResponse|boolean
	 **/
	function put($url, $vars = array()) {
		return $this->request('PUT', $url, $vars);
	}

	/**
	 * Makes an HTTP request of the specified $method to a $url with an optional array or string of $vars
	 *
	 * Returns a CurlResponse object if the request was successful, false otherwise
	 *
	 * @param string $method
	 * @param string $url
	 * @param array|string $vars
	 * @return CurlResponse|boolean
	 **/
	function request($method, $url, $vars = array()) {
		$this->error = '';
		$this->request = curl_init();
		if (is_array($vars)) $vars = http_build_query($vars, '', '&');

		$this->set_request_method($method);
		$this->set_request_options($url, $vars);
		$this->set_request_headers();
		
		//Had to add this for RAVE
		curl_setopt($this->request, CURLOPT_HTTPHEADER,array('Content-Type: application/xml')); 

		$response = curl_exec($this->request);

		if ($response) {
			$response = new CurlResponse($response);
		} else {
			$this->error = curl_errno($this->request).' - '.curl_error($this->request);
		}

		curl_close($this->request);

		return $response;
	}

	/**
	 * Formats and adds custom headers to the current request
	 *
	 * @return void
	 * @access protected
	 **/
	protected function set_request_headers() {
		$headers = array();
		foreach ($this->headers as $key => $value) {
			$headers[] = $key.': '.$value;
		}
		curl_setopt($this->request, CURLOPT_HTTPHEADER, $headers);
	}

	/**
	 * Set the associated CURL options for a request method
	 *
	 * @param string $method
	 * @return void
	 * @access protected
	 **/
	protected function set_request_method($method) {
		switch (strtoupper($method)) {
			case 'HEAD':
				curl_setopt($this->request, CURLOPT_NOBODY, true);
				break;
			case 'GET':
				curl_setopt($this->request, CURLOPT_HTTPGET, true);
				break;
			case 'POST':
				curl_setopt($this->request, CURLOPT_POST, true);
				break;
			default:
				curl_setopt($this->request, CURLOPT_CUSTOMREQUEST, $method);
		}
	}

	/**
	 * Sets the CURLOPT options for the current request
	 *
	 * @param string $url
	 * @param string $vars
	 * @return void
	 * @access protected
	 **/
	protected function set_request_options($url, $vars) {
		curl_setopt($this->request, CURLOPT_URL, $url);
		if (!empty($vars)) curl_setopt($this->request, CURLOPT_POSTFIELDS, $vars);

		# Set some default CURL options
		curl_setopt($this->request, CURLOPT_HEADER, true);
		curl_setopt($this->request, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->request, CURLOPT_USERAGENT, $this->user_agent);
		if ($this->cookie_file) {
			curl_setopt($this->request, CURLOPT_COOKIEFILE, $this->cookie_file);
			curl_setopt($this->request, CURLOPT_COOKIEJAR, $this->cookie_file);
		}
		if ($this->follow_redirects) curl_setopt($this->request, CURLOPT_FOLLOWLOCATION, true);
		if ($this->referer) curl_setopt($this->request, CURLOPT_REFERER, $this->referer);
		curl_setopt($this->request, CURLINFO_HEADER_OUT, TRUE);
		
		# Set any custom CURL options
		foreach ($this->options as $option => $value) {
			curl_setopt($this->request, constant('CURLOPT_'.str_replace('CURLOPT_', '', strtoupper($option))), $value);
		}
	}

}

/**
 * Parses the response from a Curl request into an object containing
 * the response body and an associative array of headers
 *
 * @package curl
 * @author Sean Huber <shuber@huberry.com>
 **/

class CurlResponse {

	/**
	 * The body of the response without the headers block
	 *
	 * @var string
	 **/
	public $body = '';

	/**
	 * An associative array containing the response's headers
	 *
	 * @var array
	 **/
	public $headers = array();

	/**
	 * Accepts the result of a curl request as a string
	 *
	 * <code>
	 * $response = new CurlResponse(curl_exec($curl_handle));
	 * echo $response->body;
	 * echo $response->headers['Status'];
	 * </code>
	 *
	 * @param string $response
	 **/
	function __construct($response) {
		# Headers regex
		$pattern = '#HTTP/\d\.\d.*?$.*?\r\n\r\n#ims';

		# Extract headers from response
		preg_match_all($pattern, $response, $matches);
		$headers_string = array_pop($matches[0]);
		$headers = explode("\r\n", str_replace("\r\n\r\n", '', $headers_string));

		# Remove headers from the response body
		$this->body = str_replace($headers_string, '', $response);

		# Extract the version and status from the first header
		$version_and_status = array_shift($headers);
		preg_match('#HTTP/(\d\.\d)\s(\d\d\d)\s(.*)#', $version_and_status, $matches);
		$this->headers['Http-Version'] = $matches[1];
		$this->headers['Status-Code'] = $matches[2];
		$this->headers['Status'] = $matches[2].' '.$matches[3];

		# Convert headers into an associative array
		foreach ($headers as $header) {
			preg_match('#(.*?)\:\s(.*)#', $header, $matches);
			$this->headers[$matches[1]] = $matches[2];
		}
	}

	/**
	 * Returns the response body
	 *
	 * <code>
	 * $curl = new Curl;
	 * $response = $curl->get('google.com');
	 * echo $response;  # => echo $response->body;
	 * </code>
	 *
	 * @return string
	 **/
	function __toString() {
		return $this->body;
	}

}
?>