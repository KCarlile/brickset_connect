<?php
/**
 * @file
 * Contains Drupal\brickset_connect\Form\BricksetConnectBulkImportForm.
 */
namespace Drupal\brickset_connect\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\brickset_connect\BricksetConnectAPIClient;
use Drupal\brickset_connect\BrickSet;

class BricksetConnectBulkImportForm extends FormBase {
  /** @var string Config settings */
  const SETTINGS = 'brickset_connect.bulk_import';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'brickset_connect_bulk_import_form';
  }

   /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
  	// Set Numbers
    $form['set_numbers'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Bulk Import Set Numbers:'),
      '#description' => $this->t('Enter brick set numbers, one per line.'),
      '#cols' => 4,
      '#rows' => 20,
    );
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Import'),
      '#button_type' => 'primary',
    );
    $form['actions']['refresh'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Refresh Content'),
      '#button_type' => 'primary',
      '#submit' => array('::refresh'),
    );

    $form['actions']['apicall'] = array(
      '#type' => 'submit',
      '#value' => $this->t('API Call'),
      '#button_type' => 'primary',
      '#submit' => array('::apicall'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $set_nums_length = strlen($form_state->getValue('set_numbers'));

    // only need to validate for imports
    if (($form_state->getValue('op') == 'Import') && ($set_nums_length< 4)) {
      $form_state->setErrorByName('set_numbers', $this->t('Set list is too short.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $set_numbers = explode("\r\n", $form_state->getValue('set_numbers'));
    drupal_set_message(print_r($set_numbers, true));

    // PROCESS IMPORTS
    drupal_set_message("Making API call...");

    $api_check_result = BricksetConnectAPIClient::check_api_key();

    if($api_check_result == false) {
      drupal_set_message('API check failed.');
    }
    else {
      drupal_set_message('API check succeeded.');
    }

    $user_hash = BricksetConnectAPIClient::login();
    $api_key = BricksetConnectAPIClient::get_api_key();

    if (strlen($user_hash) > 0) {
      drupal_set_message('Login hash: ' . $user_hash);
    }
    else {
      drupal_set_message('Login hash failed!');
    }

    drupal_set_message(print_r($set_numbers, true));

    $sets = BricksetConnectAPIClient::get_sets($user_hash, $set_numbers);
    drupal_set_message('SetResult: ' . print_r($sets, true));
    
    foreach ($sets as $set) {
      drupal_set_message('SetID: ' . $set->setID);
      
      $additional_images = BricksetConnectAPIClient::get_additional_images($set->setID);

      drupal_set_message('AddlImages: ' . print_r($additional_images, true));

      $brick_set = new Brickset($set->number, $set->name, $set->year, $additional_images);

      drupal_set_message("Brick set: " . print_r($brick_Set, true));

      try {
        $brick_set->create_node();  
      }
      catch (Exception $e) {
        drupal_set_message($e);
      }
      
    }
  }

  /**
   * {@inheritdoc}
   */
  public function refresh(array &$form, FormStateInterface $form_state) {
    drupal_set_message("Refreshing dees sumsa bitches!");
  }

  /**
   * {@inheritdoc}
   */
  public function apicall(array &$form, FormStateInterface $form_state) {
    $set_numbers = explode("\r\n", $form_state->getValue('set_numbers'));
    drupal_set_message(print_r($set_numbers, true));
    $result = BrickSet::brick_set_exists('7195');
    /*
    drupal_set_message("Making API call...");

    $api_check_result = BricksetConnectAPIClient::check_api_key();

    if($api_check_result == false) {
      drupal_set_message('API check failed.');
    }
    else {
      drupal_set_message('API check succeeded.');
    }

    $user_hash = BricksetConnectAPIClient::login();
    $api_key = BricksetConnectAPIClient::get_api_key();

    if (strlen($user_hash) > 0) {
      drupal_set_message('Login hash: ' . $user_hash);
    }
    else {
      drupal_set_message('Login hash failed!');
    }

    $sets = BricksetConnectAPIClient::get_sets($user_hash, array("76007", "76049"));

    foreach ($sets as $set) {
      //drupal_set_message('SetID: ' . $set->setID);
      
      $additional_images = BricksetConnectAPIClient::get_additional_images($set->setID);

      //drupal_set_message('AddlImages: ' . print_r($additional_images, true));

      $brick_set = new BrickSet($set->number, $set->name, $set->year, $additional_images);

      drupal_set_message("Brick set: " . print_r($brick_set, true));
      $brick_set->create_node();
    }
    */
    
  }
}
