<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\BookProposalForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\textbook_companion\Helper\ProposalHelper;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class BookProposalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'book_proposal_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $user = $this->currentUser();
    $user_entity = \Drupal::entityTypeManager()->getStorage('user')->load($user->id());
    $user_email = $user_entity ? $user_entity->getEmail() : '';
    $form = array();
    $form['imp_notice'] = array(
      '#type' => 'item',
      '#markup' => t('<span style="color:red;">'. '<b>' . 'Please fill up this form carefully as the details entered here will be exactly written in the Textbook Companion' . '</b>' . '</span>')
    );
    $form['name_title'] = array(
      '#type' => 'select',
      '#title' => $this->t('Title'),
      '#options' => array(
        'Dr' => 'Dr',
        'Prof' => 'Prof',
        'Mr' => 'Mr',
        'Mrs' => 'Mrs',
        'Ms' => 'Ms'
      ),
      '#required' => TRUE
    );
    $form['full_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Full Name'),
      '#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE
    );
    $form['email_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Email'),
      '#size' => 30,
      '#value' => $user_email,
      '#disabled' => TRUE
    );
    $form['mobile'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Mobile No.'),
      '#size' => 30,
      '#maxlength' => 15,
      '#required' => TRUE
    );
/*	$form['gender'] = array(
		'#type' => 'radios',
		'#title' => $this->t('Gender'),
		'#options' => array(
			'M' => 'Male',
			'F' => 'Female'
		),
		'#required' => TRUE
	);*/
    $form['how_project'] = array(
      '#type' => 'select',
      '#title' => $this->t('How did you come to know about this project'),
      '#options' => array(
        'R Website' => 'R Website',
        'Friend' => 'Friend',
        'Professor/Teacher' => 'Professor/Teacher',
        'Mailing List' => 'Mailing List',
        'Poster in my/other college' => 'Poster in my/other college',
        'Others' => 'Others'
      ),
      '#required' => TRUE
    );
    $form['course'] = array(
      '#type' => 'select',
      '#title' => $this->t('Course'),
      '#options' => _tbc_list_of_courses(),
      '#required' => TRUE,
      '#tree' => TRUE,
    );
    $form['other_course'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Enter your Course'),
      '#size' => 50,
      '#attributes' => array(
        'placeholder' => $this->t('Enter your Course name')
      ),
      '#description' => $this->t('<span style="color:red;">Maximum character limit for course name is 50 characters</span>'),
      '#states' => array(
        'visible' => array(
          ':input[name="course"]' => array(
            'value' => 'Others'
          )
        )
      )
    );
    /*$form['course'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Course'),
      '#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE
    );*/
    $form['branch'] = array(
      '#type' => 'select',
      '#title' => $this->t('Department/Branch'),
      '#options' => _list_of_departments(),
      '#required' => TRUE,
      '#tree' => TRUE,
    );
    $form['other_branch'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Enter your Department/Branch name'),
      '#size' => 100,
      '#attributes' => array(
        'placeholder' => $this->t('Enter your Department/Branch name')
      ),
      '#description' => $this->t('<span style="color:red;">Maximum character limit for branch name is 100 characters</span>'),
      '#states' => array(
        'visible' => array(
          ':input[name="branch"]' => array(
            'value' => 'Others'
          )
        )
      )
    );
    $form['university'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('University/ Institute'),
      '#size' => 80,
      '#maxlength' => 200,
      '#required' => TRUE,
      '#attributes' => array(
        'placeholder' => 'Insert full name of your institute/ university.... '
      )
    );
    $form['country'] = array(
      '#type' => 'select',
      '#title' => $this->t('Country'),
      '#options' => array(
        'India' => 'India',
        'Others' => 'Others'
      ),
      '#required' => TRUE,
      '#tree' => TRUE,
      '#validated' => TRUE
    );
    $form['other_country'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Other than India'),
      '#size' => 100,
      '#attributes' => array(
        'placeholder' => $this->t('Enter your country name')
      ),
      '#states' => array(
        'visible' => array(
          ':input[name="country"]' => array(
            'value' => 'Others'
          )
        )
      )
    );
    $form['other_state'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('State other than India'),
      '#size' => 100,
      '#attributes' => array(
        'placeholder' => $this->t('Enter your state/region name')
      ),
      '#states' => array(
        'visible' => array(
          ':input[name="country"]' => array(
            'value' => 'Others'
          )
        )
      )
    );
    $form['other_city'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('City other than India'),
      '#size' => 100,
      '#attributes' => array(
        'placeholder' => $this->t('Enter your city name')
      ),
      '#states' => array(
        'visible' => array(
          ':input[name="country"]' => array(
            'value' => 'Others'
          )
        )
      )
    );
    $form['all_state'] = array(
      '#type' => 'select',
      '#title' => $this->t('State'),
      '#selected' => array(
        '' => '-select-'
      ),
      '#options' => _list_of_states(),
      '#validated' => TRUE,
      '#states' => array(
        'visible' => array(
          ':input[name="country"]' => array(
            'value' => 'India'
          )
        )
      )
    );
    $form['city'] = array(
      '#type' => 'select',
      '#title' => $this->t('City'),
      '#options' => _list_of_cities(),
      '#states' => array(
        'visible' => array(
          ':input[name="country"]' => array(
            'value' => 'India'
          )
        )
      )
    );
    $form['pincode'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Pincode'),
      '#size' => 30,
      '#maxlength' => 6,
      '#required' => True,
      '#attributes' => array(
        'placeholder' => 'Enter pincode....'
      )
    );
    /***************************************************************************/
    $form['hr'] = array(
      '#type' => 'item',
      '#markup' => '<hr>'
    );
    $form['faculty'] = array(
      '#type' => 'hidden',
      '#value' => 'None',
      '#title' => $this->t('College Teacher/Professor'),
      '#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE
    );
    $form['faculty_email'] = array(
      '#type' => 'hidden',
      '#value' => 'None',
      '#title' => $this->t('Teacher/Professor Email Id'),
      '#value' => '@email.com',
      '#size' => 30,
      '#maxlength' => 50
    );
    $form['reviewer'] = array(
      '#type' => 'hidden',
      '#value' => 'R TBC Team',
      '#title' => $this->t('Reviewer'),
      '#size' => 30,
      '#maxlength' => 50
    );
    $form['version'] = array(
      '#type' => 'select',
      '#title' => $this->t('Version'),
      '#options' => _list_of_software_version(),
      '#required' => TRUE
    );
    $form['other_version'] = array(
      '#type' => 'textfield',
      '#size' => 30,
      '#maxlength' => 50,
      //'#required' => TRUE,
      '#description' => $this->t('Specify the Other version used'),
      '#states' => array(
        'visible' => array(
          ':input[name="version"]' => array(
            'value' => 'Others'
          )
        )
      )
    );
    $form['completion_date'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Expected Date of Completion'),
      '#required' => TRUE,
      '#description' => $this->t('Input date format should be DD-MM-YYYY. Eg: 23-03-2011'),
      '#size' => 10,
      '#maxlength' => 10
    );
    $form['operating_system'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Operating System'),
      '#required' => TRUE,
      '#size' => 30,
      '#maxlength' => 50
    );
    $reason = array(
      'Used in more than one University' => $this->t('Used in more than one University'),
      'The book has multiple editions' => $this->t('The book has multiple editions'),
      'Extremely useful' => $this->t('Extremely useful'),
      'Other reason' => $this->t('Any other reason state below')
    );
    $form['reason'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Reasons for selecting this book'),
      '#options' => $reason,
      '#required' => TRUE
    );
    $form['other_reason'] = array(
      '#type' => 'textarea',
      '#size' => 255,
      '#maxlength' => 255,
      '#description'=>$this->t('<span style="color:red;">Maximum character limit is 255 characters</span>'),
      '#states' => array(
        'visible' => array(
          ':input[name="reason[Other reason]"]' => array(
            'checked' => TRUE
          )
        )
      )
      //'#required' => FALSE,
    );
    $form['proposal_type'] = array(
      '#type' => 'hidden',
      '#default_value' => '1',
      '#required' => FALSE
    );
    $form['book_download_link'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Download link for the proposed textbook'),
      '#required' => TRUE,
      '#size' => 500,
      '#maxlength' => 500,
      '#attributes' => array(
        'placeholder' => 'Please add a link to download the proposed textbook from an online source. In case of a hard copy, kindly create a pdf of the entire textbook along with the cover and back page using a mobile application like CamScanner or some other method of choice. Then upload the pdf on google drive and enter the download link here.'
      ),
      '#description' => $this->t('<span style="color:red">For eg: https://r.fossee.in/ (add link in http or https format)</span>')
    );
    $form['reference'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Reference'),
      '#required' => TRUE,
      '#size' => 500,
      '#maxlength' => 500,
      '#attributes' => array(
        'placeholder' => 'Please mention link(s) of the syllabus where this book has been recommended by University/ College/ Institute'
      ),
      '#description' => $this->t('<span style="color:red">For eg: https://r.fossee.in/ (add link in http or https format)</span>')
    );
    $form['form_type'] = array(
      '#type' => 'hidden',
      '#value' => 1
    );
    $form['preference1'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Book Preference'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE
    );
    $form['preference1']['book1'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Title of the book'),
      '#size' => 30,
      '#maxlength' => 100,
      '#required' => TRUE
    );
    $form['preference1']['author1'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Author Name'),
      '#size' => 30,
      '#maxlength' => 100,
      '#required' => TRUE
      //'#value' => $row1->author,
      //'#disabled' => ($row1->author?TRUE:FALSE),
    );
    $form['preference1']['isbn1'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('ISBN No'),
      '#size' => 30,
      '#maxlength' => 25,
      '#required' => TRUE
      // '#value' => $row1->isbn,
      // '#disabled' => ($row1->isbn?TRUE:FALSE),
    );
    $form['preference1']['publisher1'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Publisher & Place'),
      '#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE
      //'#value' => $row1->publisher,
    );
    $form['preference1']['edition1'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Edition'),
      '#size' => 4,
      '#maxlength' => 2,
      '#required' => TRUE
      //'#value' => $row1->edition,
    );
    $form['preference1']['year1'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Year of publication'),
      '#size' => 4,
      '#maxlength' => 4,
      '#required' => TRUE
      //'#value' => $row1->year,
    );
    $form['preference1']['category'] = array(
      '#type' => 'select',
      '#title' => $this->t('Select the category'),
      '#options' => _tbc_list_of_main_categories(),
      '#default_value' => 0,
      '#required' => TRUE
    );
    $form['abstract_sample_file'] = array(
        '#type' => 'item',
        '#title' => $this->t('<h5>Ideal Sample Source Files Submission</h5>'),
      '#markup' => $this->t('Kindly refer to the following link to know the ideal sample code files submission:') . $this->t('<a href= "https://static.fossee.in/r/Sample_R_Codes/TBC_Sample_Code/Sample_R_Codes.zip" target="_blank"> Click Here</a>')
    );
    $form['samplefile'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Sample Source Files <span style="color:red;">*</span>'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE
    );
    $form['samplefile']['samplefile1'] = array(
      '#type' => 'file',
      '#title' => $this->t('Upload sample source file'),
      '#size' => 48,
      '#description' => $this->t('Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' . $this->t('<span style="color:red;">Allowed file extensions : ') . \Drupal::config('textbook_companion.settings')->get('textbook_companion_sample_source_extensions') . '</span>'
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit')
    );
    $form['dir_name'] = array(
      '#type' => 'hidden',
      '#value' => 'None'
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    /* mobile */
    if (!preg_match('/^[0-9\ \+]{0,15}$/', $values['mobile'] ?? '')) {
      $form_state->setErrorByName('mobile', $this->t('Invalid mobile number'));
    }
    /* date of completion */
    $completion_date = $values['completion_date'] ?? '';
    if (!preg_match('/^[0-9]{1,2}-[0-9]{1,2}-[0-9]{4}$/', $completion_date)) {
      $form_state->setErrorByName('completion_date', $this->t('Invalid expected date of completion'));
    }
    else {
      list($d, $m, $y) = explode('-', $completion_date);
      $d = (int) $d;
      $m = (int) $m;
      $y = (int) $y;
      if (!checkdate($m, $d, $y)) {
        $form_state->setErrorByName('completion_date', $this->t('Invalid expected date of completion'));
      }
      if (mktime(0, 0, 0, $m, $d, $y) <= time()) {
        $form_state->setErrorByName('completion_date', $this->t('Expected date of completion should be in future'));
      }
    }
    /* edition */
    $cur_year = date('Y');
    if (!preg_match('/^[A-Za-z]/', $values['book1'] ?? '')) {
      $form_state->setErrorByName('book1', $this->t('Invalid book name for Book Preference 1'));
    }
    if (!preg_match('/^[A-Za-z]/', $values['author1'] ?? '')) {
      if (!preg_match('/^[0-9\-xX]+$/', $values['isbn1'] ?? '')) {
        $form_state->setErrorByName('isbn1', $this->t('Invalid ISBN for Book Preference 1'));
      }
    }
    if (!preg_match('/^[1-9][0-9]{0,1}$/', $values['edition1'] ?? '')) {
      $form_state->setErrorByName('edition1', $this->t('Invalid edition for Book Preference 1'));
    }
    if (!preg_match('/^[1-3][0-9][0-9][0-9]$/', $values['year1'] ?? '')) {
      $form_state->setErrorByName('year1', $this->t('Invalid year of pulication for Book Preference 1'));
    }
    if ((int) ($values['year1'] ?? 0) > $cur_year) {
      $form_state->setErrorByName('year1', $this->t('Year of pulication should be not in the future for Book Preference 1'));
    }
    if (!empty($values['book1']) && !empty($values['author1'])) {
      $bk1 = trim($values['book1']);
      $auth1 = trim($values['author1']);
      //var_dump(_dir_name($bk1, $auth1))
      $pref_id = NULL;
      $dir_name = _dir_name($bk1, $auth1, $pref_id, $form_state);
      if ($dir_name !== NULL) {
        $form_state->setValue('dir_name1', $dir_name);
      } //_dir_name($bk1, $auth1, $pref_id) != NULL
    } //$values['book1'] && $values['author1']

    if (isset($values['reason'])) {
      $my_reason = $values['other_reason'] ?? '';
      if (strlen($my_reason) > 255) {
        $form_state->setErrorByName('other_reason', $this->t('Maximum limit is 255 characters'));
      }
    }
    if (($values['version'] ?? '') == 'Other version') {
      if (($values['other_version'] ?? '') == '') {
        $form_state->setErrorByName('other_version', $this->t('Please provide valid version'));
      } //$values['other_version'] == ''
    } //$values['version'] == 'Other version'
    if (($values['branch'] ?? '') == 'Others') {
      $other_branch = $values['other_branch'] ?? '';
      if (strlen($other_branch) < 10) {
        $form_state->setErrorByName('other_branch', $this->t('The minimum charater limit is 10'));
      }
      else if (strlen($other_branch) > 100) {
        $form_state->setErrorByName('other_branch', $this->t('The maximum character limit is 100'));
      }
    }
    if (($values['course'] ?? '') == 'Others') {
      $other_course = $values['other_course'] ?? '';
      if ($other_course == '') {
        $form_state->setErrorByName('other_course', $this->t('Please enter the course name'));
      }
      else if (strlen($other_course) > 50) {
        $form_state->setErrorByName('other_course', $this->t('The maximum character limit is 50'));
      }
    }
    $normalized_book_download_link = ProposalHelper::normalizeExternalUri($values['book_download_link'] ?? '');
    if ($normalized_book_download_link === NULL) {
      $form_state->setErrorByName('book_download_link', $this->t('Please enter a valid download link in http or https format.'));
    }
    else {
      $form_state->setValue('book_download_link', $normalized_book_download_link);
    }
    if (!empty($_FILES['files'])) {
      /* check if atleast one source or result file is uploaded */
      if (empty($_FILES['files']['name']['samplefile1'])) {
        $form_state->setErrorByName('samplefile1', $this->t('Please upload sample code main or source file.'));
      }
      /* check for valid filename extensions */
      foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {
        if ($file_name) {
          /* checking file type */
          if (strstr($file_form_name, 'sample')) {
            $file_type = 'S';
          }
          else {
            $file_type = 'U';
          }
          $allowed_extensions_str = '';
          switch ($file_type) {
            case 'S':
              $allowed_extensions_str = \Drupal::config('textbook_companion.settings')->get('textbook_companion_sample_source_extensions');
              break;
          } //$file_type
          $allowed_extensions = explode(',', $allowed_extensions_str);
          $fnames = explode('.', strtolower($_FILES['files']['name'][$file_form_name]));
          $temp_extension = end($fnames);
          if (!in_array($temp_extension, $allowed_extensions)) {
            $form_state->setErrorByName($file_form_name, $this->t('Only file with @ext extensions can be uploaded.', ['@ext' => $allowed_extensions_str]));
          }
          if ($_FILES['files']['size'][$file_form_name] <= 0) {
            $form_state->setErrorByName($file_form_name, $this->t('File size cannot be zero.'));
          }
          /* check if valid file name */
          if (!textbook_companion_check_valid_filename($_FILES['files']['name'][$file_form_name])) {
            $form_state->setErrorByName($file_form_name, $this->t('Invalid file name specified. Only alphabets and numbers are allowed as a valid filename.'));
          }
        } //$file_name
      } //$_FILES['files']['name'] as $file_form_name => $file_name
    } //$_FILES['files']
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $user = $this->currentUser();
    $root_path = textbook_companion_samplecode_path();
    // @FIXME
// // @FIXME
// // The correct configuration object could not be determined. You'll need to
// // rewrite this call manually.
// $selections = variable_get("aicte_" . $user->uid, "");

    if (!$user->id())
    {
      $this->messenger()->addError($this->t('It is mandatory to login on this website to access the proposal form'));
      return;
    } //!$user->uid
    /* completion date to timestamp */
    list($d, $m, $y) = explode('-', $values['completion_date']);
    $completion_date_timestamp = mktime(0, 0, 0, $m, $d, $y);
    if ($values['version'] == 'Other version')
    {
      $values['version'] = $values['other_version'];
      $form_state->setValue('version', $values['version']);
    } //$values['version'] == 'Other version'
    if($values['branch'] == 'Others') {
      $values['branch'] = $values['other_branch'];
    }
    if($values['course'] == 'Others') {
      $values['course'] = $values['other_course'];
    }
    if ($values['country'] == 'Others')
    {
      $values['country'] = $values['other_country'];
      $values['all_state'] = $values['other_state'];
      $values['city'] = $values['other_city'];
      //$values['pincode'] = $values['other_pincode'];
    } //$values['country'] == 'other'
    if (!empty($values['reason'])) {
      $my_reason = implode(", ", array_filter($values['reason']));
      if (!empty($values['other_reason'])) {
        $my_reason = $my_reason . "-" . " " . $values['other_reason'];
      }
      $values['reason'] = $my_reason;
    }
    $actual_completion_date = 0;
    //isset($_POST['reason'])
    $proposal_fields = [
      'uid' => $user->id(),
      'approver_uid' => 0,
      'name_title' => $values['name_title'],
      'full_name' => trim(ucwords(strtolower($values['full_name']))),
      'mobile' => trim($values['mobile']),
      'how_project' => $values['how_project'],
      'course' => trim($values['course']),
      'branch' => trim($values['branch']),
      'university' => trim($values['university']),
      'city' => trim($values['city']),
      'pincode' => $values['pincode'],
      'state' => trim($values['all_state']),
      'country' => $values['country'],
      'faculty' => ucwords(strtolower($values['faculty'])),
      'reviewer' => $values['reviewer'],
      'reference' => trim($values['reference']),
      'completion_date' => $actual_completion_date,
      'creation_date' => time(),
      'approval_date' => 0,
      'proposal_status' => 0,
      'r_version' => trim($values['version']),
      'operating_system' => trim($values['operating_system']),
      'teacher_email' => $values['faculty_email'],
      'reason' => $values['reason'],
      'samplefilepath' => '',
      'proposal_type' => 0,
      'proposed_completion_date' => $completion_date_timestamp,
      'book_download_link' => $values['book_download_link'],
    ];

    $proposal_id = \Drupal::database()->insert('textbook_companion_proposal')
      ->fields($proposal_fields)
      ->execute();

    if (!$proposal_id)
    {
      $this->messenger()->addError($this->t('Error receiving your proposal. Please try again.'));
      return;
    }

    $dest_path = $proposal_id . '/';
    $destination_directory = $root_path . $dest_path;
    $file_system = \Drupal::service('file_system');
    if (!$file_system->prepareDirectory($destination_directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
      $this->messenger()->addError($this->t('Error preparing upload directory: @path', ['@path' => $dest_path]));
      return;
    }
    /* uploading files */
    $uploaded_files = \Drupal::request()->files->get('files', []);
    foreach ($uploaded_files as $file_form_name => $file) {
      if (!$file instanceof UploadedFile || $file->getClientOriginalName() === '') {
        continue;
      }
      $file_name = basename((string) $file->getClientOriginalName());
      if (file_exists($destination_directory . $file_name) && !unlink($destination_directory . $file_name)) {
        $this->messenger()->addError($this->t('Error replacing existing file: @path', ['@path' => $dest_path . $file_name]));
        continue;
      }
      try {
        $file->move($destination_directory, $file_name);
        $query = "UPDATE {textbook_companion_proposal} SET samplefilepath = :samplefilepath WHERE id = :id";
        $args = array(
          ":samplefilepath" => $dest_path . $file_name,
          ":id" => $proposal_id
        );
        \Drupal::database()->query($query, $args);
        $this->messenger()->addStatus($file_name . ' uploaded successfully.');
      }
      catch (\Exception $exception) {
        $this->messenger()->addError($this->t('Error uploading file: @path', ['@path' => $dest_path . $file_name]));
      }
    }
    /* inserting first book preference */
    if ($values['book1'])
    {
      $bk1 = trim($values['book1']);
      $auth1 = trim($values['author1']);
      $pref_id = NULL;
      $directory_name = _dir_name($bk1, $auth1, $pref_id);
      $query = "INSERT INTO {textbook_companion_preference}
      (proposal_id, pref_number, book, author, isbn, publisher, edition, year, category, approval_status, directory_name) VALUES (:proposal_id, :pref_number, :book, :author, :isbn, :publisher, :edition, :year, :category, :approval_status, :directory_name)
	";
      $args = array(
        ":proposal_id" => $proposal_id,
        ":pref_number" => 1,
        ":book" => trim(ucwords(strtolower($values['book1']))),
        ":author" => trim(ucwords(strtolower($values['author1']))),
        ":isbn" => trim($values['isbn1']),
        ":publisher" => trim(ucwords(strtolower($values['publisher1']))),
        ":edition" => trim($values['edition1']),
        ":year" => trim($values['year1']),
        ":category" => $values['category'],
        ":approval_status" => 0,
        ":directory_name" => $values['dir_name1']
      );
      $result = \Drupal::database()->query($query, $args);
      if (!$result)
      {
        $this->messenger()->addError($this->t('Error receiving your first book preference.'));
      } //!$result
    } //$values['book1']
    /* sending email */
    $user_entity = \Drupal::entityTypeManager()->getStorage('user')->load($user->id());
    $email_to = $user_entity ? $user_entity->getEmail() : '';
    $from = \Drupal::config('textbook_companion.settings')->get('textbook_companion_from_email');
    $bcc = \Drupal::config('textbook_companion.settings')->get('textbook_companion_emails');
    $cc = \Drupal::config('textbook_companion.settings')->get('textbook_companion_cc_emails');
    $params['proposal_received']['proposal_id'] = $proposal_id;
    $params['proposal_received']['user_id'] = $user->id();
    $params['proposal_received']['headers'] = array(
      'From' => $from,
      'MIME-Version' => '1.0',
      'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
      'Content-Transfer-Encoding' => '8Bit',
      'X-Mailer' => 'Drupal',
      'Cc' => $cc,
      'Bcc' => $bcc
    );
    $langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
    $result = \Drupal::service('plugin.manager.mail')->mail('textbook_companion', 'proposal_received', $email_to, $langcode, $params, $from, TRUE);
    if (!$result) {
      $this->messenger()->addMessage($this->t(' sending email message.'));
    }
    $this->messenger()->addStatus($this->t('We have received you book proposal. We will get back to you soon.'));
    $form_state->setRedirect('<front>');
  }




}
