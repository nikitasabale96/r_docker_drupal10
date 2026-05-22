<?php
// $Id$
function code_approval()
{
    /* get a list of unapproved chapters */
    /*$pending_chapter_q = db_query("SELECT c.id as c_id, c.number as c_number, c.name as c_name, c.preference_id as c_preference_id FROM {textbook_companion_example} as e JOIN {textbook_companion_chapter} as c ON c.id = e.chapter_id WHERE e.approval_status = 0");*/
    $query = \Drupal::database()->select('textbook_companion_example', 'e');
    $query->fields('c', array(
        'id',
        'number',
        'name',
        'preference_id'
    ));
    $query->addField('c', 'id', 'c_id');
    $query->addField('c', 'number', 'c_number');
    $query->addField('c', 'name', 'c_name');
    $query->addField('c', 'preference_id', 'c_preference_id');
    $query->innerJoin('textbook_companion_chapter', 'c', 'c.id = e.chapter_id');
    $query->condition('e.approval_status', 0);
    $pending_chapter_q = $query->execute();
    if (!$pending_chapter_q) {
        \Drupal::messenger()->addStatus(t('There are no pending code approvals.'));
        return '';
    }
    $rows = array();
    while ($row = $pending_chapter_q->fetchObject()) {
        /* get preference data */
        /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d", $row->c_preference_id);
        $preference_data = db_fetch_object($preference_q);*/
        $query = \Drupal::database()->select('textbook_companion_preference');
        $query->fields('textbook_companion_preference');
        $query->condition('id', $row->c_preference_id);
        $query->condition('approved_codable_example_files', 1);
        $query->condition('submitted_codable_examples_file', 1);
        $query->condition('submited_all_examples_code', 1);
        $result = $query->execute();
        $preference_data = $result->fetchObject();
        /* get proposal data */
        /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE id = %d", $preference_data->proposal_id);
        $proposal_data = db_fetch_object($proposal_q);*/
        if(!$preference_data){
           //drupal_set_message('No data found.', 'status');
            //drupal_goto('textbook-companion/code-approval');
        //return;
        }
        else{
        $query = \Drupal::database()->select('textbook_companion_proposal');
        $query->fields('textbook_companion_proposal');
        $query->condition('id', $preference_data->proposal_id);
        $result = $query->execute();
        $proposal_data = $result->fetchObject();
        /* setting table row information */
        // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $rows[] = array(
//             $preference_data->book,
//             $row->c_number,
//             $row->c_name,
//             $proposal_data->full_name,
//             l('Edit', 'textbook-companion/code-approval/approve/' . $row->c_id)
//         );

    }
}
    /* check if there are any pending proposals */
    if (!$rows) {
        \Drupal::messenger()->addStatus(t('There are no pending proposals'));
        return '';
    }
    $header = array(
        'Title of the Book',
        'Chapter Number',
        'Title of the Chapter',
        'Contributor Name',
        'Actions'
    );
    // @FIXME
// theme() has been renamed to _theme() and should NEVER be called directly.
// Calling _theme() directly can alter the expected output and potentially
// introduce security issues (see https://www.drupal.org/node/2195739). You
// should use renderable arrays instead.
// 
// 
// @see https://www.drupal.org/node/2195739
// $output = theme('table', array(
//         'header' => $header,
//         'rows' => $rows
//     ));

    return $output;
}
function code_approval_form($form, &$form_state)
{
    /* get a list of unapproved chapters */
    $chapter_id = arg(3);
    /*$pending_chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d", $chapter_id);*/
    $query = \Drupal::database()->select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('id', $chapter_id);
    $pending_chapter_q = $query->execute();
    if ($pending_chapter_data = $pending_chapter_q->fetchObject()) {
        /* get preference data */
        /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d", $pending_chapter_data->preference_id);
        $preference_data = db_fetch_object($preference_q);*/
        $query = \Drupal::database()->select('textbook_companion_preference');
        $query->fields('textbook_companion_preference');
        $query->condition('id', $pending_chapter_data->preference_id);
        $result = $query->execute();
        $preference_data = $result->fetchObject();
        /* get proposal data */
        /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE id = %d", $preference_data->proposal_id);
        $proposal_data = db_fetch_object($proposal_q);*/
        $query = \Drupal::database()->select('textbook_companion_proposal');
        $query->fields('textbook_companion_proposal');
        $query->condition('id', $preference_data->proposal_id);
        $result = $query->execute();
        $proposal_data = $result->fetchObject();
    } else {
        \Drupal::messenger()->addError(t('Invalid chapter selected.'));
        //drupal_goto('textbook-companion/code-approval');
        return;
    }
    $form['#tree'] = TRUE;
    $form['contributor'] = array(
        '#type' => 'item',
        '#markup' => $proposal_data->full_name,
        '#title' => t('Contributor Name')
    );
    $form['book_details']['book'] = array(
        '#type' => 'item',
        '#markup' => $preference_data->book,
        '#title' => t('Title of the Book')
    );
    $form['book_details']['number'] = array(
        '#type' => 'item',
        '#markup' => $pending_chapter_data->number,
        '#title' => t('Chapter Number')
    );
    $form['book_details']['name'] = array(
        '#type' => 'item',
        '#markup' => $pending_chapter_data->name,
        '#title' => t('Title of the Chapter')
    );
    // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $form['book_details']['back_to_list'] = array(
//         '#type' => 'item',
//         '#markup' => l('Back to Code Approval List', 'textbook-companion/code-approval')
//     );

    /* get example data */
    /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND approval_status = 0", $chapter_id);*/
    $query = \Drupal::database()->select('textbook_companion_example');
    $query->fields('textbook_companion_example');
    $query->condition('chapter_id', $chapter_id);
    $query->condition('approval_status', 0);
    $example_q = $query->execute();
    while ($example_data = $example_q->fetchObject()) {
        $form['example_details'][$example_data->id] = array(
            '#type' => 'fieldset',
            '#collapsible' => FALSE,
            '#collapsed' => TRUE
        );
        $form['example_details'][$example_data->id]['example_number'] = array(
            '#type' => 'item',
            '#markup' => $example_data->number,
            '#title' => t('Example Number')
        );
        $form['example_details'][$example_data->id]['example_caption'] = array(
            '#type' => 'item',
            '#markup' => $example_data->caption,
            '#title' => t('Example Caption')
        );
        // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $form['example_details'][$example_data->id]['download'] = array(
//             '#type' => 'markup',
//             '#markup' => l('Download Example', 'textbook-companion/download/example/' . $example_data->id)
//         );

        $form['example_details'][$example_data->id]['approved'] = array(
            '#type' => 'radios',
            '#options' => array(
                'Approved',
                'Dis-approved'
            )
        );
        $form['example_details'][$example_data->id]['message'] = array(
            '#type' => 'textarea',
            '#size' => 500,
            '#maxlength' => 500,
            '#title' => t('Reason for dis-approval')
        );
        $form['example_details'][$example_data->id]['example_id'] = array(
            '#type' => 'hidden',
            '#value' => $example_data->id
        );
    }
    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit')
    );
    return $form;
}
function code_approval_form_submit($form, &$form_state)
{
    $user = \Drupal::currentUser();
    foreach ($form_state['values']['example_details'] as $ex_id => $ex_data) {
        /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE id = %d LIMIT 1", $ex_data['example_id']);
        $example_data = db_fetch_object($example_q);*/
        $query = \Drupal::database()->select('textbook_companion_example');
        $query->fields('textbook_companion_example');
        $query->condition('id', $ex_data['example_id']);
        $query->range(0, 1);
        $result = $query->execute();
        $example_data = $result->fetchObject();
        /*$chapter_data = db_fetch_object(db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d LIMIT 1", $example_data->chapter_id));*/
        $query = \Drupal::database()->select('textbook_companion_chapter');
        $query->fields('textbook_companion_chapter');
        $query->condition('id', $example_data->chapter_id);
        $query->range(0, 1);
        $result = $query->execute();
        $chapter_data = $result->fetchObject();
        /*$preference_data = db_fetch_object(db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d LIMIT 1", $chapter_data->preference_id));*/
        $query = \Drupal::database()->select('textbook_companion_preference');
        $query->fields('textbook_companion_preference');
        $query->condition('id', $chapter_data->preference_id);
        $query->range(0, 1);
        $result = $query->execute();
        $preference_data = $result->fetchObject();
        /*$proposal_data = db_fetch_object(db_query("SELECT * FROM {textbook_companion_proposal} WHERE id = %d LIMIT 1", $preference_data->proposal_id));*/
        $query = \Drupal::database()->select('textbook_companion_proposal');
        $query->fields('textbook_companion_proposal');
        $query->condition('id', $preference_data->proposal_id);
        $query->range(0, 1);
        $result = $query->execute();
        $proposal_data = $result->fetchObject();
        $user_data = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->uid);
        del_book_pdf($preference_data->id);
        if ($ex_data['approved'] == "0") {
            /*db_query("UPDATE {textbook_companion_example} SET approval_status = 1, approver_uid = %d, approval_date = %d WHERE id = %d", $user->uid, time(), $ex_data['example_id']);*/
            $query = \Drupal::database()->update('textbook_companion_example');
            $query->fields(array(
                'approval_status' => 1,
                'approver_uid' => $user->uid,
                'approval_date' => time()
            ));
            $query->condition('id', $ex_data['example_id']);
            $num_updated = $query->execute();
            /* sending email */
            $email_to = $user_data->mail;
            $from = \Drupal::config('textbook_companion.settings')->get('textbook_companion_from_email');
            $bcc = \Drupal::config('textbook_companion.settings')->get('textbook_companion_emails');
            $cc = \Drupal::config('textbook_companion.settings')->get('textbook_companion_cc_emails');
            $param['example_approved']['example_id'] = $ex_data['example_id'];
            $param['example_approved']['user_id'] = $user_data->uid;
            $param['example_approved']['headers'] = array(
                'From' => $from,
                'MIME-Version' => '1.0',
                'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
                'Content-Transfer-Encoding' => '8Bit',
                'X-Mailer' => 'Drupal',
                'Cc' => $cc,
                'Bcc' => $bcc
            );
            if (!drupal_mail('textbook_companion', 'example_approved', $email_to, language_default(), $param, $from, TRUE))
                \Drupal::messenger()->addError('Error sending email message.');
        } else if ($ex_data['approved'] == "1") {
            if (delete_example($ex_data['example_id'])) {
                /* sending email */
                $email_to = $user_data->mail;
                $from = \Drupal::config('textbook_companion.settings')->get('textbook_companion_from_email');
                $bcc = \Drupal::config('textbook_companion.settings')->get('textbook_companion_emails');
                $cc = \Drupal::config('textbook_companion.settings')->get('textbook_companion_cc_emails');
                $param['example_disapproved']['preference_id'] = $chapter_data->preference_id;
                $param['example_disapproved']['chapter_id'] = $example_data->chapter_id;
                $param['example_disapproved']['example_number'] = $example_data->number;
                $param['example_disapproved']['example_caption'] = $example_data->caption;
                $param['example_disapproved']['user_id'] = $user_data->uid;
                $param['example_disapproved']['message'] = $ex_data['message'];
                $param['example_disapproved']['headers'] = array(
                    'From' => $from,
                    'MIME-Version' => '1.0',
                    'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
                    'Content-Transfer-Encoding' => '8Bit',
                    'X-Mailer' => 'Drupal',
                    'Cc' => $cc,
                    'Bcc' => $bcc
                );
                if (!drupal_mail('textbook_companion', 'example_disapproved', $email_to, language_default(), $param, $from, TRUE))
                    \Drupal::messenger()->addError('Error sending email message.');
            } else {
                \Drupal::messenger()->addError('Error disapproving and deleting example. Please contact administrator.');
            }
        }
    }
    \Drupal::messenger()->addStatus('Updated successfully.');
    drupal_goto('textbook-companion/code-approval');
}
/******************************************************************************/
/********************************* BULK APPROVAL ******************************/
/******************************************************************************/
function bulk_approval_form($form, &$form_state)
{
    $options_first = _bulk_list_of_books();
    $options_two = _ajax_bulk_get_chapter_list();
    $selected = isset($form_state['values']['book']) ? $form_state['values']['book'] : key($options_first);
    $select_two = isset($form_state['values']['chapter']) ? $form_state['values']['chapter'] : key($options_two);
    $form['book'] = array(
        '#type' => 'select',
        '#title' => t('Title of the Book'),
        '#options' => _bulk_list_of_books(),
        '#default_value' => $selected,
        '#tree' => TRUE,
        '#ajax' => array(
            'callback' => 'ajax_bulk_chapter_list_callback'
        ),
        '#validated' => TRUE
    );
    $form['download_book'] = array(
        '#type' => 'item',
        '#markup' => '<div id="ajax_selected_book"></div>'
    );
    $form['download_pdf'] = array(
    '#type' => 'item',
    '#markup' => '<div id="ajax_selected_book_pdf"></div>',
    );
    $form['regenrate_book'] = array(
    '#type' => 'item',
    '#markup' => '<div id="ajax_selected_book_regenerate_pdf"></div>',
    );
    $form['notes_book'] = array(
        '#type' => 'item',
        '#markup' => '<div id="ajax_selected_book_notes"></div>'
    );
    $form['book_actions'] = array(
        '#type' => 'select',
        '#title' => t('Please select action for selected book'),
        '#options' => _bulk_list_book_actions(),
        //'#default_value' => isset($form_state['values']['lab_actions']) ? $form_state['values']['lab_actions'] : 0,
        '#prefix' => '<div id="ajax_selected_book_action" style="color:red;">',
        '#suffix' => '</div>',
        '#states' => array(
            'invisible' => array(
                ':input[name="book"]' => array(
                    'value' => 0
                )
            )
        ),
        '#validated' => TRUE
    );
    $form['chapter'] = array(
        '#type' => 'select',
        '#title' => t('Title of the Chapter'),
        '#options' => _ajax_bulk_get_chapter_list($selected),
        //'#default_value' => $chapter_default_value,
        '#prefix' => '<div id="ajax_select_chapter_list">',
        '#suffix' => '</div>',
        '#validated' => TRUE,
        '#tree' => TRUE,
        '#ajax' => array(
            'callback' => 'ajax_bulk_example_list_callback'
        ),
        '#states' => array(
            'invisible' => array(
                ':input[name="book"]' => array(
                    'value' => 0
                )
            )
        )
    );
    $form['download_chapter'] = array(
        '#type' => 'item',
        '#markup' => '<div id="ajax_download_chapter"></div>'
    );
    $form['chapter_actions'] = array(
        '#type' => 'select',
        '#title' => t('Please select action for selected chapter'),
        '#options' => _bulk_list_chapter_actions(),
        //'#default_value' => isset($form_state['values']['lab_actions']) ? $form_state['values']['lab_actions'] : 0,
        '#prefix' => '<div id="ajax_selected_chapter_action" style="color:red;">',
        '#suffix' => '</div>',
        '#states' => array(
            'invisible' => array(
                ':input[name="book"]' => array(
                    'value' => 0
                )
            )
        ),
        '#ajax' => array(
            'callback' => 'ajax_bulk_chapter_actions_callback'
        )
    );
    $form['example'] = array(
        '#type' => 'select',
        '#title' => t('Example No. (Caption)'),
        '#options' => _ajax_bulk_get_examples(),
        // '#default_value' => $example_default_value,       
        '#validated' => TRUE,
        '#prefix' => '<div id="ajax_selected_example">',
        '#suffix' => '</div>',
        '#states' => array(
            'invisible' => array(
                ':input[name="book"]' => array(
                    'value' => 0
                )
            )
        ),
        '#ajax' => array(
            'callback' => 'ajax_bulk_example_files_callback'
        )
    );
    $form['download_example'] = array(
        '#type' => 'item',
        '#markup' => '<div id="ajax_download_selected_example"></div>'
    );
    $form['edit_example'] = array(
        '#type' => 'item',
        '#markup' => '<div id="ajax_edit_selected_example"></div>'
    );
    $form['example_files'] = array(
        '#type' => 'item',
        '#markup' => '',
        '#prefix' => '<div id="ajax_example_files_list">',
        '#suffix' => '</div>'
    );
    $form['example_actions'] = array(
        '#type' => 'select',
        '#title' => t('Please select action for selected example'),
        '#options' => _bulk_list_example_actions(),
        //'#default_value' => isset($form_state['values']['lab_actions']) ? $form_state['values']['lab_actions'] : 0,
        '#prefix' => '<div id="ajax_selected_example_action" style="color:red;">',
        '#suffix' => '</div>',
        '#states' => array(
            'invisible' => array(
                ':input[name="book"]' => array(
                    'value' => 0
                )
            )
        )
    );
    $form['message'] = array(
        '#type' => 'textarea',
        '#title' => t('If Dis-Approved please specify reason for Dis-Approval'),
        '#states' => array(
            'visible' => array(
                array(
                    array(
                        ':input[name="book_actions"]' => array(
                            'value' => 3
                        )
                    ),
                    'or',
                    array(
                        ':input[name="chapter_actions"]' => array(
                            'value' => 3
                        )
                    ),
                    'or',
                    array(
                        ':input[name="example_actions"]' => array(
                            'value' => 3
                        )
                    ),
                    'or',
                    array(
                        ':input[name="book_actions"]' => array(
                            'value' => 4
                        )
                    )
                )
            ),
            'required' => array(
                array(
                    array(
                        ':input[name="book_actions"]' => array(
                            'value' => 3
                        )
                    ),
                    'or',
                    array(
                        ':input[name="chapter_actions"]' => array(
                            'value' => 3
                        )
                    ),
                    'or',
                    array(
                        ':input[name="example_actions"]' => array(
                            'value' => 3
                        )
                    ),
                    'or',
                    array(
                        ':input[name="book_actions"]' => array(
                            'value' => 4
                        )
                    )
                )
            )
        )
    );
    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit'),
        '#states' => array(
            'invisible' => array(
                ':input[name="book"]' => array(
                    'value' => 0
                )
            )
        )
    );
    return $form;
}
function bulk_approval_form_submit($form, &$form_state)
{
    $user = \Drupal::currentUser();
    $root_path = textbook_companion_path();
    if ($form_state['clicked_button']['#value'] == 'Submit') {
        if ($form_state['values']['book'])
            del_book_pdf($form_state['values']['book']);
        if (\Drupal::currentUser()->hasPermission('bulk manage code')) {
            $query = \Drupal::database()->select('textbook_companion_preference');
            $query->fields('textbook_companion_preference');
            $query->condition('id', $form_state['values']['book']);
            $result = $query->execute();
            $pref_data = $result->fetchObject();
            $prop_id = $pref_data->proposal_id;
            $query = \Drupal::database()->select('textbook_companion_proposal');
            $query->fields('textbook_companion_proposal');
            $query->condition('id', $prop_id);
            $user_query = $query->execute();
            $user_info = $user_query->fetchObject();
            $user_data = \Drupal::entityTypeManager()->getStorage('user')->load($user_info->uid);
            if (($form_state['values']['book_actions'] == 1) && ($form_state['values']['chapter_actions'] == 0) && ($form_state['values']['example_actions'] == 0)) {
                /* approving entire book */
                /* $chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE preference_id = %d", 
                $form_state['values']['book']);*/
                $query = \Drupal::database()->select('textbook_companion_preference');
                $query->fields('textbook_companion_preference');
                $query->condition('id', $form_state['values']['book']);
                $query->condition('approval_status', 1);
                $result = $query->execute();
                $preference_data = $result->fetchObject();
                $query = \Drupal::database()->select('textbook_companion_chapter');
                $query->fields('textbook_companion_chapter');
                $query->condition('preference_id', $form_state['values']['book']);
                $chapter_q = $query->execute();
                while ($chapter_data = $chapter_q->fetchObject()) {
                    /*db_query("UPDATE {textbook_companion_example} SET approval_status = 1, approver_uid = %d WHERE chapter_id = %d AND approval_status = 0", $user->uid, $chapter_data->id);*/
                    $query = \Drupal::database()->update('textbook_companion_example');
                    $query->fields(array(
                        'approval_status' => 1,
                        'approver_uid' => $user->uid
                    ));
                    $query->condition('chapter_id', $chapter_data->id);
                    $query->condition('approval_status', 0);
                    $num_updated = $query->execute();
                }
                $query = \Drupal::database()->update('textbook_companion_preference');
                $query->fields(array(
                    'submited_all_examples_code' => 2
                ));
                $query->condition('id', $form_state['values']['book']);
                $num_updated = $query->execute();
                \Drupal::messenger()->addStatus(t('Approved Entire Book.'));
                /* email */
                //$email_subject = t('Your uploaded examples have been approved');
                //$email_body = array(0=>t('Your all the uploaded examples for the book have been approved.'));
                // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $email_subject = t('[!site_name][Textbook Companion] Your uploaded Textbook Companion examples have been approved', array(
//                     '!site_name' => variable_get('site_name', '')
//                 ));

                // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $email_body = array(
//                     0 => t('
// 
// Dear !user_name,
// 
// Your all the uploaded examples for the book have been approved.
// 
// Title of the book : ' . $preference_data->book . '
// Author name : ' . $preference_data->author . '
// ISBN No. : ' . $preference_data->isbn . '
// Publisher and Place : ' . $preference_data->publisher . '
// Edition : ' . $preference_data->edition . '
// Year of publication : ' . $preference_data->year . '
// 
// Best Wishes,
// 
// !site_name Team,
// FOSSEE,IIT Bombay', array(
//                         '!site_name' => variable_get('site_name', ''),
//                         '!user_name' => $user_data->name
//                     ))
//                 );

            } elseif (($form_state['values']['book_actions'] == 2) && ($form_state['values']['chapter_actions'] == 0) && ($form_state['values']['example_actions'] == 0)) {
                /* pending entire book */
                $query = \Drupal::database()->select('textbook_companion_preference');
                $query->fields('textbook_companion_preference');
                $query->condition('id', $form_state['values']['book']);
                $query->condition('approval_status', 1);
                $result = $query->execute();
                $preference_data = $result->fetchObject();
                /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE preference_id = %d", $form_state['values']['book']);*/
                $query = \Drupal::database()->select('textbook_companion_chapter');
                $query->fields('textbook_companion_chapter');
                $query->condition('preference_id', $form_state['values']['book']);
                $chapter_q = $query->execute();
                while ($chapter_data = $chapter_q->fetchObject()) {
                    /*db_query("UPDATE {textbook_companion_example} SET approval_status = 0 WHERE chapter_id = %d", $chapter_data->id);*/
                    $query = \Drupal::database()->update('textbook_companion_example');
                    $query->fields(array(
                        'approval_status' => 0
                    ));
                    $query->condition('chapter_id', $chapter_data->id);
                    $num_updated = $query->execute();
                }
                \Drupal::messenger()->addStatus(t('Pending Review Entire Book.'));
                /* email */
                /*$email_subject = t('Your uploaded examples have been marked as pending');
                $email_body =array( t('Your all the uploaded examples for the book have been marked as pending to be review. You will be able to see the exmaples after they have been approved by one of our reviewers.'));*/
                // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $email_subject = t('[!site_name] Your uploaded Textbook Companion examples have been marked as pending', array(
//                     '!site_name' => variable_get('site_name', '')
//                 ));

                // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $email_body = array(
//                     0 => t('
// 
// Dear !user_name,
// 
// Your all the uploaded examples for the book have been marked as pending to be reviewed.
// You will be able to see the examples after they have been approved by one of our reviewers.
// 
// Title of the book : ' . $preference_data->book . '
// Author name : ' . $preference_data->author . '
// ISBN No. : ' . $preference_data->isbn . '
// Publisher and Place : ' . $preference_data->publisher . '
// Edition : ' . $preference_data->edition . '
// Year of publication : ' . $preference_data->year . '
// 
// Best Wishes,
// 
// !site_name Team,
// FOSSEE,IIT Bombay', array(
//                         '!site_name' => variable_get('site_name', ''),
//                         '!user_name' => $user_data->name
//                     ))
//                 );

            } elseif (($form_state['values']['book_actions'] == 3) && ($form_state['values']['chapter_actions'] == 0) && ($form_state['values']['example_actions'] == 0)) {
                if (strlen(trim($form_state['values']['message'])) <= 30) {
                    form_set_error('message', t(''));
                    \Drupal::messenger()->addError("Please mention the reason for disapproval. Minimum 30 character required");
                    return;
                }
                $query = \Drupal::database()->select('textbook_companion_preference');
                $query->fields('textbook_companion_preference');
                $query->condition('id', $form_state['values']['book']);
                $query->condition('approval_status', 1);
                $result = $query->execute();
                $preference_data = $result->fetchObject();
                if (!\Drupal::currentUser()->hasPermission('bulk delete code')) {
                    \Drupal::messenger()->addError(t('You do not have permission to Bulk Dis-Approved and Deleted Entire Book.'));
                    return;
                }
                if (delete_book($form_state['values']['book'])) {
                    \Drupal::messenger()->addStatus(t('Dis-Approved and Deleted Entire Book.'));
                } else {
                    \Drupal::messenger()->addError(t('Error Dis-Approving and Deleting Entire Book.'));
                }
                /* email */
                /*$email_subject = t('Your uploaded examples have been marked as dis-approved');
                $email_body =array( t('Your all the uploaded examples for the whole book have been marked as dis-approved.
                
                Reason for dis-approval:
                
                ' . $form_state['values']['message']));*/
                // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $email_subject = t('[!site_name] Your uploaded Textbook Companion examples have been marked as
// 				dis-approved', array(
//                     '!site_name' => variable_get('site_name', '')
//                 ));

                // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $email_body = array(
//                     0 => t('
// 
// Dear !user_name,
// 
// Your all the uploaded examples for the whole book have been marked as dis-approved.
// 
// Title of the book : ' . $preference_data->book . '
// Author name : ' . $preference_data->author . '
// ISBN No. : ' . $preference_data->isbn . '
// Publisher and Place : ' . $preference_data->publisher . '
// Edition : ' . $preference_data->edition . '
// Year of publication : ' . $preference_data->year . '
// 
// 
// Reason for dis-approval:' . $form_state['values']['message'] . '
// 
// Best Wishes,
// 
// !site_name Team,
// FOSSEE,IIT Bombay', array(
//                         '!site_name' => variable_get('site_name', ''),
//                         '!user_name' => $user_data->name
//                     ))
//                 );

            } elseif (($form_state['values']['book_actions'] == 4) && ($form_state['values']['chapter_actions'] == 0) && ($form_state['values']['example_actions'] == 0)) {
                if (strlen(trim($form_state['values']['message'])) <= 30) {
                    form_set_error('message', t(''));
                    \Drupal::messenger()->addError("Please mention the reason for disapproval/deletion. Minimum 30 character required");
                    return;
                }
                $query = \Drupal::database()->select('textbook_companion_preference');
                $query->fields('textbook_companion_preference');
                $query->condition('id', $form_state['values']['book']);
                $query->condition('approval_status', 1);
                $result = $query->execute();
                $pref_data = $result->fetchObject();
                if (!\Drupal::currentUser()->hasPermission('bulk delete code')) {
                    \Drupal::messenger()->addError(t('You do not have permission to Bulk Delete Entire Book Including Proposal.'));
                    return;
                }
                /* check if dependency files are present */
                /*$dep_q = db_query("SELECT * FROM {textbook_companion_dependency_files} WHERE preference_id = %d", $form_state['values']['book']);*/
                /*$query = db_select('textbook_companion_dependency_files');
                $query->fields('textbook_companion_dependency_files');
                $query->condition('preference_id', $form_state['values']['book']);
                $dep_q = $query->execute();
                
                if ($dep_data =$dep_q->fetchObject())
                {
                drupal_set_message(t("Cannot delete book since it has dependency files that can be used by others. First delete the dependency files before deleing the Book."), 'error');
                return;
                }*/
                if (delete_book($form_state['values']['book'])) {
                    \Drupal::messenger()->addStatus(t('Dis-Approved and Deleted Entire Book examples.'));
                    $dir_path = $root_path . $form_state['values']['book'];
                    if (is_dir($dir_path)) {
                        $res = rmdir($dir_path);
                        if (!$res) {
                            \Drupal::messenger()->addError(t("Cannot delete Book directory : " . $dir_path . ". Please contact administrator."));
                            return;
                        }
                    } else {
                        \Drupal::messenger()->addStatus(t("Book directory not present : " . $dir_path . ". Skipping deleting book directory."));
                    }
                    /* deleting preference and proposal */
                    /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d", $form_state['values']['book']);
                    $preference_data = db_fetch_object($preference_q);
                    */
                    $query = \Drupal::database()->select('textbook_companion_preference');
                    $query->fields('textbook_companion_preference');
                    $query->condition('id', $form_state['values']['book']);
                    $result = $query->execute();
                    $preference_data = $result->fetchObject();
                    $proposal_id = $preference_data->proposal_id;
                    /*db_query("DELETE FROM {textbook_companion_preference} WHERE proposal_id = %d", $proposal_id);*/
                    $query = \Drupal::database()->delete('textbook_companion_preference');
                    $query->condition('proposal_id', $proposal_id);
                    $num_deleted = $query->execute();
                    /*db_query("DELETE FROM {textbook_companion_proposal} WHERE id = %d", $proposal_id);*/
                    $query = \Drupal::database()->delete('textbook_companion_proposal');
                    $query->condition('id', $proposal_id);
                    $num_deleted = $query->execute();
                    \Drupal::messenger()->addStatus(t('Deleted Book Proposal.'));
                    /* email */
                    /*$email_subject = t('Your uploaded examples including the book proposal have been deleted');
                    $email_body = array(0=>t('Your all the uploaded examples including the book have been deleted permanently.
                    Reason for deletion:
                    ' . $form_state['values']['message']));*/
                    // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $email_subject = t('[!site_name] Your uploaded Textbook Companion examples including the book proposal 					have been deleted', array(
//                         '!site_name' => variable_get('site_name', '')
//                     ));

                    // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $email_body = array(
//                         0 => t('
// 
// Dear !user_name,
// 
// We regret to inform you that all the uploaded examples including the book with following details have been deleted permanently.
// 
// 
// Title of the book : ' . $pref_data->book . '
// Author name : ' . $pref_data->author . '
// ISBN No. : ' . $pref_data->isbn . '
// Publisher and Place : ' . $pref_data->publisher . '
// Edition : ' . $pref_data->edition . '
// Year of publication : ' . $pref_data->year . '
// 
// Reason for deletion:' . $form_state['values']['message'] . '
// 
// 
// Best Wishes,
// 
// !site_name Team,
// FOSSEE,IIT Bombay', array(
//                             '!site_name' => variable_get('site_name', ''),
//                             '!user_name' => $user_data->name
//                         ))
//                     );

                } else {
                    \Drupal::messenger()->addError(t('Error Dis-Approving and Deleting Entire Book.'));
                }
            } elseif (($form_state['values']['book_actions'] == 0) && ($form_state['values']['chapter_actions'] == 1) && ($form_state['values']['example_actions'] == 0)) {
                /*db_query("UPDATE {textbook_companion_example} SET approval_status = 1, approver_uid = %d WHERE chapter_id = %d AND approval_status = 0", $user->uid, $form_state['values']['chapter']);*/
                $query = \Drupal::database()->select('textbook_companion_preference');
                $query->fields('textbook_companion_preference');
                $query->condition('id', $form_state['values']['book']);
                $query->condition('approval_status', 1);
                $result = $query->execute();
                $pref_data = $result->fetchObject();
                $query = \Drupal::database()->select('textbook_companion_chapter');
                $query->fields('textbook_companion_chapter');
                $query->condition('preference_id', $form_state['values']['book']);
                $query->condition('id', $form_state['values']['chapter']);
                $result = $query->execute();
                $chap_data = $result->fetchObject();
                $query = \Drupal::database()->update('textbook_companion_example');
                $query->fields(array(
                    'approval_status' => 1,
                    'approver_uid' => $user->uid
                ));
                $query->condition('chapter_id', $form_state['values']['chapter']);
                $query->condition('approval_status', 0);
                $num_updated = $query->execute();
                \Drupal::messenger()->addStatus(t('Approved Entire Chapter.'));
                /* email */
                /*$email_subject = t('Your uploaded examples have been approved');
                $email_body = array(0=>t('Your all the uploaded examples for the chapter have been approved.'));*/
                // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $email_subject = t('[!site_name] Your uploaded Textbook Companion examples have been approved', array(
//                     '!site_name' => variable_get('site_name', '')
//                 ));

                // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $email_body = array(
//                     0 => t('
// 
// Dear !user_name,
// 
// Your all the uploaded examples for the chapter have been approved.
// 
// Title of the book : ' . $pref_data->book . '
// Title of the chapter : ' . $chap_data->name . '
// 
// Best Wishes,
// 
// !site_name Team,
// FOSSEE,IIT Bombay', array(
//                         '!site_name' => variable_get('site_name', ''),
//                         '!user_name' => $user_data->name
//                     ))
//                 );

            } elseif (($form_state['values']['book_actions'] == 0) && ($form_state['values']['chapter_actions'] == 2) && ($form_state['values']['example_actions'] == 0)) {
                /*db_query("UPDATE {textbook_companion_example} SET approval_status = 0 WHERE chapter_id = %d", $form_state['values']['chapter']);*/
                $query = \Drupal::database()->select('textbook_companion_preference');
                $query->fields('textbook_companion_preference');
                $query->condition('id', $form_state['values']['book']);
                $query->condition('approval_status', 1);
                $result = $query->execute();
                $pref_data = $result->fetchObject();
                $query = \Drupal::database()->select('textbook_companion_chapter');
                $query->fields('textbook_companion_chapter');
                $query->condition('preference_id', $form_state['values']['book']);
                $query->condition('id', $form_state['values']['chapter']);
                $result = $query->execute();
                $chap_data = $result->fetchObject();
                $query = \Drupal::database()->update('textbook_companion_example');
                $query->fields(array(
                    'approval_status' => 0
                ));
                $query->condition('chapter_id', $form_state['values']['chapter']);
                $num_updated = $query->execute();
                \Drupal::messenger()->addStatus(t('Entire Chapter marked as Pending Review.'));
                /* email */
                /*$email_subject = t('Your uploaded examples have been marked as pending');
                $email_body = array(0=>t('Your all the uploaded examples for the chapter have been marked as pending 
                to be review.'));*/
                // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $email_subject = t('[!site_name] Your uploaded Textbook Companion examples have been marked as pending', array(
//                     '!site_name' => variable_get('site_name', '')
//                 ));

                // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $email_body = array(
//                     0 => t('
// 
// Dear !user_name,
// 
// Your all the uploaded examples for the chapter have been marked as pending to be reviewed.
// 
// Title of the book : ' . $pref_data->book . '
// Title of the chapter : ' . $chap_data->name . '
// 
// Best Wishes,
// 
// !site_name Team,
// FOSSEE,IIT Bombay', array(
//                         '!site_name' => variable_get('site_name', ''),
//                         '!user_name' => $user_data->name
//                     ))
//                 );

            } elseif (($form_state['values']['book_actions'] == 0) && ($form_state['values']['chapter_actions'] == 3) && ($form_state['values']['example_actions'] == 0)) {
                $query = \Drupal::database()->select('textbook_companion_preference');
                $query->fields('textbook_companion_preference');
                $query->condition('id', $form_state['values']['book']);
                $query->condition('approval_status', 1);
                $result = $query->execute();
                $pref_data = $result->fetchObject();
                $query = \Drupal::database()->select('textbook_companion_chapter');
                $query->fields('textbook_companion_chapter');
                $query->condition('preference_id', $form_state['values']['book']);
                $query->condition('id', $form_state['values']['chapter']);
                $result = $query->execute();
                $chap_data = $result->fetchObject();
                if (strlen(trim($form_state['values']['message'])) <= 30) {
                    form_set_error('message', t(''));
                    \Drupal::messenger()->addError("Please mention the reason for disapproval. Minimum 30 character required");
                    return;
                }
                if (!\Drupal::currentUser()->hasPermission('bulk delete code')) {
                    \Drupal::messenger()->addError(t('You do not have permission to Bulk Dis-Approved and Deleted Entire Chapter.'));
                    return;
                }
                if (delete_chapter($form_state['values']['chapter'])) {
                    \Drupal::messenger()->addStatus(t('Dis-Approved and Deleted Entire Chapter.'));
                } else {
                    \Drupal::messenger()->addError(t('Error Dis-Approving and Deleting Entire Chapter.'));
                }
                /* email */
                /*$email_subject = t('Your uploaded example have been marked as dis-approved');
                $email_body = array(0=>t('Your uploaded example for the entire chapter have been marked as dis-approved.
                Reason for dis-approval:' . $form_state['values']['message']));*/
                // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $email_subject = t('[!site_name] Your uploaded Textbook Companion example have been marked as 					dis-approved', array(
//                     '!site_name' => variable_get('site_name', '')
//                 ));

                // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $email_body = array(
//                     0 => t('
// 
// Dear !user_name,
// 
// Your uploaded example for the entire chapter have been marked as dis-approved.
// 
// Title of the book : ' . $pref_data->book . '
// Title of the chapter : ' . $chap_data->name . '
// 
// 
// Reason for dis-approval:' . $form_state['values']['message'] . '
// 
// Best Wishes,
// 
// !site_name Team,
// FOSSEE,IIT Bombay', array(
//                         '!site_name' => variable_get('site_name', ''),
//                         '!user_name' => $user_data->name
//                     ))
//                 );

            } elseif (($form_state['values']['book_actions'] == 0) && ($form_state['values']['chapter_actions'] == 0) && ($form_state['values']['example_actions'] == 1)) {
                /*db_query("UPDATE {textbook_companion_example} SET approval_status = 1, approver_uid = %d WHERE id = %d", $user->uid, $form_state['values']['example']);*/
                $query = \Drupal::database()->select('textbook_companion_preference');
                $query->fields('textbook_companion_preference');
                $query->condition('id', $form_state['values']['book']);
                $query->condition('approval_status', 1);
                $result = $query->execute();
                $pref_data = $result->fetchObject();
                $query = \Drupal::database()->select('textbook_companion_chapter');
                $query->fields('textbook_companion_chapter');
                $query->condition('preference_id', $form_state['values']['book']);
                $query->condition('id', $form_state['values']['chapter']);
                $result = $query->execute();
                $chap_data = $result->fetchObject();
                $query = \Drupal::database()->select('textbook_companion_example');
                $query->fields('textbook_companion_example');
                $query->condition('id', $form_state['values']['example']);
                $result = $query->execute();
                $examp_data = $result->fetchObject();
                $query = \Drupal::database()->update('textbook_companion_example');
                $query->fields(array(
                    'approval_status' => 1,
                    'approver_uid' => $user->uid
                ));
                $query->condition('id', $form_state['values']['example']);
                $num_updated = $query->execute();
                \Drupal::messenger()->addStatus(t('Example approved.'));
                /* email */
                /*$email_subject = t('Your uploaded example has been approved');
                $email_body = array(0=>t('Your uploaded example has been approved.'));*/
                // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $email_subject = t('[!site_name] Your uploaded Textbook Companion example have been approved', array(
//                     '!site_name' => variable_get('site_name', '')
//                 ));

                // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $email_body = array(
//                     0 => t('
// 
// Dear !user_name,
// 
// Your example for R Textbook Companion with the following details is approved.
// 
// Title of the book : ' . $pref_data->book . '
// Title of the chapter : ' . $chap_data->name . '
// Example number : ' . $examp_data->number . '
// Caption : ' . $examp_data->caption . '
// 
// Best Wishes,
// 
// !site_name Team,
// FOSSEE,IIT Bombay', array(
//                         '!site_name' => variable_get('site_name', ''),
//                         '!user_name' => $user_data->name
//                     ))
//                 );

            } elseif (($form_state['values']['book_actions'] == 0) && ($form_state['values']['chapter_actions'] == 0) && ($form_state['values']['example_actions'] == 2)) {
                /*db_query("UPDATE {textbook_companion_example} SET approval_status = 0 WHERE id = %d", $form_state['values']['example']);*/
                $query = \Drupal::database()->select('textbook_companion_preference');
                $query->fields('textbook_companion_preference');
                $query->condition('id', $form_state['values']['book']);
                $query->condition('approval_status', 1);
                $result = $query->execute();
                $pref_data = $result->fetchObject();
                $query = \Drupal::database()->select('textbook_companion_chapter');
                $query->fields('textbook_companion_chapter');
                $query->condition('preference_id', $form_state['values']['book']);
                $query->condition('id', $form_state['values']['chapter']);
                $result = $query->execute();
                $chap_data = $result->fetchObject();
                $query = \Drupal::database()->select('textbook_companion_example');
                $query->fields('textbook_companion_example');
                $query->condition('id', $form_state['values']['example']);
                $result = $query->execute();
                $examp_data = $result->fetchObject();
                $query = \Drupal::database()->update('textbook_companion_example');
                $query->fields(array(
                    'approval_status' => 0
                ));
                $query->condition('id', $form_state['values']['example']);
                $num_updated = $query->execute();
                \Drupal::messenger()->addStatus(t('Example marked as Pending Review.'));
                /* email */
                /*$email_subject = t('Your uploaded example has been marked as pending');
                $email_body = array(0=>t('Your uploaded example has been marked as pending to be review.'));*/
                // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $email_subject = t('[!site_name] Your uploaded Textbook Companion example has been marked as pending', array(
//                     '!site_name' => variable_get('site_name', '')
//                 ));

                // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $email_body = array(
//                     0 => t('
// 
// Dear !user_name,
// 
// Your uploaded example for R Textbook Companion with the following details has been marked as pending to be reviewed.
// 
// Title of the book : ' . $pref_data->book . '
// Title of the chapter : ' . $chap_data->name . '
// Example number : ' . $examp_data->number . '
// Caption : ' . $examp_data->caption . '
// 
// Best Wishes,
// 
// !site_name Team,
// FOSSEE,IIT Bombay', array(
//                         '!site_name' => variable_get('site_name', ''),
//                         '!user_name' => $user_data->name
//                     ))
//                 );

            } elseif (($form_state['values']['book_actions'] == 0) && ($form_state['values']['chapter_actions'] == 0) && ($form_state['values']['example_actions'] == 3)) {
                if (strlen(trim($form_state['values']['message'])) <= 30) {
                    form_set_error('message', t(''));
                    \Drupal::messenger()->addError("Please mention the reason for disapproval. Minimum 30 character required");
                    return;
                }
                $query = \Drupal::database()->select('textbook_companion_preference');
                $query->fields('textbook_companion_preference');
                $query->condition('id', $form_state['values']['book']);
                $query->condition('approval_status', 1);
                $result = $query->execute();
                $pref_data = $result->fetchObject();
                $query = \Drupal::database()->select('textbook_companion_chapter');
                $query->fields('textbook_companion_chapter');
                $query->condition('preference_id', $form_state['values']['book']);
                $query->condition('id', $form_state['values']['chapter']);
                $result = $query->execute();
                $chap_data = $result->fetchObject();
                $query = \Drupal::database()->select('textbook_companion_example');
                $query->fields('textbook_companion_example');
                $query->condition('id', $form_state['values']['example']);
                $result = $query->execute();
                $examp_data = $result->fetchObject();
                if (delete_example($form_state['values']['example'])) {
                    \Drupal::messenger()->addStatus(t('Example Dis-Approved and Deleted.'));
                } else {
                    \Drupal::messenger()->addError(t('Error Dis-Approving and Deleting Example.'));
                }
                /* email */
                /*$email_subject = t('Your uploaded example has been marked as dis-approved');
                $email_body =array(0=> t('Your uploaded example has been marked as dis-approved.
                Reason for dis-approval:' . $form_state['values']['message']));*/
                // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $email_subject = t('[!site_name] Your uploaded Textbook Companion example has been marked as
// 				dis-approved', array(
//                     '!site_name' => variable_get('site_name', '')
//                 ));

                // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $email_body = array(
//                     0 => t('
// 
// Dear !user_name,
// 
// Your example for R Textbook Companion has been marked as dis-approved and deleted.
// 
// Title of the book : ' . $pref_data->book . '
// Title of the chapter : ' . $chap_data->name . '
// Example number : ' . $examp_data->number . '
// Caption : ' . $examp_data->caption . '
// 
// Reason for dis-approval:' . $form_state['values']['message'] . '
// 
// Best Wishes,
// 
// !site_name Team,
// FOSSEE,IIT Bombay', array(
//                         '!site_name' => variable_get('site_name', ''),
//                         '!user_name' => $user_data->name
//                     ))
//                 );

            } else {
                \Drupal::messenger()->addError(t('Please select only one action at a time'));
                return;
            }
            /****** sending email when everything done ******/
            if ($email_subject) {
                $email_to = $user_data->mail;
                $from = \Drupal::config('textbook_companion.settings')->get('textbook_companion_from_email');
                $bcc = \Drupal::config('textbook_companion.settings')->get('textbook_companion_emails');
                $cc = \Drupal::config('textbook_companion.settings')->get('textbook_companion_cc_emails');
                $param['standard']['subject'] = $email_subject;
                $param['standard']['body'] = $email_body;
                $param['standard']['headers'] = array(
                    'From' => $from,
                    'MIME-Version' => '1.0',
                    'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
                    'Content-Transfer-Encoding' => '8Bit',
                    'X-Mailer' => 'Drupal',
                    'Cc' => $cc,
                    'Bcc' => $bcc
                );
                if (!drupal_mail('textbook_companion', 'standard', $email_to, language_default(), $param, $from, TRUE))
                    \Drupal::messenger()->addError('Error sending email message.');
            }
        } else {
            \Drupal::messenger()->addError(t('You do not have permission to bulk manage code.'));
        }
    }
}
function _bulk_list_of_books()
{
    $book_titles = array(
        '0' => 'Please select...'
    );
    /*$book_titles_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE approval_status = 1 OR approval_status = 3 ORDER BY book ASC");*/
    $query = \Drupal::database()->select('textbook_companion_preference', 'pp');
    $query->join('textbook_companion_proposal', 'p', 'pp.proposal_id=p.id');
    $query->join('users', 'u', 'p.uid=u.uid');
    $query->fields('u', array(
        'name'
    ));
    $query->fields('pp', array(
        'id',
        'book',
        'author'
    ));
    $or = db_or();
    $or->condition('approval_status', 1);
    $or->condition('approval_status', 3);
    $query->condition($or);
    $query->orderBy('book', 'ASC');
    $book_titles_q = $query->execute();
    while ($book_titles_data = $book_titles_q->fetchObject()) {
        $book_titles[$book_titles_data->id] = $book_titles_data->book . ' (Written by ' . $book_titles_data->author . ')' . ' (Proposed by ' . $book_titles_data->name . ')';
    }
    return $book_titles;
}
function _ajax_bulk_get_chapter_list($preference_id = 0)
{
    $book_chapters = array(
        '0' => 'Please select...'
    );
    /*$book_chapters_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE preference_id = %d ORDER BY number ASC", $preference_id);*/
    $query = \Drupal::database()->select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('preference_id', $preference_id);
    $query->orderBy('number', 'ASC');
    $book_chapters_q = $query->execute();
    while ($book_chapters_data = $book_chapters_q->fetchObject()) {
        $book_chapters[$book_chapters_data->id] = $book_chapters_data->number . '. ' . $book_chapters_data->name;
    }
    return $book_chapters;
}
function _ajax_bulk_get_examples($chapter_id = 0)
{
    $book_examples = array(
        '0' => 'Please select...'
    );
    /*$book_examples_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d ORDER BY
    CAST(SUBSTRING_INDEX(number, '.', 1) AS BINARY) ASC,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(number , '.', 2), '.', -1) AS UNSIGNED) ASC,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(number , '.', -1), '.', 1) AS UNSIGNED) ASC", $chapter_id);*/
    $query = \Drupal::database()->select('textbook_companion_example');
    $query->fields('textbook_companion_example');
    $query->condition('chapter_id', $chapter_id);
    //$query->orderBy('CAST', 'ASC');
    //$query->orderBy('CAST', 'ASC');
    //$query->orderBy('CAST', 'ASC');
    $book_examples_q = $query->execute();
    while ($book_examples_data = $book_examples_q->fetchObject()) {
        $book_examples[$book_examples_data->id] = $book_examples_data->number . ' (' . $book_examples_data->caption . ')';
    }
    return $book_examples;
}
function _bulk_list_book_actions()
{
    $book_actions = array(
        '0' => 'Please select...'
    );
    $book_actions[1] = 'Approve Entire Book';
    $book_actions[2] = 'Pending Review Entire Book';
    $book_actions[3] = 'Dis-Approve Entire Book (This will delete all the examples in the book)';
    $book_actions[4] = 'Delete Entire Book Including Proposal';
    return $book_actions;
}
function _bulk_list_chapter_actions()
{
    $chapter_actions = array(
        '0' => 'Please select...'
    );
    $chapter_actions[1] = 'Approve Entire Chapter';
    $chapter_actions[2] = 'Pending Review Entire Chapter';
    $chapter_actions[3] = 'Dis-Approve Entire Chapter (This will delete all the examples in the chapter)';
    return $chapter_actions;
}
function _bulk_list_example_actions()
{
    $example_actions = array(
        '0' => 'Please select...'
    );
    $example_actions[1] = 'Approve  Approve Example';
    $example_actions[2] = 'Pending Review Example';
    $example_actions[3] = 'Dis-approve Example (This will delete the example)';
    return $example_actions;
}
/****************************** Ajax Callback function ***************************/
function ajax_bulk_chapter_list_callback($form, $form_state)
{
    $commands = array();
    $book_default_value = $form_state['values']['book'];
    if ($book_default_value > 0) {
        // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $commands[] = ajax_command_html('#ajax_selected_book', l('Download', 'textbook-companion/full-download/book/' . $book_default_value) . ' ' . t('(Download all the approved and unapproved examples of the entire book)'));

        // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $commands[] = ajax_command_html('#ajax_selected_book_pdf', l('Download PDF', 'textbook-companion/generate-book/' . $book_default_value . '/1') . ' ' . t('(Download PDF of all the approved and unapproved examples of the entire book)'));

        // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $commands[] = ajax_command_html('#ajax_selected_book_regenerate_pdf', l('Regenerate PDF', 'textbook-companion/delete-book/' . $book_default_value) . ' ' . t('(Manually Regenerate PDF of the entire book)'));

        /*$commands[] = ajax_command_html('#ajax_selected_book_notes', l('Notes for Reviewers', 'code_approval/notes/' . $book_default_value));*/
        $form['book_actions']['#options'] = _bulk_list_book_actions();
        $commands[] = ajax_command_replace('#ajax_selected_book_action', \Drupal::service("renderer")->render($form['book_actions']));
        $form['chapter']['#options'] = _ajax_bulk_get_chapter_list($book_default_value);
        $commands[] = ajax_command_replace('#ajax_select_chapter_list', \Drupal::service("renderer")->render($form['chapter']));
        $commands[] = ajax_command_html('#ajax_download_chapter', '');
        $form['chapter_actions']['#options'] = _bulk_list_book_actions();
        $commands[] = ajax_command_replace('#ajax_selected_chapter_action', \Drupal::service("renderer")->render($form['chapter_actions']));
        $commands[] = ajax_command_html('#ajax_selected_chapter_action', '');
        $commands[] = ajax_command_html('#ajax_selected_example', '');
        $form['example_actions']['#options'] = _bulk_list_example_actions();
        $commands[] = ajax_command_replace('#ajax_selected_example_action', \Drupal::service("renderer")->render($form['example_actions']));
        $commands[] = ajax_command_html('#ajax_selected_example_action', '');
        $commands[] = ajax_command_html('#ajax_download_selected_example', '');
        $commands[] = ajax_command_html('#ajax_edit_selected_example', '');
        $form['example_files']['#title'] = '';
        $form['example_files']['#markup'] = '';
        $commands[] = ajax_command_replace('#ajax_example_files_list', \Drupal::service("renderer")->render($form['example_files']));
    } else {
        $commands[] = ajax_command_html('#ajax_selected_book', '');
        $commands[] = ajax_command_html('#ajax_selected_book_pdf', '');
        $commands[] = ajax_command_html('#ajax_selected_book_regenerate_pdf', '');
        $commands[] = ajax_command_html('#ajax_selected_book_notes', '');
        $form['chapter']['#options'] = _ajax_bulk_get_chapter_list();
        $commands[] = ajax_command_replace('#ajax_select_chapter_list', \Drupal::service("renderer")->render($form['chapter']));
        $commands[] = ajax_command_html('#ajax_select_chapter_list', '');
        $form['book_actions']['#options'] = _bulk_list_book_actions();
        $commands[] = ajax_command_replace('#ajax_selected_book_action', \Drupal::service("renderer")->render($form['book_actions']));
        $commands[] = ajax_command_html('#ajax_selected_book_action', '');
        $form['chapter_actions']['#options'] = _bulk_list_chapter_actions();
        $commands[] = ajax_command_replace('#ajax_selected_chapter_action', \Drupal::service("renderer")->render($form['chapter_actions']));
        $commands[] = ajax_command_html('#ajax_selected_chapter_action', '');
        $commands[] = ajax_command_html('#ajax_download_chapter', '');
        $commands[] = ajax_command_html('#ajax_selected_example', '');
        $form['example_actions']['#options'] = _bulk_list_example_actions();
        $commands[] = ajax_command_replace('#ajax_selected_example_action', \Drupal::service("renderer")->render($form['example_actions']));
        $commands[] = ajax_command_html('#ajax_selected_example_action', '');
        $commands[] = ajax_command_html('#ajax_download_selected_example', '');
        $commands[] = ajax_command_html('#ajax_edit_selected_example', '');
        $form['example_files']['#title'] = '';
        $form['example_files']['#markup'] = '';
        $commands[] = ajax_command_replace('#ajax_example_files_list', \Drupal::service("renderer")->render($form['example_files']));
    }
    return array(
        '#type' => 'ajax',
        '#commands' => $commands
    );
}
function ajax_bulk_example_list_callback($form, $form_state)
{
    $commands = array();
    $chapter_default_value = $form_state['values']['chapter'];
    if ($chapter_default_value > 0) {
        // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $commands[] = ajax_command_html('#ajax_download_chapter', l('Download', 'textbook-companion/full-download/chapter/' . $chapter_default_value) . ' ' . t('(Download all the approved and unapproved examples of the entire chapter)'));

        $form['chapter_actions']['#options'] = _bulk_list_chapter_actions();
        $commands[] = ajax_command_replace('#ajax_selected_chapter_action', \Drupal::service("renderer")->render($form['chapter_actions']));
        $form['example']['#options'] = _ajax_bulk_get_examples($chapter_default_value);
        $commands[] = ajax_command_replace('#ajax_selected_example', \Drupal::service("renderer")->render($form['example']));
        $commands[] = ajax_command_html('#ajax_download_selected_example', '');
        $commands[] = ajax_command_html('#ajax_edit_selected_example', '');
        $form['example_actions']['#options'] = _bulk_list_example_actions();
        $commands[] = ajax_command_replace('#ajax_selected_example_action', \Drupal::service("renderer")->render($form['example_actions']));
        $commands[] = ajax_command_html('#ajax_selected_example_action', '');
        $form['example_files']['#title'] = '';
        $form['example_files']['#markup'] = '';
        $commands[] = ajax_command_replace('#ajax_example_files_list', \Drupal::service("renderer")->render($form['example_files']));
    } else {
        $commands[] = ajax_command_html('#ajax_download_chapter', '');
        $form['chapter_actions']['#options'] = _bulk_list_chapter_actions();
        $commands[] = ajax_command_replace('#ajax_selected_chapter_action', \Drupal::service("renderer")->render($form['chapter_actions']));
        $commands[] = ajax_command_html('#ajax_selected_chapter_action', '');
        $form['example']['#options'] = _ajax_bulk_get_examples();
        $commands[] = ajax_command_replace('#ajax_selected_example', \Drupal::service("renderer")->render($form['example']));
        $commands[] = ajax_command_html('#ajax_selected_example', '');
        $commands[] = ajax_command_html('#ajax_download_selected_example', '');
        $commands[] = ajax_command_html('#ajax_edit_selected_example', '');
        $form['example_files']['#title'] = '';
        $form['example_files']['#markup'] = '';
        $commands[] = ajax_command_replace('#ajax_example_files_list', \Drupal::service("renderer")->render($form['example_files']));
        $form['example_actions']['#options'] = _bulk_list_example_actions();
        $commands[] = ajax_command_replace('#ajax_selected_example_action', \Drupal::service("renderer")->render($form['example_actions']));
        $commands[] = ajax_command_html('#ajax_selected_example_action', '');
    }
    return array(
        '#type' => 'ajax',
        '#commands' => $commands
    );
}
function ajax_bulk_example_files_callback($form, $form_state)
{
    $commands = array();
    $example_list_default_value = $form_state['values']['example'];
    //var_dump($example_list_default_value);
    if ($example_list_default_value > 0) {
        /*************************************************************************************/
        /*$example_list_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $form_state['values']['example']);*/
        $query = \Drupal::database()->select('textbook_companion_example_files');
        $query->fields('textbook_companion_example_files');
        $query->condition('example_id', $example_list_default_value);
        $example_list_q = $query->execute();
        if ($example_list_q) {
            $example_files_rows = array();
            while ($example_list_data = $example_list_q->fetchObject()) {
                $example_file_type = '';
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
                // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $example_files_rows[] = array(
//                     l($example_list_data->filename, 'textbook-companion/download/file/' . $example_list_data->id),
//                     $example_file_type
//                 );

            }
            /* dependency files */
            /*$dependency_list_q = db_query("SELECT dependency.id as dependency_id, dependency.filename as dependency_filename, dependency.caption as dependency_caption
            FROM {textbook_companion_example_dependency} example_dependency LEFT JOIN {textbook_companion_dependency_files} dependency
            ON example_dependency.dependency_id = dependency.id
            WHERE example_dependency.example_id = %d", $form_state['values']['example']);*/
            /*
            $query = db_select('textbook_companion_example_dependency', 'example_dependency');
            $query->fields('dependency', array('id', 'filename', 'caption'));
            $query->addField('dependency','id','dependency_id');
            $query->addField('dependency','filename','dependency_filename');
            $query->addField('dependency','caption','dependency_caption');
            $query->leftJoin('textbook_companion_dependency_files', 'dependency', 'example_dependency.dependency_id = dependency.id');
            $query->condition('example_dependency.example_id',  $form_state['values']['example']);
            $dependency_list_q = $query->execute();
            
            while ($dependency_list_data = $dependency_list_q->fetchObject())
            {
            $example_file_type = 'Dependency file';
            $temp_caption = '';
            if ($dependency_list_data->dependency_caption)
            $temp_caption = ' (' . $dependency_list_data->dependency_caption . ')';
            $example_files_rows[] = array(l($dependency_list_data->dependency_filename, 'download/dependency/' . $dependency_list_data->dependency_id) . $temp_caption, $example_file_type);
            }
            */
            /* creating list of files table */
            $example_files_header = array(
                'Filename',
                'Type'
            );
            // @FIXME
// theme() has been renamed to _theme() and should NEVER be called directly.
// Calling _theme() directly can alter the expected output and potentially
// introduce security issues (see https://www.drupal.org/node/2195739). You
// should use renderable arrays instead.
// 
// 
// @see https://www.drupal.org/node/2195739
// $example_files = theme('table', array(
//                 'header' => $example_files_header,
//                 'rows' => $example_files_rows
//             ));

            $form['example_files']['#title'] = 'List of example files';
            $form['example_files']['#markup'] = $example_files;
            $commands[] = ajax_command_replace('#ajax_example_files_list', \Drupal::service("renderer")->render($form['example_files']));
            // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $commands[] = ajax_command_html('#ajax_download_selected_example', l('Download Example', 'textbook-companion/download/example/' . $example_list_default_value));

            // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $commands[] = ajax_command_html('#ajax_edit_selected_example', l('Edit Example', 'textbook-companion/code-approval/editcode/' . $example_list_default_value));

            $form['example_actions']['#options'] = _bulk_list_example_actions();
            $commands[] = ajax_command_replace('#ajax_selected_example_action', \Drupal::service("renderer")->render($form['example_actions']));
            //$commands[] = ajax_command_html('#ajax_selected_example_action', '' );
        }
    } else {
        $commands[] = ajax_command_html('#ajax_download_selected_example', '');
        $commands[] = ajax_command_html('#ajax_edit_selected_example', '');
        $form['example_files']['#title'] = '';
        $form['example_files']['#markup'] = '';
        $commands[] = ajax_command_replace('#ajax_example_files_list', \Drupal::service("renderer")->render($form['example_files']));
        $form['example_actions']['#options'] = _bulk_list_example_actions();
        $commands[] = ajax_command_replace('#ajax_selected_example_action', \Drupal::service("renderer")->render($form['example_actions']));
        $commands[] = ajax_command_html('#ajax_selected_example_action', '');
    }
    return array(
        '#type' => 'ajax',
        '#commands' => $commands
    );
}
function ajax_bulk_chapter_actions_callback()
{
    //if($form_state['values']['chapter_actions'] > 0){
    //	$form['book_actions']['#options'] = _bulk_list_book_actions();
    //$commands[] = ajax_command_replace('#ajax_selected_book_action',drupal_render($form['book_actions']));
    //	}
    return array(
        '#type' => 'ajax',
        '#commands' => $commands
    );
}
function edit_code_submission()
{
    /* get pending proposals to be approved */
    $proposal_rows = array();
    /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} ORDER BY id DESC");*/
    $query = \Drupal::database()->select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('proposal_status', 1);
    $query->orderBy('id', 'DESC');
    $proposal_q = $query->execute();
    while ($proposal_data = $proposal_q->fetchObject()) {
        /* get preference */
        /*$preference_q = db_query("SELECT * FROM textbook_companion_preference WHERE proposal_id = %d AND approval_status = 1 LIMIT 1", $proposal_data->id);   
        $preference_data = db_fetch_object($preference_q);*/
        $query = \Drupal::database()->select('textbook_companion_preference');
        $query->fields('textbook_companion_preference');
        $query->condition('proposal_id', $proposal_data->id);
        $query->condition('approval_status', 1);
        $query->condition('submited_all_examples_code', 1);
        $query->range(0, 1);
        $preference_q = $query->execute();
        $preference_data = $preference_q->fetchObject();
        if (!$preference_data) {
            /* $preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE proposal_id = %d AND pref_number = 1 LIMIT 1", $proposal_data->id);  
            $preference_data = db_fetch_object($preference_q);*/
            $query = \Drupal::database()->select('textbook_companion_preference');
            $query->fields('textbook_companion_preference');
            $query->condition('proposal_id', $proposal_data->id);
            $query->condition('pref_number', 1);
            //$query->condition('approval_status', 0);        
            $query->range(0, 1);
            $preference_q = $query->execute();
            $preference_data = $preference_q->fetchObject();
        }
        $proposal_status = '';
        switch ($preference_data->submited_all_examples_code) {
            case 0:
                $proposal_status = 'Code Submission Pending';
                break;
            case 1:
                $proposal_status = 'Submitted code for all exmample for book';
                break;
        }
        // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $proposal_rows[] = array(
//             "{$preference_data->book} <br>
// <em>by {$preference_data->author}</em>",
//             l($proposal_data->full_name, 'user/' . $proposal_data->uid),
//             $proposal_status,
//             l('Edit', 'textbook-companion/code-approval/edit-code-submission/edit/' . $preference_data->id)
//         );

    }
    /* check if there are any pending proposals */
    if (!$proposal_rows) {
        \Drupal::messenger()->addStatus(t('There are no proposals.'));
        return '';
    }
    $proposal_header = array(
        'Title of the Book',
        'Contributor Name',
        'Status',
        'Action'
    );
    // @FIXME
