<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\CodeApprovalForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

class CodeApprovalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'code_approval_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $chapter_id = NULL) {
    $chapter_id = $chapter_id ?? $this->getRouteMatch()->getParameter('chapter_id');
    $chapter_id = (int) $chapter_id;
    if ($chapter_id <= 0) {
      $this->messenger()->addError($this->t('Invalid chapter selected.'));
      return [];
    }

    $connection = \Drupal::database();
    $pending_chapter_data = $connection->select('textbook_companion_chapter')
      ->fields('textbook_companion_chapter')
      ->condition('id', $chapter_id)
      ->execute()
      ->fetchObject();

    if (!$pending_chapter_data) {
      $this->messenger()->addError($this->t('Invalid chapter selected.'));
      return [];
    }

    $preference_data = $connection->select('textbook_companion_preference')
      ->fields('textbook_companion_preference')
      ->condition('id', $pending_chapter_data->preference_id)
      ->execute()
      ->fetchObject();

    $proposal_data = $connection->select('textbook_companion_proposal')
      ->fields('textbook_companion_proposal')
      ->condition('id', $preference_data->proposal_id)
      ->execute()
      ->fetchObject();

    $form['#tree'] = TRUE;
    $form['contributor'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->full_name,
      '#title' => $this->t('Contributor Name'),
    ];
    $form['book_details']['book'] = [
      '#type' => 'item',
      '#markup' => $preference_data->book,
      '#title' => $this->t('Title of the Book'),
    ];
    $form['book_details']['number'] = [
      '#type' => 'item',
      '#markup' => $pending_chapter_data->number,
      '#title' => $this->t('Chapter Number'),
    ];
    $form['book_details']['name'] = [
      '#type' => 'item',
      '#markup' => $pending_chapter_data->name,
      '#title' => $this->t('Title of the Chapter'),
    ];
    $form['book_details']['back_to_list'] = [
      '#type' => 'item',
      '#markup' => Link::fromTextAndUrl($this->t('Back to Code Approval List'), Url::fromRoute('textbook_companion.code_approval'))->toString(),
    ];

    $example_q = $connection->select('textbook_companion_example')
      ->fields('textbook_companion_example')
      ->condition('chapter_id', $chapter_id)
      ->condition('approval_status', 0)
      ->execute();

    while ($example_data = $example_q->fetchObject()) {
      $form['example_details'][$example_data->id] = [
        '#type' => 'fieldset',
        '#collapsible' => FALSE,
        '#collapsed' => TRUE,
      ];
      $form['example_details'][$example_data->id]['example_number'] = [
        '#type' => 'item',
        '#markup' => $example_data->number,
        '#title' => $this->t('Example Number'),
      ];
      $form['example_details'][$example_data->id]['example_caption'] = [
        '#type' => 'item',
        '#markup' => $example_data->caption,
        '#title' => $this->t('Example Caption'),
      ];
      $form['example_details'][$example_data->id]['download'] = [
        '#type' => 'markup',
        '#markup' => Link::fromTextAndUrl(
          $this->t('Download Example'),
          Url::fromRoute('textbook_companion.download_example', ['example_id' => $example_data->id])
        )->toString(),
      ];
      $form['example_details'][$example_data->id]['approved'] = [
        '#type' => 'radios',
        '#options' => [
          0 => $this->t('Approved'),
          1 => $this->t('Dis-approved'),
        ],
      ];
      $form['example_details'][$example_data->id]['message'] = [
        '#type' => 'textarea',
        '#size' => 500,
        '#maxlength' => 500,
        '#title' => $this->t('Reason for dis-approval'),
      ];
      $form['example_details'][$example_data->id]['example_id'] = [
        '#type' => 'hidden',
        '#value' => $example_data->id,
      ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = $this->currentUser();
    $connection = \Drupal::database();
    $config = \Drupal::config('textbook_companion.settings');
    $from = (string) $config->get('textbook_companion_from_email');
    $bcc = (string) $config->get('textbook_companion_emails');
    $cc = (string) $config->get('textbook_companion_cc_emails');

    foreach ($form_state->getValue('example_details') as $ex_data) {
      $example_data = $connection->select('textbook_companion_example')
        ->fields('textbook_companion_example')
        ->condition('id', $ex_data['example_id'])
        ->range(0, 1)
        ->execute()
        ->fetchObject();

      $chapter_data = $connection->select('textbook_companion_chapter')
        ->fields('textbook_companion_chapter')
        ->condition('id', $example_data->chapter_id)
        ->range(0, 1)
        ->execute()
        ->fetchObject();

      $preference_data = $connection->select('textbook_companion_preference')
        ->fields('textbook_companion_preference')
        ->condition('id', $chapter_data->preference_id)
        ->range(0, 1)
        ->execute()
        ->fetchObject();

      $proposal_data = $connection->select('textbook_companion_proposal')
        ->fields('textbook_companion_proposal')
        ->condition('id', $preference_data->proposal_id)
        ->range(0, 1)
        ->execute()
        ->fetchObject();

      $user_data = User::load($proposal_data->uid);
      del_book_pdf($preference_data->id);

      if ((string) $ex_data['approved'] === '0') {
        $connection->update('textbook_companion_example')
          ->fields([
            'approval_status' => 1,
            'approver_uid' => $user->id(),
            'approval_date' => time(),
          ])
          ->condition('id', $ex_data['example_id'])
          ->execute();

        $email_to = $user_data?->getEmail() ?? '';
        $param['example_approved']['example_id'] = $ex_data['example_id'];
        $param['example_approved']['user_id'] = $user_data?->id() ?? 0;
        $param['example_approved']['headers'] = [
          'From' => $from,
          'MIME-Version' => '1.0',
          'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
          'Content-Transfer-Encoding' => '8Bit',
          'X-Mailer' => 'Drupal',
          'Cc' => $cc,
          'Bcc' => $bcc,
        ];

        if ($email_to !== '') {
          $langcode = $user_data?->getPreferredLangcode() ?? \Drupal::languageManager()->getDefaultLanguage()->getId();
          $result = \Drupal::service('plugin.manager.mail')->mail('textbook_companion', 'example_approved', $email_to, $langcode, $param, $from, TRUE);
          if (empty($result['result'])) {
            $this->messenger()->addError($this->t('Error sending email message.'));
          }
        }
      }
      elseif ((string) $ex_data['approved'] === '1') {
        if (delete_example($ex_data['example_id'])) {
          $email_to = $user_data?->getEmail() ?? '';
          $param['example_disapproved']['preference_id'] = $chapter_data->preference_id;
          $param['example_disapproved']['chapter_id'] = $example_data->chapter_id;
          $param['example_disapproved']['example_number'] = $example_data->number;
          $param['example_disapproved']['example_caption'] = $example_data->caption;
          $param['example_disapproved']['user_id'] = $user_data?->id() ?? 0;
          $param['example_disapproved']['message'] = $ex_data['message'];
          $param['example_disapproved']['headers'] = [
            'From' => $from,
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
            'Content-Transfer-Encoding' => '8Bit',
            'X-Mailer' => 'Drupal',
            'Cc' => $cc,
            'Bcc' => $bcc,
          ];
          if ($email_to !== '') {
            $langcode = $user_data?->getPreferredLangcode() ?? \Drupal::languageManager()->getDefaultLanguage()->getId();
            $result = \Drupal::service('plugin.manager.mail')->mail('textbook_companion', 'example_disapproved', $email_to, $langcode, $param, $from, TRUE);
            if (empty($result['result'])) {
              $this->messenger()->addError($this->t('Error sending email message.'));
            }
          }
        }
        else {
          $this->messenger()->addError($this->t('Error disapproving and deleting example. Please contact administrator.'));
        }
      }
    }

    $this->messenger()->addStatus($this->t('Updated successfully.'));
    $form_state->setRedirect('textbook_companion.code_approval');
  }

}
