<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\ChequeStatusForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

class ChequeStatusForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cheque_status_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $proposal_id = NULL) {
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
      $form_state->setRedirect('textbook_companion._proposal_all');
      return [];
    }

    $cheque_data = $connection->select('textbook_companion_cheque', 'tc')
      ->fields('tc')
      ->condition('proposal_id', $proposal_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if (!$cheque_data) {
      $connection->insert('textbook_companion_cheque')
        ->fields(['proposal_id' => $proposal_id])
        ->execute();
      $cheque_data = (object) [];
    }

    $paper_data = $connection->select('textbook_companion_paper', 'tp')
      ->fields('tp')
      ->condition('proposal_id', $proposal_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if (!$paper_data) {
      $this->messenger()->addError($this->t('Invalid proposal selected. Please try again.'));
      $form_state->setRedirect('textbook_companion._proposal_all');
      return [];
    }

    $form['proposal_id'] = [
      '#type' => 'hidden',
      '#default_value' => $proposal_id,
    ];

    $form['candidate_detail'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Candidate Details'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#attributes' => [
        'id' => 'candidate_detail',
      ],
    ];

    $user_entity = User::load($proposal_data->uid);
    $form['candidate_detail']['full_name'] = [
      '#type' => 'item',
      '#plain_text' => $proposal_data->full_name ?? '',
      '#title' => $this->t('Contributor Name'),
    ];
    $form['candidate_detail']['email'] = [
      '#type' => 'item',
      '#plain_text' => $user_entity?->getEmail() ?? '',
      '#title' => $this->t('Email'),
    ];
    $form['candidate_detail']['mobile'] = [
      '#type' => 'item',
      '#plain_text' => $proposal_data->mobile ?? '',
      '#title' => $this->t('Mobile'),
    ];
    $form['candidate_detail']['alt_mobile'] = [
      '#type' => 'item',
      '#plain_text' => $cheque_data->alt_mobno ?? '',
      '#title' => $this->t('Alternate Mobile No.'),
    ];

    $preference_html = '<ul>';
    $preference_q = $connection->select('textbook_companion_preference', 'tp')
      ->fields('tp')
      ->condition('proposal_id', $proposal_id)
      ->orderBy('pref_number', 'ASC')
      ->execute();

    while ($preference_data = $preference_q->fetchObject()) {
      $book = Html::escape($preference_data->book ?? '');
      $author = Html::escape($preference_data->author ?? '');
      if ((int) $preference_data->approval_status === 1) {
        $preference_html .= '<li><strong>' . $book . ' (Written by ' . $author . ')  - Approved Book</strong></li>';
      }
      else {
        $preference_html .= '<li>' . $book . ' (Written by ' . $author . ')</li>';
      }
    }
    $preference_html .= '</ul>';

    $form['book_preference_f'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Book Preferences/Application Status'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#attributes' => [
        'id' => 'book_preference_f',
      ],
    ];
    $form['book_preference_f']['book_preference'] = [
      '#type' => 'item',
      '#markup' => $preference_html,
      '#title' => $this->t('Book Preferences'),
    ];

    $form_html = '<ul>';
    if (!empty($paper_data->internship_form)) {
      $form_html .= '<li><strong>Internship Application </strong> Form Submitted</li>';
    }
    else {
      $form_html .= '<li><strong>Internship Application </strong> Form Not Submitted </li>';
    }
    if (!empty($paper_data->copyright_form)) {
      $form_html .= '<li><strong>Copyright Application </strong> Form Submitted</li>';
    }
    else {
      $form_html .= '<li><strong>Copyright Application</strong> Form Not Submitted </li>';
    }
    if (!empty($paper_data->undertaking_form)) {
      $form_html .= '<li><strong>Undertaking Application </strong> Form Submitted</li>';
    }
    else {
      $form_html .= '<li><strong>Undertaking Application</strong> Form Not Submitted </li>';
    }
    $form_html .= '</ul>';
    $form['book_preference_f']['formsubmit'] = [
      '#type' => 'item',
      '#markup' => $form_html,
      '#title' => $this->t('Application Form Status'),
    ];

    $form['stu_cheque_details'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Student Cheque Details'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#attributes' => [
        'id' => 'stu_cheque_details',
      ],
    ];
    $form['tea_cheque_details'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Teacher Cheque Details'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#attributes' => [
        'id' => 'tea_cheque_details',
      ],
    ];
    $form['perm_cheque_address'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Permanent Address'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#attributes' => [
        'id' => 'perm_cheque_address',
      ],
    ];
    $form['temp_cheque_address'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Temporary Address'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#attributes' => [
        'id' => 'temp_cheque_address',
      ],
    ];
    $form['cheque_delivery'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Cheque Delivery'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#attributes' => [
        'id' => 'cheque_delivery',
      ],
    ];
    $form['commentf'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Remark'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#attributes' => [
        'id' => 'commentf',
      ],
    ];

    $chq1 = $cheque_data->cheque_no ?? '';
    $chq2 = $cheque_data->address ?? '';
    $chq3 = $cheque_data->cheque_amt ?? '';
    $chq4 = $cheque_data->cheque_sent ?? '';
    $chq5 = $cheque_data->cheque_cleared ?? '';
    $chq7 = $cheque_data->perm_city ?? '';
    $chq8 = $cheque_data->perm_state ?? '';
    $chq9 = $cheque_data->perm_pincode ?? '';
    $chq10 = $cheque_data->temp_chq_address ?? '';
    $chq12 = $cheque_data->temp_city ?? '';
    $chq13 = $cheque_data->temp_state ?? '';
    $chq14 = $cheque_data->temp_pincode ?? '';
    $chq15 = $cheque_data->commentf ?? '';
    $chq16 = $cheque_data->t_cheque_amt ?? '';
    $chq17 = $cheque_data->t_cheque_no ?? '';

    if (!empty($cheque_data->proposal_id)) {
      $form['stu_cheque_details']['cheque_no'] = [
        '#type' => 'textfield',
        '#default_value' => $chq1,
        '#title' => $this->t('Cheque No'),
        '#size' => 54,
      ];
      $form['tea_cheque_details']['cheque_no_t'] = [
        '#type' => 'textfield',
        '#default_value' => $chq17,
        '#title' => $this->t('Cheque No'),
        '#size' => 54,
      ];
      $form['perm_cheque_address']['chq_address'] = [
        '#type' => 'textarea',
        '#default_value' => $chq2,
        '#title' => $this->t('Address Street 1'),
        '#attributes' => ['readonly' => 'readonly'],
      ];
      $form['perm_cheque_address']['perm_city'] = [
        '#type' => 'textfield',
        '#default_value' => $chq7,
        '#title' => $this->t('City'),
        '#size' => 35,
        '#attributes' => ['readonly' => 'readonly'],
      ];
      $form['perm_cheque_address']['perm_state'] = [
        '#type' => 'textfield',
        '#default_value' => $chq8,
        '#title' => $this->t('State'),
        '#size' => 35,
        '#attributes' => ['readonly' => 'readonly'],
      ];
      $form['perm_cheque_address']['perm_pincode'] = [
        '#type' => 'textfield',
        '#default_value' => $chq9,
        '#title' => $this->t('Zip code'),
        '#size' => 35,
        '#attributes' => ['readonly' => 'readonly'],
      ];
      $form['stu_cheque_details']['cheq_amt'] = [
        '#type' => 'textfield',
        '#default_value' => $chq3,
        '#title' => $this->t('Cheque Amount'),
        '#size' => 54,
      ];
      $form['tea_cheque_details']['cheq_amt_t'] = [
        '#type' => 'textfield',
        '#default_value' => $chq17,
        '#title' => $this->t('Cheque Amount'),
        '#size' => 54,
      ];
      $form['temp_cheque_address']['temp_chq_address'] = [
        '#type' => 'textarea',
        '#default_value' => $chq10,
        '#title' => $this->t('Address Street 1'),
        '#attributes' => ['readonly' => 'readonly'],
      ];
      $form['temp_cheque_address']['temp_city'] = [
        '#type' => 'textfield',
        '#default_value' => $chq12,
        '#title' => $this->t('City'),
        '#size' => 35,
        '#attributes' => ['readonly' => 'readonly'],
      ];
      $form['temp_cheque_address']['temp_state'] = [
        '#type' => 'textfield',
        '#default_value' => $chq13,
        '#title' => $this->t('State'),
        '#size' => 35,
        '#attributes' => ['readonly' => 'readonly'],
      ];
      $form['temp_cheque_address']['temp_pincode'] = [
        '#type' => 'textfield',
        '#default_value' => $chq14,
        '#title' => $this->t('Zipcode'),
        '#size' => 35,
        '#attributes' => ['readonly' => 'readonly'],
      ];
    }
    else {
      $form['stu_cheque_details']['cheque_no'] = [
        '#type' => 'textfield',
        '#default_value' => $chq1,
        '#title' => $this->t('Cheque No'),
      ];
      $form['tea_cheque_details']['cheque_no_t'] = [
        '#type' => 'textfield',
        '#default_value' => $chq16,
        '#title' => $this->t('Cheque No'),
      ];
      $form['perm_cheque_address']['chq_address'] = [
        '#type' => 'textarea',
        '#default_value' => $chq2,
        '#title' => $this->t('Address Street 1'),
      ];
      $form['perm_cheque_address']['perm_city'] = [
        '#type' => 'textfield',
        '#default_value' => $chq7,
        '#title' => $this->t('City'),
        '#size' => 35,
      ];
      $form['perm_cheque_address']['perm_state'] = [
        '#type' => 'textfield',
        '#default_value' => $chq8,
        '#title' => $this->t('State'),
        '#size' => 35,
      ];
      $form['perm_cheque_address']['perm_pincode'] = [
        '#type' => 'textfield',
        '#default_value' => $chq9,
        '#title' => $this->t('Zip code'),
        '#size' => 35,
      ];
      $form['perm_cheque_address']['same_address'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Same As Permanent Address'),
        '#attributes' => [
          'onclick' => 'copy_address()',
        ],
      ];
      $form['stu_cheque_details']['cheq_amt'] = [
        '#type' => 'textfield',
        '#default_value' => $chq3,
        '#title' => $this->t('Cheque Amount'),
      ];
      $form['tea_cheque_details']['cheq_amt'] = [
        '#type' => 'textfield',
        '#default_value' => $chq17,
        '#title' => $this->t('Cheque Amount'),
      ];
      $form['temp_cheque_address']['temp_chq_address'] = [
        '#type' => 'textarea',
        '#default_value' => $chq10,
        '#title' => $this->t('Address Street 1'),
      ];
      $form['temp_cheque_address']['temp_city'] = [
        '#type' => 'textfield',
        '#default_value' => $chq12,
        '#title' => $this->t('City'),
        '#size' => 35,
      ];
      $form['temp_cheque_address']['temp_state'] = [
        '#type' => 'textfield',
        '#default_value' => $chq13,
        '#title' => $this->t('State'),
        '#size' => 35,
      ];
      $form['temp_cheque_address']['temp_pincode'] = [
        '#type' => 'textfield',
        '#default_value' => $chq14,
        '#title' => $this->t('Zip code'),
        '#size' => 35,
      ];
      $form['temp_cheque_address']['same_address'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Same As Permanent Address'),
        '#attributes' => [
          'onclick' => 'copy_address()',
        ],
      ];
    }

    $form['cheque_delivery']['cheque_sent'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Cheque Sent'),
      '#default_value' => $chq4,
      '#description' => $this->t('Check if the Cheque has been sent to the user.'),
      '#attributes' => [
        'id' => 'cheque_sent',
      ],
    ];
    $form['cheque_delivery']['cheque_cleared'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Cheque Cleared'),
      '#default_value' => $chq5,
      '#description' => $this->t('Check if the Cheque has been <strong>Realised</strong> to the User Account.'),
      '#attributes' => [
        'id' => 'cheque_cleared',
      ],
    ];
    $form['commentf']['comment_cheque'] = [
      '#type' => 'textarea',
      '#size' => 35,
      '#attributes' => [
        'id' => 'comment',
      ],
      '#default_value' => $chq15,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    $form['cancel'] = [
      '#type' => 'markup',
      '#markup' => Link::fromTextAndUrl($this->t('Cancel'), Url::fromRoute('textbook_companion._proposal_all'))->toString(),
    ];

    $form['#attached']['html_head'][] = [
      [
        '#tag' => 'script',
        '#value' => 'function copy_address(){var permAddr=document.querySelector(\'[name="perm_cheque_address[chq_address]"]\');var permCity=document.querySelector(\'[name="perm_cheque_address[perm_city]"]\');var permState=document.querySelector(\'[name="perm_cheque_address[perm_state]"]\');var permPin=document.querySelector(\'[name="perm_cheque_address[perm_pincode]"]\');var tempAddr=document.querySelector(\'[name="temp_cheque_address[temp_chq_address]"]\');var tempCity=document.querySelector(\'[name="temp_cheque_address[temp_city]"]\');var tempState=document.querySelector(\'[name="temp_cheque_address[temp_state]"]\');var tempPin=document.querySelector(\'[name="temp_cheque_address[temp_pincode]"]\');var permCheck=document.querySelector(\'[name="perm_cheque_address[same_address]"]\');var tempCheck=document.querySelector(\'[name="temp_cheque_address[same_address]"]\');if(!permAddr||!tempAddr){return;}if((permCheck&&permCheck.checked)||(tempCheck&&tempCheck.checked)){tempAddr.value=permAddr.value;tempCity.value=permCity?permCity.value:\'\';tempState.value=permState?permState.value:\'\';tempPin.value=permPin?permPin.value:\'\';}}',
      ],
      'textbook_companion_copy_address',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $connection = \Drupal::database();
    $proposal_id = (int) $form_state->getValue('proposal_id');

    $fields = [
      'cheque_no' => $form_state->getValue('cheque_no'),
      'cheque_amt' => $form_state->getValue('cheq_amt'),
      'address' => $form_state->getValue('chq_address'),
      'perm_city' => $form_state->getValue('perm_city'),
      'perm_state' => $form_state->getValue('perm_state'),
      'perm_pincode' => $form_state->getValue('perm_pincode'),
      'temp_chq_address' => $form_state->getValue('temp_chq_address'),
      'temp_city' => $form_state->getValue('temp_city'),
      'temp_state' => $form_state->getValue('temp_state'),
      'temp_pincode' => $form_state->getValue('temp_pincode'),
      'commentf' => $form_state->getValue('comment_cheque'),
      't_cheque_no' => $form_state->getValue('cheque_no_t'),
    ];

    $teacher_amt = $form_state->getValue('cheq_amt_t');
    if ($teacher_amt === NULL) {
      $teacher_amt = $form_state->getValue('cheq_amt');
    }
    $fields['t_cheque_amt'] = $teacher_amt;

    $alt_mobile = $form_state->getValue('mobileno2');
    if ($alt_mobile !== NULL) {
      $fields['alt_mobno'] = $alt_mobile;
    }

    $connection->update('textbook_companion_cheque')
      ->fields($fields)
      ->condition('proposal_id', $proposal_id)
      ->execute();

    $proposal_data = $connection->select('textbook_companion_proposal', 'tp')
      ->fields('tp')
      ->condition('id', $proposal_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    $book_user = $proposal_data ? User::load($proposal_data->uid) : NULL;
    $email_to = $book_user?->getEmail() ?? '';

    $config = \Drupal::config('textbook_companion.settings');
    $from = (string) $config->get('textbook_companion_from_email');
    $bcc = (string) $config->get('textbook_companion_emails');
    $cc = (string) $config->get('textbook_companion_cc_emails');

    $headers = [
      'From' => $from,
      'MIME-Version' => '1.0',
      'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
      'Content-Transfer-Encoding' => '8Bit',
      'X-Mailer' => 'Drupal',
      'Cc' => $cc,
      'Bcc' => $bcc,
    ];

    if ((int) $form_state->getValue('cheque_sent') === 1) {
      $connection->update('textbook_companion_cheque')
        ->fields(['cheque_sent' => $form_state->getValue('cheque_sent')])
        ->condition('proposal_id', $proposal_id)
        ->execute();

      if ($book_user && $email_to !== '') {
        $params['cheque_sent']['proposal_id'] = $proposal_id;
        $params['cheque_sent']['user_id'] = $proposal_data->uid;
        $params['cheque_sent']['headers'] = $headers;
        $langcode = $book_user->getPreferredLangcode();
        $result = \Drupal::service('plugin.manager.mail')->mail('textbook_companion', 'cheque_sent', $email_to, $langcode, $params, $from, TRUE);
        if (empty($result['result'])) {
          $this->messenger()->addError($this->t('Error sending email message.'));
        }
      }
      $this->messenger()->addStatus($this->t('Cheque for Book proposal has been Sent. User has been notified .'));
    }

    if ((int) $form_state->getValue('cheque_cleared') === 1) {
      $connection->update('textbook_companion_cheque')
        ->fields([
          'cheque_cleared' => $form_state->getValue('cheque_cleared'),
        ])
        ->condition('proposal_id', $proposal_id)
        ->execute();

      $connection->update('textbook_companion_cheque')
        ->expression('cheque_dispatch_date', 'NOW()')
        ->condition('proposal_id', $proposal_id)
        ->execute();

      $this->messenger()->addStatus($this->t('Cheque Has Been Debited into User Account.'));
    }

    if ($form_state->getValue('comment_cheque')) {
      if ($book_user && $email_to !== '') {
        $params['remark']['proposal_id'] = $proposal_id;
        $params['remark']['user_id'] = $proposal_data->uid;
        $params['remark']['has_remark'] = 1;
        $params['remark']['remark_text'] = (string) $form_state->getValue('comment_cheque');
        $params['remark']['headers'] = $headers;
        $langcode = $book_user->getPreferredLangcode();
        $result = \Drupal::service('plugin.manager.mail')->mail('textbook_companion', 'remark', $email_to, $langcode, $params, $from, TRUE);
        if (empty($result['result'])) {
          $this->messenger()->addError($this->t('Error sending email message.'));
        }
      }
      $this->messenger()->addStatus($this->t('Remark Updated. User has been notified'));
    }
    else {
      if ($book_user && $email_to !== '') {
        $params['remark']['proposal_id'] = $proposal_id;
        $params['remark']['user_id'] = $proposal_data->uid;
        $params['remark']['has_remark'] = 0;
        $params['remark']['headers'] = $headers;
        $langcode = $book_user->getPreferredLangcode();
        $result = \Drupal::service('plugin.manager.mail')->mail('textbook_companion', 'remark', $email_to, $langcode, $params, $from, TRUE);
        if (empty($result['result'])) {
          $this->messenger()->addError($this->t('Error sending email message.'));
        }
      }
      $this->messenger()->addStatus($this->t('No Remarks. User has been notified .'));
    }
  }

}