// theme() has been renamed to _theme() and should NEVER be called directly.
// Calling _theme() directly can alter the expected output and potentially
// introduce security issues (see https://www.drupal.org/node/2195739). You
// should use renderable arrays instead.
// 
// 
// @see https://www.drupal.org/node/2195739
// $output = theme('table', array(
//         'header' => $proposal_header,
//         'rows' => $proposal_rows
//     ));

    return $output;
}
function get_edit_code_submission($id = NULL)
{
    if ($id) {
        return \Drupal::formBuilder()->getForm('edit_code_submission_form', $id);
    } else {
        return 'Access denied';
    }
}
function edit_code_submission_form($form, $form_state, $preference_id)
{
    /* get current proposal */
    $preference_id = arg(4);
    /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d", $preference_id);
    $preference_data = db_fetch_object($preference_q);*/
    $query = \Drupal::database()->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('id', $preference_id);
    $preference_data = $query->execute()->fetchObject();
    if (!$preference_data) {
        \Drupal::messenger()->addError(t('Invalid book selected. Please try again.'));
        drupal_goto('textbook-companion/code-approval/edit-code-submission');
        return;
    }
    $form = array();
    $form['book'] = array(
        '#type' => 'item',
        '#title' => t('Title of the book'),
        '#markup' => $preference_data->book
    );
    $form['author'] = array(
        '#type' => 'item',
        '#title' => t('Author Name'),
        '#markup' => $preference_data->author
    );
    $form['isbn'] = array(
        '#type' => 'item',
        '#title' => t('ISBN No'),
        '#markup' => $preference_data->isbn
    );
    $form['publisher'] = array(
        '#type' => 'item',
        '#title' => t('Publisher & Place'),
        '#markup' => $preference_data->publisher
    );
    $form['edition'] = array(
        '#type' => 'item',
        '#title' => t('Edition'),
        '#markup' => $preference_data->edition
    );
    $form['year'] = array(
        '#type' => 'item',
        '#title' => t('Year of pulication'),
        '#markup' => $preference_data->year
    );
    $form['all_example_submitted'] = array(
        '#type' => 'checkbox',
        '#title' => t('Enable code submission interface for user'),
        '#description' => 'Once you have submited this option user can upload more examples.',
        '#required' => TRUE
    );
    $form['hidden_preference_id'] = array(
        '#type' => 'hidden',
        '#value' => $preference_id
    );
    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit')
    );
    // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $form['cancle'] = array(
