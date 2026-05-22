<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\PaperSubmissionForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

class PaperSubmissionForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'paper_submission_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $proposal_id = NULL) {
    $proposal_id = $proposal_id ?? \Drupal::routeMatch()->getParameter('proposal_id');
    $proposal_id = (int) $proposal_id;
    $connection = \Drupal::database();

    $paper_data = $connection->select('textbook_companion_paper', 'tp')
      ->fields('tp')
      ->condition('proposal_id', $proposal_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if (!$paper_data) {
      $connection->insert('textbook_companion_paper')
        ->fields(['proposal_id' => $proposal_id])
        ->execute();
      $paper_data = (object) [];
    }

    $form['proposal_id'] = [
      '#type' => 'hidden',
      '#default_value' => $proposal_id,
    ];
    $form['internshipform'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Recieved Internship Application'),
      '#description' => $this->t('Check if the Internship Application has been recieved.'),
      '#default_value' => $paper_data->internship_form ?? 0,
    ];
    $form['copyrighttransferform'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Recieved Copyright Transfer Form'),
      '#description' => $this->t('Check if the Copyright Transfer Form has been recieved.'),
      '#default_value' => $paper_data->copyright_form ?? 0,
    ];
    $form['undertakingform'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Recieved Undertaking Form'),
      '#description' => $this->t('Check if the Undertaking Form has been recieved.'),
      '#default_value' => $paper_data->undertaking_form ?? 0,
    ];
    $form['recieptform'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Recieved Reciept Form'),
      '#description' => $this->t('Check if the Reciept Form has been recieved.'),
      '#default_value' => $paper_data->reciept_form ?? 0,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send Email'),
    ];
    $form['cancel'] = [
      '#type' => 'markup',
      '#markup' => Link::fromTextAndUrl($this->t('Cancel'), Url::fromRoute('textbook_companion._proposal_all'))->toString(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $connection = \Drupal::database();
    $proposal_id = (int) $form_state->getValue('proposal_id');

    $connection->update('textbook_companion_paper')
      ->fields([
        'internship_form' => $form_state->getValue('internshipform'),
        'copyright_form' => $form_state->getValue('copyrighttransferform'),
        'undertaking_form' => $form_state->getValue('undertakingform'),
        'reciept_form' => $form_state->getValue('recieptform'),
      ])
      ->condition('proposal_id', $proposal_id)
      ->execute();

    $proposal_data = $connection->select('textbook_companion_proposal', 'tp')
      ->fields('tp')
      ->condition('id', $proposal_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    $book_user = $proposal_data ? User::load($proposal_data->uid) : NULL;
    $email_to = $book_user?->getEmail() ?? '';

    $config = \Drupal::config('textbook_companion.settings');
    $from = (string) $config->get('textbook_companion_from_email');
    $bcc = (string) $config->get('textbook_companion_emails');
    $cc = (string) $config->get('textbook_companion_cc_emails');

    $headers = [
      'From' => $from,
      'MIME-Version' => '1.0',
      'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
      'Content-Transfer-Encoding' => '8Bit',
      'X-Mailer' => 'Drupal',
      'Cc' => $cc,
      'Bcc' => $bcc,
    ];

    $langcode = $book_user?->getPreferredLangcode() ?? \Drupal::languageManager()->getDefaultLanguage()->getId();

    if ($form_state->getValue('internshipform') == 1) {
      $params['internshipform']['proposal_id'] = $proposal_id;
      $params['internshipform']['user_id'] = $proposal_data->uid;
      $params['internshipform']['is_received'] = 1;
      $params['internshipform']['headers'] = $headers;
      if ($email_to !== '') {
        $result = \Drupal::service('plugin.manager.mail')->mail('textbook_companion', 'internshipform', $email_to, $langcode, $params, $from, TRUE);
        if (empty($result['result'])) {
          $this->messenger()->addError($this->t('Error sending email message.'));
        }
      }
      $this->messenger()->addStatus($this->t('Internship Form for Book proposal has been recieved. User has been notified .'));
    }
    else {
      $params['internshipform']['proposal_id'] = $proposal_id;
      $params['internshipform']['user_id'] = $proposal_data->uid;
      $params['internshipform']['is_received'] = 0;
      $params['internshipform']['headers'] = $headers;
      if ($email_to !== '') {
        $result = \Drupal::service('plugin.manager.mail')->mail('textbook_companion', 'internshipform', $email_to, $langcode, $params, $from, TRUE);
        if (empty($result['result'])) {
          $this->messenger()->addError($this->t('Error sending email message.'));
        }
      }
      $this->messenger()->addStatus($this->t('Internship Form for Book proposal has not been recieved. User has been notified .'));
    }

    if ($form_state->getValue('copyrighttransferform') == 1) {
      $params['copyrighttransferform']['proposal_id'] = $proposal_id;
      $params['copyrighttransferform']['user_id'] = $proposal_data->uid;
      $params['copyrighttransferform']['is_received'] = 1;
      $params['copyrighttransferform']['headers'] = $headers;
      if ($email_to !== '') {
        $result = \Drupal::service('plugin.manager.mail')->mail('textbook_companion', 'copyrighttransferform', $email_to, $langcode, $params, $from, TRUE);
        if (empty($result['result'])) {
          $this->messenger()->addError($this->t('Error sending email message.'));
        }
      }
      $this->messenger()->addStatus($this->t('Copyright Form for Book proposal has been recieved. User has been notified .'));
    }
    else {
      $params['copyrighttransferform']['proposal_id'] = $proposal_id;
      $params['copyrighttransferform']['user_id'] = $proposal_data->uid;
      $params['copyrighttransferform']['is_received'] = 0;
      $params['copyrighttransferform']['headers'] = $headers;
      if ($email_to !== '') {
        $result = \Drupal::service('plugin.manager.mail')->mail('textbook_companion', 'copyrighttransferform', $email_to, $langcode, $params, $from, TRUE);
        if (empty($result['result'])) {
          $this->messenger()->addError($this->t('Error sending email message.'));
        }
      }
      $this->messenger()->addStatus($this->t('Copyright Transfer Form for Book proposal has not been recieved. User has been notified .'));
    }

    if ($form_state->getValue('undertakingform') == 1) {
      $params['undertakingform']['proposal_id'] = $proposal_id;
      $params['undertakingform']['user_id'] = $proposal_data->uid;
      $params['undertakingform']['is_received'] = 1;
      $params['undertakingform']['headers'] = $headers;
      if ($email_to !== '') {
        $result = \Drupal::service('plugin.manager.mail')->mail('textbook_companion', 'undertakingform', $email_to, $langcode, $params, $from, TRUE);
        if (empty($result['result'])) {
          $this->messenger()->addError($this->t('Error sending email message.'));
        }
      }
      $this->messenger()->addStatus($this->t('Undertaking Form for Book proposal has been recieved. User has been notified .'));
    }
    else {
      $params['undertakingform']['proposal_id'] = $proposal_id;
      $params['undertakingform']['user_id'] = $proposal_data->uid;
      $params['undertakingform']['is_received'] = 0;
      $params['undertakingform']['headers'] = $headers;
      if ($email_to !== '') {
        $result = \Drupal::service('plugin.manager.mail')->mail('textbook_companion', 'undertakingform', $email_to, $langcode, $params, $from, TRUE);
        if (empty($result['result'])) {
          $this->messenger()->addError($this->t('Error sending email message.'));
        }
      }
      $this->messenger()->addStatus($this->t('Undertaking Form for Book proposal has not been recieved. User has been notified .'));
    }

    $this->messenger()->addStatus($this->t('Proposal Updated'));
  }

}
