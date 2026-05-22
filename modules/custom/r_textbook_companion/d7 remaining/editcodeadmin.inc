<?php
// $Id$
/******************************************************************************/
/***************************** EDIT EXAMPLE ***********************************/
/******************************************************************************/
function upload_examples_admin_edit_form($form, &$form_state)
  {
    $user = \Drupal::currentUser();
    $example_id = arg(3);
    /* get example details */
    /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE id = %d LIMIT 1", $example_id);
    $example_data = db_fetch_object($example_q);*/
    $query = \Drupal::database()->select('textbook_companion_example');
    $query->fields('textbook_companion_example');
    $query->condition('id', $example_id);
    $query->range(0, 1);
    $example_q = $query->execute();
    $example_data = $example_q->fetchObject();
    if (!$example_q)
      {
        \Drupal::messenger()->addError(t("Invalid example selected."));
        drupal_goto('');
        return;
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
    $query = \Drupal::database()->select('textbook_companion_example_files');
    $query->fields('textbook_companion_example_files');
    $query->condition('example_id', $example_id);
    $example_files_q = $query->execute();
    while ($example_files_data = $example_files_q->fetchObject())
      {
        if ($example_files_data->filetype == "S")
          {
            // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $source_file = l($example_files_data->filename, 'textbook-companion/download/file/' . $example_files_data->id);

            $source_file_id = $example_files_data->id;
          }
        else if ($example_files_data->filetype == "D")
          {
            // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $dataset_file = l($example_files_data->filename, 'textbook-companion/download/file/' . $example_files_data->id);

            $dataset_file_id = $example_files_data->id;
          }
      }
    /* get chapter details */
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d", $example_data->chapter_id);
    $chapter_data = db_fetch_object($chapter_q);*/
    $query = \Drupal::database()->select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('id', $example_data->chapter_id);
    $result = $query->execute();
    $chapter_data = $result->fetchObject();
    if (!$chapter_data)
      {
        \Drupal::messenger()->addError(t("Invalid chapter selected."));
        drupal_goto('');
        return;
      }
    /* get preference details */
    /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d", $chapter_data->preference_id);
    $preference_data = db_fetch_object($preference_q);*/
    $query = \Drupal::database()->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('id', $chapter_data->preference_id);
    $result = $query->execute();
    $preference_data = $result->fetchObject();
    if (!$preference_data)
      {
        \Drupal::messenger()->addError(t("Invalid book selected."));
        drupal_goto('');
        return;
      }
    /* get proposal details */
    /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE id = %d", $preference_data->proposal_id);
    $proposal_data = db_fetch_object($proposal_q);*/
    $query = \Drupal::database()->select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('id', $preference_data->proposal_id);
    $result = $query->execute();
    $proposal_data = $result->fetchObject();
    if (!$proposal_data)
      {
        \Drupal::messenger()->addError(t("Invalid proposal selected."));
        drupal_goto('');
        return;
      }
    $user_data = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->uid);
    $form['#redirect'] = 'code_approval/bulk';
    $form['#attributes'] = array(
        'enctype' => "multipart/form-data"
    );
    $form['book_details']['book'] = array(
        '#type' => 'item',
        '#markup' => $preference_data->book,
        '#title' => t('Title of the Book')
    );
    $form['contributor_name'] = array(
        '#type' => 'item',
        '#markup' => $proposal_data->full_name,
        '#title' => t('Contributor Name')
    );
    $form['number'] = array(
        '#type' => 'item',
        '#title' => t('Chapter No'),
        '#markup' => $chapter_data->number
    );
    $form['name'] = array(
        '#type' => 'item',
        '#title' => t('Title of the Chapter'),
        '#markup' => $chapter_data->name
    );
    $form['example_number'] = array(
        '#type' => 'item',
        '#title' => t('Example No'),
        '#markup' => $example_data->number
    );
    $form['example_caption'] = array(
        '#type' => 'textfield',
        '#title' => t('Caption'),
        '#size' => 40,
        '#maxlength' => 255,
        '#required' => TRUE,
        '#default_value' => $example_data->caption
    );
    $form['example_warning'] = array(
        '#type' => 'item',
        '#title' => t('You should upload all the files (main or source files, result files, executable file if any)'),
        '#prefix' => '<div style="color:red">',
        '#suffix' => '</div>'
    );
    $form['sourcefile'] = array(
        '#type' => 'fieldset',
        '#title' => t('Main or Source Files'),
        '#collapsible' => FALSE,
        '#collapsed' => FALSE
    );
    if ($source_file)
      {
        $form['sourcefile']['cur_source'] = array(
            '#type' => 'item',
            '#title' => t('Existing Main or Source File'),
            '#markup' => $source_file
        );
        $form['sourcefile']['cur_source_checkbox'] = array(
            '#type' => 'checkbox',
            '#title' => t('Delete Existing Main or Source File'),
            '#description' => 'Check to delete the existing Main or Source file.'
        );
        $form['sourcefile']['sourcefile1'] = array(
            '#type' => 'file',
            '#title' => t('Upload New Main or Source File'),
            '#size' => 48,
            '#description' => t("Upload new Main or Source file above if you want to replace the existing file. Leave blank if you want to keep using the existing file. <br />") . t('Allowed file extensions : ') . \Drupal::config('textbook_companion.settings')->get('textbook_companion_source_extensions')
        );
        $form['sourcefile']['cur_source_file_id'] = array(
            '#type' => 'hidden',
            '#default_value' => $source_file_id
        );
      }
    else
      {
        $form['sourcefile']['sourcefile1'] = array(
            '#type' => 'file',
            '#title' => t('Upload New Main or Source File'),
            '#size' => 48,
            '#description' => t('Allowed file extensions : ') . \Drupal::config('textbook_companion.settings')->get('textbook_companion_source_extensions')
        );
      }
    $form['dataset'] = array(
        '#type' => 'fieldset',
        '#title' => t('Dataset Files'),
        '#collapsible' => FALSE,
        '#collapsed' => FALSE
    );
    if ($dataset_file)
      {
        $form['dataset']['cur_dataset'] = array(
            '#type' => 'item',
            '#title' => t('Existing Dataset File'),
            '#markup' => $dataset_file
        );
        $form['dataset']['cur_dataset_checkbox'] = array(
            '#type' => 'checkbox',
            '#title' => t('Delete Existing Dataset File'),
            '#description' => 'Check to delete the existing Dataset file.',
            '#attributes' => array(
                            'onClick' => 'return confirm("Are you sure you want to delete the example?")'
                        )
        );
        $form['dataset']['upload_dataset'] = array(
            '#type' => 'file',
            '#title' => t('Upload New Dataset File'),
            '#size' => 48,
            '#description' => t("Upload new Dataset file above if you want to replace the existing file. Leave blank if you want to keep using the existing file. <br />") . t('Allowed file extensions : ') . \Drupal::config('textbook_companion.settings')->get('textbook_companion_dataset_extensions')
        );
        $form['dataset']['cur_dataset_file_id'] = array(
            '#type' => 'hidden',
            '#value' => $dataset_file_id
        );
      }
    else
      {
        $form['dataset']['upload_dataset'] = array(
            '#type' => 'file',
            '#title' => t('Upload New Dataset File'),
            '#size' => 48,
            '#description' => t('Allowed file extensions : ') . \Drupal::config('textbook_companion.settings')->get('textbook_companion_dataset_extensions')
        );
      }
    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit')
    );
    // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $form['cancel'] = array(
