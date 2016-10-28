<?php


class NamiCleverreachConnector {

	private $nami_password;
	private $nami_username;

	private $cleverreach_client_id;
	private $cleverreach_login;
	private $cleverreach_password;

	private $namiObject;
	private $cleverReachConnector;

	private $cleverreach_group_receivers = array();
	private $new_reveivers = array();
	private $updated_reveivers = array();
	private $all_nami_member_mails = array();
	private $deprecated_mails = array();


	public function __construct( $nami_password, $nami_username, $cleverreach_client_id, $cleverreach_login, $cleverreach_password ){
		$this->nami_password = $nami_password;
		$this->nami_username = $nami_username;
		$this->cleverreach_client_id = $cleverreach_client_id;
		$this->cleverreach_login = $cleverreach_login;
		$this->cleverreach_password = $cleverreach_password;

		$this->connect_to_apis();
	}

	private function connect_to_apis(){

		$this->nami_login();
		$this->cleverreach_login();

	}


	private function cleverreach_login() {
		
		$this->cleverReachConnector = new CR\tools\rest("https://rest.cleverreach.com/v1");
		$this->cleverReachConnector->throwExceptions = true;	//default
		
		$token = $this->cleverReachConnector->post('/login', 
			array(
				"client_id"=> $this->cleverreach_client_id,
				"login"=> $this->cleverreach_login,
				"password"=>$this->cleverreach_password
			)
		);
		//no error, lets use the key
		$this->cleverReachConnector->setAuthMode("jwt", $token);

	}

	private function nami_login() {

		$this->nami = new NamiConnector( true, 'nami.dpsg.de' );
		$this->nami->login( array(
			"username"	=> $this->nami_username,
			"password"	=> $this->nami_password
			)
		);

	}

	private function get_group_receivers( $group_id ) {

		//echo "### Return all receivers from group ###\n";
		$receivers = $this->cleverReachConnector->get("/groups/" . $group_id . "/receivers");
		foreach ($receivers as $receiver) {
			$this->cleverreach_group_receivers[] = $receiver->email;
		}

		//var_dump( $this->cleverreach_group_receivers );

	}


	private function get_nami_members( $filterString, $searchString ) {
		$this->nami_members = $this->nami->get_members( $filterString, $searchString );

		foreach ($this->nami_members as $member) {
			echo $member['descriptor'] . '<br />';
		}
	}

	public function compare_nami_and_cleverreach( $filter, $searchString = '', $groupid ) {
		$this->get_group_receivers( $groupid );
		$this->get_nami_members($filter, $searchString);

		foreach ( $this->nami_members as $member ) {

			if (!empty($member["entries"]["emailVertretungsberechtigter"])) {

				/*$this->all_nami_member_mails[] = $member["entries"]["emailVertretungsberechtigter"];

				// Check "Vertretungsberechtigter"
				if ( in_array( $member["entries"]["emailVertretungsberechtigter"], $this->cleverreach_group_receivers ) ) {
					$this->updated_reveivers[] = array(
						
						"email" => $member["entries"]["emailVertretungsberechtigter"],
					);
				}else{
					$this->new_reveivers[] = array(
						
						"email" => $member["entries"]["emailVertretungsberechtigter"],
						"registered"		=> time(),	//current date
						"activated"			=> time(),
						"global_attributes"	=> array(
							"mitgliedsNummer" => $member["entries"]["mitgliedsNummer"],
							"Vertretungsberechtigter" => "0",
						),
					);
				}*/
			}

			// Check Mitglied

			if ( !empty( $member["entries"]["email"] ) ) {

				$this->all_nami_member_mails[] = $member["entries"]["email"];

				if ( in_array( $member["entries"]["email"] , $this->cleverreach_group_receivers ) ) {
					$this->updated_reveivers[] = array(
							"email"			=> $member["entries"]["email"],
						);
				}else{

					$this->new_reveivers[] = array(
						"email"			=> $member["entries"]["email"],
						"registered"		=> time(),	//current date
						"activated"			=> time(),
						"attributes"	=> array(
							"firstname" => $member["entries"]["vorname"],
							"lastname" => $member["entries"]["nachname"],
						),
						"global_attributes"	=> array(
							"mitgliedsNummer" => $member["entries"]["mitgliedsNummer"],
							"Vertretungsberechtigter" => "0",
						),
					);
				}

			} //check if mail is empty

		}

			echo "<h2>Updated Reveivers</h2>";
			var_dump($this->updated_reveivers);

			echo "<h2>New Reveivers</h2>";
			var_dump($this->new_reveivers);

		$this->define_deprecated_mails();

		echo "<h2>Deprecated Reveivers</h2>";
		var_dump( $this->deprecated_mails );

		//$this->add_new_receivers( $groupid );
		$this->delete_deprecated_receivers( $groupid );

	}

	private function define_deprecated_mails() {
		var_dump($this->cleverreach_group_receivers);
		foreach ( $this->cleverreach_group_receivers as $group_receiver ) {
			if ( !in_array($group_receiver, $this->all_nami_member_mails )) {
				$this->deprecated_mails[] = $group_receiver;
			}
		}


	}


	private function add_new_receivers( $groupid ) {

		try {

			$this->cleverReachConnector->post("/groups/{$groupid}/receivers", $this->new_reveivers);

		} catch (\Exception $e){
			echo "!!!! Batcomputer Error: {$this->cleverReachConnector->error} !!!!";
			exit();
		}

	}

	private function delete_deprecated_receivers( $groupid ) {

		if ( count( $this->deprecated_mails) > 0 ) {
			# code...
			foreach ($this->deprecated_mails as $mail) {
				try {
					$this->cleverReachConnector->put("/groups/{$groupid}/receivers/{$mail}/setinactive", array( "email" => $mail ));

				} catch (\Exception $e){
					echo "!!!! Batcomputer Error: {$this->cleverReachConnector->error} !!!!";
					exit();
				}
			}
		}


	}


	public function reset_members(){
		$this->cleverreach_group_receivers = array();
		$this->deprecated_mails = array();
		$this->new_reveivers = array();
		$this->updated_reveivers = array();
		$this->all_nami_member_mails = array();
	}

}