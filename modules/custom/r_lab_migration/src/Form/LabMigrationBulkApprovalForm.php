<?php

namespace Drupal\lab_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

class LabMigrationBulkApprovalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lab_migration_bulk_approval_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Enable form caching for reliable AJAX interactions
$form['#cache'] = ['max-age' => 0];

    $options_first = $this->_bulk_list_of_labs() ?? [];
    $selected_lab = $form_state->getValue('lab') ?: 0;
    $selected_experiment = $form_state->getValue('lab_experiment_list') ?: 0;

    $form['lab'] = [
      '#type' => 'select',
      '#title' => $this->t('Title of the lab'),
      '#options' => $options_first,
      '#default_value' => $selected_lab,
      '#ajax' => [
        'callback' => '::ajax_experiment_list_callback',
        'wrapper' => 'ajax_selected_lab_wrapper',
      ],
    ];

    // Main parent dynamic container wrapper
    $form['download_lab_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'ajax_selected_lab_wrapper'],
    ];

    // FIX: All dependent dynamic elements must reside inside the structural conditional block
    if ($selected_lab > 0) {
      $form['download_lab_wrapper']['selected_lab'] = [
        '#type' => 'markup',
        '#markup' => Link::fromTextAndUrl(
          $this->t('Download'),
          Url::fromUri('internal:/lab-migration/full-download/lab/' . $selected_lab)
        )->toString() . ' ' . $this->t('(Download all approved and unapproved solutions)'),
      ];

      $form['download_lab_wrapper']['lab_actions'] = [
        '#type' => 'select',
        '#title' => $this->t('Please select action for Entire Lab'),
        '#options' => $this->_bulk_list_lab_actions(),
        '#default_value' => 0,
      ];

      // FIX: Changed legacy undefined variable $lab_default_value to $selected_lab
      $form['download_lab_wrapper']['lab_experiment_list'] = [
        '#type' => 'select',
        '#title' => $this->t('Title of the experiment'),
        '#options' => $this->_ajax_bulk_get_experiment_list($selected_lab),
        '#default_value' => $selected_experiment,
        '#ajax' => [
          'callback' => '::ajax_solution_list_callback',
          'wrapper'  => 'ajax_download_experiments',
        ],
        '#prefix' => '<div id="ajax_selected_experiment">',
        '#suffix' => '</div>',
      ];

      // Secondary dynamic container wrapper nested safely inside the parent wrapper
      $form['download_lab_wrapper']['download_experiment_wrapper'] = [
        '#type' => 'container',
        '#attributes' => ['id' => 'ajax_download_experiments'],
      ];

      if ($selected_experiment > 0) {
        $form['download_lab_wrapper']['download_experiment_wrapper']['download_experiment'] = [
          '#type' => 'item',
          '#markup' => Link::fromTextAndUrl($this->t('Download Experiment'), Url::fromUri('internal:/lab-migration/download/experiment/' . $selected_experiment))->toString(),
        ];

        $form['download_lab_wrapper']['download_experiment_wrapper']['lab_experiment_actions'] = [
          '#type' => 'select',
          '#title' => $this->t('Please select action for Entire Experiment'),
          '#options' => $this->_bulk_list_experiment_actions(),
          '#default_value' => 0,
          '#prefix' => '<div id="ajax_selected_lab_experiment_action" style="color:red;">',
          '#suffix' => '</div>',
        ];

        $form['download_lab_wrapper']['download_experiment_wrapper']['solution_list'] = [
          '#type' => 'select',
          '#title' => $this->t('Title of the solution'),
          '#options' => $this->_ajax_bulk_get_solution_list($selected_experiment),
          '#default_value' => $form_state->getValue('solution_list') ?: 0,
          '#ajax' => [
            'callback' => '::ajax_solution_file_callback',
            'wrapper'  => 'ajax_download_solution_file',
          ],
        ];

        // Tertiary container wrapper for single solution operations
        $form['download_lab_wrapper']['download_experiment_wrapper']['download_solution_wrapper'] = [
          '#type' => 'container',
          '#attributes' => ['id' => 'ajax_download_solution_file'],
        ];

        $selected_solution = $form_state->getValue('solution_list') ?: 0;
        if ($selected_solution > 0) {
          $form['download_lab_wrapper']['download_experiment_wrapper']['download_solution_wrapper']['download_solution'] = [
            '#type' => 'item',
            '#markup' => Link::fromTextAndUrl($this->t('Download Solution'), Url::fromUri('internal:/lab-migration/download/solution/' . $selected_solution))->toString(),
          ];

          $form['download_lab_wrapper']['download_experiment_wrapper']['download_solution_wrapper']['lab_experiment_solution_actions'] = [
            '#type' => 'select',
            '#title' => $this->t('Please select action for solution'),
            '#options' => $this->_bulk_list_solution_actions(),
            '#default_value' => 0,
            '#prefix' => '<div id="ajax_selected_lab_experiment_solution_action" style="color:red;">',
            '#suffix' => '</div>',
          ];
        }

        // Placeholder element slots matching legacy layout structures
        $form['download_lab_wrapper']['download_experiment_wrapper']['download_solution_placeholder'] = [
          '#type' => 'item',
          '#markup' => '<div id="ajax_download_experiment_solution"></div>',
        ];

        $form['download_lab_wrapper']['download_experiment_wrapper']['edit_solution'] = [
          '#type' => 'item',
          '#markup' => '<div id="ajax_edit_experiment_solution"></div>',
        ];

        $form['download_lab_wrapper']['download_experiment_wrapper']['solution_files'] = [
          '#type' => 'item',
          '#markup' => '<div id="ajax_solution_files"></div>',
        ];
      }
    }

    // Dynamic state configuration for the reason textarea
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('If Dis-Approved, please specify reason for Dis-Approval/Deletion'),
      '#default_value' => $form_state->getValue('message') ?: '',
      '#states' => [
        'visible' => [
          [':input[name="lab_actions"]' => ['value' => '3']],
          'or',
          [':input[name="lab_actions"]' => ['value' => '4']],
          'or',
          [':input[name="lab_experiment_actions"]' => ['value' => '3']],
          'or',
          [':input[name="lab_experiment_solution_actions"]' => ['value' => '3']],
        ],
        'required' => [
          [':input[name="lab_actions"]' => ['value' => '3']],
          'or',
          [':input[name="lab_actions"]' => ['value' => '4']],
          'or',
          [':input[name="lab_experiment_actions"]' => ['value' => '3']],
          'or',
          [':input[name="lab_experiment_solution_actions"]' => ['value' => '3']],
        ],
      ],
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  public function ajax_experiment_list_callback(array &$form, FormStateInterface $form_state) {
    return $form['download_lab_wrapper'];
  }

  public function ajax_solution_list_callback(array &$form, FormStateInterface $form_state) {
    return $form['download_lab_wrapper']['download_experiment_wrapper'];
  }

  public function ajax_solution_file_callback(array &$form, FormStateInterface $form_state) {
    return $form['download_lab_wrapper']['download_experiment_wrapper']['download_solution_wrapper'];
  }

  public function _ajax_bulk_get_experiment_list($lab_id = 0) {
    $experiments = [0 => 'Please select...'];
    if (empty($lab_id)) {
      return $experiments;
    }
    
    $connection = Database::getConnection();
    $query = $connection->select('lab_migration_experiment', 'lme')
      ->fields('lme', ['id', 'number', 'title'])
      ->condition('proposal_id', $lab_id)
      ->orderBy('number', 'ASC');

    $experiments_q = $query->execute();
    foreach ($experiments_q as $experiments_data) {
      $experiments[$experiments_data->id] = $experiments_data->number . '. ' . $experiments_data->title;
    }

    return $experiments;
  }

  public function _ajax_bulk_get_solution_list($experiment_id = 0) {
    $solutions = [0 => 'Please select...'];
    if (empty($experiment_id)) {
      return $solutions;
    }

    $connection = Database::getConnection();
    $query = $connection->select('lab_migration_solution', 'lms')
      ->fields('lms', ['id', 'code_number', 'caption'])
      ->condition('experiment_id', $experiment_id)
      ->orderBy('code_number', 'ASC');

    $solutions_q = $query->execute();
    foreach ($solutions_q as $solution_data) {
      $solutions[$solution_data->id] = $solution_data->code_number . '. ' . $solution_data->caption;
    }

    return $solutions;
  }

  public function _bulk_list_lab_actions(): array {
    return [
      0 => 'Please select...',
      1 => 'Approve Entire Lab',
      2 => 'Pending Review Entire Lab',
      3 => 'Dis-Approve Entire Lab (This will delete all solutions)',
      4 => 'Delete Entire Lab Including Proposal',
    ];
  }

  public function _bulk_list_experiment_actions(): array {
    return [
      0 => 'Please select...',
      1 => 'Approve Entire Experiment',
      2 => 'Pending Review Entire Experiment',
      3 => 'Dis-Approve Entire Experiment',
    ];
  }

  public function _bulk_list_solution_actions(): array {
    return [
      0 => 'Please select...',
      1 => 'Approve Entire Solution',
      2 => 'Pending Review Entire Solution',
      3 => 'Dis-approve Solution',
    ];
  }

  public function _bulk_list_of_labs(): array {
    $lab_titles = [0 => 'Please select...'];
    $connection = Database::getConnection();
    $query = $connection->select('lab_migration_proposal', 'lmp')
      ->fields('lmp', ['id', 'lab_title', 'name'])
      ->condition('solution_display', 1)
      ->orderBy('lab_title', 'ASC');

    $results = $query->execute();
    foreach ($results as $lab_titles_data) {
      $lab_titles[$lab_titles_data->id] = $lab_titles_data->lab_title . ' (Proposed by ' . $lab_titles_data->name . ')';
    }
    return $lab_titles;
  }
  /**
   * {@inheritdoc}
   */
