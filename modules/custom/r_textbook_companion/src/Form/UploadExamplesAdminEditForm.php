<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\UploadExamplesAdminEditForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Render\Element;
use Drupal\user\Entity\User;

class UploadExamplesAdminEditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'upload_examples_admin_edit_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, int $example_id = NULL) {
    $user = \Drupal::currentUser();
    $connection = \Drupal::database();
    $example_id = $example_id ? (int) $example_id : 0;
    if ($example_id <= 0) {
      $this->messenger()->addError($this->t('Invalid example selected.'));
      $form_state->setRedirect('<front>');
      return [];
    }
    /* get example details */
    /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE id = %d LIMIT 1", $example_id);
    $example_data = db_fetch_object($example_q);*/
    $example_data = $connection->select('textbook_companion_example', 'tce')
      ->fields('tce')
      ->condition('id', $example_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();
    if (!$example_data) {
      $this->messenger()->addError($this->t('Invalid example selected.'));
      $form_state->setRedirect('<front>');
      return [];
    }
    /* get examples files */
    $source_file = "";
    $source_id = 0;
    $dataset_file = "";
    $dataset_id = 0;
    $result2_file = "";
    $result2_id = 0;
    $xcos1_file = "";
    $xcos1_id = 0;
    $xcos2_file = "";
    $xcos2_id = 0;
    /*$example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $example_id);*/
    $example_files_q = $connection->select('textbook_companion_example_files', 'tcef')
      ->fields('tcef')
      ->condition('example_id', $example_id)
      ->execute();
    while ($example_files_data = $example_files_q->fetchObject()) {
      if ($example_files_data->filetype == "S") {
        $source_file = Link::fromTextAndUrl(
          $example_files_data->filename,
          Url::fromRoute('textbook_companion.download_example_file', ['example_file_id' => $example_files_data->id])
        )->toString();
        $source_file_id = $example_files_data->id;
      }
      else {
        if ($example_files_data->filetype == "D") {
          $dataset_file = Link::fromTextAndUrl(
            $example_files_data->filename,
            Url::fromRoute('textbook_companion.download_example_file', ['example_file_id' => $example_files_data->id])
          )->toString();
          $dataset_file_id = $example_files_data->id;
        }
      }
    }
    /* get chapter details */
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d", $example_data->chapter_id);
    $chapter_data = db_fetch_object($chapter_q);*/
    $chapter_data = $connection->select('textbook_companion_chapter', 'tcc')
      ->fields('tcc')
      ->condition('id', $example_data->chapter_id)
      ->execute()
      ->fetchObject();
    if (!$chapter_data) {
      $this->messenger()->addError($this->t('Invalid chapter selected.'));
      $form_state->setRedirect('<front>');
      return [];
    }
    /* get preference details */
    /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d", $chapter_data->preference_id);
    $preference_data = db_fetch_object($preference_q);*/
    $preference_data = $connection->select('textbook_companion_preference', 'tp')
      ->fields('tp')
      ->condition('id', $chapter_data->preference_id)
      ->execute()
      ->fetchObject();
    if (!$preference_data) {
      $this->messenger()->addError($this->t('Invalid book selected.'));
      $form_state->setRedirect('<front>');
      return [];
    }
    /* get proposal details */
    /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE id = %d", $preference_data->proposal_id);
    $proposal_data = db_fetch_object($proposal_q);*/
    $proposal_data = $connection->select('textbook_companion_proposal', 'tpr')
      ->fields('tpr')
      ->condition('id', $preference_data->proposal_id)
      ->execute()
      ->fetchObject();
    if (!$proposal_data) {
      $this->messenger()->addError($this->t('Invalid proposal selected.'));
      $form_state->setRedirect('<front>');
      return [];
    }
    $config = \Drupal::config('textbook_companion.settings');
    $form['#attributes'] = ['enctype' => "multipart/form-data"];
    $form['example_id'] = [
      '#type' => 'hidden',
      '#value' => $example_id,
    ];
    $form['book_details']['book'] = [
      '#type' => 'item',
      '#markup' => $preference_data->book,
      '#title' => t('Title of the Book'),
    ];
    $form['contributor_name'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->full_name,
      '#title' => t('Contributor Name'),
    ];
    $form['number'] = [
      '#type' => 'item',
      '#title' => t('Chapter No'),
      '#markup' => $chapter_data->number,
    ];
    $form['name'] = [
      '#type' => 'item',
      '#title' => t('Title of the Chapter'),
      '#markup' => $chapter_data->name,
    ];
    $form['example_number'] = [
      '#type' => 'item',
      '#title' => t('Example No'),
      '#markup' => $example_data->number,
    ];
    $form['example_caption'] = [
      '#type' => 'textfield',
      '#title' => t('Caption'),
      '#size' => 40,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $example_data->caption,
    ];
    $form['example_warning'] = [
      '#type' => 'item',
      '#title' => t('You should upload all the files (main or source files, result files, executable file if any)'),
      '#prefix' => '<div style="color:red">',
      '#suffix' => '</div>',
    ];
    $form['sourcefile'] = [
      '#type' => 'fieldset',
      '#title' => t('Main or Source Files'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    if ($source_file) {
      $form['sourcefile']['cur_source'] = [
        '#type' => 'item',
        '#title' => t('Existing Main or Source File'),
        '#markup' => $source_file,
      ];
      $form['sourcefile']['cur_source_checkbox'] = [
        '#type' => 'checkbox',
        '#title' => t('Delete Existing Main or Source File'),
        '#description' => 'Check to delete the existing Main or Source file.',
      ];
      $form['sourcefile']['sourcefile1'] = [
        '#type' => 'file',
        '#title' => t('Upload New Main or Source File'),
        '#size' => 48,
        '#description' => t("Upload new Main or Source file above if you want to replace the existing file. Leave blank if you want to keep using the existing file. <br />") . t('Allowed file extensions : ') . (string) $config->get('textbook_companion_source_extensions'),
      ];
      $form['sourcefile']['cur_source_file_id'] = [
        '#type' => 'hidden',
        '#default_value' => $source_file_id,
      ];
    }
    else {
      $form['sourcefile']['sourcefile1'] = [
        '#type' => 'file',
        '#title' => t('Upload New Main or Source File'),
        '#size' => 48,
        '#description' => t('Allowed file extensions : ') . (string) $config->get('textbook_companion_source_extensions'),
      ];
    }
    $form['dataset'] = [
      '#type' => 'fieldset',
      '#title' => t('Dataset Files'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    if ($dataset_file) {
      $form['dataset']['cur_dataset'] = [
        '#type' => 'item',
        '#title' => t('Existing Dataset File'),
        '#markup' => $dataset_file,
      ];
      $form['dataset']['cur_dataset_checkbox'] = [
        '#type' => 'checkbox',
        '#title' => t('Delete Existing Dataset File'),
        '#description' => 'Check to delete the existing Dataset file.',
        '#attributes' => [
          'onClick' => 'return confirm("Are you sure you want to delete the example?")'
          ],
      ];
      $form['dataset']['upload_dataset'] = [
        '#type' => 'file',
        '#title' => t('Upload New Dataset File'),
        '#size' => 48,
        '#description' => t("Upload new Dataset file above if you want to replace the existing file. Leave blank if you want to keep using the existing file. <br />") . t('Allowed file extensions : ') . (string) $config->get('textbook_companion_dataset_extensions'),
      ];
      $form['dataset']['cur_dataset_file_id'] = [
        '#type' => 'hidden',
        '#value' => $dataset_file_id,
      ];
    }
    else {
      $form['dataset']['upload_dataset'] = [
        '#type' => 'file',
        '#title' => t('Upload New Dataset File'),
        '#size' => 48,
        '#description' => t('Allowed file extensions : ') . (string) $config->get('textbook_companion_dataset_extensions'),
      ];
    }
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    $form['cancel'] = [
      '#type' => 'markup',
      '#markup' => Link::fromTextAndUrl($this->t('Cancel'), Url::fromRoute('textbook_companion.list_chapters'))->toString(),
    ];
    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $config = \Drupal::config('textbook_companion.settings');
    if (!check_name($form_state->getValue(['example_caption']))) {
      $form_state->setErrorByName('example_caption', t('Example Caption can contain only alphabets, numbers and spaces.'));
    }
    if (isset($_FILES['files'])) {
      /* check for valid filename extensions */
      foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {
        if ($file_name) {
          /* checking file type */
          if (strstr($file_form_name, 'sourcefile1')) {
            $file_type = 'S';
          }
          else {
            if (strstr($file_form_name, 'upload_dataset')) {
              $file_type = 'D';
            }
          }
          $allowed_extensions_str = '';
          switch ($file_type) {
            case 'S':
              $allowed_extensions_str = (string) $config->get('textbook_companion_source_extensions');
              break;
            case 'D':
              $allowed_extensions_str = (string) $config->get('textbook_companion_dataset_extensions');
              break;
          }
          $allowed_extensions = explode(',', $allowed_extensions_str);
          $temp_ext = explode('.', strtolower($_FILES['files']['name'][$file_form_name]));
          $temp_extension = end($temp_ext);
          if (!in_array($temp_extension, $allowed_extensions)) {
            $form_state->setErrorByName($file_form_name, t('Only file with ' . $allowed_extensions_str . ' extensions can be uploaded.'));
          }
          if ($_FILES['files']['size'][$file_form_name] <= 0) {
            $form_state->setErrorByName($file_form_name, t('File size cannot be zero.'));
          }
          /* check if valid file name */
          if (!textbook_companion_check_valid_filename($_FILES['files']['name'][$file_form_name])) {
            $form_state->setErrorByName($file_form_name, t('Invalid file name specified. Only alphabets, numbers and underscore is allowed as a valid filename.'));
          }
        }
      }
    }
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $connection = \Drupal::database();
    $example_id = (int) $form_state->getValue('example_id');
    if (!$example_id) {
      $example_id = (int) \Drupal::routeMatch()->getParameter('example_id');
    }
    if ($example_id <= 0) {
      $this->messenger()->addError($this->t('Invalid example selected.'));
      $form_state->setRedirect('<front>');
      return;
    }
    $example_data = $connection->select('textbook_companion_example', 'tce')
      ->fields('tce')
      ->condition('id', $example_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();
    if (!$example_data) {
      $this->messenger()->addError($this->t('Invalid example selected.'));
      $form_state->setRedirect('<front>');
      return;
    }
    $chapter_data = $connection->select('textbook_companion_chapter', 'tcc')
      ->fields('tcc')
      ->condition('id', $example_data->chapter_id)
      ->execute()
      ->fetchObject();
    if (!$chapter_data) {
      $this->messenger()->addError($this->t('Invalid chapter selected.'));
      $form_state->setRedirect('<front>');
      return;
    }
    $preference_data = $connection->select('textbook_companion_preference', 'tp')
      ->fields('tp')
      ->condition('id', $chapter_data->preference_id)
      ->execute()
      ->fetchObject();
    if (!$preference_data) {
      $this->messenger()->addError($this->t('Invalid book selected.'));
      $form_state->setRedirect('<front>');
      return;
    }
    $proposal_data = $connection->select('textbook_companion_proposal', 'tpr')
      ->fields('tpr')
      ->condition('id', $preference_data->proposal_id)
      ->execute()
      ->fetchObject();
    if (!$proposal_data) {
      $this->messenger()->addError($this->t('Invalid proposal selected.'));
      $form_state->setRedirect('<front>');
      return;
    }
    /* creating directories */
    $root_path = textbook_companion_path();
    $dest_path = $preference_data->directory_name . '/';
    if (!is_dir($root_path . $dest_path)) {
      mkdir($root_path . $dest_path);
    }
    $dest_path .= 'CH' . $chapter_data->number . '/';
    if (!is_dir($root_path . $dest_path)) {
      mkdir($root_path . $dest_path);
    }
    $dest_path .= 'EX' . $example_data->number . '/';
    if (!is_dir($root_path . $dest_path)) {
      mkdir($root_path . $dest_path);
    }
    $filepath = 'CH' . $chapter_data->number . '/' . 'EX' . $example_data->number . '/';
    /* updating example caption */
    /*db_query("UPDATE {textbook_companion_example} SET caption = '%s' WHERE id = %d", $form_state['values']['example_caption'], $example_id);*/
    $connection->update('textbook_companion_example')
      ->fields(['caption' => $form_state->getValue('example_caption')])
      ->condition('id', $example_id)
      ->execute();
    /* handle source file */
    if (!$form_state->getValue(['cur_source_file_id']) || !$form_state->getValue(['cur_dataset_file_id'])) {
      $cur_file_id = $form_state->getValue(['cur_source_file_id']);
      $cur_dataset_id = $form_state->getValue(['cur_dataset_file_id']);
    }
    else {
      $cur_file_id = !$form_state->getValue(['cur_source_file_id']);
      $cur_dataset_id = !$form_state->getValue(['cur_dataset_file_id']);
    }
    //var_dump($cur_file_id);die;
    if ($cur_file_id > 0) {
      /*$file_q = db_query("SELECT * FROM  {textbook_companion_example_files} WHERE id = %d AND example_id = %d", $cur_file_id, $example_data->id);
        $file_data = db_fetch_object($file_q);*/
      //var_dump($cur_file_id. $example_data->id);die;
      $file_data = $connection->select('textbook_companion_example_files', 'tcef')
        ->fields('tcef')
        ->condition('id', $cur_file_id)
        ->condition('example_id', $example_data->id)
        ->execute()
        ->fetchObject();
      if (!$file_data) {
        $this->messenger()->addError($this->t('Error deleting example source file. File not present in database.'));
        return;
      }
      if (($form_state->getValue(['cur_source_checkbox']) == 1) && (!$_FILES['files']['name']['sourcefile1'])) {
        if (!delete_file($cur_file_id)) {
          $this->messenger()->addError($this->t('Error deleting example source file.'));
          return;
        }
      }
    }
    if ($cur_dataset_id > 0) {
      $file_data = $connection->select('textbook_companion_example_files', 'tcef')
        ->fields('tcef')
        ->condition('id', $cur_dataset_id)
        ->condition('example_id', $example_data->id)
        ->execute()
        ->fetchObject();
      if (!$file_data) {
        $this->messenger()->addError($this->t('Error deleting example dataset file. File not present in database.'));
        return;
      }
      if (($form_state->getValue(['cur_dataset_checkbox']) == 1) && (!$_FILES['files']['name']['upload_dataset'])) {
        if (!delete_file($cur_dataset_id)) {
          $this->messenger()->addError($this->t('Error deleting example dataset file.'));
          return;
        }
      }
    }
    if ($_FILES['files']['name']['sourcefile1']) {
      if ($cur_file_id > 0) {
        if (!delete_file($cur_file_id)) {
          $this->messenger()->addError($this->t('Error removing previous example source file.'));
          return;
        }
      }
    }
    else {
      if ($_FILES['files']['name']['upload_dataset']) {
        if ($cur_dataset_id > 0) {
          if (!delete_file($cur_dataset_id)) {
            $this->messenger()->addError($this->t('Error removing previous dataset file.'));
            return;
          }
        }
      }
    }
    $file_caption = substr((string) $form_state->getValue('example_caption'), 0, 100);
    foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {
      if ($file_name) {
        /* checking file type */
        if (strstr($file_form_name, 'sourcefile1')) {
          $file_type = 'S';
        } //strstr($file_form_name, 'upload_flowsheet_developed_process')
        else {
          if (strstr($file_form_name, 'upload_dataset')) {
            $file_type = 'D';
          }
        }

        //$file_type = 'S';
        switch ($file_type) {
          case 'S':
            if (file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])) {
              $this->messenger()->addError($this->t('Error uploading file. File @filename already exists.', [
                '@filename' => $_FILES['files']['name'][$file_form_name],
              ]));
              return;
            }
            /* uploading file */
            if (move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])) {
              $connection->insert('textbook_companion_example_files')
                ->fields([
                  'example_id' => $example_id,
                  'filename' => $_FILES['files']['name'][$file_form_name],
                  'filepath' => $filepath . $_FILES['files']['name'][$file_form_name],
                  'filemime' => 'application/R',
                  'filesize' => $_FILES['files']['size'][$file_form_name],
                  'filetype' => $file_type,
                  'caption' => $file_caption,
                  'timestamp' => time(),
                ])
                ->execute();
              $this->messenger()->addStatus($this->t('@filename uploaded successfully.', ['@filename' => $file_name]));
            }
            else {
              $this->messenger()->addError($this->t('Error uploading file : @path', ['@path' => $dest_path . '/' . $file_name]));
            }
            break;
          case 'D':
            if (file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])) {
              $this->messenger()->addError($this->t('Error uploading file. File @filename already exists.', [
                '@filename' => $_FILES['files']['name'][$file_form_name],
              ]));
              return;
            }
            /* uploading file */
            if (move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])) {
              $connection->insert('textbook_companion_example_files')
                ->fields([
                  'example_id' => $example_id,
                  'filename' => $_FILES['files']['name'][$file_form_name],
                  'filepath' => $filepath . $_FILES['files']['name'][$file_form_name],
                  'filemime' => 'application/csv',
                  'filesize' => $_FILES['files']['size'][$file_form_name],
                  'filetype' => $file_type,
                  'caption' => $file_caption,
                  'timestamp' => time(),
                ])
                ->execute();
              $this->messenger()->addStatus($this->t('@filename uploaded successfully.', ['@filename' => $file_name]));
            }
            else {
              $this->messenger()->addError($this->t('Error uploading file : @path', ['@path' => $dest_path . '/' . $file_name]));
            }
            break;
        }
      }
    }

    $this->messenger()->addStatus($this->t('Example uploaded successfully.'));
    /* sending email */
    $account = User::load($proposal_data->uid);
    $email_to = $account?->getEmail() ?? '';
    $config = \Drupal::config('textbook_companion.settings');
    $from = (string) $config->get('textbook_companion_from_email');
    $param['example_updated_admin']['example_id'] = $example_id;
    $param['example_updated_admin']['user_id'] = $proposal_data->uid;
    $langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
    $result = \Drupal::service('plugin.manager.mail')->mail('textbook_companion', 'example_updated_admin', $email_to, $langcode, $param, $from, TRUE);
    if (empty($result['result'])) {
      $this->messenger()->addError($this->t('Error sending email message.'));
    }
    Cache::invalidateTags([
      'textbook_companion:example_list',
      "textbook_companion:chapter:{$chapter_data->id}",
      "textbook_companion:preference:{$preference_data->id}",
      "textbook_companion:proposal:{$proposal_data->id}",
    ]);
    $this->messenger()->addStatus($this->t('Example successfully udpated.'));
    $form_state->setRedirect('textbook_companion.bulk_approval_form');
  }

}
