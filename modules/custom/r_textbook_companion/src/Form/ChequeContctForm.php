<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\ChequeContctForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

class ChequeContctForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cheque_contct_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $current_user = $this->currentUser();
    $connection = \Drupal::database();

    $proposal_id = $connection->select('textbook_companion_proposal', 'tp')
      ->fields('tp', ['id'])
      ->condition('uid', $current_user->id())
      ->range(0, 1)
      ->execute()
      ->fetchField();
    $proposal_id = $proposal_id ? (int) $proposal_id : 0;

    if ($current_user->id()) {
      $form['#redirect'] = FALSE;

      $form['search'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Search'),
        '#size' => 48,
      ];

      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Search'),
      ];

      $form['cancel'] = [
        '#type' => 'markup',
        '#markup' => Link::fromTextAndUrl($this->t('Cancel'), Url::fromRoute('<front>'))->toString(),
      ];

      $form['submit2'] = [
        '#type' => 'markup',
        '#markup' => Link::fromTextAndUrl($this->t('Generate Report'), Url::fromRoute('textbook_companion.cheque_report_form'))->toString(),
        '#attributes' => [
          'id' => 'perm_report',
        ],
      ];

      $search_term = trim((string) $form_state->getValue('search'));

      $query = $connection->select('textbook_companion_proposal', 'p');
      $query->join('textbook_companion_cheque', 'c', 'p.id = c.proposal_id');
      $query->fields('p', ['full_name']);
      $query->fields('c', ['address_con', 'cheque_no', 'cheque_dispatch_date', 'proposal_id']);
      $query->condition('c.address_con', 'Submitted');
      if ($search_term !== '') {
        $query->condition('p.full_name', '%' . $search_term . '%', 'LIKE');
      }
      $result = $query->execute();

      $search_rows = [];
      while ($search_data = $result->fetchObject()) {
        $search_rows[] = [
          Link::fromTextAndUrl(
            $search_data->full_name,
            Url::fromRoute('textbook_companion.cheque_status_form', ['proposal_id' => $search_data->proposal_id])
          )->toString(),
          $search_data->address_con,
          $search_data->cheque_no,
          $search_data->cheque_dispatch_date,
        ];
      }

      $results_title = $search_term !== ''
        ? $this->t('Search results for "@term"', ['@term' => $search_term])
        : $this->t('Search results');

      if ($search_rows) {
        $search_header = [
          $this->t('Name Of The Student'),
          $this->t('Application Form Status'),
          $this->t('Cheque No'),
          $this->t('Cheque Clearance Date'),
        ];
        $table = [
          '#theme' => 'table',
          '#header' => $search_header,
          '#rows' => $search_rows,
        ];
        $form['search_results'] = [
          '#type' => 'item',
          '#title' => $results_title,
          '#markup' => \Drupal::service('renderer')->render($table),
        ];
      }
      else {
        $form['search_results'] = [
          '#type' => 'item',
          '#title' => $results_title,
          '#markup' => $this->t('No results found'),
        ];
      }

      return $form;
    }

    $paper_data = $proposal_id ? $connection->select('textbook_companion_paper', 'tp')
      ->fields('tp')
      ->condition('proposal_id', $proposal_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject() : NULL;

    $proposal_data = $proposal_id ? $connection->select('textbook_companion_proposal', 'tp')
      ->fields('tp')
      ->condition('id', $proposal_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject() : NULL;

    $form2 = $paper_data->internship_form ?? 0;
    $form3 = $paper_data->copyright_form ?? 0;
    $form4 = $paper_data->undertaking_form ?? 0;
    $form5 = $paper_data->reciept_form ?? 0;

    $full_name = $proposal_data->full_name ?? '';
    $how_project = $proposal_data->how_project ?? '';
    $mobile = $proposal_data->mobile ?? '';
    $course = $proposal_data->course ?? '';
    $branch = $proposal_data->branch ?? '';
    $university = $proposal_data->university ?? '';

    if ($form2 && $form3 && $form4 && $form5) {
      $form['full_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Full Name'),
        '#size' => 30,
        '#maxlength' => 50,
        '#default_value' => $full_name,
      ];
      $form['mobile'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Mobile No.'),
        '#size' => 30,
        '#maxlength' => 15,
        '#default_value' => $mobile,
      ];
      $form['how_project'] = [
        '#type' => 'select',
        '#title' => $this->t('How did you come to know about this project'),
        '#options' => [
          'eSim Website' => 'eSim Website',
          'Friend' => 'Friend',
          'Professor/Teacher' => 'Professor/Teacher',
          'Mailing List' => 'Mailing List',
          'Poster in my/other college' => 'Poster in my/other college',
          'Others' => 'Others',
        ],
        '#default_value' => $how_project,
      ];
      $form['course'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Course'),
        '#size' => 30,
        '#maxlength' => 50,
        '#default_value' => $course,
      ];
      $form['branch'] = [
        '#type' => 'select',
        '#title' => $this->t('Department/Branch'),
        '#options' => [
          'Electrical Engineering' => 'Electrical Engineering',
          'Electronics Engineering' => 'Electronics Engineering',
          'Computer Engineering' => 'Computer Engineering',
          'Chemical Engineering' => 'Chemical Engineering',
          'Instrumentation Engineering' => 'Instrumentation Engineering',
          'Mechanical Engineering' => 'Mechanical Engineering',
          'Civil Engineering' => 'Civil Engineering',
          'Physics' => 'Physics',
          'Mathematics' => 'Mathematics',
          'Others' => 'Others',
        ],
        '#default_value' => $branch,
      ];

      $form['university'] = [
        '#type' => 'textfield',
        '#title' => $this->t('University/Institute'),
        '#size' => 30,
        '#maxlength' => 100,
        '#default_value' => $university,
      ];
      $form['addressforcheque'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Address For Mailing Cheque'),
        '#size' => 30,
        '#maxlength' => 100,
      ];
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
      ];
      $form['cancel'] = [
        '#type' => 'markup',
        '#markup' => $this->t('Cancel'),
      ];
    }
    if (!$form2) {
      $this->messenger()->addError($this->t('Internship Form has not been recieved.'));
    }
    if (!$form3) {
      $this->messenger()->addError($this->t('Copyright Form has not been recieved.'));
    }
    if (!$form4) {
      $this->messenger()->addError($this->t('Undertaking Form has not been recieved.'));
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($this->currentUser()->id()) {
      $form_state->setRebuild(TRUE);
    }
  }

}