//         '#type' => 'markup',
//         '#value' => l(t('Cancel'), 'textbook-companion/code')
//     );

    return $form;
  }
function upload_examples_admin_edit_form_validate($form, &$form_state)
  {
    if (!check_name($form_state['values']['example_caption']))
        form_set_error('example_caption', t('Example Caption can contain only alphabets, numbers and spaces.'));
    if (isset($_FILES['files']))
      {
        /* check for valid filename extensions */
        foreach ($_FILES['files']['name'] as $file_form_name => $file_name)
          {
            if ($file_name)
              {
                /* checking file type */
                if (strstr($file_form_name, 'sourcefile1'))
                    $file_type = 'S';
                else if (strstr($file_form_name, 'upload_dataset'))
                    $file_type = 'D';
                $allowed_extensions_str = '';
                switch ($file_type)
                {
                    case 'S':
                        $allowed_extensions_str = \Drupal::config('textbook_companion.settings')->get('textbook_companion_source_extensions');
                        break;
                    case 'D':
                        $allowed_extensions_str = \Drupal::config('textbook_companion.settings')->get('textbook_companion_dataset_extensions');
                        break;
                }
                $allowed_extensions = explode(',', $allowed_extensions_str);
                $temp_ext = explode('.', strtolower($_FILES['files']['name'][$file_form_name]));
                $temp_extension = end($temp_ext);
                if (!in_array($temp_extension, $allowed_extensions))
                    form_set_error($file_form_name, t('Only file with ' . $allowed_extensions_str . ' extensions can be uploaded.'));
                if ($_FILES['files']['size'][$file_form_name] <= 0)
                    form_set_error($file_form_name, t('File size cannot be zero.'));
                /* check if valid file name */
                if (!textbook_companion_check_valid_filename($_FILES['files']['name'][$file_form_name]))
                    form_set_error($file_form_name, t('Invalid file name specified. Only alphabets, numbers and underscore is allowed as a valid filename.'));
              }
          }
      }
  }
