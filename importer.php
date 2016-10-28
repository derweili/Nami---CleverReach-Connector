<?php


require_once('config.php');
require_once('classes/NamiConnector.php');
require_once('classes/rest_client.php');
require_once('classes/NamiCleverreachConnector.php');


// Init API connections
$nami_cleverreach_connector = new NamiCleverreachConnector( NAMI_PASSWORD, NAMI_USERNAME, CLEVERREACH_CLIENT_ID, CLEVERREACH_LOGIN, CLEVERREACH_PASSWORD );

/** execute import process
* @param $filter-string (woelflinge, jungpfadfinder, pfadfinder, rover )
* @param clever reach group id int()
*/
$nami_cleverreach_connector->compare_nami_and_cleverreach( 'pfadfinder', 958461 );
$nami_cleverreach_connector->reset_members(); //Reset Stored data to get ready for next import