//         '#type' => 'item',
//         '#markup' => l('Cancle', 'textbook-companion/code-approval/edit-code-submission')
//     );

    return $form;
}
function edit_code_submission_form_validate($form, $form_state)
{
    if ($form_state['values']['all_example_submitted'] != 1) {
        form_set_error('all_example_submitted', t('Please check the field if you are intrested to submit the all uploaded examples for review!'));
    }
    return;
}
function edit_code_submission_form_submit($form, $form_state)
{
    $user = \Drupal::currentUser();
    if ($form_state['values']['all_example_submitted'] == 1) {
        if (\Drupal::database()->query('UPDATE textbook_companion_preference SET submited_all_examples_code = 0 WHERE id = :preference_id', array(
            ':preference_id' => $form_state['values']['hidden_preference_id']
        ))) {
            $query = ("SELECT proposal_id FROM textbook_companion_preference WHERE id= :preference_id");
            $args = array(
                ":preference_id" => $form_state['values']['hidden_preference_id']
            );
            $proposal_data = \Drupal::database()->query($query, $args);
            $proposal_data_result = $proposal_data->fetchObject();
            $proposal_query = \Drupal::database()->select('textbook_companion_proposal');
            $proposal_query->fields('textbook_companion_proposal');
            $proposal_query->condition('proposal_status', 1);
            $proposal_query->condition('id', $proposal_data_result->proposal_id);
            $proposal_data_query = $proposal_query->execute()->fetchObject();
            /* sending email */
            $book_user = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data_query->uid);
            $email_to = $book_user->mail;
            $from = \Drupal::config('textbook_companion.settings')->get('textbook_companion_from_email');
            $bcc = \Drupal::config('textbook_companion.settings')->get('textbook_companion_emails');
            $cc = \Drupal::config('textbook_companion.settings')->get('textbook_companion_cc_emails');
            $param['all_code_submitted_status_changed']['proposal_id'] = $proposal_data_result->proposal_id;
            $param['all_code_submitted_status_changed']['user_id'] = $user->uid;
            $param['all_code_submitted_status_changed']['headers'] = array(
                'From' => $from,
                'MIME-Version' => '1.0',
                'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
                'Content-Transfer-Encoding' => '8Bit',
                'X-Mailer' => 'Drupal',
                'Cc' => $cc,
                'Bcc' => $bcc
            );
            if (!drupal_mail('textbook_companion', 'all_code_submitted_status_changed', $email_to, language_default(), $param, $from, TRUE))
                \Drupal::messenger()->addError('Error sending email message.');
            \Drupal::messenger()->addMessage('Enabled code submission interface for user');
            drupal_goto('textbook-companion/code-approval/edit-code-submission');
        }
    }
}

