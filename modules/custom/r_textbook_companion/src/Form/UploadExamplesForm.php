<?php

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Cache\Cache;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadExamplesForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'upload_examples_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $uid = (int) \Drupal::currentUser()->id();
    $connection = \Drupal::database();
    $query = $connection->select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('uid', $uid);
    $query->orderBy('id', 'DESC');
    $query->range(0, 1);
    $result = $query->execute();
    $proposal_data = $result->fetchObject();
    if (!$proposal_data) {
      $this->messenger()->addError($this->t('Please submit a proposal before uploading code.'));
      return [];
    }
    if ($proposal_data->proposal_status != 1 && $proposal_data->proposal_status != 4) {
      switch ($proposal_data->proposal_status) {
        case 0:
          $this->messenger()->addStatus($this->t('We have already received your proposal. We will get back to you soon.'));
          return [];
        case 2:
          $this->messenger()->addError($this->t('Your proposal has been dis-approved.'));
          return [];
        case 3:
          $this->messenger()->addStatus($this->t('Your proposal is completed. Please submit a new proposal.'));
          return [];
        case 5:
          $this->messenger()->addStatus($this->t('You have submitted all your codes.'));
          return [];
        default:
          $this->messenger()->addError($this->t('Invalid proposal state. Please contact site administrator for further information.'));
          return [];
      }
    }
    $query = $connection->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('proposal_id', $proposal_data->id);
    $query->condition('approval_status', 1);
    $query->range(0, 1);
    $result = $query->execute();
    $preference_data = $result->fetchObject();
    if (!$preference_data) {
      $this->messenger()->addError($this->t('Invalid Book Preference status. Please contact site administrator for further information.'));
      return [];
    }
    $form['#attributes'] = [
      'enctype' => 'multipart/form-data',
    ];
    $form['pref_id'] = [
      '#type' => 'hidden',
      '#value' => $preference_data->id,
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
    $options = [
      '' => '(Select)',
    ];
    for ($i = 1; $i <= 100; $i++) {
      $options[$i] = $i;
    }
    $form['number'] = [
      '#type' => 'select',
      '#title' => t('Chapter No'),
      '#options' => $options,
      '#multiple' => FALSE,
      '#size' => 1,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::ajaxChapterNameCallback',
      ],
    ];
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => t('Title of the Chapter'),
      '#size' => 40,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#prefix' => '<div id="ajax-chapter-name-replace">',
      '#suffix' => '</div>',
    ];
    $form['example_number'] = [
      '#type' => 'textfield',
      '#title' => t('Example No'),
      '#size' => 5,
      '#maxlength' => 10,
      '#description' => t('Example number should be separated by dots only.<br />Example: 1.1.a &nbsp;or&nbsp; 1.1.1'),
      '#required' => TRUE,
    ];
    $form['example_caption'] = [
      '#type' => 'textfield',
      '#title' => t('Caption'),
      '#size' => 40,
      '#maxlength' => 255,
      '#description' => t('Example caption should contain only alphabets, numbers and spaces.'),
      '#required' => TRUE,
    ];
    $form['example_warning'] = [
      '#type' => 'item',
      '#title' => t('You should upload all the files as extention ".' . \Drupal::config('textbook_companion.settings')->get('textbook_companion_source_extensions') . '" main or source files, result files, executable file if any): '),
      '#prefix' => '<div style="color:red">',
      '#suffix' => '</div>',
    ];
    $form['sourcefile'] = [
      '#type' => 'fieldset',
      '#title' => t('Main or Source Files'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    $form['sourcefile']['sourcefile1'] = [
      '#type' => 'file',
      '#title' => t('Upload main or source file'),
      '#size' => 48,
      '#description' => t('Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' . t('<span style="color:red;">Allowed file extensions : ') . \Drupal::config('textbook_companion.settings')->get('textbook_companion_source_extensions') . '</span>',
    ];
    $form['upload_dataset'] = [
      '#type' => 'file',
      '#title' => t('Upload the dataset used for this example'),
      '#description' => t('Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' . t('<span style="color:red;">Allowed file extensions : ') . \Drupal::config('textbook_companion.settings')->get('textbook_companion_dataset_extensions') . '</span>',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    return $form;
  }

  public function ajaxChapterNameCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $pref_id = $form_state->getValue('pref_id');
    $chapter_number = $form_state->getValue('number');
    $chapter_name = \Drupal::database()->select('textbook_companion_chapter', 'c')
      ->fields('c', ['name'])
      ->condition('preference_id', $pref_id)
      ->condition('number', $chapter_number)
      ->range(0, 1)
      ->execute()
      ->fetchField();
    if ($chapter_name) {
      $form['name']['#value'] = $chapter_name;
      $form['name']['#attributes']['readonly'] = 'readonly';
    }
    else {
      $form['name']['#value'] = '';
      unset($form['name']['#attributes']['readonly']);
    }
    $response->addCommand(new ReplaceCommand('#ajax-chapter-name-replace', $form['name']));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!check_name($form_state->getValue('name'))) {
      $form_state->setErrorByName('name', $this->t('Title of the Chapter can contain only alphabets, numbers and spaces.'));
    }
    if (!check_name($form_state->getValue('example_caption'))) {
      $form_state->setErrorByName('example_caption', $this->t('Example Caption can contain only alphabets, numbers and spaces.'));
    }
    if (!check_chapter_number($form_state->getValue('example_number'))) {
      $form_state->setErrorByName('example_number', $this->t('Invalid Example Number. Example Number can contain only alphabets and numbers separated by dots.'));
    }
    $files = \Drupal::request()->files->get('files', []);
    foreach ($files as $file_form_name => $file) {
      if (!$file instanceof UploadedFile || $file->getClientOriginalName() === '') {
        continue;
      }
      if (str_contains($file_form_name, 'sourcefile1')) {
        $file_type = 'S';
      }
      elseif (str_contains($file_form_name, 'upload_dataset')) {
        $file_type = 'D';
      }
      else {
        continue;
      }
      $allowed_extensions_str = '';
      switch ($file_type) {
        case 'S':
          $allowed_extensions_str = (string) \Drupal::config('textbook_companion.settings')->get('textbook_companion_source_extensions');
          break;
        case 'D':
          $allowed_extensions_str = (string) \Drupal::config('textbook_companion.settings')->get('textbook_companion_dataset_extensions');
          break;
      }
      $allowed_extensions = array_filter(array_map('trim', explode(',', $allowed_extensions_str)));
      $extension = strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
      if ($extension === '' || !in_array($extension, $allowed_extensions, TRUE)) {
        $form_state->setErrorByName($file_form_name, $this->t('Only file with @ext extensions can be uploaded.', ['@ext' => $allowed_extensions_str]));
      }
      if ($file->getSize() <= 0) {
        $form_state->setErrorByName($file_form_name, $this->t('File size cannot be zero.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $uid = (int) \Drupal::currentUser()->id();
    $root_path = textbook_companion_path();
    $connection = \Drupal::database();
    $query = $connection->select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('uid', $uid);
    $query->orderBy('id', 'DESC');
    $query->range(0, 1);
    $result = $query->execute();
    $proposal_data = $result->fetchObject();
    if (!$proposal_data) {
      $this->messenger()->addError($this->t('Please submit a proposal before uploading code.'));
      $form_state->setRedirect('<front>');
      return;
    }
    if ($proposal_data->proposal_status != 1 && $proposal_data->proposal_status != 4) {
      switch ($proposal_data->proposal_status) {
        case 0:
          $this->messenger()->addStatus($this->t('We have already received your proposal. We will get back to you soon.'));
          $form_state->setRedirect('<front>');
          return;
        case 2:
          $this->messenger()->addError($this->t('Your proposal has been dis-approved.'));
          $form_state->setRedirect('<front>');
          return;
        case 3:
          $this->messenger()->addStatus($this->t('Your proposal is completed. Please submit a new proposal.'));
          $form_state->setRedirect('<front>');
          return;
        case 5:
          $this->messenger()->addStatus($this->t('You have submitted all your codes.'));
          $form_state->setRedirect('<front>');
          return;
        default:
          $this->messenger()->addError($this->t('Invalid proposal state. Please contact site administrator for further information.'));
          $form_state->setRedirect('<front>');
          return;
      }
    }
    $query = $connection->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('proposal_id', $proposal_data->id);
    $query->condition('approval_status', 1);
    $query->range(0, 1);
    $result = $query->execute();
    $preference_data = $result->fetchObject();
    if (!$preference_data) {
      $this->messenger()->addError($this->t('Invalid Book Preference status. Please contact site administrator for further information.'));
      $form_state->setRedirect('<front>');
      return;
    }
    $result = $connection->select('textbook_companion_preference')
      ->condition('proposal_id', $proposal_data->id)
      ->condition('approval_status', 1)
      ->countQuery()
      ->execute()
      ->fetchField();
    if ((int) $result > 1) {
      $this->messenger()->addError($this->t('You cannot upload your code. This book directory already exists. Please contact the administrator.'));
      return;
    }
    $proposal_directory = $preference_data->directory_name;
    $dest_path = $proposal_directory . '/';
    $file_system = \Drupal::service('file_system');
    $destination_directory = $root_path . $dest_path;
    if (!is_dir($destination_directory)) {
      if (!$file_system->prepareDirectory($destination_directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
        $this->messenger()->addError($this->t('You cannot upload your code. Error in creating directory.'));
        return;
      }
    }
    $chapter_id = 0;
    $preference_id = $preference_data->id;
    $query = $connection->select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('preference_id', $preference_id);
    $query->condition('number', $form_state->getValue('number'));
    $chapter_result = $query->execute();
    if (!$chapter_row = $chapter_result->fetchObject()) {
      $chapter_id = $connection->insert('textbook_companion_chapter')
        ->fields([
          'preference_id' => $preference_id,
          'number' => $form_state->getValue('number'),
          'name' => $form_state->getValue('name'),
        ])
        ->execute();
    }
    else {
      $chapter_id = $chapter_row->id;
      $connection->update('textbook_companion_chapter')
        ->fields([
          'name' => $form_state->getValue('name'),
        ])
        ->condition('id', $chapter_id)
        ->execute();
    }
    $query = $connection->select('textbook_companion_example');
    $query->fields('textbook_companion_example');
    $query->condition('chapter_id', $chapter_id);
    $query->condition('number', $form_state->getValue('example_number'));
    $cur_example_q = $query->execute();
    if ($cur_example_d = $cur_example_q->fetchObject()) {
      if ($cur_example_d->approval_status == 1) {
        $this->messenger()->addError($this->t('Example already approved. Cannot overwrite it.'));
        $form_state->setRedirect('textbook_companion.list_chapters');
        return;
      }
      elseif ($cur_example_d->approval_status == 0) {
        $this->messenger()->addError($this->t('Example is under pending review. Delete the example and reupload it.'));
        $form_state->setRedirect('textbook_companion.list_chapters');
        return;
      }
      else {
        $this->messenger()->addError($this->t('Error uploading example. Please contact administrator.'));
        $form_state->setRedirect('textbook_companion.list_chapters');
        return;
      }
    }
    $dest_path .= 'CH' . $form_state->getValue('number') . '/';
    $destination_directory = $root_path . $dest_path;
    if (!is_dir($destination_directory)) {
      if (!$file_system->prepareDirectory($destination_directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
        $this->messenger()->addError($this->t('You cannot upload your code. Error in creating directory.'));
        return;
      }
    }
    $dest_path .= 'EX' . $form_state->getValue('example_number') . '/';
    $destination_directory = $root_path . $dest_path;
    if (!is_dir($destination_directory)) {
      if (!$file_system->prepareDirectory($destination_directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
        $this->messenger()->addError($this->t('You cannot upload your code. Error in creating directory.'));
        return;
      }
    }
    $filepath = 'CH' . $form_state->getValue('number') . '/' . 'EX' . $form_state->getValue('example_number') . '/';
    $example_id = $connection->insert('textbook_companion_example')
      ->fields([
        'chapter_id' => $chapter_id,
        'number' => $form_state->getValue('example_number'),
        'caption' => $form_state->getValue('example_caption'),
        'approval_date' => \Drupal::time()->getRequestTime(),
        'approval_status' => 0,
        'timestamp' => \Drupal::time()->getRequestTime(),
      ])
      ->execute();

    $uploaded_files = \Drupal::request()->files->get('files', []);
    $file_caption = substr((string) $form_state->getValue('example_caption'), 0, 100);
    foreach ($uploaded_files as $file_form_name => $file) {
      if (!$file instanceof UploadedFile || $file->getClientOriginalName() === '') {
        continue;
      }
      if (str_contains($file_form_name, 'sourcefile1')) {
        $file_type = 'S';
        $default_mime = 'application/R';
      }
      elseif (str_contains($file_form_name, 'upload_dataset')) {
        $file_type = 'D';
        $default_mime = 'application/csv';
      }
      else {
        continue;
      }
      $original_name = $file->getClientOriginalName();
      $file_mime = $file->getMimeType() ?: $default_mime;
      $file_size = (int) $file->getSize();
      $destination = $root_path . $dest_path . $original_name;
      if (file_exists($destination)) {
        $this->messenger()->addError($this->t('Error uploading file. File @filename already exists.', ['@filename' => $original_name]));
        return;
      }
      $file->move($root_path . $dest_path, $original_name);
      $connection->insert('textbook_companion_example_files')
        ->fields([
          'example_id' => $example_id,
          'filename' => $original_name,
          'filepath' => $filepath . $original_name,
          'filemime' => $file_mime,
          'filesize' => $file_size,
          'filetype' => $file_type,
          'caption' => $file_caption,
          'timestamp' => \Drupal::time()->getRequestTime(),
        ])
        ->execute();
      $this->messenger()->addStatus($this->t('@filename uploaded successfully.', ['@filename' => $original_name]));
    }

    $this->messenger()->addStatus($this->t('Example uploaded successfully.'));
    $account = User::load($uid);
    $email_to = $account?->getEmail() ?? '';
    $config = \Drupal::config('textbook_companion.settings');
    $from = (string) $config->get('textbook_companion_from_email');
    $bcc = (string) $config->get('textbook_companion_emails');
    $cc = (string) $config->get('textbook_companion_cc_emails');
    $params['example_uploaded']['example_id'] = $example_id;
    $params['example_uploaded']['user_id'] = $uid;
    $params['example_uploaded']['headers'] = [
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
      $result = \Drupal::service('plugin.manager.mail')->mail('textbook_companion', 'example_uploaded', $email_to, $langcode, $params, $from, TRUE);
      if (empty($result['result'])) {
        $this->messenger()->addError($this->t('Error sending email message.'));
      }
    }
    Cache::invalidateTags([
      'textbook_companion:example_list',
      "textbook_companion:chapter:{$chapter_id}",
      "textbook_companion:preference:{$preference_id}",
      "textbook_companion:proposal:{$proposal_data->id}",
    ]);
    $form_state->setRedirect('textbook_companion.list_chapters');
  }

}
