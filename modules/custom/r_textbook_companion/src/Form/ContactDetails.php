<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\ContactDetails.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

class ContactDetails extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contact_details';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $current_user = $this->currentUser();
    $connection = \Drupal::database();

    if (!\Drupal::request()->query->has('msg')) {
      $this->messenger()->addError(Markup::create('<strong>Caution</strong>:Please update Contact Detail carefully as this will be used for future reference during <strong>Payment</strong></li></ul>'));
    }

    $proposal = $connection->select('textbook_companion_proposal', 'tp')
      ->fields('tp')
      ->condition('uid', $current_user->id())
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if (!$proposal) {
      $link = Link::fromTextAndUrl($this->t('Book Proposal Form'), Url::fromRoute('textbook_companion.proposal_all'))->toString();
      $this->messenger()->addError($this->t('Fill Up The @link', ['@link' => Markup::create($link)]));
      return [];
    }

    $approved_preference = $connection->select('textbook_companion_preference', 'tp')
      ->fields('tp')
      ->condition('approval_status', 1)
      ->condition('proposal_id', $proposal->id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if (!$approved_preference || empty($approved_preference->approval_status)) {
      $this->messenger()->addError($this->t('Book Proposal Has Not Been Accpeted .'));
      return [];
    }

    $proposal_id = (int) $proposal->id;

    $cheque_data = $connection->select('textbook_companion_cheque', 'c')
      ->fields('c')
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

    $user_entity = User::load($current_user->id());

    $form['candidate_detail'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Candidate Detail'),
      '#attributes' => [
        'id' => 'candidate_detail',
      ],
    ];
    $form['proposal_id'] = [
      '#type' => 'hidden',
      '#default_value' => $proposal_id,
    ];
    $form['candidate_detail']['fullname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Full Name'),
      '#size' => 48,
      '#default_value' => $proposal->full_name ?? '',
    ];
    $form['candidate_detail']['email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email'),
      '#size' => 48,
      '#value' => $user_entity?->getEmail() ?? '',
      '#disabled' => TRUE,
    ];
    $form['candidate_detail']['mobileno1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mobile No'),
      '#size' => 48,
      '#default_value' => $proposal->mobile ?? '',
    ];

    $form['candidate_detail']['mobileno2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Alternate Mobile No'),
      '#size' => 48,
      '#default_value' => $cheque_data->alt_mobno ?? '',
    ];

    $paper_data = $connection->select('textbook_companion_paper', 'tp')
      ->fields('tp')
      ->condition('proposal_id', $proposal_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    $form_html = '<ul>';
    if (!empty($paper_data->internship_form)) {
      $form_html .= '<li><strong>Internship Application </strong> Form Submitted</li>';
    }
    else {
      $form_html .= '<li><strong>Internship Application </strong> Form Not Submitted.<br>Please submit it as soon as possible.</li>';
    }
    if (!empty($paper_data->copyright_form)) {
      $form_html .= '<li><strong>Copyright Application </strong> Form Submitted</li>';
    }
    else {
      $form_html .= '<li><strong>Copyright Application</strong> Form Not Submitted.<br>Please submit it as soon as possible.</li>';
    }
    if (!empty($paper_data->undertaking_form)) {
      $form_html .= '<li><strong>Undertaking Application </strong> Form Submitted</li>';
    }
    else {
      $form_html .= '<li><strong>Undertaking Application</strong> Form Not Submitted.<br>Please submit it as soon as possible.</li>';
    }
    $form_html .= '</ul>';

    $form['Application Status'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Application Form Status'),
      '#attributes' => [
        'id' => 'app_status',
      ],
    ];
    $form['Application Status']['status'] = [
      '#type' => 'item',
      '#markup' => $form_html,
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
    $form['perm_cheque_address']['chq_address'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Address'),
      '#size' => 35,
      '#default_value' => $cheque_data->address ?? '',
    ];
    $form['perm_cheque_address']['perm_city'] = [
      '#type' => 'textfield',
      '#default_value' => $cheque_data->perm_city ?? '',
      '#title' => $this->t('City'),
      '#size' => 35,
    ];
    $form['perm_cheque_address']['perm_state'] = [
      '#type' => 'textfield',
      '#default_value' => $cheque_data->perm_state ?? '',
      '#title' => $this->t('State'),
      '#size' => 35,
    ];
    $form['perm_cheque_address']['perm_pincode'] = [
      '#type' => 'textfield',
      '#default_value' => $cheque_data->perm_pincode ?? '',
      '#title' => $this->t('Zip code'),
      '#size' => 35,
    ];
    $form['temp_cheque_address']['temp_chq_address'] = [
      '#type' => 'textarea',
      '#default_value' => $cheque_data->temp_chq_address ?? '',
      '#title' => $this->t('Address'),
      '#size' => 35,
    ];
    $form['temp_cheque_address']['temp_city'] = [
      '#type' => 'textfield',
      '#default_value' => $cheque_data->temp_city ?? '',
      '#title' => $this->t('City'),
      '#size' => 35,
    ];
    $form['temp_cheque_address']['temp_state'] = [
      '#type' => 'textfield',
      '#default_value' => $cheque_data->temp_state ?? '',
      '#title' => $this->t('State'),
      '#size' => 35,
    ];
    $form['temp_cheque_address']['temp_pincode'] = [
      '#type' => 'textfield',
      '#default_value' => $cheque_data->temp_pincode ?? '',
      '#title' => $this->t('Zip code'),
      '#size' => 35,
    ];
    $form['temp_cheque_address']['same_address'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Same As Permanent Address'),
    ];

    if (!empty($cheque_data->commentf)) {
      $form['commentu'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Remarks'),
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
        '#attributes' => [
          'id' => 'comment_cheque',
        ],
      ];
      $form['commentu']['comment_cheque'] = [
        '#type' => 'textarea',
        '#size' => 35,
        '#default_value' => $cheque_data->commentf ?? '',
        '#attributes' => [
          'readonly' => 'readonly',
        ],
      ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
    ];
    $form['cancel'] = [
      '#type' => 'markup',
      '#markup' => Link::fromTextAndUrl($this->t('Cancel'), Url::fromRoute('textbook_companion._proposal_all'))->toString(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_user = $this->currentUser();
    $connection = \Drupal::database();

    $proposal = $connection->select('textbook_companion_proposal', 'tp')
      ->fields('tp')
      ->condition('uid', $current_user->id())
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if (!$proposal) {
      return;
    }

    $connection->update('textbook_companion_cheque')
      ->fields([
        'alt_mobno' => $form_state->getValue('mobileno2'),
        'address' => $form_state->getValue('chq_address'),
        'perm_city' => $form_state->getValue('perm_city'),
        'perm_state' => $form_state->getValue('perm_state'),
        'perm_pincode' => $form_state->getValue('perm_pincode'),
        'temp_chq_address' => $form_state->getValue('temp_chq_address'),
        'temp_city' => $form_state->getValue('temp_city'),
        'temp_state' => $form_state->getValue('temp_state'),
        'temp_pincode' => $form_state->getValue('temp_pincode'),
        'address_con' => 'Submitted',
      ])
      ->condition('proposal_id', $proposal->id)
      ->execute();

    $this->messenger()->addStatus($this->t('Contact Details Has Been Updated.....!'));
    $form_state->setRedirect('textbook_companion.contact_details', [], ['query' => ['msg' => 0]]);
  }

}
