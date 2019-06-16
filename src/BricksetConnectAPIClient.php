<?php
/**
 * @file
 * Contains Drupal\brickset_connect\src\BricksetConnectAPIClient.
 */
namespace Drupal\brickset_connect;

class BricksetConnectAPIClient {
 	const WSDL = 'https://brickset.com/api/v2.asmx?WSDL';
 	const SETTINGS = 'brickset_connect.settings';
 	// EchoNW
 	// bsLegoLink!1380
 	// LG2Q-F4KX-CCxg
 	private $user_hash = null;
 	private $loaded_sets = null;
 	private $api_key = null;
 	private $username = null;
 	private $password = null;

 	public function __construct() {
 		$config = \Drupal::configFactory()->get(static::SETTINGS);

 		$this->api_key = $config->get('brickset_api_key');
 		$this->username = $config->get('brickset_username');
 		$this->password = $config->get('brickset_password');
 	}

	public function get_api_key() {
    //$config = \Drupal::configFactory()->get(static::SETTINGS);

		//return $config->get('brickset_api_key');
		return $this->api_key;
	}

	protected function get_username() {
    //$config = \Drupal::configFactory()->get(static::SETTINGS);

		//return $config->get('brickset_username');
		return $this->username;
	}

	protected function get_password() {
   	//$config = \Drupal::configFactory()->get(static::SETTINGS);

		//return $config->get('brickset_password');
		return $this->password;
	}

	public function get_user_hash() {
		return $this->user_hash;
	}

	/**
   	  * {@inheritdoc}
   	  * Login to the Brickset API to get a user hash for API call authentication.
      */
	public function login() {
		$client = new \SoapClient(static::WSDL);
	    $params = array(
	      'apiKey' => $this->api_key,
	      'username' => $this->username,
	      'password' => $this->password,
	    );

	    $user_hash = $client->login($params)->loginResult;

	    $result = false;

	    if ($this->check_user_hash($user_hash)) {
	    	$this->user_hash = $user_hash;
	    	$result = true;
	    }

	    return $result;
	}

	private function check_user_hash($user_hash) {
		$result = false;

		if ( ($user_hash) && (strlen($user_hash) > 0) && ($user_hash != 'INVALIDKEY') ) {
			$result = true;
		}

		return $result;
	}

	/**
	  * {@inheritdoc}
	  * Static function to check Brickset API key.
	  */
	public static function check_api_key($api_key = null) {
		if (!$api_key) {
			$config = \Drupal::configFactory()->get(static::SETTINGS);
			$api_key = $config->get('brickset_api_key');
		}

		$client = new \SoapClient(static::WSDL);
	  $params = array(
	    'apiKey' => $api_key,
    );

    $check_key_result = $client->checkKey($params);

    $result = false;

    if($check_key_result->checkKeyResult == 'OK') {
      $result = true;
    }

    return $result;
	}

	public function load_sets($set_numbers) {
		// make sure we have a valid user hash before we continue
		if(!$this->check_user_hash($this->user_hash)) {
			throw new \Exception("Brickset user hash check failed");
		}
drupal_set_message('get_sets user hash passed');

		$set_list = '';

		// for some reason, the default set number (part number) is always the
		// set + '-1', so we need to append '-1' to the set number to get the
		// actual set rather than the manual or box or some part of the set
		foreach ($set_numbers as $set_number) {
			$set_list .= $set_number . '-1,';
		}

		$client = new \SoapClient(static::WSDL);

		// must pass ALL parameters, even if they are blank!
    $params = array(
      'apiKey' => $this->api_key,
      'userHash' => $this->user_hash,
      'setNumber' => rtrim(trim($set_list),','),
      'query' => '',
      'theme' => '',
      'subtheme' => '',
      'year' => '',
      'owned' => '',
      'wanted' => '',
      'orderBy' => '',
      'pageSize' => '',
      'pageNumber' => '',
      'userName' => '',
    );

drupal_set_message('Sets params:' . print_r($params, true));

    $sets = $client->getSets($params)->getSetsResult->sets;

    // make sure we got at least one set loaded
    if (!$sets || count($sets) < 1) {
			throw new \Exception("Less than 1 set loaded");
    }

    // one set returns an object, not an array with one element, so...
    // if the result isn't an array, let's stick it in an array, so...
    // we'll always know that it's an array, regardless of how many elements
    if (!is_array($sets)) {
    	$sets = array($sets);
    }

drupal_set_message('Sets loaded:' . print_r($sets, true));


		foreach ($sets as $set) {
drupal_set_message('SetNumber: ' . $set->number . ' ---- ' . 'SetID: ' . $set->setID);
			$additional_images = null;

			if ($set->additionalImageCount > 0) {
      	$additional_images = $this->get_additional_images($set->setID);

      	// remember this ol' gag from above?
      	// let's make sure it's an array
		    if (!is_array($additional_images)) {
		    	$additional_images = array($additional_images);
		    }
drupal_set_message('AddlImages: ' . print_r($additional_images, true));
    	}

      $brick_set = new BrickSet($set->number, $set->name, $set->year, $additional_images);
// maybe need this -->      //$this->loaded_sets[] = $brick_set;

drupal_set_message("Brick set: " . print_r($brick_set, true));

      try {
        $brick_set->create_node();  
      }
      catch (Exception $e) {
        drupal_set_message($e);
      }
    }


/*
    return $sets;
 */
	}

	public function get_additional_images($set_id) {
		$additional_images = array();

		$client = new \SoapClient(static::WSDL);

		// must pass ALL parameters, even if they are blank!
		// not sure why this one only wants the API key an not the user hash
    $params = array(
      'apiKey' => $this->api_key,
      'setID' => $set_id,
    );

    $additional_images = $client->getAdditionalImages($params)->getAdditionalImagesResult->additionalImages;

    return $additional_images;
	}
}