function upload_examples_admin_edit_form_submit($form, &$form_state)
  {
    $user = \Drupal::currentUser();
    $example_id = arg(3);
    /* get example details */
    /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE id = %d LIMIT 1", $example_id);
    $example_data = db_fetch_object($example_q);*/
    $query = \Drupal::database()->select('textbook_companion_example');
    $query->fields('textbook_companion_example');
    $query->condition('id', $example_id);
    $query->range(0, 1);
    $example_q = $query->execute();
    $example_data = $example_q->fetchObject();
    if (!$example_q)
      {
        \Drupal::messenger()->addError(t("Invalid example selected."));
        drupal_goto('');
        return;
      }
    /* get chapter details */
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d", $example_data->chapter_id);
    $chapter_data = db_fetch_object($chapter_q);*/
    $query = \Drupal::database()->select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('id', $example_data->chapter_id);
    $chapter_q = $query->execute();
    $chapter_data = $chapter_q->fetchObject();
    if (!$chapter_data)
      {
        \Drupal::messenger()->addError(t("Invalid chapter selected."));
        drupal_goto('');
        return;
      }
    /* get preference details */
    /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d", $chapter_data->preference_id);
    $preference_data = db_fetch_object($preference_q);*/
    $query = \Drupal::database()->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('id', $chapter_data->preference_id);
    $result = $query->execute();
    $preference_data = $result->fetchObject();
    if (!$preference_data)
      {
        \Drupal::messenger()->addError(t("Invalid book selected."));
        drupal_goto('');
        return;
      }
    /* get proposal details */
    /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE id = %d", $preference_data->proposal_id);
    $proposal_data = db_fetch_object($proposal_q);*/
    $query = \Drupal::database()->select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('id', $preference_data->proposal_id);
    $result = $query->execute();
    $proposal_data = $result->fetchObject();
    if (!$proposal_data)
      {
        \Drupal::messenger()->addError(t("Invalid proposal selected."));
        drupal_goto('');
        return;
      }
    $user_data = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->uid);
    /* creating directories */
    $root_path = textbook_companion_path();
    $dest_path = $preference_data->directory_name . '/';
    if (!is_dir($root_path . $dest_path))
        mkdir($root_path . $dest_path);
    $dest_path .= 'CH' . $chapter_data->number . '/';
    if (!is_dir($root_path . $dest_path))
        mkdir($root_path . $dest_path);
    $dest_path .= 'EX' . $example_data->number . '/';
    if (!is_dir($root_path . $dest_path))
        mkdir($root_path . $dest_path);
    $filepath = 'CH' . $chapter_data->number . '/' . 'EX' . $example_data->number . '/';
    /* updating example caption */
    /*db_query("UPDATE {textbook_companion_example} SET caption = '%s' WHERE id = %d", $form_state['values']['example_caption'], $example_id);*/
    $query = \Drupal::database()->update('textbook_companion_example');
    $query->fields(array(
        'caption' => $form_state['values']['example_caption']
    ));
    $query->condition('id', $example_id);
    $num_updated = $query->execute();
    /* handle source file */
    if (isset($form_state['values']['cur_source_file_id']) || isset($form_state['values']['cur_dataset_file_id']))
      {
        $cur_file_id = $form_state['values']['cur_source_file_id'];
        $cur_dataset_id = $form_state['values']['cur_dataset_file_id'];
      }
    else
      {
        $cur_file_id = isset($form_state['values']['cur_source_file_id']);
         $cur_dataset_id = isset($form_state['values']['cur_dataset_file_id']);
      }
    //var_dump($cur_file_id);die;
    if ($cur_file_id > 0)
      {
        /*$file_q = db_query("SELECT * FROM  {textbook_companion_example_files} WHERE id = %d AND example_id = %d", $cur_file_id, $example_data->id);
        $file_data = db_fetch_object($file_q);*/
        //var_dump($cur_file_id. $example_data->id);die;
        $query = \Drupal::database()->select('textbook_companion_example_files');
        $query->fields('textbook_companion_example_files');
        $query->condition('id', $cur_file_id);
        $query->condition('example_id', $example_data->id);
        $result = $query->execute();
        $file_data = $result->fetchObject();
        if (!$file_data)
          {
            \Drupal::messenger()->addError("Error deleting example source file. File not present in database.");
            return;
          }
        if (($form_state['values']['cur_source_checkbox'] == 1) && (!$_FILES['files']['name']['sourcefile1']))
          {
            if (!delete_file($cur_file_id))
              {
                \Drupal::messenger()->addError("Error deleting example source file.");
                return;
              }
          }
      }
    if ($cur_dataset_id > 0)
      {
        $query = \Drupal::database()->select('textbook_companion_example_files');
        $query->fields('textbook_companion_example_files');
        $query->condition('id', $cur_dataset_id);
        $query->condition('example_id', $example_data->id);
        $result = $query->execute();
        $file_data = $result->fetchObject();
        if (!$file_data)
          {
            \Drupal::messenger()->addError("Error deleting example dataset file. File not present in database.");
            return;
          }
        if (($form_state['values']['cur_dataset_checkbox'] == 1) && (!$_FILES['files']['name']['upload_dataset']))
          {
            if (!delete_file($cur_dataset_id))
              {
                \Drupal::messenger()->addError("Error deleting example dataset file.");
                return;
              }
          }
      }
    if ($_FILES['files']['name']['sourcefile1'])
      {
        if ($cur_file_id > 0)
          {
            if (!delete_file($cur_file_id))
              {
                \Drupal::messenger()->addError("Error removing previous example source file.");
                return;
              }
          }
        }
    else if($_FILES['files']['name']['upload_dataset'])
      {
        if ($cur_dataset_id > 0)
          {
            if (!delete_file($cur_dataset_id))
              {
                \Drupal::messenger()->addError("Error removing previous dataset file.");
                return;
              }
          }
      }
      foreach ($_FILES['files']['name'] as $file_form_name => $file_name)
      {
        if ($file_name)
          {
            /* checking file type */
            if (strstr($file_form_name, 'sourcefile1'))
            {
                $file_type = 'S';
            } //strstr($file_form_name, 'upload_flowsheet_developed_process')
            else if (strstr($file_form_name, 'upload_dataset'))
            {
                $file_type = 'D';
            }

            //$file_type = 'S';
            switch ($file_type) {
                case 'S':
                    if (file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]))
                      {
                        \Drupal::messenger()->addError(t("Error uploading file. File !filename already exists.", array(
                            '!filename' => $_FILES['files']['name'][$file_form_name]
                        )));
                        return;
                      }
                    /* uploading file */
                    if (move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name]))
                      {
                        $query = "INSERT INTO {textbook_companion_example_files} (example_id, filename, filepath,filemime, filesize, filetype, timestamp)
                  VALUES (:example_id, :filename ,:filepath,:filemime, :filesize, :filetype, :timestamp)";
                        $args = array(
                            ":example_id" => $example_id,
                            ":filename" => $_FILES['files']['name'][$file_form_name],
                            ":filepath" => $filepath . $_FILES['files']['name'][$file_form_name],
                            ":filemime" => 'application/R',
                            ":filesize" => $_FILES['files']['size'][$file_form_name],
                            ":filetype" => $file_type,
                            ":timestamp" => time()
                        );
                        $result = \Drupal::database()->query($query, $args, $query);
                        \Drupal::messenger()->addStatus($file_name . ' uploaded successfully.');
                      }
                    else
                      {
                        \Drupal::messenger()->addError('Error uploading file : ' . $dest_path . '/' . $file_name);
                      }
                      break;
                case 'D':
                    if (file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]))
                      {
                        \Drupal::messenger()->addError(t("Error uploading file. File !filename already exists.", array(
                            '!filename' => $_FILES['files']['name'][$file_form_name]
                        )));
                        return;
                      }
                    /* uploading file */
                    if (move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name]))
                      {
                        $query = "INSERT INTO {textbook_companion_example_files} (example_id, filename, filepath,filemime, filesize, filetype, timestamp)
                  VALUES (:example_id, :filename ,:filepath,:filemime, :filesize, :filetype, :timestamp)";
                        $args = array(
                            ":example_id" => $example_id,
                            ":filename" => $_FILES['files']['name'][$file_form_name],
                            ":filepath" => $filepath . $_FILES['files']['name'][$file_form_name],
                            ":filemime" => 'application/csv',
                            ":filesize" => $_FILES['files']['size'][$file_form_name],
                            ":filetype" => $file_type,
                            ":timestamp" => time()
                        );
                        $result = \Drupal::database()->query($query, $args, $query);
                        \Drupal::messenger()->addStatus($file_name . ' uploaded successfully.');
                      }
                    else
                      {
                        \Drupal::messenger()->addError('Error uploading file : ' . $dest_path . '/' . $file_name);
                      }
                      break;
            }
          }
      }
  
    \Drupal::messenger()->addStatus('Example uploaded successfully.');
    /* sending email */
    $email_to = $user_data->mail;
    $param['example_updated_admin']['example_id'] = $example_id;
    $param['example_updated_admin']['user_id'] = $proposal_data->uid;
    if (!drupal_mail('textbook_companion', 'example_updated_admin', $email_to, language_default(), $param, \Drupal::config('textbook_companion.settings')->get('textbook_companion_from_email'), TRUE))
        \Drupal::messenger()->addError('Error sending email message.');
    \Drupal::messenger()->addStatus(t("Example successfully udpated."));
  }
