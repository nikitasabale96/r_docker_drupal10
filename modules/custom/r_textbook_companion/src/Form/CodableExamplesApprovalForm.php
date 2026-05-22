<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\CodableExamplesApprovalForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

class CodableExamplesApprovalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'codable_examples_approval_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $request = \Drupal::request();
    $pref_id = $request->attributes->get('preference_id') ?? $request->query->get('preference_id');
    $pref_id = $pref_id ? (int) $pref_id : 0;

    if ($pref_id <= 0) {
      $this->messenger()->addError($this->t('Invalid book selected.'));
      return [];
    }

    $connection = \Drupal::database();
    $preference_data = $connection->select('textbook_companion_preference', 'tp')
      ->fields('tp')
      ->condition('id', $pref_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if (!$preference_data) {
      $this->messenger()->addError($this->t('Invalid book selected.'));
      return [];
    }

    $proposal_data = $connection->select('textbook_companion_proposal', 'tpr')
      ->fields('tpr')
      ->condition('id', $preference_data->proposal_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if (!$proposal_data) {
      $this->messenger()->addError($this->t('Invalid proposal selected.'));
      return [];
    }

    $example_data = $connection->select('textbook_companion_codable_example_files', 'tcef')
      ->fields('tcef')
      ->condition('proposal_id', $preference_data->proposal_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if (!$example_data) {
      $this->messenger()->addError($this->t('No codable example file found for this proposal.'));
      return [];
    }

    $form['#tree'] = TRUE;
    $form['contributor'] = [
      '#type' => 'item',
      '#plain_text' => $proposal_data->full_name ?? '',
      '#title' => $this->t('Contributor Name'),
    ];
    $form['book_details']['book'] = [
      '#type' => 'item',
      '#plain_text' => $preference_data->book ?? '',
      '#title' => $this->t('Title of the Book'),
    ];

    $form['download_file'] = [
      '#type' => 'item',
      '#title' => $this->t('Click to download the file with list of codable examples'),
      '#markup' => Link::fromTextAndUrl(
        $this->t('Download Example'),
        Url::fromRoute('textbook_companion.download_codable_example_file', ['proposal_id' => $example_data->proposal_id])
      )->toString(),
    ];
    $form['status_of_codable_example'] = [
      '#type' => 'radios',
      '#options' => [
        $this->t('Approved'),
        $this->t('Dis-approved'),
      ],
    ];
    $form['message'] = [
      '#type' => 'textarea',
      '#size' => 5000,
      '#maxlength' => 5000,
      '#title' => $this->t('Reason for dis-approval'),
    ];
    $form['proposal_id'] = [
      '#type' => 'hidden',
      '#value' => $example_data->proposal_id,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    $form['back_to_list'] = [
      '#type' => 'item',
      '#markup' => Link::fromTextAndUrl(
        $this->t('Back to Code Approval List'),
        Url::fromRoute('textbook_companion.codable_example_approval')
      )->toString(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $proposal_id = (int) $form_state->getValue('proposal_id');
    $connection = \Drupal::database();

    $preference_data = $connection->select('textbook_companion_preference', 'tp')
      ->fields('tp')
      ->condition('proposal_id', $proposal_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if (!$preference_data) {
      $this->messenger()->addError($this->t('Invalid proposal selected.'));
      return;
    }

    $proposal_data = $connection->select('textbook_companion_proposal', 'tpr')
      ->fields('tpr')
      ->condition('id', $preference_data->proposal_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    $user_data = $proposal_data ? User::load($proposal_data->uid) : NULL;

    $status = (string) $form_state->getValue('status_of_codable_example');
    if ($status === '0') {
      $connection->update('textbook_companion_preference')
        ->fields(['approved_codable_example_files' => 1])
        ->condition('proposal_id', $proposal_id)
        ->execute();

      $email_to = $user_data?->getEmail() ?? '';
      $config = \Drupal::config('textbook_companion.settings');
      $from = (string) $config->get('textbook_companion_from_email');
      $bcc = (string) $config->get('textbook_companion_emails');
      $cc = (string) $config->get('textbook_companion_cc_emails');
      $params['codable_example_approved']['proposal_id'] = $proposal_id;
      $params['codable_example_approved']['user_id'] = $user_data?->id() ?? 0;
      $params['codable_example_approved']['headers'] = [
        'From' => $from,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
        'Content-Transfer-Encoding' => '8Bit',
        'X-Mailer' => 'Drupal',
        'Cc' => $cc,
        'Bcc' => $bcc,
      ];

      if ($email_to !== '' && $user_data) {
        $langcode = $user_data->getPreferredLangcode();
        $result = \Drupal::service('plugin.manager.mail')->mail('textbook_companion', 'codable_example_approved', $email_to, $langcode, $params, $from, TRUE);
        if (empty($result['result'])) {
          $this->messenger()->addError($this->t('Error sending email message.'));
        }
      }

      $this->messenger()->addStatus($this->t('File approved.'));
      $form_state->setRedirect('textbook_companion.codable_example_approval');
      return;
    }

    if ($status === '1') {
      $connection->update('textbook_companion_preference')
        ->fields([
          'submitted_codable_examples_file' => 0,
          'submited_all_examples_code' => 0,
          'approved_codable_example_files' => 0,
        ])
        ->condition('proposal_id', $proposal_id)
        ->execute();

      $email_to = $user_data?->getEmail() ?? '';
      $config = \Drupal::config('textbook_companion.settings');
      $from = (string) $config->get('textbook_companion_from_email');
      $bcc = (string) $config->get('textbook_companion_emails');
      $cc = (string) $config->get('textbook_companion_cc_emails');
      $params['codable_example_disapproved']['proposal_id'] = $proposal_id;
      $params['codable_example_disapproved']['user_id'] = $user_data?->id() ?? 0;
      $params['codable_example_disapproved']['message'] = (string) $form_state->getValue('message');
      $params['codable_example_disapproved']['headers'] = [
        'From' => $from,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
        'Content-Transfer-Encoding' => '8Bit',
        'X-Mailer' => 'Drupal',
        'Cc' => $cc,
        'Bcc' => $bcc,
      ];

      if ($email_to !== '' && $user_data) {
        $langcode = $user_data->getPreferredLangcode();
        $result = \Drupal::service('plugin.manager.mail')->mail('textbook_companion', 'codable_example_disapproved', $email_to, $langcode, $params, $from, TRUE);
        if (empty($result['result'])) {
          $this->messenger()->addError($this->t('Error sending email message.'));
        }
      }

      $this->messenger()->addStatus($this->t('File disapproved and user has been notified of the changes.'));
      $form_state->setRedirect('textbook_companion.codable_example_approval');
      return;
    }

    $this->messenger()->addError($this->t('Error in updating the status. Please contact administrator.'));
  }

}
