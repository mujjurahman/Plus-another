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
  public function buildForm(array $form, FormStateInterface $form_state) {
    // To be continued
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // To be continued
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // To be continued
  }
}
