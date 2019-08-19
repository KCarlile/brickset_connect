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
    \Drupal::logger('brickset_connect')->notice(print_r($set_numbers, true));

   	try {
  	 	$brickset_connect = new BricksetConnectAPIClient();
  	 	
  	 	if (!$brickset_connect->login()) {
  	 		throw new \Exception("Brickset login failed");
  	 	}

      \Drupal::logger('brickset_connect')->notice('Logged in. User hash:' . $brickset_connect->get_user_hash());

      $brick_sets = $brickset_connect->load_sets($set_numbers);
  	  $brickset_connect->save_sets($brick_sets, true);
   	} catch (\Exception $e) {
      \Drupal::logger('brickset_connect')->error($e->getMessage());

   		$api_check_result = BricksetConnectAPIClient::check_api_key();

   		if (!$api_check_result) {
   		  $e . "<p><strong>API key check failed.</strong></p>";
   		}

      \Drupal::logger('brickset_connect')->error('Exception: ' . $e->getMessage());
   	}
  }
}