/******************************************************************************/
/************************** GENERAL FUNCTIONS *********************************/
/******************************************************************************/
function _list_of_book_titles()
  {
    $book_titles = array(
        '0' => 'Please select...'
    );
    /*$book_titles_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE approval_status = 1 OR approval_status = 3 ORDER BY book ASC");*/
    $query = \Drupal::database()->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $or = db_or();
    $or->condition('approval_status', 1);
    $or->condition('approval_status', 3);
    $query->condition($or);
    $query->orderBy('book', 'ASC');
    $book_titles_q = $query->execute();
    while ($book_titles_data = $book_titles_q->fetchObject())
      {
        $book_titles[$book_titles_data->id] = $book_titles_data->book . ' (Written by ' . $book_titles_data->author . ')';
      }
    return $book_titles;
  }
function _list_of_book_dependency_files()
  {
    $book_dependency_files = array();
    $book_dependency_files_class = array();
    /*$book_dependency_files_q = db_query("SELECT * FROM {textbook_companion_dependency_files} ORDER BY filename ASC");*/
    $query = \Drupal::database()->select('textbook_companion_dependency_files');
    $query->fields('textbook_companion_dependency_files');
    $query->orderBy('filename', 'ASC');
    $book_dependency_files_q = $query->execute();
    while ($book_dependency_files_data = $book_dependency_files_q->fetchObject())
      {
        $temp_caption = '';
        if ($book_dependency_files_data->caption)
            $temp_caption .= ' (' . $book_dependency_files_data->caption . ')';
        // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $book_dependency_files[$book_dependency_files_data->id] = l($book_dependency_files_data->filename . $temp_caption, '/textbook-companion/download/dependency/' . $book_dependency_files_data->id);

        $book_dependency_files_class[$book_dependency_files_data->id] = $book_dependency_files_data->preference_id;
      }
    return array(
        $book_dependency_files,
        $book_dependency_files_class
    );
  }
