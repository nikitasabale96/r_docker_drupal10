<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\BulkApprovalForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

class BulkApprovalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bulk_approval_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options_first = $this->bulkListOfBooks();
    $options_two = $this->bulkGetChapterList();
    $selected = $form_state->getValue('book') ?: array_key_first($options_first);
    $select_two = $form_state->getValue('chapter') ?: array_key_first($options_two);

    $form['book'] = [
      '#type' => 'select',
      '#title' => $this->t('Title of the Book'),
      '#options' => $this->bulkListOfBooks(),
      '#default_value' => $selected,
      '#tree' => TRUE,
      '#ajax' => [
        'callback' => '::ajaxBulkChapterListCallback',
      ],
      '#validated' => TRUE,
    ];
    $form['download_book'] = [
      '#type' => 'item',
      '#markup' => '<div id="ajax_selected_book"></div>',
    ];
    $form['download_pdf'] = [
      '#type' => 'item',
      '#markup' => '<div id="ajax_selected_book_pdf"></div>',
    ];
    $form['regenrate_book'] = [
      '#type' => 'item',
      '#markup' => '<div id="ajax_selected_book_regenerate_pdf"></div>',
    ];
    $form['notes_book'] = [
      '#type' => 'item',
      '#markup' => '<div id="ajax_selected_book_notes"></div>',
    ];
    $form['book_actions'] = [
      '#type' => 'select',
      '#title' => $this->t('Please select action for selected book'),
      '#options' => $this->bulkListBookActions(),
      '#prefix' => '<div id="ajax_selected_book_action" style="color:red;">',
      '#suffix' => '</div>',
      '#states' => [
        'invisible' => [
          ':input[name="book"]' => [
            'value' => 0,
          ],
        ],
      ],
      '#validated' => TRUE,
    ];
    $form['chapter'] = [
      '#type' => 'select',
      '#title' => $this->t('Title of the Chapter'),
      '#options' => $this->bulkGetChapterList($selected),
      '#prefix' => '<div id="ajax_select_chapter_list">',
      '#suffix' => '</div>',
      '#validated' => TRUE,
      '#tree' => TRUE,
      '#ajax' => [
        'callback' => '::ajaxBulkExampleListCallback',
      ],
      '#states' => [
        'invisible' => [
          ':input[name="book"]' => ['value' => 0],
        ],
      ],
    ];
    $form['download_chapter'] = [
      '#type' => 'item',
      '#markup' => '<div id="ajax_download_chapter"></div>',
    ];
    $form['chapter_actions'] = [
      '#type' => 'select',
      '#title' => $this->t('Please select action for selected chapter'),
      '#options' => $this->bulkListChapterActions(),
      '#prefix' => '<div id="ajax_selected_chapter_action" style="color:red;">',
      '#suffix' => '</div>',
      '#states' => [
        'invisible' => [
          ':input[name="book"]' => [
            'value' => 0,
          ],
        ],
      ],
      '#ajax' => ['callback' => '::ajaxBulkChapterActionsCallback'],
    ];
    $form['example'] = [
      '#type' => 'select',
      '#title' => $this->t('Example No. (Caption)'),
      '#options' => $this->bulkGetExamples(),
      '#validated' => TRUE,
      '#prefix' => '<div id="ajax_selected_example">',
      '#suffix' => '</div>',
      '#states' => [
        'invisible' => [
          ':input[name="book"]' => [
            'value' => 0,
          ],
        ],
      ],
      '#ajax' => ['callback' => '::ajaxBulkExampleFilesCallback'],
    ];
    $form['download_example'] = [
      '#type' => 'item',
      '#markup' => '<div id="ajax_download_selected_example"></div>',
    ];
    $form['edit_example'] = [
      '#type' => 'item',
      '#markup' => '<div id="ajax_edit_selected_example"></div>',
    ];
    $form['example_files'] = [
      '#type' => 'item',
      '#markup' => '',
      '#prefix' => '<div id="ajax_example_files_list">',
      '#suffix' => '</div>',
    ];
    $form['example_actions'] = [
      '#type' => 'select',
      '#title' => $this->t('Please select action for selected example'),
      '#options' => $this->bulkListExampleActions(),
      '#prefix' => '<div id="ajax_selected_example_action" style="color:red;">',
      '#suffix' => '</div>',
      '#states' => [
        'invisible' => [
          ':input[name="book"]' => [
            'value' => 0,
          ],
        ],
      ],
    ];
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('If Dis-Approved please specify reason for Dis-Approval'),
      '#states' => [
        'visible' => [
          [
            [
              ':input[name="book_actions"]' => [
                'value' => 3,
              ],
            ],
            'or',
            [':input[name="chapter_actions"]' => ['value' => 3]],
            'or',
            [
              ':input[name="example_actions"]' => [
                'value' => 3,
              ],
            ],
            'or',
            [':input[name="book_actions"]' => ['value' => 4]],
          ],
        ],
        'required' => [
          [
            [':input[name="book_actions"]' => ['value' => 3]],
            'or',
            [
              ':input[name="chapter_actions"]' => [
                'value' => 3,
              ],
            ],
            'or',
            [':input[name="example_actions"]' => ['value' => 3]],
            'or',
            [
              ':input[name="book_actions"]' => [
                'value' => 4,
              ],
            ],
          ],
        ],
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#states' => [
        'invisible' => [
          ':input[name="book"]' => [
            'value' => 0,
          ],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_user = $this->currentUser();
    if (!$current_user->hasPermission('bulk manage code')) {
      $this->messenger()->addError($this->t('You do not have permission to bulk manage code.'));
      return;
    }

    $book_id = (int) $form_state->getValue('book');
    if ($book_id > 0) {
      del_book_pdf($book_id);
    }

    $connection = \Drupal::database();
    $config = \Drupal::config('textbook_companion.settings');
    $site_name = (string) \Drupal::config('system.site')->get('name');

    $pref_data = $connection->select('textbook_companion_preference', 'tp')
      ->fields('tp')
      ->condition('id', $book_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if (!$pref_data) {
      $this->messenger()->addError($this->t('Invalid book selection.'));
      return;
    }

    $proposal_data = $connection->select('textbook_companion_proposal', 'tpr')
      ->fields('tpr')
      ->condition('id', $pref_data->proposal_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    $user_entity = $proposal_data ? User::load($proposal_data->uid) : NULL;
    $user_name = $user_entity?->getDisplayName() ?? '';
    $email_to = $user_entity?->getEmail() ?? '';

    $email_subject = '';
    $email_body = [];

    if ((int) $form_state->getValue('book_actions') === 1 && (int) $form_state->getValue('chapter_actions') === 0 && (int) $form_state->getValue('example_actions') === 0) {
      $preference_data = $connection->select('textbook_companion_preference', 'tp')
        ->fields('tp')
        ->condition('id', $book_id)
        ->condition('approval_status', 1)
        ->range(0, 1)
        ->execute()
        ->fetchObject();

      $chapter_q = $connection->select('textbook_companion_chapter', 'tc')
        ->fields('tc')
        ->condition('preference_id', $book_id)
        ->execute();

      while ($chapter_data = $chapter_q->fetchObject()) {
        $connection->update('textbook_companion_example')
          ->fields([
            'approval_status' => 1,
            'approver_uid' => $current_user->id(),
          ])
          ->condition('chapter_id', $chapter_data->id)
          ->condition('approval_status', 0)
          ->execute();
      }

      $connection->update('textbook_companion_preference')
        ->fields(['submited_all_examples_code' => 2])
        ->condition('id', $book_id)
        ->execute();

      $this->messenger()->addStatus($this->t('Approved Entire Book.'));

      $email_subject = $this->t('[@site_name][Textbook Companion] Your uploaded Textbook Companion examples have been approved', [
        '@site_name' => $site_name,
      ]);
      $email_body = [
        $this->t("\n\nDear @user_name,\n\nYour all the uploaded examples for the book have been approved.\n\nTitle of the book : @book\nAuthor name : @author\nISBN No. : @isbn\nPublisher and Place : @publisher\nEdition : @edition\nYear of publication : @year\n\nBest Wishes,\n\n@site_name Team,\nFOSSEE,IIT Bombay", [
          '@site_name' => $site_name,
          '@user_name' => $user_name,
          '@book' => $preference_data->book ?? '',
          '@author' => $preference_data->author ?? '',
          '@isbn' => $preference_data->isbn ?? '',
          '@publisher' => $preference_data->publisher ?? '',
          '@edition' => $preference_data->edition ?? '',
          '@year' => $preference_data->year ?? '',
        ]),
      ];
    }
    elseif ((int) $form_state->getValue('book_actions') === 2 && (int) $form_state->getValue('chapter_actions') === 0 && (int) $form_state->getValue('example_actions') === 0) {
      $preference_data = $connection->select('textbook_companion_preference', 'tp')
        ->fields('tp')
        ->condition('id', $book_id)
        ->condition('approval_status', 1)
        ->range(0, 1)
        ->execute()
        ->fetchObject();

      $chapter_q = $connection->select('textbook_companion_chapter', 'tc')
        ->fields('tc')
        ->condition('preference_id', $book_id)
        ->execute();

      while ($chapter_data = $chapter_q->fetchObject()) {
        $connection->update('textbook_companion_example')
          ->fields(['approval_status' => 0])
          ->condition('chapter_id', $chapter_data->id)
          ->execute();
      }

      $this->messenger()->addStatus($this->t('Pending Review Entire Book.'));

      $email_subject = $this->t('[@site_name] Your uploaded Textbook Companion examples have been marked as pending', [
        '@site_name' => $site_name,
      ]);
      $email_body = [
        $this->t("\n\nDear @user_name,\n\nYour all the uploaded examples for the book have been marked as pending to be reviewed.\nYou will be able to see the examples after they have been approved by one of our reviewers.\n\nTitle of the book : @book\nAuthor name : @author\nISBN No. : @isbn\nPublisher and Place : @publisher\nEdition : @edition\nYear of publication : @year\n\nBest Wishes,\n@site_name Team,\nFOSSEE,IIT Bombay", [
          '@site_name' => $site_name,
          '@user_name' => $user_name,
          '@book' => $preference_data->book ?? '',
          '@author' => $preference_data->author ?? '',
          '@isbn' => $preference_data->isbn ?? '',
          '@publisher' => $preference_data->publisher ?? '',
          '@edition' => $preference_data->edition ?? '',
          '@year' => $preference_data->year ?? '',
        ]),
      ];
    }
    elseif ((int) $form_state->getValue('book_actions') === 3 && (int) $form_state->getValue('chapter_actions') === 0 && (int) $form_state->getValue('example_actions') === 0) {
      if (strlen(trim((string) $form_state->getValue('message'))) <= 30) {
        $form_state->setErrorByName('message', $this->t(''));
        $this->messenger()->addError($this->t('Please mention the reason for disapproval. Minimum 30 character required'));
        return;
      }

      $preference_data = $connection->select('textbook_companion_preference', 'tp')
        ->fields('tp')
        ->condition('id', $book_id)
        ->condition('approval_status', 1)
        ->range(0, 1)
        ->execute()
        ->fetchObject();

      if (!$current_user->hasPermission('bulk delete code')) {
        $this->messenger()->addError($this->t('You do not have permission to Bulk Dis-Approved and Deleted Entire Book.'));
        return;
      }

      if (delete_book($book_id)) {
        $this->messenger()->addStatus($this->t('Dis-Approved and Deleted Entire Book.'));
      }
      else {
        $this->messenger()->addError($this->t('Error Dis-Approving and Deleting Entire Book.'));
      }

      $email_subject = $this->t('[@site_name] Your uploaded Textbook Companion examples have been marked as dis-approved', [
        '@site_name' => $site_name,
      ]);
      $email_body = [
        $this->t("\n\nDear @user_name,\n\nYour all the uploaded examples for the whole book have been marked as dis-approved.\n\nTitle of the book : @book\nAuthor name : @author\nISBN No. : @isbn\nPublisher and Place : @publisher\nEdition : @edition\nYear of publication : @year\n\nReason for dis-approval:@reason\n\nBest Wishes,\n\n@site_name Team,\nFOSSEE,IIT Bombay", [
          '@site_name' => $site_name,
          '@user_name' => $user_name,
          '@book' => $preference_data->book ?? '',
          '@author' => $preference_data->author ?? '',
          '@isbn' => $preference_data->isbn ?? '',
          '@publisher' => $preference_data->publisher ?? '',
          '@edition' => $preference_data->edition ?? '',
          '@year' => $preference_data->year ?? '',
          '@reason' => (string) $form_state->getValue('message'),
        ]),
      ];
    }
    elseif ((int) $form_state->getValue('book_actions') === 4 && (int) $form_state->getValue('chapter_actions') === 0 && (int) $form_state->getValue('example_actions') === 0) {
      if (strlen(trim((string) $form_state->getValue('message'))) <= 30) {
        $form_state->setErrorByName('message', $this->t(''));
        $this->messenger()->addError($this->t('Please mention the reason for disapproval/deletion. Minimum 30 character required'));
        return;
      }

      $pref_data = $connection->select('textbook_companion_preference', 'tp')
        ->fields('tp')
        ->condition('id', $book_id)
        ->condition('approval_status', 1)
        ->range(0, 1)
        ->execute()
        ->fetchObject();

      if (!$current_user->hasPermission('bulk delete code')) {
        $this->messenger()->addError($this->t('You do not have permission to Bulk Delete Entire Book Including Proposal.'));
        return;
      }

      if (delete_book($book_id)) {
        $this->messenger()->addStatus($this->t('Dis-Approved and Deleted Entire Book examples.'));
        $root_path = textbook_companion_path();
        $dir_path = $root_path . $book_id;
        if (is_dir($dir_path)) {
          $res = rmdir($dir_path);
          if (!$res) {
            $this->messenger()->addError($this->t('Cannot delete Book directory : @path. Please contact administrator.', ['@path' => $dir_path]));
            return;
          }
        }
        else {
          $this->messenger()->addStatus($this->t('Book directory not present : @path. Skipping deleting book directory.', ['@path' => $dir_path]));
        }

        $preference_data = $connection->select('textbook_companion_preference', 'tp')
          ->fields('tp')
          ->condition('id', $book_id)
          ->range(0, 1)
          ->execute()
          ->fetchObject();

        $proposal_id = $preference_data->proposal_id ?? 0;
        if ($proposal_id) {
          $connection->delete('textbook_companion_preference')
            ->condition('proposal_id', $proposal_id)
            ->execute();
          $connection->delete('textbook_companion_proposal')
            ->condition('id', $proposal_id)
            ->execute();
          $this->messenger()->addStatus($this->t('Deleted Book Proposal.'));
        }

        $email_subject = $this->t('[@site_name] Your uploaded Textbook Companion examples including the book proposal have been deleted', [
          '@site_name' => $site_name,
        ]);
        $email_body = [
          $this->t("\n\nDear @user_name,\n\nWe regret to inform you that all the uploaded examples including the book with following details have been deleted permanently.\n\nTitle of the book : @book\nAuthor name : @author\nISBN No. : @isbn\nPublisher and Place : @publisher\nEdition : @edition\nYear of publication : @year\n\nReason for deletion:@reason\n\nBest Wishes,\n\n@site_name Team,\nFOSSEE,IIT Bombay", [
            '@site_name' => $site_name,
            '@user_name' => $user_name,
            '@book' => $pref_data->book ?? '',
            '@author' => $pref_data->author ?? '',
            '@isbn' => $pref_data->isbn ?? '',
            '@publisher' => $pref_data->publisher ?? '',
            '@edition' => $pref_data->edition ?? '',
            '@year' => $pref_data->year ?? '',
            '@reason' => (string) $form_state->getValue('message'),
          ]),
        ];
      }
      else {
        $this->messenger()->addError($this->t('Error Dis-Approving and Deleting Entire Book.'));
      }
    }
    elseif ((int) $form_state->getValue('book_actions') === 0 && (int) $form_state->getValue('chapter_actions') === 1 && (int) $form_state->getValue('example_actions') === 0) {
      $pref_data = $connection->select('textbook_companion_preference', 'tp')
        ->fields('tp')
        ->condition('id', $book_id)
        ->condition('approval_status', 1)
        ->range(0, 1)
        ->execute()
        ->fetchObject();

      $chap_data = $connection->select('textbook_companion_chapter', 'tc')
        ->fields('tc')
        ->condition('preference_id', $book_id)
        ->condition('id', (int) $form_state->getValue('chapter'))
        ->range(0, 1)
        ->execute()
        ->fetchObject();

      $connection->update('textbook_companion_example')
        ->fields([
          'approval_status' => 1,
          'approver_uid' => $current_user->id(),
        ])
        ->condition('chapter_id', (int) $form_state->getValue('chapter'))
        ->condition('approval_status', 0)
        ->execute();

      $this->messenger()->addStatus($this->t('Approved Entire Chapter.'));

      $email_subject = $this->t('[@site_name] Your uploaded Textbook Companion examples have been approved', [
        '@site_name' => $site_name,
      ]);
      $email_body = [
        $this->t("\n\nDear @user_name,\n\nYour all the uploaded examples for the chapter have been approved.\n\nTitle of the book : @book\nTitle of the chapter : @chapter\n\nBest Wishes,\n\n@site_name Team,\nFOSSEE,IIT Bombay", [
          '@site_name' => $site_name,
          '@user_name' => $user_name,
          '@book' => $pref_data->book ?? '',
          '@chapter' => $chap_data->name ?? '',
        ]),
      ];
    }
    elseif ((int) $form_state->getValue('book_actions') === 0 && (int) $form_state->getValue('chapter_actions') === 2 && (int) $form_state->getValue('example_actions') === 0) {
      $pref_data = $connection->select('textbook_companion_preference', 'tp')
        ->fields('tp')
        ->condition('id', $book_id)
        ->condition('approval_status', 1)
        ->range(0, 1)
        ->execute()
        ->fetchObject();

      $chap_data = $connection->select('textbook_companion_chapter', 'tc')
        ->fields('tc')
        ->condition('preference_id', $book_id)
        ->condition('id', (int) $form_state->getValue('chapter'))
        ->range(0, 1)
        ->execute()
        ->fetchObject();

      $connection->update('textbook_companion_example')
        ->fields(['approval_status' => 0])
        ->condition('chapter_id', (int) $form_state->getValue('chapter'))
        ->execute();

      $this->messenger()->addStatus($this->t('Entire Chapter marked as Pending Review.'));

      $email_subject = $this->t('[@site_name] Your uploaded Textbook Companion examples have been marked as pending', [
        '@site_name' => $site_name,
      ]);
      $email_body = [
        $this->t("\n\nDear @user_name,\n\nYour all the uploaded examples for the chapter have been marked as pending to be reviewed.\n\nTitle of the book : @book\nTitle of the chapter : @chapter\n\nBest Wishes,\n\n@site_name Team,\nFOSSEE,IIT Bombay", [
          '@site_name' => $site_name,
          '@user_name' => $user_name,
          '@book' => $pref_data->book ?? '',
          '@chapter' => $chap_data->name ?? '',
        ]),
      ];
    }
    elseif ((int) $form_state->getValue('book_actions') === 0 && (int) $form_state->getValue('chapter_actions') === 3 && (int) $form_state->getValue('example_actions') === 0) {
      $pref_data = $connection->select('textbook_companion_preference', 'tp')
        ->fields('tp')
        ->condition('id', $book_id)
        ->condition('approval_status', 1)
        ->range(0, 1)
        ->execute()
        ->fetchObject();

      $chap_data = $connection->select('textbook_companion_chapter', 'tc')
        ->fields('tc')
        ->condition('preference_id', $book_id)
        ->condition('id', (int) $form_state->getValue('chapter'))
        ->range(0, 1)
        ->execute()
        ->fetchObject();

      if (strlen(trim((string) $form_state->getValue('message'))) <= 30) {
        $form_state->setErrorByName('message', $this->t(''));
        $this->messenger()->addError($this->t('Please mention the reason for disapproval. Minimum 30 character required'));
        return;
      }

      if (!$current_user->hasPermission('bulk delete code')) {
        $this->messenger()->addError($this->t('You do not have permission to Bulk Dis-Approved and Deleted Entire Chapter.'));
        return;
      }

      if (delete_chapter((int) $form_state->getValue('chapter'))) {
        $this->messenger()->addStatus($this->t('Dis-Approved and Deleted Entire Chapter.'));
      }
      else {
        $this->messenger()->addError($this->t('Error Dis-Approving and Deleting Entire Chapter.'));
      }

      $email_subject = $this->t('[@site_name] Your uploaded Textbook Companion example have been marked as dis-approved', [
        '@site_name' => $site_name,
      ]);
      $email_body = [
        $this->t("\n\nDear @user_name,\n\nYour uploaded example for the entire chapter have been marked as dis-approved.\n\nTitle of the book : @book\nTitle of the chapter : @chapter\n\nReason for dis-approval:@reason\n\nBest Wishes,\n\n@site_name Team,\nFOSSEE,IIT Bombay", [
          '@site_name' => $site_name,
          '@user_name' => $user_name,
          '@book' => $pref_data->book ?? '',
          '@chapter' => $chap_data->name ?? '',
          '@reason' => (string) $form_state->getValue('message'),
        ]),
      ];
    }
    elseif ((int) $form_state->getValue('book_actions') === 0 && (int) $form_state->getValue('chapter_actions') === 0 && (int) $form_state->getValue('example_actions') === 1) {
      $pref_data = $connection->select('textbook_companion_preference', 'tp')
        ->fields('tp')
        ->condition('id', $book_id)
        ->condition('approval_status', 1)
        ->range(0, 1)
        ->execute()
        ->fetchObject();

      $chap_data = $connection->select('textbook_companion_chapter', 'tc')
        ->fields('tc')
        ->condition('preference_id', $book_id)
        ->condition('id', (int) $form_state->getValue('chapter'))
        ->range(0, 1)
        ->execute()
        ->fetchObject();

      $examp_data = $connection->select('textbook_companion_example', 'te')
        ->fields('te')
        ->condition('id', (int) $form_state->getValue('example'))
        ->range(0, 1)
        ->execute()
        ->fetchObject();

      $connection->update('textbook_companion_example')
        ->fields([
          'approval_status' => 1,
          'approver_uid' => $current_user->id(),
        ])
        ->condition('id', (int) $form_state->getValue('example'))
        ->execute();

      $this->messenger()->addStatus($this->t('Example approved.'));

      $email_subject = $this->t('[@site_name] Your uploaded Textbook Companion example have been approved', [
        '@site_name' => $site_name,
      ]);
      $email_body = [
        $this->t("\n\nDear @user_name,\n\nYour example for R Textbook Companion with the following details is approved.\n\nTitle of the book : @book\nTitle of the chapter : @chapter\nExample number : @number\nCaption : @caption\n\nBest Wishes,\n\n@site_name Team,\nFOSSEE,IIT Bombay", [
          '@site_name' => $site_name,
          '@user_name' => $user_name,
          '@book' => $pref_data->book ?? '',
          '@chapter' => $chap_data->name ?? '',
          '@number' => $examp_data->number ?? '',
          '@caption' => $examp_data->caption ?? '',
        ]),
      ];
    }
    elseif ((int) $form_state->getValue('book_actions') === 0 && (int) $form_state->getValue('chapter_actions') === 0 && (int) $form_state->getValue('example_actions') === 2) {
      $pref_data = $connection->select('textbook_companion_preference', 'tp')
        ->fields('tp')
        ->condition('id', $book_id)
        ->condition('approval_status', 1)
        ->range(0, 1)
        ->execute()
        ->fetchObject();

      $chap_data = $connection->select('textbook_companion_chapter', 'tc')
        ->fields('tc')
        ->condition('preference_id', $book_id)
        ->condition('id', (int) $form_state->getValue('chapter'))
        ->range(0, 1)
        ->execute()
        ->fetchObject();

      $examp_data = $connection->select('textbook_companion_example', 'te')
        ->fields('te')
        ->condition('id', (int) $form_state->getValue('example'))
        ->range(0, 1)
        ->execute()
        ->fetchObject();

      $connection->update('textbook_companion_example')
        ->fields(['approval_status' => 0])
        ->condition('id', (int) $form_state->getValue('example'))
        ->execute();

      $this->messenger()->addStatus($this->t('Example marked as Pending Review.'));

      $email_subject = $this->t('[@site_name] Your uploaded Textbook Companion example has been marked as pending', [
        '@site_name' => $site_name,
      ]);
      $email_body = [
        $this->t("\n\nDear @user_name,\n\nYour uploaded example for R Textbook Companion with the following details has been marked as pending to be reviewed.\n\nTitle of the book : @book\nTitle of the chapter : @chapter\nExample number : @number\nCaption : @caption\n\nBest Wishes,\n\n@site_name Team,\nFOSSEE,IIT Bombay", [
          '@site_name' => $site_name,
          '@user_name' => $user_name,
          '@book' => $pref_data->book ?? '',
          '@chapter' => $chap_data->name ?? '',
          '@number' => $examp_data->number ?? '',
          '@caption' => $examp_data->caption ?? '',
        ]),
      ];
    }
    elseif ((int) $form_state->getValue('book_actions') === 0 && (int) $form_state->getValue('chapter_actions') === 0 && (int) $form_state->getValue('example_actions') === 3) {
      if (strlen(trim((string) $form_state->getValue('message'))) <= 30) {
        $form_state->setErrorByName('message', $this->t(''));
        $this->messenger()->addError($this->t('Please mention the reason for disapproval. Minimum 30 character required'));
        return;
      }

      $pref_data = $connection->select('textbook_companion_preference', 'tp')
        ->fields('tp')
        ->condition('id', $book_id)
        ->condition('approval_status', 1)
        ->range(0, 1)
        ->execute()
        ->fetchObject();

      $chap_data = $connection->select('textbook_companion_chapter', 'tc')
        ->fields('tc')
        ->condition('preference_id', $book_id)
        ->condition('id', (int) $form_state->getValue('chapter'))
        ->range(0, 1)
        ->execute()
        ->fetchObject();

      $examp_data = $connection->select('textbook_companion_example', 'te')
        ->fields('te')
        ->condition('id', (int) $form_state->getValue('example'))
        ->range(0, 1)
        ->execute()
        ->fetchObject();

      if (delete_example((int) $form_state->getValue('example'))) {
        $this->messenger()->addStatus($this->t('Example Dis-Approved and Deleted.'));
      }
      else {
        $this->messenger()->addError($this->t('Error Dis-Approving and Deleting Example.'));
      }

      $email_subject = $this->t('[@site_name] Your uploaded Textbook Companion example has been marked as dis-approved', [
        '@site_name' => $site_name,
      ]);
      $email_body = [
        $this->t("\n\nDear @user_name,\n\nYour example for R Textbook Companion has been marked as dis-approved and deleted.\n\nTitle of the book : @book\nTitle of the chapter : @chapter\nExample number : @number\nCaption : @caption\n\nReason for dis-approval:@reason\n\nBest Wishes,\n\n@site_name Team,\nFOSSEE,IIT Bombay", [
          '@site_name' => $site_name,
          '@user_name' => $user_name,
          '@book' => $pref_data->book ?? '',
          '@chapter' => $chap_data->name ?? '',
          '@number' => $examp_data->number ?? '',
          '@caption' => $examp_data->caption ?? '',
          '@reason' => (string) $form_state->getValue('message'),
        ]),
      ];
    }
    else {
      $this->messenger()->addError($this->t('Please select only one action at a time'));
      return;
    }

    if ($email_subject && $email_to !== '') {
      $from = (string) $config->get('textbook_companion_from_email');
      $bcc = (string) $config->get('textbook_companion_emails');
      $cc = (string) $config->get('textbook_companion_cc_emails');
      $params['standard']['subject'] = $email_subject;
      $params['standard']['body'] = $email_body;
      $params['standard']['headers'] = [
        'From' => $from,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
        'Content-Transfer-Encoding' => '8Bit',
        'X-Mailer' => 'Drupal',
        'Cc' => $cc,
        'Bcc' => $bcc,
      ];

      $langcode = $user_entity?->getPreferredLangcode() ?? \Drupal::languageManager()->getDefaultLanguage()->getId();
      $result = \Drupal::service('plugin.manager.mail')->mail('textbook_companion', 'standard', $email_to, $langcode, $params, $from, TRUE);
      if (empty($result['result'])) {
        $this->messenger()->addError($this->t('Error sending email message.'));
      }
    }
  }

  public function ajaxBulkChapterListCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $book_default_value = (int) $form_state->getValue('book');

    if ($book_default_value > 0) {
      $response->addCommand(new HtmlCommand('#ajax_selected_book', $this->buildLink($this->t('Download'), 'textbook_companion.download_full_book_internal', ['book_id' => $book_default_value]) . ' ' . $this->t('(Download all the approved and unapproved examples of the entire book)')));
      $response->addCommand(new HtmlCommand('#ajax_selected_book_pdf', $this->buildLink($this->t('Download PDF'), 'textbook_companion.generate_book_pdf', ['book_id' => $book_default_value, 'include_unapproved' => 1]) . ' ' . $this->t('(Download PDF of all the approved and unapproved examples of the entire book)')));
      $response->addCommand(new HtmlCommand('#ajax_selected_book_regenerate_pdf', $this->buildLink($this->t('Regenerate PDF'), 'textbook_companion.delete_book', ['book_id' => $book_default_value]) . ' ' . $this->t('(Manually Regenerate PDF of the entire book)')));
      $response->addCommand(new HtmlCommand('#ajax_selected_book_notes', $this->buildLink($this->t('Notes for Reviewers'), 'textbook_companion.book_notes_form', ['preference_id' => $book_default_value])));
      $form['book_actions']['#options'] = $this->bulkListBookActions();
      $response->addCommand(new ReplaceCommand('#ajax_selected_book_action', $form['book_actions']));
      $form['chapter']['#options'] = $this->bulkGetChapterList($book_default_value);
      $response->addCommand(new ReplaceCommand('#ajax_select_chapter_list', $form['chapter']));
      $response->addCommand(new HtmlCommand('#ajax_download_chapter', ''));
      $form['chapter_actions']['#options'] = $this->bulkListChapterActions();
      $response->addCommand(new ReplaceCommand('#ajax_selected_chapter_action', $form['chapter_actions']));
      $response->addCommand(new HtmlCommand('#ajax_selected_chapter_action', ''));
      $response->addCommand(new HtmlCommand('#ajax_selected_example', ''));
      $form['example_actions']['#options'] = $this->bulkListExampleActions();
      $response->addCommand(new ReplaceCommand('#ajax_selected_example_action', $form['example_actions']));
      $response->addCommand(new HtmlCommand('#ajax_selected_example_action', ''));
      $response->addCommand(new HtmlCommand('#ajax_download_selected_example', ''));
      $response->addCommand(new HtmlCommand('#ajax_edit_selected_example', ''));
      $form['example_files']['#title'] = '';
      $form['example_files']['#markup'] = '';
      $response->addCommand(new ReplaceCommand('#ajax_example_files_list', $form['example_files']));
    }
    else {
      $response->addCommand(new HtmlCommand('#ajax_selected_book', ''));
      $response->addCommand(new HtmlCommand('#ajax_selected_book_pdf', ''));
      $response->addCommand(new HtmlCommand('#ajax_selected_book_regenerate_pdf', ''));
      $response->addCommand(new HtmlCommand('#ajax_selected_book_notes', ''));
      $form['chapter']['#options'] = $this->bulkGetChapterList();
      $response->addCommand(new ReplaceCommand('#ajax_select_chapter_list', $form['chapter']));
      $response->addCommand(new HtmlCommand('#ajax_select_chapter_list', ''));
      $form['book_actions']['#options'] = $this->bulkListBookActions();
      $response->addCommand(new ReplaceCommand('#ajax_selected_book_action', $form['book_actions']));
      $response->addCommand(new HtmlCommand('#ajax_selected_book_action', ''));
      $form['chapter_actions']['#options'] = $this->bulkListChapterActions();
      $response->addCommand(new ReplaceCommand('#ajax_selected_chapter_action', $form['chapter_actions']));
      $response->addCommand(new HtmlCommand('#ajax_selected_chapter_action', ''));
      $response->addCommand(new HtmlCommand('#ajax_download_chapter', ''));
      $response->addCommand(new HtmlCommand('#ajax_selected_example', ''));
      $form['example_actions']['#options'] = $this->bulkListExampleActions();
      $response->addCommand(new ReplaceCommand('#ajax_selected_example_action', $form['example_actions']));
      $response->addCommand(new HtmlCommand('#ajax_selected_example_action', ''));
      $response->addCommand(new HtmlCommand('#ajax_download_selected_example', ''));
      $response->addCommand(new HtmlCommand('#ajax_edit_selected_example', ''));
      $form['example_files']['#title'] = '';
      $form['example_files']['#markup'] = '';
      $response->addCommand(new ReplaceCommand('#ajax_example_files_list', $form['example_files']));
    }

    return $response;
  }

  public function ajaxBulkExampleListCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $chapter_default_value = (int) $form_state->getValue('chapter');

    if ($chapter_default_value > 0) {
      $response->addCommand(new HtmlCommand('#ajax_download_chapter', $this->buildLink($this->t('Download'), 'textbook_companion.download_full_chapter_internal', ['chapter_id' => $chapter_default_value]) . ' ' . $this->t('(Download all the approved and unapproved examples of the entire chapter)')));
      $form['chapter_actions']['#options'] = $this->bulkListChapterActions();
      $response->addCommand(new ReplaceCommand('#ajax_selected_chapter_action', $form['chapter_actions']));
      $form['example']['#options'] = $this->bulkGetExamples($chapter_default_value);
      $response->addCommand(new ReplaceCommand('#ajax_selected_example', $form['example']));
      $response->addCommand(new HtmlCommand('#ajax_download_selected_example', ''));
      $response->addCommand(new HtmlCommand('#ajax_edit_selected_example', ''));
      $form['example_actions']['#options'] = $this->bulkListExampleActions();
      $response->addCommand(new ReplaceCommand('#ajax_selected_example_action', $form['example_actions']));
      $response->addCommand(new HtmlCommand('#ajax_selected_example_action', ''));
      $form['example_files']['#title'] = '';
      $form['example_files']['#markup'] = '';
      $response->addCommand(new ReplaceCommand('#ajax_example_files_list', $form['example_files']));
    }
    else {
      $response->addCommand(new HtmlCommand('#ajax_download_chapter', ''));
      $form['chapter_actions']['#options'] = $this->bulkListChapterActions();
      $response->addCommand(new ReplaceCommand('#ajax_selected_chapter_action', $form['chapter_actions']));
      $response->addCommand(new HtmlCommand('#ajax_selected_chapter_action', ''));
      $form['example']['#options'] = $this->bulkGetExamples();
      $response->addCommand(new ReplaceCommand('#ajax_selected_example', $form['example']));
      $response->addCommand(new HtmlCommand('#ajax_selected_example', ''));
      $response->addCommand(new HtmlCommand('#ajax_download_selected_example', ''));
      $response->addCommand(new HtmlCommand('#ajax_edit_selected_example', ''));
      $form['example_files']['#title'] = '';
      $form['example_files']['#markup'] = '';
      $response->addCommand(new ReplaceCommand('#ajax_example_files_list', $form['example_files']));
      $form['example_actions']['#options'] = $this->bulkListExampleActions();
      $response->addCommand(new ReplaceCommand('#ajax_selected_example_action', $form['example_actions']));
      $response->addCommand(new HtmlCommand('#ajax_selected_example_action', ''));
    }

    return $response;
  }

  public function ajaxBulkExampleFilesCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $example_list_default_value = (int) $form_state->getValue('example');

    if ($example_list_default_value > 0) {
      $example_list_q = \Drupal::database()->select('textbook_companion_example_files', 'tef')
        ->fields('tef')
        ->condition('example_id', $example_list_default_value)
        ->execute();

      $example_files_rows = [];
      while ($example_list_data = $example_list_q->fetchObject()) {
        switch ($example_list_data->filetype) {
          case 'S':
            $example_file_type = 'Source or Main file';
            break;

          case 'R':
            $example_file_type = 'Result file';
            break;

          case 'X':
            $example_file_type = 'xcos file';
            break;

          default:
            $example_file_type = 'Unknown';
            break;
        }

        $example_files_rows[] = [
          $this->buildLink($example_list_data->filename, 'textbook_companion.download_example_file', ['example_file_id' => $example_list_data->id]),
          $example_file_type,
        ];
      }

      $table = [
        '#theme' => 'table',
        '#header' => [
          $this->t('Filename'),
          $this->t('Type'),
        ],
        '#rows' => $example_files_rows,
      ];

      $form['example_files']['#title'] = $this->t('List of example files');
      $form['example_files']['#markup'] = \Drupal::service('renderer')->render($table);
      $response->addCommand(new ReplaceCommand('#ajax_example_files_list', $form['example_files']));
      $response->addCommand(new HtmlCommand('#ajax_download_selected_example', $this->buildLink($this->t('Download Example'), 'textbook_companion.download_example', ['example_id' => $example_list_default_value])));
      $response->addCommand(new HtmlCommand('#ajax_edit_selected_example', $this->buildLink($this->t('Edit Example'), 'textbook_companion.upload_examples_admin_edit_form', ['example_id' => $example_list_default_value])));
      $form['example_actions']['#options'] = $this->bulkListExampleActions();
      $response->addCommand(new ReplaceCommand('#ajax_selected_example_action', $form['example_actions']));
    }
    else {
      $response->addCommand(new HtmlCommand('#ajax_download_selected_example', ''));
      $response->addCommand(new HtmlCommand('#ajax_edit_selected_example', ''));
      $form['example_files']['#title'] = '';
      $form['example_files']['#markup'] = '';
      $response->addCommand(new ReplaceCommand('#ajax_example_files_list', $form['example_files']));
      $form['example_actions']['#options'] = $this->bulkListExampleActions();
      $response->addCommand(new ReplaceCommand('#ajax_selected_example_action', $form['example_actions']));
      $response->addCommand(new HtmlCommand('#ajax_selected_example_action', ''));
    }

    return $response;
  }

  public function ajaxBulkChapterActionsCallback(array &$form, FormStateInterface $form_state) {
    return new AjaxResponse();
  }

  private function bulkListOfBooks() {
    $book_titles = [
      '0' => $this->t('Please select...'),
    ];

    $query = \Drupal::database()->select('textbook_companion_preference', 'pp');
    $query->join('textbook_companion_proposal', 'p', 'pp.proposal_id = p.id');
    $query->join('users_field_data', 'u', 'p.uid = u.uid');
    $query->fields('u', ['name']);
    $query->fields('pp', ['id', 'book', 'author']);
    $or = $query->orConditionGroup()
      ->condition('pp.approval_status', 1)
      ->condition('pp.approval_status', 3);
    $query->condition($or);
    $query->orderBy('pp.book', 'ASC');

    $book_titles_q = $query->execute();
    while ($book_titles_data = $book_titles_q->fetchObject()) {
      $book_titles[$book_titles_data->id] = $book_titles_data->book . ' (Written by ' . $book_titles_data->author . ')' . ' (Proposed by ' . $book_titles_data->name . ')';
    }

    return $book_titles;
  }

  private function bulkGetChapterList($preference_id = 0) {
    $book_chapters = [
      '0' => $this->t('Please select...'),
    ];

    $query = \Drupal::database()->select('textbook_companion_chapter', 'tc');
    $query->fields('tc');
    $query->condition('preference_id', $preference_id);
    $query->orderBy('number', 'ASC');

    $book_chapters_q = $query->execute();
    while ($book_chapters_data = $book_chapters_q->fetchObject()) {
      $book_chapters[$book_chapters_data->id] = $book_chapters_data->number . '. ' . $book_chapters_data->name;
    }

    return $book_chapters;
  }

  private function bulkGetExamples($chapter_id = 0) {
    $book_examples = [
      '0' => $this->t('Please select...'),
    ];

    $query = \Drupal::database()->select('textbook_companion_example', 'te');
    $query->fields('te');
    $query->condition('chapter_id', $chapter_id);

    $book_examples_q = $query->execute();
    while ($book_examples_data = $book_examples_q->fetchObject()) {
      $book_examples[$book_examples_data->id] = $book_examples_data->number . ' (' . $book_examples_data->caption . ')';
    }

    return $book_examples;
  }

  private function bulkListBookActions() {
    $book_actions = [
      '0' => $this->t('Please select...'),
    ];
    $book_actions[1] = $this->t('Approve Entire Book');
    $book_actions[2] = $this->t('Pending Review Entire Book');
    $book_actions[3] = $this->t('Dis-Approve Entire Book (This will delete all the examples in the book)');
    $book_actions[4] = $this->t('Delete Entire Book Including Proposal');

    return $book_actions;
  }

  private function bulkListChapterActions() {
    $chapter_actions = [
      '0' => $this->t('Please select...'),
    ];
    $chapter_actions[1] = $this->t('Approve Entire Chapter');
    $chapter_actions[2] = $this->t('Pending Review Entire Chapter');
    $chapter_actions[3] = $this->t('Dis-Approve Entire Chapter (This will delete all the examples in the chapter)');

    return $chapter_actions;
  }

  private function bulkListExampleActions() {
    $example_actions = [
      '0' => $this->t('Please select...'),
    ];
    $example_actions[1] = $this->t('Approve  Approve Example');
    $example_actions[2] = $this->t('Pending Review Example');
    $example_actions[3] = $this->t('Dis-approve Example (This will delete the example)');

    return $example_actions;
  }

  private function buildLink($text, string $route, array $parameters = [], array $options = []) {
    $url = Url::fromRoute($route, $parameters, $options);
    return Link::fromTextAndUrl($text, $url)->toString();
  }

}
