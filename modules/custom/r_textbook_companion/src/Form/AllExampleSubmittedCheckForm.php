<?php

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AllExampleSubmittedCheckForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'all_example_submitted_check_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, int $preference_id = NULL) {
    if ($preference_id === NULL) {
      $this->messenger()->addError($this->t('Invalid preference.'));
      return [];
    }
    $query = \Drupal::database()->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('id', $preference_id);
    $query->condition('approval_status', 1);
    $query->range(0, 1);
    $result = $query->execute();
    $preference_data = $result->fetchObject();
    $form['#attributes'] = [
      'enctype' => 'multipart/form-data',
    ];
    $form['all_example_submitted'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('I have submitted codes for all the examples'),
      '#description' => 'Once you have submited this option you are not able to upload more examples.',
      '#required' => TRUE,
    ];
    if ($preference_data && $preference_data->approved_codable_example_files == 0) {
      $form['upload_codable_examples'] = [
        '#type' => 'file',
        '#states' => [
          'visible' => [
            ':input[name="all_example_submitted"]' => [
              'checked' => TRUE,
            ],
          ],
        ],
        '#title' => $this->t('Upload a document file containing a list of codable examples as shown in the template <a href="https://static.fossee.in/r/manuals/Template.docx" target="_blank">here</a><span style="color:red">*</span>'),
        '#description' => $this->t('Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' . $this->t('<span style="color:red;">Allowed file extensions : ') . \Drupal::config('textbook_companion.settings')->get('textbook_companion_codable_examples_extensions') . '</span>',
      ];
    }
    $form['hidden_preference_id'] = [
      '#type' => 'hidden',
      '#value' => $preference_id,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('all_example_submitted') != 1) {
      $form_state->setErrorByName('all_example_submitted', $this->t('Please check the field if you are intrested to submit the all uploaded examples for review!'));
    }
    $files = \Drupal::request()->files->get('files', []);
    if (!empty($files) && empty($files['upload_codable_examples'])) {
      $form_state->setErrorByName('upload_codable_examples', $this->t('Please upload the file containing a list of codable examples.'));
    }
    foreach ($files as $file_form_name => $file) {
      if (!$file instanceof UploadedFile || $file->getClientOriginalName() === '') {
        continue;
      }
      $allowed_extensions_str = (string) \Drupal::config('textbook_companion.settings')->get('textbook_companion_codable_examples_extensions');
      $allowed_extensions = array_filter(array_map('trim', explode(',', $allowed_extensions_str)));
      $extension = strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
      if ($extension === '' || !in_array($extension, $allowed_extensions, TRUE)) {
        $form_state->setErrorByName($file_form_name, $this->t('Only file with @ext extensions can be uploaded.', ['@ext' => $allowed_extensions_str]));
      }
      if ($file->getSize() <= 0) {
        $form_state->setErrorByName($file_form_name, $this->t('File size cannot be zero.'));
      }
      if (!textbook_companion_check_valid_filename($file->getClientOriginalName())) {
        $form_state->setErrorByName($file_form_name, $this->t('Invalid file name specified. Only alphabets and numbers are allowed as a valid filename.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $uid = (int) $this->currentUser()->id();
    $connection = \Drupal::database();
    $proposal_data = $connection->select('textbook_companion_proposal')
      ->fields('textbook_companion_proposal')
      ->condition('uid', $uid)
      ->orderBy('id', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetchObject();
    if (!$proposal_data) {
      $this->messenger()->addError($this->t('Please submit a proposal before submitting all examples.'));
      $form_state->setRedirect('<front>');
      return;
    }
    if ($proposal_data->proposal_status != 1 && $proposal_data->proposal_status != 4) {
      $this->messenger()->addError($this->t('Invalid proposal state. Please contact site administrator for further information.'));
      $form_state->setRedirect('<front>');
      return;
    }
    $preference_data = $connection->select('textbook_companion_preference')
      ->fields('textbook_companion_preference')
      ->condition('proposal_id', $proposal_data->id)
      ->condition('approval_status', 1)
      ->range(0, 1)
      ->execute()
      ->fetchObject();
    if (!$preference_data) {
      $this->messenger()->addError($this->t('Invalid Book Preference status. Please contact site administrator for further information.'));
      $form_state->setRedirect('<front>');
      return;
    }

    if ($preference_data->approved_codable_example_files == 0) {
      $root_path = textbook_companion_path();
      $proposal_directory = $preference_data->directory_name;
      $dest_path = $proposal_directory . '/codable_example_file';
      $file_system = \Drupal::service('file_system');
      $destination_directory = $root_path . $dest_path;
      if (!$file_system->prepareDirectory($destination_directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
        $this->messenger()->addError($this->t('You cannot upload your code. Error in creating directory.'));
        return;
      }
      $filepath = 'codable_example_file/';
      $uploaded_files = \Drupal::request()->files->get('files', []);
      foreach ($uploaded_files as $file_form_name => $file) {
        if (!$file instanceof UploadedFile || $file->getClientOriginalName() === '') {
          continue;
        }
        $original_name = $file->getClientOriginalName();
        $file_mime = $file->getMimeType();
        $file_size = (int) $file->getSize();
        $destination = $root_path . $dest_path . '/' . $original_name;
        if (file_exists($destination)) {
          $file->move($root_path . $dest_path, $original_name);
          $this->messenger()->addStatus($this->t('File @filename already exists and has been overwritten.', ['@filename' => $original_name]));
        }
        else {
          $file->move($root_path . $dest_path, $original_name);
        }

        $existing = $connection->select('textbook_companion_codable_example_files')
          ->fields('textbook_companion_codable_example_files')
          ->condition('proposal_id', $preference_data->proposal_id)
          ->range(0, 1)
          ->execute()
          ->fetchObject();
        $fields = [
          'proposal_id' => $preference_data->proposal_id,
          'filename' => $original_name,
          'filepath' => $filepath . $original_name,
          'filemime' => $file_mime,
          'filesize' => $file_size,
          'filetype' => 'C',
          'timestamp' => \Drupal::time()->getRequestTime(),
        ];
        if ($existing) {
          $connection->update('textbook_companion_codable_example_files')
            ->fields($fields)
            ->condition('proposal_id', $preference_data->proposal_id)
            ->execute();
          $this->messenger()->addStatus($this->t('@filename file updated successfully.', ['@filename' => $original_name]));
        }
        else {
          $connection->insert('textbook_companion_codable_example_files')
            ->fields($fields)
            ->execute();
          $this->messenger()->addStatus($this->t('File uploaded successfully.'));
        }
        $connection->update('textbook_companion_preference')
          ->fields([
            'submited_all_examples_code' => 1,
            'submitted_codable_examples_file' => 1,
          ])
          ->condition('id', $preference_data->id)
          ->execute();
      }
    }
    else {
      $connection->update('textbook_companion_preference')
        ->fields(['submited_all_examples_code' => 1])
        ->condition('id', $preference_data->id)
        ->execute();
    }

    $proposal_id_query = $connection->select('textbook_companion_preference');
    $proposal_id_query->addField('textbook_companion_preference', 'proposal_id');
    $proposal_id_query->condition('id', $form_state->getValue('hidden_preference_id'));
    $proposal_id = $proposal_id_query->execute()->fetchField();
    $account = User::load($uid);
    $email_to = $account?->getEmail() ?? '';
    $config = \Drupal::config('textbook_companion.settings');
    $from = (string) $config->get('textbook_companion_from_email');
    $bcc = (string) $config->get('textbook_companion_emails');
    $cc = (string) $config->get('textbook_companion_cc_emails');
    $param['all_code_submitted']['proposal_id'] = $proposal_id;
    $param['all_code_submitted']['user_id'] = $uid;
    $param['all_code_submitted']['headers'] = [
      'From' => $from,
      'MIME-Version' => '1.0',
      'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
      'Content-Transfer-Encoding' => '8Bit',
      'X-Mailer' => 'Drupal',
      'Cc' => $cc,
      'Bcc' => $bcc,
    ];
    if ($email_to !== '') {
      $langcode = $account?->getPreferredLangcode() ?? \Drupal::languageManager()->getDefaultLanguage()->getId();
      $result = \Drupal::service('plugin.manager.mail')->mail('textbook_companion', 'all_code_submitted', $email_to, $langcode, $param, $from, TRUE);
      if (empty($result['result'])) {
        $this->messenger()->addError($this->t('Error sending email message.'));
      }
    }
    Cache::invalidateTags([
      'textbook_companion:proposal_list',
      "textbook_companion:proposal:{$proposal_data->id}",
      "textbook_companion:preference:{$preference_data->id}",
    ]);
    $form_state->setRedirect('textbook_companion.list_chapters');
  }

}
