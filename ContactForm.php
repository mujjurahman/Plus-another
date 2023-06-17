<?php

namespace Drupal\custom_module\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\Core\Mail\MailManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Database;

/**
 * Class ContactForm.
 */
class ContactForm extends FormBase {

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
    return 'contact_form';
  }

  /**
   * {@inheritdoc}
   */
// Building the form
public function buildForm(array $form, FormStateInterface $form_state) {

    $form['file_upload'] = [
        '#type' => 'managed_file',
        '#title' => $this->t('Upload file'),
        '#upload_location' => 'public://custom_module/',
        '#required' => TRUE,
        '#description' => $this->t('Only files with the following extensions are allowed: xlsx xls csv'),
        '#upload_validators' => [
            'file_validate_extensions' => ['xlsx xls csv'],
        ],
    ];

    $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
    ];

    return $form;
}

// Validate form
public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check if the uploaded file extension is csv
    $all_files = $form_state->getValue(['file_upload'], []);
    if (!empty($all_files)) {
        /* Fetch the array of the file stored temporarily in database */
        $current_file = File::load( $all_files[0] );
        // Check if file is not empty
        if ($current_file !== null) {
            $file_extension = $current_file->getFileUri();
            $file_extension = pathinfo($file_extension, PATHINFO_EXTENSION);
            if ($file_extension !== 'csv') {
                // If file is not a CSV, raise an error
                $form_state->setErrorByName('file_upload', $this->t('Only CSV files are allowed.'));
            }
        }
    }
}

// Submit form
public function submitForm(array &$form, FormStateInterface $form_state) {
    $connection = Database::getConnection();
    $all_files = $form_state->getValue(['file_upload'], []);
    if (!empty($all_files)) {
        /* Load the object of the file by it's fid */
        $file = File::load( $all_files[0] );
        /* Set the status flag permanent of the file object */
        $file->setPermanent();
        /* Save the file in database */
        $file->save();

        $handle = fopen($file->getFileUri(), 'r');
        $header = fgetcsv($handle);
        $queries = [];
        while (($data = fgetcsv($handle)) !== FALSE) {
            $row = array_combine($header, $data);
            $queries[] = [
                'produit' => $row['Produit'],
                'aire_therapeutique' => $row['Aire thérapeutique'],
                'department' => $row['Département'],
                'rmr_adresse_email' => $row['RMR adresse email'],
                'backup_adresse_email' => $row['Backup adresse email'],
            ];
        }
        fclose($handle);
        // Delete existing data in table
        $connection->truncate('custom_table')->execute();
        // Insert new data
        foreach($queries as $query) {
            $connection->insert('custom_table')->fields($query)->execute();
        }
        $this->messenger()->addMessage($this->t('The CSV file has been successfully imported.'));
    }
}
