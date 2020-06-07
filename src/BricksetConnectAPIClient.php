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
		return $this->api_key;
	}

	protected function get_username() {
		return $this->username;
	}

	protected function get_password() {
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

	/**
	 * Wrapper for singular call of load_sets
	 * @return BrickSet object
	 */
	public function load_set($set_number) {
		$set_numbers = array($set_number);

		$brick_sets = $this->load_sets($set_numbers);

		if (count($brick_sets) < 1) {
			throw new \Exception("Failed to load at least one set");
		}
	}

	/**
	  * Load multiple sets
	  * @return An array of BrickSet objects
	  */
	public function load_sets($set_numbers) {
		// make sure we have a valid user hash before we continue
		if(!$this->check_user_hash($this->user_hash)) {
			throw new \Exception("Brickset user hash check failed");
		}

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

    \Drupal::logger('brickset_connect')->notice('Query params:' . print_r($params, true));

    $sets = $client->getSets($params)->getSetsResult->sets;

    // make sure we got at least one set loaded
    //if (!$sets || count($sets) < 1) {
    if (!$sets) {
			throw new \Exception("Less than 1 set loaded");
    }

    // one set returns an object, not an array with one element, so...
    // if the result isn't an array, let's stick it in an array, so...
    // we'll always know that it's an array, regardless of how many elements
    if (!is_array($sets)) {
    	$sets = array($sets);
    }

    \Drupal::logger('brickset_connect')->notice('Sets loaded:' . print_r($sets, true));
		$brick_sets = array(); // return array

		foreach ($sets as $set) {
      \Drupal::logger('brickset_connect')->notice('SetNumber: ' . $set->number . ' ---- ' . 'SetID: ' . $set->setID);
			$images = array();

      // make sure there is at least one image
      if ($set->imageURL) {
        $images[] = $set->imageURL;
      }

      \Drupal::logger('brickset_connect')->notice('Additional images: ' . $set->additionalImageCount);
			if ($set->additionalImageCount > 0) {
      	$additional_images = $this->get_additional_images($set->setID);
        // add additional images to end of the array with the main imagea
        $images = array_merge($images, $additional_images);
    	}

      \Drupal::logger('brickset_connect')->notice('TOTAL images: ' . print_r($images, true));
    	$brick_set = new BrickSet($set->number, $set->name, $set->year, $set->theme, $set->subtheme, $images);
    	$brick_sets[] = $brick_set;

      \Drupal::logger('brickset_connect')->notice("Loaded brick set(s): " . print_r($brick_set, true));
    }

    return $brick_sets;
	}

	/**
	  * Save an array of BrickSets
	  */
	public function save_sets($brick_sets) {
        // make sure we got at least one set loaded
        if (!$brick_sets || count($brick_sets) < 1) {
    			throw new \Exception("Less than 1 brick set for saving");
        }

        // one set returns an object, not an array with one element, so...
        // if the result isn't an array, let's stick it in an array, so...
        // we'll always know that it's an array, regardless of how many elements
        if (!is_array($brick_sets)) {
        	$brick_sets = array($brick_sets);
        }

    		foreach ($brick_sets as $brick_set) {
          \Drupal::logger('brickset_connect')->notice("Save set: " . print_r($brick_set, true));

          try {
            $brick_set->create_node();
          }
          catch (Exception $e) {
            \Drupal::messenger()->addMessage($e->getMessage());
          }
        }
	}

	/**
	  * Get the additional images for a set_id
		* @return object of additional image data
	  */
	public function get_additional_images($set_id) {
		$client = new \SoapClient(static::WSDL);

		// must pass ALL parameters, even if they are blank!
		// not sure why this one only wants the API key an not the user hash
    $params = array(
      'apiKey' => $this->api_key,
      'setID' => $set_id,
    );

    $additional_images_objs = $client->getAdditionalImages($params)->getAdditionalImagesResult->additionalImages;

    if (!is_array($additional_images_objs)) {
      $additional_images_objs = array($additional_images_objs);
    }

    $additional_images = array();

    foreach($additional_images_objs as $image) {
      $additional_images[] = $image->imageURL;
    }

    \Drupal::logger('brickset_connect')->notice('Additional images returning: ' . print_r($additional_images, true));

    return $additional_images;
	}
}
