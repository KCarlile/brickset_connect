<?php
/**
 * @file
 * Contains Drupal\brickset_connect\Form\BricksetConnectContentMenuForm.
 */
namespace Drupal\brickset_connect\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\brickset_connect\BricksetConnectAPIClient;
//use Drupal\brickset_connect\BrickSet;

class BricksetConnectContentMenuForm extends FormBase {
  /** @var string Config settings */
  const SETTINGS = 'brickset_connect.content_menu';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'brickset_connect_content_menu_form';
  }

   /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
  	// Set Numbers
    $form['set_numbers'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Import Set Numbers:'),
      '#description' => $this->t('Enter brick set numbers, one per line.'),
      '#cols' => 4,
      '#rows' => 10,
    );
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Import'),
      '#button_type' => 'primary',
    );
    /*
    $form['actions']['refresh'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Refresh Content'),
      '#button_type' => 'primary',
      '#submit' => array('::refresh'),
    );
    */
    /*
    $form['actions']['apicall'] = array(
      '#type' => 'submit',
      '#value' => $this->t('API Call'),
      '#button_type' => 'primary',
      '#submit' => array('::apicall'),
    );
    */
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $set_nums_length = strlen($form_state->getValue('set_numbers'));

    // only need to validate for imports
    if (($form_state->getValue('op') == 'Import') && ($set_nums_length < 3)) {
      $form_state->setErrorByName('set_numbers', $this->t('Set list is too short.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $set_numbers = explode("\r\n", $form_state->getValue('set_numbers'));
 drupal_set_message(print_r($set_numbers, true));

   	try {
  	 	$brickset_connect = new BricksetConnectAPIClient();
  	 	
  	 	if (!$brickset_connect->login()) {
  	 		throw new \Exception("Brickset login failed");
  	 	}

drupal_set_message('User hash:' . $brickset_connect->get_user_hash());
drupal_set_message('Logged in!');

      $brick_sets = $brickset_connect->load_sets($set_numbers);
  	  $brickset_connect->save_sets($brick_sets, true);
   	} catch (\Exception $e) {
      drupal_set_message('Exception: ' . $e->getMessage());

   		$api_check_result = BricksetConnectAPIClient::check_api_key();

   		if (!$api_check_result) {
   		  $e . "<p><strong>API key check failed.</strong></p>";
   		}

      drupal_set_message('Exception: ' . $e->getMessage());
   	}
  }

  /**
   * {@inheritdoc}
   */
  public function refresh(array &$form, FormStateInterface $form_state) {
    drupal_set_message("Refreshing dees sumsa bitches!");
  }
}