function codable_example_approval()
{
        $query = \Drupal::database()->select('textbook_companion_preference');
        $query->fields('textbook_companion_preference');
        $query->condition('approved_codable_example_files', 0);
        $query->condition('submited_all_examples_code', 1);
        $query->condition('submitted_codable_examples_file', 1);
        $result = $query->execute();
        $rows = array();
        while ($preference_data = $result->fetchObject()) {
        $query_pro = \Drupal::database()->select('textbook_companion_proposal');
        $query_pro->fields('textbook_companion_proposal');
        $query_pro->condition('id', $preference_data->proposal_id);
        $result_pro = $query_pro->execute();
        $proposal_data = $result_pro->fetchObject();
        /* setting table row information */
        // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $rows[] = array(
//             $preference_data->book,
//             $proposal_data->full_name,
//             l('Edit', 'textbook-companion/code-approval/approve-codable-examples/' . $preference_data->id)
//         );

        }
    
    /* check if there are any pending proposals */
    if (!$rows) {
        \Drupal::messenger()->addStatus(t('There are no proposals with codable example file'));
        return '';
    }
    $header = array(
        'Title of the Book',
        'Contributor Name',
        'Actions'
    );
    // @FIXME
// theme() has been renamed to _theme() and should NEVER be called directly.
// Calling _theme() directly can alter the expected output and potentially
// introduce security issues (see https://www.drupal.org/node/2195739). You
// should use renderable arrays instead.
// 
// 
// @see https://www.drupal.org/node/2195739
// $output = theme('table', array(
//         'header' => $header,
//         'rows' => $rows
//     ));

    return $output;
}

