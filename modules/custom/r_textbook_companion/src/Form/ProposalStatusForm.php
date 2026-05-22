<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\ProposalStatusForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\textbook_companion\Helper\ProposalHelper;

class ProposalStatusForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'proposal_status_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $proposal_id = NULL) {
    $proposal_id = $proposal_id ?? $this->getRouteMatch()->getParameter('proposal_id');
    $proposal_id = (int) $proposal_id;
    $connection = \Drupal::database();

    $proposal_data = $connection->select('textbook_companion_proposal')
      ->fields('textbook_companion_proposal')
      ->condition('id', $proposal_id)
      ->execute()
      ->fetchObject();

    if (!$proposal_data) {
      $this->messenger()->addError($this->t('Invalid proposal selected. Please try again.'));
      $form_state->setRedirect('textbook_companion._proposal_all');
      return [];
    }

    $user_entity = User::load($proposal_data->uid);
    $email = $user_entity?->getEmail() ?? '';

    $form['full_name'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->full_name,
      '#title' => $this->t('Contributor Name'),
    ];
    $form['email'] = [
      '#type' => 'item',
      '#markup' => $email,
      '#title' => $this->t('Email'),
    ];
    $form['mobile'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->mobile,
      '#title' => $this->t('Mobile'),
    ];
    $form['how_project'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->how_project,
      '#title' => $this->t('How did you come to know about this project'),
    ];
    $form['course'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->course,
      '#title' => $this->t('Course'),
    ];
    $form['branch'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->branch,
      '#title' => $this->t('Department/Branch'),
    ];
    $form['university'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->university,
      '#title' => $this->t('University/Institute'),
    ];
    $form['city'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->city,
      '#title' => $this->t('City/Village'),
    ];
    $form['pincode'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->pincode,
      '#title' => $this->t('Pincode'),
    ];
    $form['state'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->state,
      '#title' => $this->t('State'),
    ];
    $form['faculty'] = [
      '#type' => 'hidden',
      '#value' => $proposal_data->faculty,
      '#title' => $this->t('College Teacher/Professor'),
    ];
    $form['reviewer'] = [
      '#type' => 'hidden',
      '#value' => $proposal_data->reviewer,
      '#title' => $this->t('Reviewer'),
    ];

    $approval_date = $proposal_data->approval_date ? date('d-m-Y', $proposal_data->approval_date) : '-----';
    $completion_date = $proposal_data->completion_date ? date('d-m-Y', $proposal_data->completion_date) : '-----';
    $proposed_completion_date = $proposal_data->proposed_completion_date ? date('d-m-Y', $proposal_data->proposed_completion_date) : '-----';

    $form['approval_date'] = [
      '#type' => 'item',
      '#markup' => $approval_date,
      '#title' => $this->t('Date of Approval'),
    ];
    $form['completion_date'] = [
      '#type' => 'item',
      '#markup' => $completion_date,
      '#title' => $this->t('Date of Completion'),
    ];
    $form['proposed_completion_date'] = [
      '#type' => 'item',
      '#markup' => $proposed_completion_date,
      '#title' => $this->t('Proposed Date of Completion'),
    ];
    $form['operating_system'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->operating_system,
      '#title' => $this->t('Operating System'),
    ];
    $form['version'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->r_version,
      '#title' => $this->t('R Version'),
    ];

    if ((int) $proposal_data->proposal_type === 1) {
      $form['reason'] = [
        '#type' => 'hidden',
        '#value' => $proposal_data->reason,
        '#title' => $this->t('Reason'),
      ];
    }

    $preference_html = '<ul>';
    $preference_q = $connection->select('textbook_companion_preference')
      ->fields('textbook_companion_preference')
      ->condition('proposal_id', $proposal_id)
      ->orderBy('pref_number', 'ASC')
      ->execute();
    while ($preference_data = $preference_q->fetchObject()) {
      if ($preference_data->approval_status == 1) {
        $preference_html .= '<li><strong>' . $preference_data->book . ' (Written by ' . $preference_data->author . ')  - Approved Book</strong></li>';
      }
      else {
        $preference_html .= '<li>' . $preference_data->book . ' (Written by ' . $preference_data->author . ')</li>';
      }
    }
    $preference_html .= '</ul>';
    $form['book_preference'] = [
      '#type' => 'item',
      '#markup' => $preference_html,
      '#title' => $this->t('Book Preferences'),
    ];

    $form['reference'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->reference,
      '#title' => $this->t('References'),
    ];

    $book_link = (string) $proposal_data->book_download_link;
    if ($book_link !== '') {
      $url = ProposalHelper::buildExternalUrl($book_link);
      $form['book_download_link'] = [
        '#type' => 'item',
        '#title' => $this->t('Download link for the proposed textbook'),
      ];
      if ($url) {
        $form['book_download_link']['#markup'] = Link::fromTextAndUrl($book_link, $url)->toString();
      }
      else {
        $form['book_download_link']['#plain_text'] = $book_link;
      }
    }

    $proposal_status = match ((int) $proposal_data->proposal_status) {
      0 => $this->t('Pending'),
      1 => $this->t('Approved'),
      2 => $this->t('Dis-approved'),
      3 => $this->t('Completed'),
      4 => $this->t('External'),
      5 => $this->t('Submitted all codes'),
      default => $this->t('Unkown'),
    };

    $form['proposal_status'] = [
      '#type' => 'item',
      '#markup' => $proposal_status,
      '#title' => $this->t('Proposal Status'),
    ];

    if ($proposal_data->proposal_status == 2) {
      $form['message'] = [
        '#type' => 'item',
        '#markup' => $proposal_data->message,
        '#title' => $this->t('Reason for disapproval'),
      ];
    }

    $preference_q_status = $connection->select('textbook_companion_preference')
      ->fields('textbook_companion_preference')
      ->condition('proposal_id', $proposal_id)
      ->orderBy('pref_number', 'ASC')
      ->execute()
      ->fetchObject();

    if ($preference_q_status && $preference_q_status->submited_all_examples_code == 1) {
      $form['submit_all_code'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('<strong>Enable Code Submission for user</strong>'),
        '#description' => $this->t('Check if user has not submitted all the book examples.'),
      ];
      $form['completed'] = [
        '#type' => 'hidden',
        '#value' => 0,
      ];
    }
    elseif ($preference_q_status && $preference_q_status->submited_all_examples_code == 2) {
      if (in_array((int) $proposal_data->proposal_status, [1, 4, 5], TRUE)) {
        $form['completed'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('<strong>Completed</strong>'),
          '#description' => $this->t('Check if user has completed all the book examples.'),
        ];
        $form['submit_all_code'] = [
          '#type' => 'hidden',
          '#value' => 0,
        ];
      }
    }

    $form['review_no'] = [
      '#type' => 'item',
      '#title' => $this->t('No. of reviews'),
      '#markup' => $preference_q_status?->review_no ?? 0,
    ];

    if ($proposal_data->proposal_status == 0) {
      $form['approve'] = [
        '#type' => 'item',
        '#markup' => Link::fromTextAndUrl($this->t('Click here'), Url::fromRoute('textbook_companion.proposal_approval_form', ['proposal_id' => $proposal_id]))->toString(),
        '#title' => $this->t('Approve'),
      ];
      $form['completed'] = [
        '#type' => 'hidden',
        '#value' => 0,
      ];
      $form['submit_all_code'] = [
        '#type' => 'hidden',
        '#value' => 0,
      ];
    }

    $form['proposal_id'] = [
      '#type' => 'hidden',
      '#value' => $proposal_id,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    $form['cancel'] = [
      '#type' => 'item',
      '#markup' => Link::fromTextAndUrl($this->t('Cancel'), Url::fromRoute('textbook_companion._proposal_all'))->toString(),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $proposal_id = (int) $form_state->getValue('proposal_id');
    $connection = \Drupal::database();

    $proposal_data = $connection->select('textbook_companion_proposal')
      ->fields('textbook_companion_proposal')
      ->condition('id', $proposal_id)
      ->execute()
      ->fetchObject();

    if (!$proposal_data) {
      $this->messenger()->addError($this->t('Invalid proposal selected. Please try again.'));
      $form_state->setRedirect('textbook_companion._proposal_all');
      return;
    }

    $config = \Drupal::config('textbook_companion.settings');
    $from = (string) $config->get('textbook_companion_from_email');
    $bcc = (string) $config->get('textbook_companion_emails');
    $cc = (string) $config->get('textbook_companion_cc_emails');

    if ((int) $form_state->getValue('submit_all_code') === 1) {
      $preference_q_status = $connection->select('textbook_companion_preference')
        ->fields('textbook_companion_preference')
        ->condition('proposal_id', $proposal_id)
        ->orderBy('pref_number', 'ASC')
        ->execute()
        ->fetchObject();
      $review_no = ($preference_q_status?->review_no ?? 0) + 1;

      $connection->update('textbook_companion_preference')
        ->fields([
          'submited_all_examples_code' => 0,
          'review_no' => $review_no,
        ])
        ->condition('proposal_id', $proposal_id)
        ->execute();

      $book_user = User::load($proposal_data->uid);
      $email_to = $book_user?->getEmail() ?? '';
      $params['all_code_submitted_status_changed']['proposal_id'] = $proposal_id;
      $params['all_code_submitted_status_changed']['user_id'] = $proposal_data->uid;
      $params['all_code_submitted_status_changed']['headers'] = [
        'From' => $from,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
        'Content-Transfer-Encoding' => '8Bit',
        'X-Mailer' => 'Drupal',
        'Cc' => $cc,
        'Bcc' => $bcc,
      ];

      if ($email_to !== '') {
        $langcode = $book_user?->getPreferredLangcode() ?? \Drupal::languageManager()->getDefaultLanguage()->getId();
        $result = \Drupal::service('plugin.manager.mail')->mail('textbook_companion', 'all_code_submitted_status_changed', $email_to, $langcode, $params, $from, TRUE);
        if (empty($result['result'])) {
          $this->messenger()->addError($this->t('Error sending email message.'));
        }
      }
      $this->messenger()->addStatus($this->t('User has been notified of that code submission interface is now available .'));
      $form_state->setRedirect('textbook_companion._proposal_all');
      return;
    }

    if ((int) $form_state->getValue('completed') === 1) {
      $connection->update('textbook_companion_proposal')
        ->fields([
          'proposal_status' => 3,
          'completion_date' => time(),
        ])
        ->condition('id', $proposal_id)
        ->execute();

      ProposalHelper::createReadmeFileTextbookCompanion($proposal_id);

      $book_user = User::load($proposal_data->uid);
      $email_to = $book_user?->getEmail() ?? '';
      $param['proposal_completed']['proposal_id'] = $proposal_id;
      $param['proposal_completed']['user_id'] = $proposal_data->uid;
      $param['proposal_completed']['headers'] = [
        'From' => $from,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
        'Content-Transfer-Encoding' => '8Bit',
        'X-Mailer' => 'Drupal',
        'Cc' => $cc,
        'Bcc' => $bcc,
      ];

      if ($email_to !== '') {
        $langcode = $book_user?->getPreferredLangcode() ?? \Drupal::languageManager()->getDefaultLanguage()->getId();
        $result = \Drupal::service('plugin.manager.mail')->mail('textbook_companion', 'proposal_completed', $email_to, $langcode, $param, $from, TRUE);
        if (empty($result['result'])) {
          $this->messenger()->addError($this->t('Error sending email message.'));
        }
      }
      $this->messenger()->addStatus($this->t('Congratulations! Book proposal has been marked as completed. User has been notified of the completion.'));
      $form_state->setRedirect('textbook_companion._proposal_all');
      return;
    }

    $this->messenger()->addError($this->t('Please select any one action.'));
    $form_state->setRedirect('textbook_companion.proposal_status_form', ['proposal_id' => $proposal_id]);
  }

}
