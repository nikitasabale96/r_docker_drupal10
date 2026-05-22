<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\ProposalEditForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Render\Element;
use Drupal\textbook_companion\Helper\ProposalHelper;
use Drupal\user\Entity\User;

class ProposalEditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'proposal_edit_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, $proposal_id = NULL) {
    $proposal_id = $proposal_id ?? \Drupal::routeMatch()->getParameter('proposal_id');
    $proposal_id = (int) $proposal_id;
    $connection = \Drupal::database();
    $proposal_data = $connection->select('textbook_companion_proposal', 'tp')
      ->fields('tp')
      ->condition('id', $proposal_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();
    if (!$proposal_data) {
      $this->messenger()->addError($this->t('Invalid proposal selected. Please try again.'));
      return [];
    }
    /*if (!$proposal_data) {
            drupal_set_message(t('Invalid proposal selected. Please try again.'), 'error');
            //drupal_goto('textbook-companion/manage-proposal');
            return;
        }
    else {
        //drupal_set_message(t('Invalid proposal selected. Please try again.'), 'error');
        //drupal_goto('textbook-companion/manage-proposal');
       // return;
    }*/
    $user_data = User::load($proposal_data->uid);
    //  var_dump($user_data->mail);die;
    $preference1_data = $connection->select('textbook_companion_preference', 'tp')
      ->fields('tp')
      ->condition('proposal_id', $proposal_id)
      ->condition('pref_number', 1)
      ->range(0, 1)
      ->execute()
      ->fetchObject();
    $form['#attributes'] = ['enctype' => "multipart/form-data"];
    $form['name_title'] = [
      '#type' => 'select',
      '#title' => t('Title'),
      '#options' => [
        'Dr' => 'Dr',
        'Prof' => 'Prof',
        'Mr' => 'Mr',
        'Mrs' => 'Mrs',
        'Ms' => 'Ms',
      ],
      '#required' => TRUE,
      '#default_value' => $proposal_data->name_title,
    ];
    $form['full_name'] = [
      '#type' => 'textfield',
      '#title' => t('Full Name'),
      '#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE,
      '#default_value' => $proposal_data->full_name,
    ];
    $form['email_id'] = [
      '#type' => 'textfield',
      '#title' => t('Email'),
      '#size' => 30,
      '#value' => $user_data?->getEmail() ?? '',
      '#disabled' => TRUE,
    ];
    $form['mobile'] = [
      '#type' => 'textfield',
      '#title' => t('Mobile No.'),
      '#size' => 30,
      '#maxlength' => 15,
      '#required' => TRUE,
      '#default_value' => $proposal_data->mobile,
    ];
    $form['how_project'] = [
      '#type' => 'select',
      '#title' => t('How did you come to know about this project'),
      '#options' => [
        'R Website' => 'R Website',
        'Friend' => 'Friend',
        'Professor/Teacher' => 'Professor/Teacher',
        'Mailing List' => 'Mailing List',
        'Poster in my/other college' => 'Poster in my/other college',
        'Others' => 'Others',
      ],
      '#required' => TRUE,
      '#default_value' => $proposal_data->how_project,
    ];
    $form['course'] = [
      '#type' => 'textfield',
      '#title' => t('Course'),
      '#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE,
      '#default_value' => $proposal_data->course,
    ];
    if (in_array($proposal_data->branch, _list_of_departments())) {
      $branch = $proposal_data->branch;
    }
    else {
      $branch = 'Others';
    }
    $form['branch'] = [
      '#type' => 'select',
      '#title' => t('Department/Branch'),
      '#options' => _list_of_departments(),
      '#required' => TRUE,
      '#default_value' => $branch,
    ];
    $form['other_branch'] = [
      '#type' => 'textfield',
      '#title' => t('Enter your Department/Branch name'),
      '#size' => 50,
      '#attributes' => [
        'placeholder' => t('Enter your Department/Branch name')
        ],
      '#default_value' => $proposal_data->branch,
      '#states' => [
        'visible' => [
          ':input[name="branch"]' => [
            'value' => 'Others'
            ]
          ]
        ],
    ];
    $form['university'] = [
      '#type' => 'textfield',
      '#title' => t('University/ Institute'),
      '#size' => 80,
      '#maxlength' => 200,
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => 'Insert full name of your institute/ university.... '
        ],
      '#default_value' => $proposal_data->university,
    ];
    $form['country'] = [
      '#type' => 'select',
      '#title' => t('Country'),
      '#options' => [
        'India' => 'India',
        'Others' => 'Others',
      ],
      '#required' => TRUE,
      '#tree' => TRUE,
      '#validated' => TRUE,
      '#default_value' => $proposal_data->country,
    ];
    $form['other_country'] = [
      '#type' => 'textfield',
      '#title' => t('Other than India'),
      '#size' => 100,
      '#attributes' => [
        'placeholder' => t('Enter your country name')
        ],
      '#default_value' => $proposal_data->country,
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others'
            ]
          ]
        ],
    ];
    $form['other_state'] = [
      '#type' => 'textfield',
      '#title' => t('State other than India'),
      '#size' => 100,
      '#default_value' => $proposal_data->state,
      '#attributes' => [
        'placeholder' => t('Enter your state/region name')
        ],
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others'
            ]
          ]
        ],
    ];
    $form['other_city'] = [
      '#type' => 'textfield',
      '#title' => t('City other than India'),
      '#size' => 100,
      '#default_value' => $proposal_data->city,
      '#attributes' => [
        'placeholder' => t('Enter your city name')
        ],
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others'
            ]
          ]
        ],
    ];
    $form['all_state'] = [
      '#type' => 'select',
      '#title' => t('State'),
      '#selected' => [
        '' => '-select-'
        ],
      '#options' => _list_of_states(),
      '#default_value' => $proposal_data->state,
      '#validated' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'India'
            ]
          ]
        ],
    ];
    $form['city'] = [
      '#type' => 'select',
      '#title' => t('City'),
      '#default_value' => $proposal_data->city,
      '#options' => _list_of_cities(),
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'India'
            ]
          ]
        ],
    ];
    $form['pincode'] = [
      '#type' => 'textfield',
      '#title' => t('Pincode'),
      '#size' => 30,
      '#maxlength' => 6,
      '#required' => FALSE,
      '#default_value' => $proposal_data->pincode,
      '#attributes' => [
        'placeholder' => 'Enter pincode....'
        ],
    ];
    /***************************************************************************/
    $form['hr'] = [
      '#type' => 'item',
      '#markup' => '<hr>',
    ];
    $form['faculty'] = [
      '#type' => 'hidden',
      '#title' => t('College Teacher/Professor'),
      '#size' => 30,
      '#maxlength' => 100,
      '#required' => TRUE,
      '#default_value' => $proposal_data->faculty,
    ];
    $form['reviewer'] = [
      '#type' => 'hidden',
      '#title' => t('Reviewer'),
      '#size' => 30,
      '#maxlength' => 100,
      '#default_value' => $proposal_data->reviewer,
    ];
    $form['completion_date'] = [
      '#type' => 'textfield',
      '#title' => t('Expected Date of Completion'),
      '#description' => t('Input date format should be DD-MM-YYYY. Eg: 23-03-2011'),
      '#size' => 10,
      '#maxlength' => 10,
      '#default_value' => date('d-m-Y', $proposal_data->completion_date),
    ];
    $form['version'] = [
      '#type' => 'textfield',
      '#title' => t('R Version'),
      '#size' => 10,
      '#maxlength' => 20,
      '#default_value' => $proposal_data->r_version,
    ];
    $form['operating_system'] = [
      '#type' => 'textfield',
      '#title' => t('Operating System'),
      '#size' => 30,
      '#maxlength' => 50,
      '#default_value' => $proposal_data->operating_system,
    ];
    $form['preference1'] = [
      '#type' => 'fieldset',
      '#title' => t('Book Preference 1'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['preference1']['book1'] = [
      '#type' => 'textfield',
      '#title' => t('Title of the book'),
      '#size' => 30,
      '#maxlength' => 100,
      '#required' => TRUE,
      '#default_value' => $preference1_data->book,
    ];
    $form['preference1']['author1'] = [
      '#type' => 'textfield',
      '#title' => t('Author Name'),
      '#size' => 30,
      '#maxlength' => 100,
      '#required' => TRUE,
      '#default_value' => $preference1_data->author,
    ];
    $form['preference1']['isbn1'] = [
      '#type' => 'textfield',
      '#title' => t('ISBN No'),
      '#size' => 30,
      '#maxlength' => 25,
      '#required' => TRUE,
      '#default_value' => $preference1_data->isbn,
    ];
    $form['preference1']['publisher1'] = [
      '#type' => 'textfield',
      '#title' => t('Publisher & Place'),
      '#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE,
      '#default_value' => $preference1_data->publisher,
    ];
    $form['preference1']['edition1'] = [
      '#type' => 'textfield',
      '#title' => t('Edition'),
      '#size' => 4,
      '#maxlength' => 2,
      '#required' => TRUE,
      '#default_value' => $preference1_data->edition,
    ];
    $form['preference1']['year1'] = [
      '#type' => 'textfield',
      '#title' => t('Year of pulication'),
      '#size' => 4,
      '#maxlength' => 4,
      '#required' => TRUE,
      '#default_value' => $preference1_data->year,
    ];
    $form['hidden_preference_id1'] = [
      '#type' => 'hidden',
      '#default_value' => $preference1_data->id,
    ];
    //var_dump($proposal_id);die;
    $form['hidden_proposal_id'] = [
      '#type' => 'hidden',
      '#default_value' => $proposal_id,
    ];
    //var_dump($preference1_data->category);die;
    /*$form['main_category'] = array(
        '#type' => 'select',
        '#title' => t('Select the main category'),
        '#default_value' => $preference1_data->category,
        '#options' => _tbc_list_of_main_categories(),
        '#tree' => TRUE,
        '#ajax' => array(
            'callback' => 'ajax_subcategory_list_callback',
            'wrapper' => 'ajax-subcategory-list-replace',
         //   'method' => 'replace',
        ),
    );
    $main_category = isset($form_state['values']['main_category']) ? $form_state['values']['main_category'] : $preference1_data->category;
    $form['subcategory'] = array(
        '#type' => 'select',
        '#title' => 'List of subcategory',
        '#prefix' => '<div id="ajax-subcategory-list-replace">',
        '#suffix' => '</div>',
        '#options' => _tbc_list_of_subcategories($main_category),
        '#multiple' => TRUE,
        '#default_value' => default_value_for_selections($preference1_data->id),
        '#states' => array(
            'invisible' => array(
                ':input[name="main_category"]' => array(
                    'value' => 0
                )
            )
        )
    );*/
    /* hidden fields */
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    $form['cancel'] = [
      '#type' => 'item',
      '#markup' => Link::fromTextAndUrl($this->t('Cancel'), Url::fromUri('internal:/textbook-companion/manage-proposal/pending'))->toString(),
    ];
    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    if ($form_state->getValue(['book1']) && $form_state->getValue(['author1'])) {
      $bk1 = trim($form_state->getValue(['book1']));
      $auth1 = trim($form_state->getValue(['author1']));
      $dir_name = _dir_name($bk1, $auth1, $form_state->getValue([
        'hidden_preference_id1',
      ]), $form_state);
      if ($dir_name !== NULL) {
        $form_state->setValue(['dir_name1'], $dir_name);
      }
    }
    /* mobile */
    if (!preg_match('/^[0-9\ \+]{0,15}$/', $form_state->getValue(['mobile']))) {
      $form_state->setErrorByName('mobile', t('Invalid mobile number'));
    }
    /* date of completion */
    if (!preg_match('/^[0-9]{1,2}-[0-9]{1,2}-[0-9]{4}$/', $form_state->getValue([
      'completion_date'
      ]))) {
      $form_state->setErrorByName('completion_date', t('Invalid expected date of completion'));
    }
    list($d, $m, $y) = explode('-', $form_state->getValue(['completion_date']));
    $d = (int) $d;
    $m = (int) $m;
    $y = (int) $y;
    if (!checkdate($m, $d, $y)) {
      $form_state->setErrorByName('completion_date', t('Invalid expected date of completion'));
    }
    //if (mktime(0, 0, 0, $m, $d, $y) <= time())
    //form_set_error('completion_date', t('Expected date of completion should be in future'));  
    /* edition */
    if (!preg_match('/^[1-9][0-9]{0,1}$/', $form_state->getValue(['edition1']))) {
      $form_state->setErrorByName('edition1', t('Invalid edition for Book Preference 1'));
    }
    /* year of publication */
    if (!preg_match('/^[1-3][0-9][0-9][0-9]$/', $form_state->getValue(['year1']))) {
      $form_state->setErrorByName('year1', t('Invalid year of pulication for Book Preference 1'));
    }
    /* year of publication */
    $cur_year = date('Y');
    if ((int) $form_state->getValue(['year1']) > $cur_year) {
      $form_state->setErrorByName('year1', t('Year of pulication should be not in the future for Book Preference 1'));
    }
    /* isbn */
    if (!preg_match('/^[0-9\-xX]+$/', $form_state->getValue(['isbn1']))) {
      $form_state->setErrorByName('isbn1', t('Invalid ISBN for Book Preference 1'));
    }
    if ($form_state->getValue(['version']) == 'olderversion') {
      if ($form_state->getValue(['older']) == '') {
        $form_state->setErrorByName('older', t('Please provide valid version'));
      }
    }
    if ($form_state->getValue(['branch']) == 'Others') {
      if (strlen($form_state->getValue(['other_branch'])) < 10) {
        $form_state->setErrorByName('other_branch', t('The minimum charater limit is 10'));
      }
      else {
        if (strlen($form_state->getValue(['other_branch'])) > 100) {
          $form_state->setErrorByName('other_branch', t('The maximum character limit is 100'));
        }
      }
    }
    /* if ($form_state['values']['subcategory'])
    {
        $subcategory = implode("| ", $_POST['subcategory']);
        $form_state['values']['subcategory'] = trim($subcategory);
    } */
    return;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    /* completion date to timestamp */
    list($d, $m, $y) = explode('-', $form_state->getValue(['completion_date']));
    $completion_date_timestamp = mktime(0, 0, 0, $m, $d, $y);
    $proposal_id = $form_state->getValue(['hidden_proposal_id']);
    $connection = \Drupal::database();
    if ($form_state->getValue(['version']) == 'olderversion') {
      $form_state->setValue(['version'], $form_state->getValue(['older']));
    }
    if ($form_state->getValue(['country']) == 'other') {
      $form_state->setValue(['country'], $form_state->getValue(['other_country']));
      $form_state->setValue(['all_state'], $form_state->getValue(['other_state']));
    }
    if ($form_state->getValue(['branch']) == 'Others') {
      $form_state->setValue(['branch'], $form_state->getValue(['other_branch']));
    }
    $connection->update('textbook_companion_proposal')
      ->fields([
      'name_title' => $form_state->getValue(['name_title']),
      'full_name' => $form_state->getValue(['full_name']),
      'mobile' => $form_state->getValue(['mobile']),
      'how_project' => $form_state->getValue(['how_project']),
      'course' => $form_state->getValue(['course']),
      'branch' => $form_state->getValue(['branch']),
      'university' => $form_state->getValue(['university']),
      'city' => $form_state->getValue(['city']),
      'pincode' => $form_state->getValue(['pincode']),
      'state' => $form_state->getValue(['all_state']),
      'country' => $form_state->getValue(['country']),
      'faculty' => $form_state->getValue(['faculty']),
      'reviewer' => $form_state->getValue(['reviewer']),
      'completion_date' => $completion_date_timestamp,
      'operating_system' => $form_state->getValue(['operating_system']),
      'r_version' => $form_state->getValue(['version']),
      ])
      ->condition('id', $proposal_id)
      ->execute();
    $preference1_data = $connection->select('textbook_companion_preference', 'tp')
      ->fields('tp')
      ->condition('proposal_id', $proposal_id)
      ->condition('pref_number', 1)
      ->range(0, 1)
      ->execute()
      ->fetchObject();
    $preference1_id = $preference1_data->id;
    if ($preference1_data) {
      del_book_pdf($preference1_data->id);
      ProposalHelper::renameDir($preference1_id, $form_state->getValue(['dir_name1']));
      $connection->update('textbook_companion_preference')
        ->fields([
        'book' => $form_state->getValue(['book1']),
        'author' => $form_state->getValue(['author1']),
        'isbn' => $form_state->getValue(['isbn1']),
        'publisher' => $form_state->getValue(['publisher1']),
        'edition' => $form_state->getValue(['edition1']),
        'year' => $form_state->getValue(['year1']),
        'directory_name' => $form_state->getValue(['dir_name1']),
        ])
        ->condition('id', $preference1_id)
        ->execute();
    }
    ProposalHelper::createReadmeFileTextbookCompanion($proposal_id);
    $this->messenger()->addStatus($this->t('Proposal Updated'));
  }

}
?>