function codable_examples_approval_form($form, &$form_state)
{
    /* get a list of unapproved chapters */
    $pref_id = arg(3);
    
        $query = \Drupal::database()->select('textbook_companion_preference');
        $query->fields('textbook_companion_preference');
        $query->condition('id', $pref_id);
        $result = $query->execute();
        $preference_data = $result->fetchObject();
        //var_dump($preference_data);die;
        /* get proposal data */
        /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE id = %d", $preference_data->proposal_id);
        $proposal_data = db_fetch_object($proposal_q);*/
        $query = \Drupal::database()->select('textbook_companion_proposal');
        $query->fields('textbook_companion_proposal');
        $query->condition('id', $preference_data->proposal_id);
        $result = $query->execute();
        $proposal_data = $result->fetchObject();

    $form['#tree'] = TRUE;
    $form['contributor'] = array(
        '#type' => 'item',
        '#markup' => $proposal_data->full_name,
        '#title' => t('Contributor Name')
    );
    $form['book_details']['book'] = array(
        '#type' => 'item',
        '#markup' => $preference_data->book,
        '#title' => t('Title of the Book')
    );
    
    /* get example data */
    /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND approval_status = 0", $chapter_id);*/
    $query = \Drupal::database()->select('textbook_companion_codable_example_files');
    $query->fields('textbook_companion_codable_example_files');
    $query->condition('proposal_id', $preference_data->proposal_id);
    //$query->condition('approval_status', 0);
    $example_q = $query->execute();
    $example_data = $example_q->fetchObject();
        /*$form['example_details'][$example_data->id] = array(
            '#type' => 'fieldset',
            '#collapsible' => FALSE,
            '#collapsed' => TRUE
        );*/
        // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $form['download_file'] = array(
//             '#type' => 'item',
//             '#title' => t('Click to download the file with list of codable examples'),
//             '#markup' => l('Download Example', 'textbook-companion/download/codable-example/' . $example_data->proposal_id)
//         );

        $form['status_of_codable_example'] = array(
            '#type' => 'radios',
            '#options' => array(
                'Approved',
                'Dis-approved'
            )
        );
        $form['message'] = array(
            '#type' => 'textarea',
            '#size' => 5000,
            '#maxlength' => 5000,
            '#title' => t('Reason for dis-approval')
        );
        $form['proposal_id'] = array(
            '#type' => 'hidden',
            '#value' => $example_data->proposal_id
        );
    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit')
    );
    // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $form['back_to_list'] = array(