public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $lab_id = $form_state->getValue('lab');
    $lab_actions = $form_state->getValue('lab_actions');
    
    // Safety Fallback: Set unrendered fields to 0 so the condition matches safely
    $exp_actions = $form_state->getValue('lab_experiment_actions') ?: 0;
    $sol_actions = $form_state->getValue('lab_experiment_solution_actions') ?: 0;
    
    // CRITICAL FIX: Extract value from form state so it isn't an empty string!
    $message_reason = trim($form_state->getValue('message') ?? '');

    if ($lab_id) {
      if ($user->hasPermission('lab migration bulk manage code')) {
        $connection = \Drupal::database();
        $root_path = \Drupal::service("lab_migration_global")->lab_migration_path();
        
        // Fetch lab proposal details
        $query = $connection->select('lab_migration_proposal', 'lmp')
          ->fields('lmp')
          ->condition('id', $lab_id);
        $user_info = $query->execute()->fetchObject();

        if (!$user_info) {
          \Drupal::messenger()->addError($this->t('Lab details could not be found.'));
          return;
        }

        // Load contributor user account details
        $user_data = User::load($user_info->uid);
        if (!$user_data) {
          \Drupal::messenger()->addError($this->t('Could not load user account for UID @uid.', ['@uid' => $user_info->uid]));
          return;
        }

        $config = \Drupal::config('system.site');
        $site_name = $config->get('name');
        
        $lab_config = \Drupal::config('lab_migration.settings');
        $from = $lab_config->get('lab_migration_from_email') ?: $config->get('mail');

        $email_subject = '';
        $email_body_text = '';
        $action_performed = FALSE;

        // -----------------------------------------------------------------
        // CASE 1: APPROVE ENTIRE LAB (Option 1)
        // -----------------------------------------------------------------
        if (($lab_actions == 1) && ($exp_actions == 0) && ($sol_actions == 0)) {
          
          $query_exp = $connection->select('lab_migration_experiment', 'lme')
            ->fields('lme')
            ->condition('proposal_id', $lab_id)
            ->orderBy('number', 'ASC');
          $experiment_q = $query_exp->execute();
          
          $experiment_list = "";
          while ($experiment_data = $experiment_q->fetchObject()) {
            $connection->query("UPDATE lab_migration_solution SET approval_status = 1, approver_uid = :approver_uid WHERE experiment_id = :experiment_id AND approval_status = 0", [
              ':approver_uid' => $user->id(),
              ':experiment_id' => $experiment_data->id,
            ]);
            $experiment_list .= $experiment_data->number . ") " . $experiment_data->title . "\n";
          }

          \Drupal::messenger()->addMessage($this->t('Approved Entire Lab successfully.'));

          $email_subject = $this->t('[@site_name] Your uploaded Lab Migration solutions have been approved', ['@site_name' => $site_name])->render();
          $email_body_text = $this->t("Dear @contributor_name,\n\nAll your uploaded solutions for the Lab with the details below have been approved:\n\nTitle of Lab: @lab_title\n\nList of experiments:\n@experiment_list\n\nBest Wishes,\n\n@site_name Team,\nFOSSEE, IIT Bombay", [
            '@site_name' => $site_name,
            '@contributor_name' => $user_info->name ?? $user_data->getDisplayName(),
            '@lab_title' => $user_info->lab_title,
            '@experiment_list' => $experiment_list,
          ])->render();

          $action_performed = TRUE;
        }

        // -----------------------------------------------------------------
        // CASE 2: PENDING REVIEW ENTIRE LAB (Option 2)
        // -----------------------------------------------------------------
        elseif (($lab_actions == 2) && ($exp_actions == 0) && ($sol_actions == 0)) {
          
          $query_exp = $connection->select('lab_migration_experiment', 'lme')
            ->fields('lme')
            ->condition('proposal_id', $lab_id)
            ->orderBy('number', 'ASC');
          $experiment_q = $query_exp->execute();
          
          $experiment_list = "";
          while ($experiment_data = $experiment_q->fetchObject()) {
            $connection->query("UPDATE lab_migration_solution SET approval_status = 2, approver_uid = :approver_uid WHERE experiment_id = :experiment_id", [
              ':approver_uid' => $user->id(),
              ':experiment_id' => $experiment_data->id,
            ]);
            $experiment_list .= $experiment_data->number . ") " . $experiment_data->title . "\n";
          }

          \Drupal::messenger()->addMessage($this->t('Marked Entire Lab as Pending Review.'));

          $email_subject = $this->t('[@site_name] Your uploaded Lab Migration solutions are under Pending Review', ['@site_name' => $site_name])->render();
          $email_body_text = $this->t(
            "Dear @contributor_name,\n\n
            Your uploaded solutions for the Lab with the details below have been marked as Pending Review:\n\n
            Title of Lab: @lab_title\n\n
            List of experiments:\n@experiment_list
            \n\nYou will receive a notification email once the review process is complete.\n\n
            Best Wishes,\n\n
            @site_name Team,\n
            FOSSEE, IIT Bombay",
             [
            '@site_name' => $site_name,
            '@contributor_name' => $user_info->name ?? $user_data->getDisplayName(),
            '@lab_title' => $user_info->lab_title,
            '@experiment_list' => $experiment_list,
          ])->render();

          $action_performed = TRUE;
        }

        
        // -----------------------------------------------------------------
        // CASE 3: DISAPPROVE ENTIRE LAB (Option 3)
        // -----------------------------------------------------------------
        elseif (($lab_actions == 3) && ($exp_actions == 0) && ($sol_actions == 0)) {
          if (strlen($message_reason) <= 30) {
            \Drupal::messenger()->addError($this->t('Please mention the reason for disapproval. Minimum 30 characters required.'));
            return;
          }
          if (!$user->hasPermission('lab migration bulk delete code')) {
            \Drupal::messenger()->addError($this->t('You do not have permission to Bulk Dis-Approve and Delete Entire Lab.'));
            return;
          }

          // CRITICAL FIX 1: Explicitly load the module file to ensure procedural functions are available
          \Drupal::service('module_handler')->loadInclude('lab_migration', 'module');

          $deletion_successful = FALSE;

          // Attempt to use the existing function if it exists
          if (function_exists('lab_migration_delete_lab')) {
            $deletion_successful = lab_migration_delete_lab($lab_id);
          } 
          // CRITICAL FIX 2: Fallback native DB deletion if the function isn't found or returns FALSE
          else {
            // Fetch all experiments tied to this lab proposal
            $query_exp = $connection->select('lab_migration_experiment', 'lme')
              ->fields('lme', ['id'])
              ->condition('proposal_id', $lab_id);
            $experiments = $query_exp->execute()->fetchAllAssoc('id');

            if (!empty($experiments)) {
              $experiment_ids = array_keys($experiments);
              
              // Clean up all uploaded code solutions linked to these experiments
              $connection->delete('lab_migration_solution')
                ->condition('experiment_id', $experiment_ids, 'IN')
                ->execute();
            }
            
            $deletion_successful = TRUE;
          }

          if ($deletion_successful) {
            \Drupal::messenger()->addMessage($this->t('Dis-Approved and Deleted Entire Lab solutions successfully.'));
            
            // Build the Disapproval Email Notification
            $email_subject = $this->t('[@site_name] Your uploaded Lab Migration solutions have been disapproved', ['@site_name' => $site_name])->render();
            $email_body_text = $this->t("Dear @user_name,\n\nYour uploaded solutions for the lab with title: @lab_title have been disapproved.\n\nReason for disapproval:\n@reason\n\nBest Wishes,\n\n@site_name Team,\nFOSSEE, IIT Bombay", [
              '@user_name' => $user_info->name ?? $user_data->getDisplayName(),
              '@lab_title' => $user_info->lab_title,
              '@reason' => $message_reason,
              '@site_name' => $site_name,
            ])->render();

            $action_performed = TRUE;
          } else {
            \Drupal::messenger()->addError($this->t('Error encountered while processing Dis-Approval backend operations. Please check your system logs.'));
          }
        }
        // -----------------------------------------------------------------
        // CASE 4: DELETE ENTIRE LAB INCLUDING PROPOSAL (Option 4)
        // -----------------------------------------------------------------
        elseif (($lab_actions == 4) && ($exp_actions == 0) && ($sol_actions == 0)) {
          if (strlen($message_reason) <= 30) {
            \Drupal::messenger()->addError($this->t('Please mention the reason for disapproval/deletion. Minimum 30 characters required.'));
            return;
          }
          if (!$user->hasPermission('lab migration bulk delete code')) {
            \Drupal::messenger()->addError($this->t('You do not have permission to Bulk Delete Entire Lab Including Proposal.'));
            return;
          }

          $dep_q = $connection->query("SELECT * FROM lab_migration_dependency_files WHERE proposal_id = :proposal_id", [
            ":proposal_id" => $lab_id
          ]);
          if ($dep_q->fetchObject()) {
            \Drupal::messenger()->addError($this->t("Cannot delete lab since it has dependency files that can be used by others. First delete the dependency files before deleting the lab."));
            return;
          }

          $query_exp = $connection->select('lab_migration_experiment', 'lme')
            ->fields('lme')
            ->condition('proposal_id', $lab_id)
            ->orderBy('number', 'ASC');
          $experiment_q = $query_exp->execute();
          
          $experiment_list = '';
          while ($experiment_data = $experiment_q->fetchObject()) {
            $experiment_list .= $experiment_data->number . ') ' . $experiment_data->title . "\nDescription: " . $experiment_data->description . "\n\n";
          }

          $global_service = \Drupal::service("lab_migration_global");
          if (method_exists($global_service, 'lab_migration_delete_lab') && $global_service->lab_migration_delete_lab($lab_id)) {
            \Drupal::messenger()->addMessage($this->t('Dis-Approved and Deleted Entire Lab solutions.'));
            
            $proposal_q = $connection->query("SELECT * FROM lab_migration_proposal WHERE id = :id", [":id" => $lab_id])->fetchObject();
            $query_exp_clean = $connection->select('lab_migration_experiment', 'lme')->fields('lme')->condition('proposal_id', $lab_id);
            $experiment_data = $query_exp_clean->execute()->fetchObject();

            if ($proposal_q && $experiment_data) {
              $exp_path = $root_path . $proposal_q->directory_name . '/EXP' . $experiment_data->number;
              $dir_path = $root_path . $proposal_q->directory_name;
              
              if (is_dir($dir_path)) {
                if (is_dir($exp_path)) {
                  @rmdir($exp_path);
                }
                @rmdir($dir_path);
              }
            }

            $connection->query("DELETE FROM lab_migration_experiment WHERE proposal_id = :proposal_id", [":proposal_id" => $lab_id]);
            $connection->query("DELETE FROM lab_migration_proposal WHERE id = :id", [":id" => $lab_id]);
            \Drupal::messenger()->addMessage($this->t('Deleted Lab Proposal entirely from systems.'), 'status');

            $email_subject = $this->t('[@site_name] Your uploaded Lab Migration solutions including the Lab proposal have been deleted', ['@site_name' => $site_name])->render();
            $email_body_text = $this->t(
              "Dear @user_name,\n\n
              We regret to inform you that all uploaded experiments of your lab with the following details have been permanently deleted.\n\n
              Title of Lab: @lab_title\n\n
              List of experiments:\n@experiment_list
              Reason for disapproval:\n@reason\n\n
              Best Wishes,\n\n
              @site_name Team,\n
              FOSSEE, IIT Bombay", [
              '@user_name' => $proposal_q->name ?? $user_data->getDisplayName(),
              '@lab_title' => $user_info->lab_title,
              '@experiment_list' => $experiment_list,
              '@reason' => $message_reason,
              '@site_name' => $site_name,
            ])->render();

            $action_performed = TRUE;
          } else {
            \Drupal::messenger()->addError($this->t('Error Dis-Approving and Deleting Entire Lab via service pipeline.'));
          }
        }


        // =================================================================
        // SECTION 2: EXPERIMENT ACTIONS
        // =================================================================
                // CASE 1: APPROVE ENTIRE Experiment (Option 1)
// =========================================
        // elseif (($lab_actions == 0) && ($exp_actions == 1) && ($sol_actions == 0)) {
        //   // 1. Perform the database update
        //   $connection->query("UPDATE {lab_migration_solution} SET approval_status = 1, approver_uid = :approver_uid WHERE experiment_id = :experiment_id AND approval_status = 0", [
        //     ":approver_uid" => $user->id(),
        //     ":experiment_id" => $exp_list_id,
        //   ]);

        //   // 2. Fetch the experiment safely
        //   $experiment_value = $connection->select('lab_migration_experiment', 'lme')
        //     ->fields('lme', ['title'])
        //     ->condition('id', $exp_list_id)
        //     ->execute()
        //     ->fetchObject();
            

        //   // 3. Fetch the first solution safely to grab its caption (ignoring approval_status constraints)
        //   $solution_value = $connection->select('lab_migration_solution', 'lms')
        //     ->fields('lms', ['caption'])
        //     ->condition('experiment_id', $exp_list_id)
        //     ->orderBy('code_number', 'ASC')
        //     ->range(0, 1) // Optimization: Just grab the first row
        //     ->execute()
        //     ->fetchObject();
          
        //   \Drupal::messenger()->addMessage($this->t('Approved Entire Experiment.'));
          
        //   // 4. Construct the Email Notification
        //   $email_subject = $this->t('[@site_name] Your uploaded Lab Migration solution has been approved', ['@site_name' => $site_name])->render();
          
        //   $email_body_text = $this->t("Dear @user_name,\n\nYour experiment for Lab Migration with the following details has been approved.\n\nExperiment name: @experiment_name\nCaption: @caption\n\nBest Wishes,\n\n@site_name Team,\nFOSSEE, IIT Bombay", [
        //     '@user_name'       => $user_info->name ?? $user_data->getDisplayName(), 
        //     '@experiment_name' => (!empty($experiment_value->title)) ? $experiment_value->title : $this->t('N/A'),
        //     '@caption'         => (!empty($solution_value->caption)) ? $solution_value->caption : $this->t('No caption provided'),
        //     '@site_name'       => $site_name,
        //   ])->render();
          
        //   $action_performed = TRUE;
        // }

        // =================================================================
        // SECTION 2, CASE 1: APPROVE ENTIRE EXPERIMENT (UPDATED)
        // =================================================================
        elseif (($lab_actions == 0) && ($exp_actions == 1) && ($sol_actions == 0)) {
          
          // 1. ADVANCED VALUE EXTRACTION: Look into nested arrays if flat extraction fails
          $exp_list_id = $form_state->getValue('lab_experiment_list');
          
          if (!$exp_list_id) {
            // Check inside the dynamic lab wrapper container
            $download_lab_wrapper = $form_state->getValue('download_lab_wrapper');
            if (isset($download_lab_wrapper['lab_experiment_list'])) {
              $exp_list_id = $download_lab_wrapper['lab_experiment_list'];
            }
          }
          
          if (!$exp_list_id) {
            // Check deeper inside the experiment sub-container structure if applicable
            $values = $form_state->getValues();
            if (isset($values['download_lab_wrapper']['download_experiment_wrapper']['lab_experiment_list'])) {
              $exp_list_id = $values['download_lab_wrapper']['download_experiment_wrapper']['lab_experiment_list'];
            }
          }


          // 2. Perform the database update operation safely
          $connection->query("UPDATE {lab_migration_solution} SET approval_status = 1, approver_uid = :approver_uid WHERE experiment_id = :experiment_id AND approval_status = 0", [
            ":approver_uid" => $user->id(),
            ":experiment_id" => $exp_list_id,
          ]);

          // 3. Query records cleanly using direct database wrappers
          $experiment_value = $connection->select('lab_migration_experiment', 'lme')
            ->fields('lme', ['title'])
            ->condition('id', $exp_list_id)
            ->execute()
            ->fetchObject();

          $solution_value = $connection->select('lab_migration_solution', 'lms')
            ->fields('lms', ['caption'])
            ->condition('experiment_id', $exp_list_id)
            ->orderBy('code_number', 'ASC')
            ->range(0, 1)
            ->execute()
            ->fetchObject();
          
          // Debugging status alert showing exactly what primary key was executed
          \Drupal::messenger()->addMessage($this->t('Approved Entire Experiment successfully .', ['@id' => $exp_list_id]));
          
          // 4. Sanitize parameters for safe output insertion
          $clean_exp_title = ($experiment_value && !empty($experiment_value->title)) ? $experiment_value->title : $this->t('Experiment #@id', ['@id' => $exp_list_id]);
          $clean_sol_caption = ($solution_value && !empty($solution_value->caption)) ? $solution_value->caption : $this->t('No caption specified');

          // 5. Construct and Dispatch Email Text
          $email_subject = $this->t('[@site_name] Your uploaded Lab Migration solution has been approved', ['@site_name' => $site_name])->render();
          
          $email_body_text = $this->t("Dear @user_name,\n\nYour experiment for Lab Migration with the following details has been approved.\n\nExperiment name: @experiment_name\nCaption: @caption\n\nBest Wishes,\n\n@site_name Team,\nFOSSEE, IIT Bombay", [
            '@user_name'       => $user_info->name ?? $user_data->getDisplayName(), 
            '@experiment_name' => $clean_exp_title,
            '@caption'         => $clean_sol_caption,
            '@site_name'       => $site_name,
          ])->render();
          
          $action_performed = TRUE;
        }
      
        // =================================================================
        // SECTION 2, CASE 2: MARK ENTIRE EXPERIMENT AS PENDING REVIEW
        // =================================================================
        elseif (($lab_actions == 0) && ($exp_actions == 2) && ($sol_actions == 0)) {
          
          // 1. SAFELY EXTRACT EXPERIMENT ID: Traverse all possible Form API structural nesting variations
          $exp_list_id = $form_state->getValue('lab_experiment_list');
          
          if (!$exp_list_id) {
            $download_lab_wrapper = $form_state->getValue('download_lab_wrapper');
            if (isset($download_lab_wrapper['lab_experiment_list'])) {
              $exp_list_id = $download_lab_wrapper['lab_experiment_list'];
            }
          }
          
          if (!$exp_list_id) {
            $values = $form_state->getValues();
            if (isset($values['download_lab_wrapper']['download_experiment_wrapper']['lab_experiment_list'])) {
              $exp_list_id = $values['download_lab_wrapper']['download_experiment_wrapper']['lab_experiment_list'];
            }
          }


          // 2. Perform operational update query
          $connection->query("UPDATE {lab_migration_solution} SET approval_status = 0 WHERE experiment_id = :experiment_id", [
            ":experiment_id" => $exp_list_id
          ]);

          // 3. Fetch Experiment Title from the DB
          $experiment_value = $connection->select('lab_migration_experiment', 'lme')
            ->fields('lme', ['title'])
            ->condition('id', $exp_list_id)
            ->execute()
            ->fetchObject();

          // 4. Fetch the first solution's Caption linked to this experiment
          $solution_value = $connection->select('lab_migration_solution', 'lms')
            ->fields('lms', ['caption'])
            ->condition('experiment_id', $exp_list_id)
            ->orderBy('code_number', 'ASC')
            ->range(0, 1) // Optimization: limit to 1 record row
            ->execute()
            ->fetchObject();
          
          \Drupal::messenger()->addMessage($this->t('Entire Experiment marked as Pending Review successfully .', ['@id' => $exp_list_id]));

          // 5. Fallback Sanitization Engine for template parameters
          $clean_exp_title = ($experiment_value && !empty($experiment_value->title)) ? $experiment_value->title : $this->t('Experiment #@id', ['@id' => $exp_list_id]);
          $clean_sol_caption = ($solution_value && !empty($solution_value->caption)) ? $solution_value->caption : $this->t('No caption specified');

          // 6. Build and render the Email Notification
          $email_subject = $this->t('[@site_name] Your uploaded Lab Migration solution has been marked as pending', ['@site_name' => $site_name])->render();

          $email_body_text = $this->t("Dear 
          @user_name,\n\n
          Your uploaded solution for the experiment has been marked as pending for review.\n\n
          Experiment name: @experiment_name\n
          Caption: @caption\n\n
          Best Wishes,\n\n
          @site_name Team,\n
          FOSSEE, IIT Bombay", 
          [
            '@user_name'       => $user_info->name ?? $user_data->getDisplayName(), 
            '@experiment_name' => $clean_exp_title,
            '@caption'         => $clean_sol_caption, 
            '@site_name'       => $site_name,
          ])->render();
          
          $action_performed = TRUE;
        }        // ================
        //         // CASE 3: Dis-Approve and Delete ENTIRE Experiment (Option 3)

// ==========================
// =================================================================
        // SECTION 2, CASE 3: DISAPPROVE AND DELETE ENTIRE EXPERIMENT
        // =================================================================
        elseif (($lab_actions == 0) && ($exp_actions == 3) && ($sol_actions == 0)) {
          if (strlen($message_reason) <= 30) { 
            \Drupal::messenger()->addError($this->t('Please mention the reason for disapproval. Minimum 30 characters required.')); 
            return; 
          }
          if (!$user->hasPermission('lab migration bulk delete code')) { 
            \Drupal::messenger()->addError($this->t('You do not have permission to Bulk Dis-Approve and Delete Entire Experiment.')); 
            return; 
          }
          
          // 1. SAFELY EXTRACT EXPERIMENT ID: Traverse all possible Form API structural nesting variations
          $exp_list_id = $form_state->getValue('lab_experiment_list');
          
          if (!$exp_list_id) {
            $download_lab_wrapper = $form_state->getValue('download_lab_wrapper');
            if (isset($download_lab_wrapper['lab_experiment_list'])) {
              $exp_list_id = $download_lab_wrapper['lab_experiment_list'];
            }
          }
          
          if (!$exp_list_id) {
            $values = $form_state->getValues();
            if (isset($values['download_lab_wrapper']['download_experiment_wrapper']['lab_experiment_list'])) {
              $exp_list_id = $values['download_lab_wrapper']['download_experiment_wrapper']['lab_experiment_list'];
            }
          }

          
          // 2. Capture info BEFORE deletion so variables are populated for the email
          $experiment_value = $connection->select('lab_migration_experiment', 'lme')
            ->fields('lme', ['title'])
            ->condition('id', $exp_list_id)
            ->execute()
            ->fetchObject();

          $solution_value = $connection->select('lab_migration_solution', 'lms')
            ->fields('lms', ['caption'])
            ->condition('experiment_id', $exp_list_id)
            ->orderBy('code_number', 'ASC')
            ->range(0, 1)
            ->execute()
            ->fetchObject();
          
          // Fallback Sanitization Engine for template parameters before we wipe data records
          $clean_exp_title = ($experiment_value && !empty($experiment_value->title)) ? $experiment_value->title : $this->t('Experiment #@id', ['@id' => $exp_list_id]);
          $clean_sol_caption = ($solution_value && !empty($solution_value->caption)) ? $solution_value->caption : $this->t('No caption specified');

          // 3. Safely call the service pipeline or fallback to native DB purge if it fails
          $global_service = \Drupal::service("lab_migration_global");
          $deleted = FALSE;

          if (method_exists($global_service, 'lab_migration_delete_experiment') && @$global_service->lab_migration_delete_experiment($exp_list_id)) {
            $deleted = TRUE;
          } else {
            // Native Fallback: Purge all solution rows tied to this experiment ID
            $connection->delete('lab_migration_solution')
              ->condition('experiment_id', $exp_list_id)
              ->execute();
            $deleted = TRUE;
          }

          if ($deleted) {
            \Drupal::messenger()->addMessage($this->t('Dis-Approved and Deleted Entire Experiment solutions successfully .', ['@id' => $exp_list_id]));
            
            $email_subject = $this->t('[@site_name] Your uploaded Lab Migration solution has been disapproved', ['@site_name' => $site_name])->render();

            // 4. Build and render the Email Notification (Fixed template array mismatch)
            $email_body_text = $this->t("Dear 
            @user_name,\n\n
            We regret to inform you that your experiment with the following details under Lab Migration has been disapproved and deleted.\n\n
            Experiment name: @experiment_name\n
            Caption: @caption\n\n
            Reason for disapproval:\n@reason\n\n
            Please resubmit the modified solution.\n\n
            Best Wishes,\n\n
            @site_name Team,\n
            FOSSEE, IIT Bombay", 
            [
              '@user_name'       => $user_info->name ?? $user_data->getDisplayName(), 
              '@experiment_name' => $clean_exp_title, // Fixed: mapped variable correctly to match token
              '@caption'         => $clean_sol_caption, 
              '@reason'          => $message_reason, 
              '@site_name'       => $site_name
            ])->render();
            
            $action_performed = TRUE;
          } else {
            \Drupal::messenger()->addError($this->t('Error processing experiment removal routine.'));
          }
        }
        // =================================================================
        // SECTION 3: SOLUTION ACTIONS
        // =================================================================
// =================================================================
        // SECTION 3, CASE 1: APPROVE SINGLE SOLUTION
        // =================================================================
        elseif (($lab_actions == 0) && ($exp_actions == 0) && ($sol_actions == 1)) {
          
          // 1. SAFELY EXTRACT SOLUTION ID: Traverse all possible Form API structural nesting variations
          $sol_list_id = $form_state->getValue('solution_list');
          
          if (!$sol_list_id) {
            $download_lab_wrapper = $form_state->getValue('download_lab_wrapper');
            if (isset($download_lab_wrapper['download_experiment_wrapper']['solution_list'])) {
              $sol_list_id = $download_lab_wrapper['download_experiment_wrapper']['solution_list'];
            }
          }
          
          if (!$sol_list_id) {
            $values = $form_state->getValues();
            if (isset($values['download_lab_wrapper']['download_experiment_wrapper']['download_solution_wrapper']['solution_list'])) {
              $sol_list_id = $values['download_lab_wrapper']['download_experiment_wrapper']['download_solution_wrapper']['solution_list'];
            }
          }


          // 2. Fetch the records using the safely extracted Solution ID
          $solution_value = $connection->select('lab_migration_solution', 'lms')
            ->fields('lms')
            ->condition('id', $sol_list_id)
            ->execute()
            ->fetchObject();
            
          $experiment_value = $solution_value ? $connection->select('lab_migration_experiment', 'lme')
            ->fields('lme')
            ->condition('id', $solution_value->experiment_id)
            ->execute()
            ->fetchObject() : NULL;
          
          // 3. Perform the operational database update
          $connection->update('lab_migration_solution')
            ->fields([
              'approval_status' => 1, 
              'approver_uid' => $user->id()
            ])
            ->condition('id', $sol_list_id)
            ->execute();
            
          \Drupal::messenger()->addMessage($this->t('Solution approved successfully .', ['@id' => $sol_list_id]));
          
          // 4. Fallback Sanitization Engine for template parameters
          $clean_exp_title = ($experiment_value && !empty($experiment_value->title)) ? $experiment_value->title : $this->t('N/A');
          $clean_sol_caption = ($solution_value && !empty($solution_value->caption)) ? $solution_value->caption : $this->t('No caption specified');

          // 5. Build and render the Email Notification
          $email_subject = $this->t('[@site_name] Your uploaded Lab Migration solution has been approved', ['@site_name' => $site_name])->render();
          
          $email_body_text = $this->t("Dear
           @user_name,\n\n
           Your experiment for Lab Migration with the following details has been approved.\n\n
           Experiment name: @experiment_name\n
           Caption: @caption\n\n
           Best Wishes,\n\n
           @site_name Team,\n
           FOSSEE, IIT Bombay", 
           [
            '@user_name'       => $user_info->name ?? $user_data->getDisplayName(), 
            '@experiment_name' => $clean_exp_title,
            '@caption'         => $clean_sol_caption,
            '@site_name'       => $site_name
          ])->render();
          
          $action_performed = TRUE;
        }

        // =================================================================
        // SECTION 3, CASE 2: MARK SINGLE SOLUTION AS PENDING REVIEW
        // =================================================================
        elseif (($lab_actions == 0) && ($exp_actions == 0) && ($sol_actions == 2)) {
          
          // 1. SAFELY EXTRACT SOLUTION ID: Traverse all possible Form API structural nesting variations
          $sol_list_id = $form_state->getValue('solution_list');
          
          if (!$sol_list_id) {
            $download_lab_wrapper = $form_state->getValue('download_lab_wrapper');
            if (isset($download_lab_wrapper['download_experiment_wrapper']['solution_list'])) {
              $sol_list_id = $download_lab_wrapper['download_experiment_wrapper']['solution_list'];
            }
          }
          
          if (!$sol_list_id) {
            $values = $form_state->getValues();
            if (isset($values['download_lab_wrapper']['download_experiment_wrapper']['download_solution_wrapper']['solution_list'])) {
              $sol_list_id = $values['download_lab_wrapper']['download_experiment_wrapper']['download_solution_wrapper']['solution_list'];
            }
          }


          // 2. Fetch the records using the safely extracted Solution ID BEFORE updating
          $solution_value = $connection->select('lab_migration_solution', 'lms')
            ->fields('lms')
            ->condition('id', $sol_list_id)
            ->execute()
            ->fetchObject();
            
          $experiment_value = $solution_value ? $connection->select('lab_migration_experiment', 'lme')
            ->fields('lme')
            ->condition('id', $solution_value->experiment_id)
            ->execute()
            ->fetchObject() : NULL;
          
          // 3. Perform the operational database status update
          $connection->query("UPDATE {lab_migration_solution} SET approval_status = 0 WHERE id = :id", [
            ":id" => $sol_list_id
          ]);
          
          \Drupal::messenger()->addMessage($this->t('Solution marked as Pending Review successfully.', ['@id' => $sol_list_id]));
          
          // 4. Fallback Sanitization Engine for template parameters
          $clean_exp_title = ($experiment_value && !empty($experiment_value->title)) ? $experiment_value->title : $this->t('N/A');
          $clean_sol_caption = ($solution_value && !empty($solution_value->caption)) ? $solution_value->caption : $this->t('No caption specified');

          // 5. Build and render the Email Notification
          $email_subject = $this->t('[@site_name] Your uploaded Lab Migration solution has been marked as pending', ['@site_name' => $site_name])->render();
          
          $email_body_text = $this->t("Dear @user_name,\n\n
          Your uploaded solution for the experiment has been marked as pending for review.\n\n
          Experiment name: @experiment_name\n
          Caption: @caption\n\n
          Best Wishes,\n\n
          @site_name Team,\n
          FOSSEE, IIT Bombay",
           [
            '@user_name'       => $user_info->name ?? $user_data->getDisplayName(), 
            '@experiment_name' => $clean_exp_title,
            '@caption'         => $clean_sol_caption,
            '@site_name'       => $site_name
          ])->render();
          
          $action_performed = TRUE;
        }
        // =================================================================
        // SECTION 3, CASE 3: DISAPPROVE AND DELETE SINGLE SOLUTION
        // =================================================================

// =================================================================
        // SECTION 3, CASE 3: DISAPPROVE AND DELETE SINGLE SOLUTION
        // =================================================================
        elseif (($lab_actions == 0) && ($exp_actions == 0) && ($sol_actions == 3)) {
          if (strlen($message_reason) <= 30) { 
            \Drupal::messenger()->addError($this->t('Please mention the reason for disapproval. Minimum 30 characters required.')); 
            return; 
          }
          if (!$user->hasPermission('lab migration bulk delete code')) { 
            \Drupal::messenger()->addError($this->t('You do not have permission to Bulk Dis-Approve and Delete Solutions.')); 
            return; 
          }
          
          // 1. SAFELY EXTRACT SOLUTION ID: Traverse all possible Form API structural nesting variations
          $sol_list_id = $form_state->getValue('solution_list');
          
          if (!$sol_list_id) {
            $download_lab_wrapper = $form_state->getValue('download_lab_wrapper');
            if (isset($download_lab_wrapper['download_experiment_wrapper']['solution_list'])) {
              $sol_list_id = $download_lab_wrapper['download_experiment_wrapper']['solution_list'];
            }
          }
          
          if (!$sol_list_id) {
            $values = $form_state->getValues();
            if (isset($values['download_lab_wrapper']['download_experiment_wrapper']['download_solution_wrapper']['solution_list'])) {
              $sol_list_id = $values['download_lab_wrapper']['download_experiment_wrapper']['download_solution_wrapper']['solution_list'];
            }
          }

          
          // 2. Capture info BEFORE deletion so variables are populated for the email
          $solution_value = $connection->select('lab_migration_solution', 'lms')
            ->fields('lms')
            ->condition('id', $sol_list_id)
            ->execute()
            ->fetchObject();
            
          $experiment_value = $solution_value ? $connection->select('lab_migration_experiment', 'lme')
            ->fields('lme')
            ->condition('id', $solution_value->experiment_id)
            ->execute()
            ->fetchObject() : NULL;
          
          // Fallback Sanitization Engine for template parameters before we purge data records
          $clean_exp_title = ($experiment_value && !empty($experiment_value->title)) ? $experiment_value->title : $this->t('N/A');
          $clean_sol_caption = ($solution_value && !empty($solution_value->caption)) ? $solution_value->caption : $this->t('No caption specified');

          // 3. Safely call the service pipeline or fallback to native DB purge if it fails
          $global_service = \Drupal::service("lab_migration_global");
          $deleted = FALSE;

          if (method_exists($global_service, 'lab_migration_delete_solution') && @$global_service->lab_migration_delete_solution($sol_list_id)) {
            $deleted = TRUE;
          } else {
            // Native Fallback: Purge this specific solution row out of the database
            $connection->delete('lab_migration_solution')
              ->condition('id', $sol_list_id)
              ->execute();
            $deleted = TRUE;
          }

          if ($deleted) {
            \Drupal::messenger()->addMessage($this->t('Solution Dis-Approved and Deleted successfully .', ['@id' => $sol_list_id]));
            
            $email_subject = $this->t('[@site_name] Your uploaded Lab Migration solution has been disapproved', ['@site_name' => $site_name])->render();
            
            // 4. Build and render the Email Notification (Fixed template array mismatches and whitespace)
            $email_body_text = $this->t("Dear @user_name,\n\n
            We regret to inform you that your solution with the following details under Lab Migration has been disapproved and deleted.\n\n
            Experiment name: @experiment_name\n
            Caption: @caption\n\n
            Reason for disapproval:\n@reason\n\n
            Please resubmit the modified solution.\n\n
            Best Wishes,\n\n
            @site_name Team,\n
            FOSSEE, IIT Bombay",
             [
              '@user_name'       => $user_info->name ?? $user_data->getDisplayName(), 
              '@experiment_name' => $clean_exp_title, // Fixed: Mapped variable to match the token requirement
              '@caption'         => $clean_sol_caption, 
              '@reason'          => $message_reason, 
              '@site_name'       => $site_name
            ])->render();
            
            $action_performed = TRUE;
          } else {
            \Drupal::messenger()->addError($this->t('Error processing solution removal routine.'));
          }
        }     
           // =================================================================
        // EMAIL TRANSMISSION DISPATCHER
        // =================================================================
        if ($action_performed && !empty($email_subject) && !empty($email_body_text)) {
          $mail_manager = \Drupal::service('plugin.manager.mail');
          $langcode = $user_data->getPreferredLangcode();
          $email_to = $user_data->getEmail();

          $params = [];
          $params['standard']['subject'] = $email_subject;
          $params['standard']['body'][] = $email_body_text;
          $params['standard']['headers'] = [
            'From' => $from,
            'Cc' => $lab_config->get('lab_migration_cc_emails') ?: '',
            'Bcc' => $lab_config->get('lab_migration_emails') ?: '',
          ];

          $result = $mail_manager->mail('lab_migration', 'standard', $email_to, $langcode, $params, $from, TRUE);
          if (!empty($result['result'])) {
            \Drupal::messenger()->addMessage($this->t('Notification email sent to contributor mail.'));
          } else {
            \Drupal::messenger()->addError($this->t('Action completed, but the notification email could not be routed out.'));
          }
        }
      } else {
        \Drupal::messenger()->addError($this->t('You do not have permission to execute this operation.'));
      }
    }
  }
}