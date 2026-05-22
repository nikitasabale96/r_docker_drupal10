<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\ProposalApprovalForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Cache\Cache;
use Drupal\textbook_companion\Helper\ProposalHelper;
use Drupal\user\Entity\User;

class ProposalApprovalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'proposal_approval_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, int $proposal_id = NULL) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    if (!$proposal_id) {
      $this->messenger()->addError($this->t('Invalid proposal selected. Please try again.'));
      return [];
    }
    /*$result = db_query("SELECT * FROM {textbook_companion_proposal} WHERE proposal_status = 0 and id = %d", $proposal_id);*/
    $connection = \Drupal::database();
    $query = $connection->select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('proposal_status', 0);
    $query->condition('id', $proposal_id);
    $result = $query->execute();
    if ($result) {
      if ($row = $result->fetchObject()) {
        /* everything ok */
      }
      else {
        $this->messenger()->addError($this->t('Invalid proposal selected. Please try again.'));
        return [];
      }
    }
    else {
      $this->messenger()->addError($this->t('Invalid proposal selected. Please try again.'));
      return [];
    }
    $form['full_name'] = [
      '#type' => 'item',
      '#markup' => Link::fromTextAndUrl($row->full_name, Url::fromRoute('entity.user.canonical', ['user' => $row->uid]))->toString(),
      '#title' => t('Contributor Name'),
    ];
    $account = User::load($row->uid);
    $form['email'] = [
      '#type' => 'item',
      '#markup' => $account?->getEmail() ?? '',
      '#title' => t('Email'),
    ];
    $form['mobile'] = [
      '#type' => 'item',
      '#plain_text' => (string) $row->mobile,
      '#title' => t('Mobile'),
    ];
    $form['how_project'] = [
      '#type' => 'item',
      '#plain_text' => (string) $row->how_project,
      '#title' => t('How did you come to know about this project'),
    ];
    $form['course'] = [
      '#type' => 'item',
      '#plain_text' => (string) $row->course,
      '#title' => t('Course'),
    ];
    $form['branch'] = [
      '#type' => 'item',
      '#plain_text' => (string) $row->branch,
      '#title' => t('Department/Branch'),
    ];
    $form['university'] = [
      '#type' => 'item',
      '#plain_text' => (string) $row->university,
      '#title' => t('University/Institute'),
    ];
    $form['city'] = [
      '#type' => 'item',
      '#plain_text' => (string) $row->city,
      '#title' => t('City/Village'),
    ];
    $form['pincode'] = [
      '#type' => 'item',
      '#plain_text' => (string) $row->pincode,
      '#title' => t('Pincode'),
    ];
    $form['state'] = [
      '#type' => 'item',
      '#plain_text' => (string) $row->state,
      '#title' => t('State'),
    ];
    $form['faculty'] = [
      '#type' => 'hidden',
      '#markup' => $row->faculty,
      '#title' => t('College Teacher/Professor'),
    ];
    $form['reviewer'] = [
      '#type' => 'hidden',
      '#markup' => $row->reviewer,
      '#title' => t('Reviewer'),
    ];
    if ($row->proposed_completion_date != 0) {
      $proposed_completion_date = date('d-m-Y', $row->proposed_completion_date);
    }
    else {
      $proposed_completion_date = "-----";
    }

    $form['proposed_completion_date'] = [
      '#type' => 'item',
      '#markup' => $proposed_completion_date,
      '#title' => t('Proposed Date of Completion'),
    ];
    if ($row->completion_date != 0) {
      $actual_completion_date = date('d-m-Y', $row->completion_date);
    }
    else {
      $actual_completion_date = "-----";
    }
    $form['completion_date'] = [
      '#type' => 'item',
      '#markup' => $actual_completion_date,
      '#title' => t('Actual Date of Completion'),
    ];
    $form['operating_system'] = [
      '#type' => 'item',
      '#plain_text' => (string) $row->operating_system,
      '#title' => t('Operating System'),
    ];
    $form['version'] = [
      '#type' => 'item',
      '#plain_text' => (string) $row->r_version,
      '#title' => t('R Version'),
    ];
    $form['reference'] = [
      '#type' => 'item',
      '#plain_text' => (string) $row->reference,
      '#title' => t('References'),
    ];
    $book_download_link = trim((string) $row->book_download_link);
    $book_download_url = ProposalHelper::buildExternalUrl($book_download_link);
    $form['book_download_link'] = [
      '#type' => 'item',
      '#title' => t('Download link for the proposed textbook'),
    ];
    if ($book_download_link !== '') {
      if ($book_download_url) {
        $form['book_download_link']['#markup'] = Link::fromTextAndUrl($book_download_link, $book_download_url)->toString();
      }
      else {
        $form['book_download_link']['#plain_text'] = $book_download_link;
      }
    }
    $form['reason'] = [
      '#type' => 'item',
      '#plain_text' => (string) $row->reason,
      '#title' => t('Reasons'),
    ];
    /* get book preference */
    $preference_rows = [];
    /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE proposal_id = %d ORDER BY pref_number ASC", $proposal_id);*/
    $query = $connection->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('proposal_id', $proposal_id);
    $query->orderBy('pref_number', 'ASC');
    $preference_q = $query->execute();
    while ($preference_data = $preference_q->fetchObject()) {
      $preference_rows[$preference_data->id] = $preference_data->book . ' (Written by ' . $preference_data->author . ') - Edition: ' . $preference_data->edition;
    }
    if ($row->proposal_type == 1) {
      $form['book_preference'] = [
        '#type' => 'radios',
        '#options' => $preference_rows,
        '#title' => t('Book Preferences'),
        '#required' => TRUE,
      ];
    }
    else {
      $form['book_preference'] = [
        '#type' => 'radios',
        '#title' => t('Book Preferences'),
        '#options' => $preference_rows,
        '#required' => TRUE,
      ];
    }
    if ($row->samplefilepath != "Not available") {
      $form['samplecode'] = [
        '#type' => 'markup',
        '#markup' => Link::fromTextAndUrl($this->t('Download Sample Code'), Url::fromRoute('textbook_companion.download_sample_code', ['proposal_id' => $proposal_id]))->toString() . "<br><br>",
      ];
    }
    /*$form['category'] = array(
        '#type' => 'item',
        '#markup' => _user_selected_category($proposal_id),
        '#title' => t('User selected category')
    );*/
    $form['disapprove'] = [
      '#type' => 'checkbox',
      '#title' => t('Disapprove all the above book preferences'),
    ];
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => t('Reason for disapproval'),
      '#states' => [
        'visible' => [
          ':input[name="disapprove"]' => [
            'checked' => TRUE
            ]
          ],
        'required' => [':input[name="disapprove"]' => ['checked' => TRUE]],
      ],
    ];
    $form['proposal_id'] = [
      '#type' => 'hidden',
      '#value' => $proposal_id,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    $form['cancel'] = [
      '#type' => 'markup',
      '#markup' => Link::fromTextAndUrl($this->t('Cancel'), Url::fromRoute('textbook_companion._proposal_all'))->toString(),
    ];
    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    if ($form_state->getValue(['disapprove'])) {
      if (strlen(trim($form_state->getValue(['message']))) <= 30) {
        $form_state->setErrorByName('message', t('Please mention the reason for disapproval in minimum 30 characters.'));
      }
    }
    return;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    $proposal_id = (int) $form_state->getValue(['proposal_id']);
    /*$result = db_query("SELECT * FROM {textbook_companion_proposal} WHERE proposal_status = 0 and id = %d", $proposal_id);*/
    $connection = \Drupal::database();
    $query = $connection->select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('proposal_status', 0);
    $query->condition('id', $proposal_id);
    $result = $query->execute();
    if ($result) {
      if ($row = $result->fetchObject()) {
        /* everything ok */
      }
      else {
        $this->messenger()->addError($this->t('Invalid proposal selected. Please try again.'));
        $form_state->setRedirect('textbook_companion._proposal_all');
        return;
      }
    }
    else {
      $this->messenger()->addError($this->t('Invalid proposal selected. Please try again.'));
      $form_state->setRedirect('textbook_companion._proposal_all');
      return;
    }
    /* disapprove */
    if ($form_state->getValue(['disapprove'])) {
      /*db_query("UPDATE {textbook_companion_proposal} SET approver_uid = %d, approval_date = %d, proposal_status = 2, message = '%s' WHERE id = %d", $user->uid, time(), $form_state['values']['message'], $proposal_id);*/
      $connection->update('textbook_companion_proposal')
        ->fields([
          'approver_uid' => $user->id(),
          'approval_date' => \Drupal::time()->getRequestTime(),
          'proposal_status' => 2,
          'completion_date' => 0,
          'message' => $form_state->getValue(['message']),
        ])
        ->condition('id', $proposal_id)
        ->execute();
      $connection->update('textbook_companion_preference')
        ->fields(['approval_status' => 2])
        ->condition('proposal_id', $proposal_id)
        ->execute();
      /* sending email */
      $book_user = User::load($row->uid);
      $email_to = $book_user?->getEmail() ?? '';
      $config = \Drupal::config('textbook_companion.settings');
      $from = (string) $config->get('textbook_companion_from_email');
      $bcc = (string) $config->get('textbook_companion_emails');
      $cc = (string) $config->get('textbook_companion_cc_emails');
      $param['proposal_disapproved']['proposal_id'] = $proposal_id;
      $param['proposal_disapproved']['user_id'] = $row->uid;
      $param['proposal_disapproved']['headers'] = [
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
        $result = \Drupal::service('plugin.manager.mail')->mail('textbook_companion', 'proposal_disapproved', $email_to, $langcode, $param, $from, TRUE);
        if (empty($result['result'])) {
          $this->messenger()->addError($this->t('Error sending email message.'));
        }
      }
      $this->messenger()->addError($this->t('Book proposal dis-approved. User has been notified of the dis-approval.'));
      Cache::invalidateTags(['textbook_companion:proposal_list', "textbook_companion:proposal:{$proposal_id}", 'textbook_companion:preference_list']);
      $form_state->setRedirect('textbook_companion._proposal_all');
      return;
    }
    /* get book preference and set the status */
    $preference_id = $form_state->getValue(['book_preference']);
    /*db_query("UPDATE {textbook_companion_proposal} SET approver_uid = %d, approval_date = %d, proposal_status = 1 WHERE id = %d", $user->uid, time(), $proposal_id);*/
    $connection->update('textbook_companion_proposal')
      ->fields([
        'approver_uid' => $user->id(),
        'approval_date' => \Drupal::time()->getRequestTime(),
        'proposal_status' => 1,
      ])
      ->condition('id', $proposal_id)
      ->execute();
    $connection->update('textbook_companion_preference')
      ->fields(['approval_status' => 1])
      ->condition('id', $preference_id)
      ->execute();
    /* unlock aicte books except the one which was approved out of 3 nos */
    /* $query = "
    UPDATE textbook_companion_aicte
    SET status = 0, uid = 0, proposal_id = 0, preference_id = 0
    WHERE proposal_id = {$proposal_id} AND preference_id != {$preference_id}
    ";
    db_query($query);*/
    /*$query = db_update('textbook_companion_aicte');
    $query->fields(array(
    'status' => 0,
    'uid' => 0,
    'proposal_id' => 0,
    'preference_id' => 0,
    ));
    $query->condition('proposal_id', '$proposal_id');
    $query->condition('preference_id', '$preference_id', '<>');
    $num_updated = $query->execute();*/
    /* sending email */
    $book_user = User::load($row->uid);
    $email_to = $book_user?->getEmail() ?? '';
    $config = \Drupal::config('textbook_companion.settings');
    $from = (string) $config->get('textbook_companion_from_email');
    $bcc = (string) $config->get('textbook_companion_emails');
    $cc = (string) $config->get('textbook_companion_cc_emails');
    $param['proposal_approved']['proposal_id'] = $proposal_id;
    $param['proposal_approved']['user_id'] = $row->uid;
    $param['proposal_approved']['headers'] = [
      'From' => $from,
      'MIME-Version' => '1.0',
      'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
      'Content-Transfer-Encoding' => '8Bit',
      'X-Mailer' => 'Drupal',
      'Cc' => $cc,
      'Bcc' => $bcc,
    ];
    //var_dump($param);die;
    if ($email_to !== '') {
      $langcode = $book_user?->getPreferredLangcode() ?? \Drupal::languageManager()->getDefaultLanguage()->getId();
      $result = \Drupal::service('plugin.manager.mail')->mail('textbook_companion', 'proposal_approved', $email_to, $langcode, $param, $from, TRUE);
      if (empty($result['result'])) {
        $this->messenger()->addError($this->t('Error sending email message.'));
      }
    }
    $this->messenger()->addStatus($this->t('Book proposal approved. User has been notified of the approval.'));
    Cache::invalidateTags(['textbook_companion:proposal_list', "textbook_companion:proposal:{$proposal_id}", 'textbook_companion:preference_list']);
    $form_state->setRedirect('textbook_companion._proposal_all');
    return;
  }

}
?>