//         '#type' => 'item',
//         '#markup' => l('Back to Code Approval List', 'textbook-companion/code-approval/codable-examples')
//     );

    return $form;
}

function codable_examples_approval_form_submit($form, &$form_state){
    $user = \Drupal::currentUser();
    $proposal_id = $form_state['values']['proposal_id'];
    $query = \Drupal::database()->select('textbook_companion_preference');
        $query->fields('textbook_companion_preference');
        $query->condition('proposal_id', $proposal_id);
        $result = $query->execute();
        $preference_data = $result->fetchObject();
     $query = \Drupal::database()->select('textbook_companion_proposal');
        $query->fields('textbook_companion_proposal');
        $query->condition('id', $preference_data->proposal_id);
        $result = $query->execute();
        $proposal_data = $result->fetchObject();
    $user_data = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->uid);
    $ex_data = $form_state['values'];
    if ($ex_data['status_of_codable_example'] == "0") {
            /*db_query("UPDATE {textbook_companion_example} SET approval_status = 1, approver_uid = %d, approval_date = %d WHERE id = %d", $user->uid, time(), $ex_data['example_id']);*/
            $query = \Drupal::database()->update('textbook_companion_preference');
            $query->fields(array(
                'approved_codable_example_files' => 1,
            ));
            $query->condition('proposal_id', $ex_data['proposal_id']);
            $num_updated = $query->execute();
            /* sending email */
            $email_to = $user_data->mail;
            $from = \Drupal::config('textbook_companion.settings')->get('textbook_companion_from_email');
            $bcc = \Drupal::config('textbook_companion.settings')->get('textbook_companion_emails');
            $cc = \Drupal::config('textbook_companion.settings')->get('textbook_companion_cc_emails');
            $param['codable_example_approved']['proposal_id'] = $ex_data['proposal_id'];
            $param['codable_example_approved']['user_id'] = $user_data->uid;
            $param['codable_example_approved']['headers'] = array(
                'From' => $from,
                'MIME-Version' => '1.0',
                'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
                'Content-Transfer-Encoding' => '8Bit',
                'X-Mailer' => 'Drupal',
                'Cc' => $cc,
                'Bcc' => $bcc
            );
            if (!drupal_mail('textbook_companion', 'codable_example_approved', $email_to, language_default(), $param, $from, TRUE))
                \Drupal::messenger()->addError('Error sending email message.');
            \Drupal::messenger()->addStatus('File approved.');
            drupal_goto('textbook-companion/code-approval/codable-examples');
        } else if ($ex_data['status_of_codable_example'] == "1") {
            
                /* sending email */
                $query = \Drupal::database()->update('textbook_companion_preference');
            $query->fields(array(
                'submitted_codable_examples_file' => 0,
                'submited_all_examples_code' => 0,
                'approved_codable_example_files' => 0,
            ));
            $query->condition('proposal_id', $ex_data['proposal_id']);
            $num_updated = $query->execute();
                $email_to = $user_data->mail;
                $from = \Drupal::config('textbook_companion.settings')->get('textbook_companion_from_email');
                $bcc = \Drupal::config('textbook_companion.settings')->get('textbook_companion_emails');
                $cc = \Drupal::config('textbook_companion.settings')->get('textbook_companion_cc_emails');
                $param['codable_example_disapproved']['proposal_id'] = $ex_data['proposal_id'];
                $param['codable_example_disapproved']['user_id'] = $user_data->uid;
                $param['codable_example_disapproved']['message'] = $ex_data['message'];
                $param['codable_example_disapproved']['headers'] = array(
                    'From' => $from,
                    'MIME-Version' => '1.0',
                    'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
                    'Content-Transfer-Encoding' => '8Bit',
                    'X-Mailer' => 'Drupal',
                    'Cc' => $cc,
                    'Bcc' => $bcc
                );
                if (!drupal_mail('textbook_companion', 'codable_example_disapproved', $email_to, language_default(), $param, $from, TRUE))
                    \Drupal::messenger()->addError('Error sending email message.');
                \Drupal::messenger()->addStatus('File disapproved and user has been notified of the changes.');
                drupal_goto('textbook-companion/code-approval/codable-examples');
            }
            else {
                \Drupal::messenger()->addError('Error in updating the status. Please contact administrator.');
            }
}
