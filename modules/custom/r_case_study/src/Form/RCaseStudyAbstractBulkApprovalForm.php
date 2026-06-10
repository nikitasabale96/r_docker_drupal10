<?php

namespace Drupal\r_case_study\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Database\Database;

class RCaseStudyAbstractBulkApprovalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'r_case_study_abstract_bulk_approval_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options_first = $this->_bulk_list_of_case_study_project();
    $selected = $form_state->getValue('case_study_project') ? $form_state->getValue('case_study_project') : key($options_first);
    
    $form = [];
    $form['case_study_project'] = [
      '#type' => 'select',
      '#title' => t('Title of the Case Study Project'),
      '#options' => $options_first,
      '#default_value' => $selected,
      '#ajax' => [
        'callback' => '::ajax_bulk_case_study_abstract_details_callback',
        'wrapper' => 'ajax_selected_case_study'
      ],
    ];

    $form['update_case_study_details'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'ajax_selected_case_study'],
      '#states' => [
        'visible' => [
          ':input[name="case_study_project"]' => ['!value' => 0]
        ],
      ],
    ];

    $form['update_case_study_details']['cs_details'] = [
      '#type' => 'markup',
      '#markup' => $this->_case_study_details($form_state->getValue('case_study_project')),
    ];

    $form['update_case_study_details']['case_study_actions'] = [
      '#type' => 'select',
      '#title' => t('Please select action for Case Study Project'),
      '#options' => $this->_bulk_list_case_study_actions(),
      '#default_value' => 0,
    ];

    $form['update_case_study_details']['message'] = [
      '#type' => 'textarea',
      '#title' => t('Specify the reason for resubmission/ disapproval.'),
      '#states' => [
        'visible' => [
          [':input[name="case_study_actions"]' => ['value' => 3]],
          [':input[name="case_study_actions"]' => ['value' => 2]]
        ],
        'required' => [
          [':input[name="case_study_actions"]' => ['value' => 3]],
          [':input[name="case_study_actions"]' => ['value' => 2]]
        ]
      ],
    ];

    $form['update_case_study_details']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];

    return $form;
  }

  public function ajax_bulk_case_study_abstract_details_callback(array &$form, FormStateInterface $form_state) {
    return $form['update_case_study_details'];
  }

  public function _bulk_list_of_case_study_project() {
    $project_titles = [0 => 'Please select...'];
    $connection = Database::getConnection();
    
    $results = $connection->select('case_study_proposal', 'p')
      ->fields('p')
      ->condition('is_submitted', 1)
      ->condition('approval_status', 1)
      ->orderBy('project_title', 'ASC')
      ->execute();

    foreach ($results as $record) {
      $project_titles[$record->id] = $record->project_title . ' (Proposed by ' . $record->contributor_name . ')';
    }

    return $project_titles;
  }

  public function _bulk_list_case_study_actions() {
    return [
      0 => 'Please select...',
      1 => 'Approve Entire Case Study Project',
      2 => 'Resubmit Project files',
      3 => 'Disapprove Entire Case Study Project (This will delete Case Study Project)',
    ];
  }

  public function _case_study_details($case_study_proposal_id) {
    if (empty($case_study_proposal_id)) {
      return '';
    }

    $return_html = '';
    $connection = Database::getConnection();

    $abstracts_pro = $connection->select('case_study_proposal', 'p')
      ->fields('p')
      ->condition('id', $case_study_proposal_id)
      ->execute()
      ->fetchObject();

    if (!$abstracts_pro) {
      return '';
    }

    $abstracts_pdf = $connection->select('case_study_submitted_abstracts_file', 'f')
      ->fields('f')
      ->condition('proposal_id', $case_study_proposal_id)
      ->condition('filetype', 'R')
      ->execute()
      ->fetchObject();

    $abstract_filename = ($abstracts_pdf && !empty($abstracts_pdf->filename) && $abstracts_pdf->filename !== 'NULL') ? $abstracts_pdf->filename : 'File not uploaded';

    $abstracts_query_process = $connection->select('case_study_submitted_abstracts_file', 'f')
      ->fields('f')
      ->condition('proposal_id', $case_study_proposal_id)
      ->condition('filetype', 'C')
      ->execute()
      ->fetchObject();

    $abstracts_query_process_filename = ($abstracts_query_process && !empty($abstracts_query_process->filename) && $abstracts_query_process->filename !== 'NULL') ? $abstracts_query_process->filename : 'File not uploaded';

    $download_url = Url::fromUri('internal:/case-study-project/full-download/project/' . $case_study_proposal_id);
    $download_case_study = Link::fromTextAndUrl('Download Case Study Project', $download_url)->toString();

    $return_html .= '<strong>Contributor Name:</strong><br />' . htmlspecialchars($abstracts_pro->name_title . ' ' . $abstracts_pro->contributor_name) . '<br /><br />';
    $return_html .= '<strong>Title of the Case Study Project:</strong><br />' . htmlspecialchars($abstracts_pro->project_title) . '<br /><br />';
    $return_html .= '<strong>Uploaded an abstract (brief outline) of the project:</strong><br />' . htmlspecialchars($abstract_filename) . '<br /><br />';
    $return_html .= '<strong>Uploaded Case study files:</strong><br />' . htmlspecialchars($abstracts_query_process_filename) . '<br /><br />';
    $return_html .= $download_case_study;

    return $return_html;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_user = \Drupal::currentUser();
    $connection = \Drupal::database();
    $case_study_project_id = $form_state->getValue('case_study_project');
    $action = $form_state->getValue('case_study_actions');

    if ($form_state->getTriggeringElement()['#value'] == 'Submit' && !empty($case_study_project_id)) {
      
      $user_info = $connection->select('case_study_proposal', 'csp')
        ->fields('csp')
        ->condition('id', $case_study_project_id)
        ->execute()
        ->fetchObject();

      if (!$user_info) {
        \Drupal::messenger()->addError(t('Invalid Case Study Project selected.'));
        return;
      }

      $user_data = \Drupal\user\Entity\User::load($user_info->uid);
      $email_to = $user_data ? $user_data->getEmail() : '';
      
      $config = \Drupal::config('r_case_study.settings');
      $from = $config->get('case_study_from_email');
      $bcc = $config->get('case_study_emails');
      $cc = $config->get('case_study_cc_emails');
      $langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
      $mail_manager = \Drupal::service('plugin.manager.mail');

      // ===================== ACTION 1: APPROVE =====================
      if ($action == 1) {
        if (!$current_user->hasPermission('Case Study bulk manage abstract')) {
          \Drupal::messenger()->addError(t('You do not have permission to manage abstracts.'));
          return;
        }

        $abstracts_q = $connection->select('case_study_submitted_abstracts', 'csa')
          ->fields('csa')
          ->condition('proposal_id', $case_study_project_id)
          ->execute();

        while ($abstract_data = $abstracts_q->fetchObject()) {
          $connection->update('case_study_submitted_abstracts')
            ->fields([
              'abstract_approval_status' => 1,
              'is_submitted' => 1,
              'approver_uid' => $current_user->id(),
            ])
            ->condition('id', $abstract_data->id)
            ->execute();

          $connection->update('case_study_submitted_abstracts_file')
            ->fields([
              'file_approval_status' => 1,
              'approvar_uid' => $current_user->id(),
            ])
            ->condition('submitted_abstract_id', $abstract_data->id)
            ->execute();
        }

        \Drupal::messenger()->addStatus(t('Approved Case Study.'));

        $params['abstract_approval'] = [
          'proposal_id' => $case_study_project_id,
          'user_id' => $user_info->uid,
          'headers' => [
            'From' => $from,
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
            'Content-Transfer-Encoding' => '8Bit',
            'X-Mailer' => 'Drupal',
            'Cc' => $cc,
            'Bcc' => $bcc,
          ],
        ];

        // Correctly calls 'case_study' module context
        $result = $mail_manager->mail('case_study', 'abstract_approval', $email_to, $langcode, $params, $from, TRUE);
        if (!$result['result']) {
          \Drupal::messenger()->addError(t('Error sending approval email.'));
        } else {
          \Drupal::messenger()->addStatus(t('Approval email sent successfully.'));
        }
      }

      // ===================== ACTION 2: RESUBMIT =====================
      elseif ($action == 2) {
        if (!$current_user->hasPermission('Case Study bulk manage abstract')) {
          \Drupal::messenger()->addError(t('You do not have permission to request resubmissions.'));
          return;
        }

        $abstracts_q = $connection->select('case_study_submitted_abstracts')
          ->fields('case_study_submitted_abstracts')
          ->condition('proposal_id', $case_study_project_id)
          ->execute();

        while ($abstract_data = $abstracts_q->fetchObject()) {
          $connection->update('case_study_submitted_abstracts')
            ->fields([
              'abstract_approval_status' => 0,
              'is_submitted' => 0,
              'approver_uid' => $current_user->id(),
            ])
            ->condition('id', $abstract_data->id)
            ->execute();

          $connection->update('case_study_proposal')
            ->fields([
              'is_submitted' => 0,
              'approver_uid' => $current_user->id(),
            ])
            ->condition('id', $abstract_data->proposal_id)
            ->execute();

          $connection->update('case_study_submitted_abstracts_file')
            ->fields([
              'file_approval_status' => 0,
              'approvar_uid' => $current_user->id(),
            ])
            ->condition('submitted_abstract_id', $abstract_data->id)
            ->execute();
        }

        $params['solution_resubmission'] = [
          'proposal_id' => $case_study_project_id,
          'disapproval_message' => $form_state->getValue('message'),
          'user_id' => $user_info->uid,
          'headers' => [
            'From' => $from,
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
            'Content-Transfer-Encoding' => '8Bit',
            'X-Mailer' => 'Drupal',
            'Cc' => $cc,
            'Bcc' => $bcc,
          ],
        ];

        // FIXED: Changed module parameter from 'r_case_study' to 'case_study' to match your hook_mail structure
        $result = $mail_manager->mail('case_study', 'solution_resubmission', $email_to, $langcode, $params, $from, TRUE);
        if (!$result['result']) {
          \Drupal::messenger()->addError(t('Error sending resubmission email.'));
        } else {
          \Drupal::messenger()->addStatus(t('Resubmission mail sent successfully.'));
        }
        \Drupal::messenger()->addStatus(t('Files marked for resubmission.'));
      }

      // ===================== ACTION 3: DISAPPROVE & DELETE =====================
      elseif ($action == 3) {
        if (!$current_user->hasPermission('Case Study bulk delete abstract')) {
          \Drupal::messenger()->addError(t('You do not have permission to Bulk Disapprove and Delete Entire Lab records.'));
          return;
        }

        $proposal_id = $user_info->id;

        $params['solution_disapproved'] = [
          'proposal_id' => $proposal_id,
          'disapproval_message' => $form_state->getValue('message'),
          'user_id' => $user_info->uid,
          'fallback_name' => $user_info->contributor_name ?? '',
          'fallback_title' => $user_info->project_title ?? '',
          'headers' => [
            'From' => $from,
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
            'Content-Transfer-Encoding' => '8Bit',
            'X-Mailer' => 'Drupal',
            'Cc' => $cc,
            'Bcc' => $bcc,
          ],
        ];

        // FIXED: Changed module parameter from 'r_case_study' to 'case_study' to match your hook_mail structure
        $result = $mail_manager->mail('case_study', 'solution_disapproved', $email_to, $langcode, $params, $from, TRUE);
        
        if (!$result['result']) {
          \Drupal::messenger()->addError(t('Error sending disapproval email.'));
        } else {
          \Drupal::messenger()->addStatus(t('Disapproval email sent successfully.'));
        }

        $global_service = \Drupal::service("r_case_study_global");
        if ($global_service->r_case_study_abstract_delete_project($case_study_project_id)) {
          $connection->delete('case_study_submitted_abstracts_file')->condition('proposal_id', $proposal_id)->execute();
          $connection->delete('case_study_submitted_abstracts')->condition('proposal_id', $proposal_id)->execute();
          $connection->delete('case_study_proposal')->condition('id', $proposal_id)->execute();
          
          \Drupal::messenger()->addStatus(t('Dis-Approved and Deleted Entire Case Study Project.'));
        } else {
          \Drupal::messenger()->addError(t('Error cleaning up Case Study Project database tracking records.'));
        }
      }      
    }
  }
}