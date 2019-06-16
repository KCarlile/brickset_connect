<?php
/**
 * @file
 * Contains Drupal\brickset_connect\Form\BricksetConnectAdminForm.
 */
namespace Drupal\brickset_connect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\brickset_connect\BricksetConnectAPIClient;

class BricksetConnectAdminForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'brickset_connect_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      BricksetConnectAPIClient::SETTINGS,
    ];
  }

   /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);
    // Default settings.
    $config = $this->config(BricksetConnectAPIClient::SETTINGS);

    // Brickset username
    $form['brickset_username'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Brickset Username:'),
      '#default_value' => $config->get('brickset_username'),
      '#description' => $this->t('Enter username of your Brickset account.'),
    );

    // Brickset password
    $form['brickset_password'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Brickset Password:'),
      '#default_value' => $config->get('brickset_password'),
      '#description' => $this->t('Enter password of your Brickset account.'),
    );

    // Brickset API key
    $form['brickset_api_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Brickset API Key:'),
      '#default_value' => $config->get('brickset_api_key'),
      '#description' => $this->t('Enter the API key from your Brickset account.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $api_check_result = BricksetConnectAPIClient::check_api_key($form_state->getValue('brickset_api_key'));

    if($api_check_result == false) {
      $form_state->setErrorByName('brickset_api_key', $this->t('API key check failed.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
	  // Retrieve the configuration
	  $this->configFactory->getEditable(BricksetConnectAPIClient::SETTINGS)
		  // Set the submitted configuration setting
		  ->set('brickset_api_key', $form_state->getValue('brickset_api_key'))
      ->set('brickset_username', $form_state->getValue('brickset_username'))
      ->set('brickset_password', $form_state->getValue('brickset_password'))
		  ->save();

    drupal_set_message('Settings saved.');
  }
}
