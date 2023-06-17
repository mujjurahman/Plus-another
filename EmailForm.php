<?php

namespace Drupal\custom_module\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Database;

/**
 * Class EmailForm.
 */
class EmailForm extends FormBase {

  /**
   * Drupal\Core\Mail\MailManagerInterface definition.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;
  
  public function __construct(
    MailManagerInterface $mail_manager
  ) {
    $this->mailManager = $mail_manager;
  }
  
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.mail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'email_form';
  }

  /**
   * {@inheritdoc}
   */
// Building the form
public function buildForm(array $form, FormStateInterface $form_state) {

    // Fetch all unique products from the database
    $database = Database::getConnection();
    $query = $database->select('custom_table', 'ct');
    $query->fields('ct', ['produit']);
    $query->distinct(TRUE);
    $results = $query->execute()->fetchCol();

    // Create options array for product dropdown
    $product_options = ['' => $this->t('Select a Product')] + array_combine($results, $results);

    // Create product dropdown
    $form['produit'] = [
        '#type' => 'select',
        '#title' => $this->t('Product'),
        '#options' => $product_options,
    ];

    // Repeat the process for the therapeutic area and district
    // ...

    $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
    ];

    return $form;
}

// Validate form
public function validateForm(array &$form, FormStateInterface $form_state) {
    // You may add validation if necessary
}

// Submit form
public function submitForm(array &$form, FormStateInterface $form_state) {
    $database = Database::getConnection();

    // Fetch the form values
    $produit = $form_state->getValue('produit');
    // Do this for the other fields...

    // Construct the query based on the form values
    $query = $database->select('custom_table', 'ct');

    $query->fields('ct', ['rmr_adresse_email', 'backup_adresse_email']);
    $query->condition('ct.produit', $produit, '=');
    // Add more conditions based on the other fields...

    $results = $query->execute()->fetchAll();

    // Loop through the results and send the emails
    foreach ($results as $result) {
        $to = $result->rmr_adresse_email;
        $backup = $result->backup_adresse_email;

        $mailManager = \Drupal::service('plugin.manager.mail');
        $params = ['message' => 'Hello, this is a test email.'];
        $langcode = \Drupal::currentUser()->getPreferredLangcode();
        $send = TRUE;

        $result = $mailManager->mail('custom_module', 'contact', $to, $langcode, $params, NULL, $send);

        if ($result['result'] !== TRUE) {
            $message = t('There was a problem sending your message to @email.', ['@email' => $to]);
            \Drupal::logger('mail-log')->error($message);
            \Drupal::messenger()->addError($message);
        }

        // Do the same for the backup email...
    }
}
}
