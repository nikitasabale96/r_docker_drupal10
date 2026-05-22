<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\TextbookCompanionBrowseCollegeForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

class TextbookCompanionBrowseCollegeForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'textbook_companion_browse_college_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $usage_default_value = $form_state->getValue(['college_info', 'college']);
    if ($usage_default_value === NULL) {
      $usage_default_value = '0';
    }
    $form['college_info'] = [
      '#type' => 'fieldset',
      '#prefix' => '<div id="college-info-wrapper">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
    ];
    $form['college_info']['college'] = [
      '#type' => 'select',
      '#title' => $this->t('College Name'),
      '#options' => $this->listOfColleges(),
      '#default_value' => $usage_default_value,
      '#ajax' => [
        'callback' => '::ajaxCollegeDetailsCallback',
        'wrapper' => 'college-info-wrapper',
      ],
    ];
    if ($usage_default_value != '0') {
      $form['college_info']['book_details'] = $this->listBooksByCollege($usage_default_value);
    }
    return $form;
  }

  public function ajaxCollegeDetailsCallback(array &$form, FormStateInterface $form_state) {
    return $form['college_info'];
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  private function listOfColleges() {
    $college_names = [
      '0' => '--- select ---',
    ];
    $query = \Drupal::database()->select('textbook_companion_proposal', 'p');
    $query->fields('p', ['university']);
    $query->distinct();
    $or = $query->orConditionGroup();
    $or->condition('proposal_status', 1);
    $or->condition('proposal_status', 3);
    $query->condition($or);
    $query->orderBy('university', 'ASC');
    $college_names_q = $query->execute();
    while ($college_names_data = $college_names_q->fetchObject()) {
      $college_names[$college_names_data->university] = $college_names_data->university;
    }
    return $college_names;
  }

  private function listBooksByCollege($college) {
    $query = \Drupal::database()->select('textbook_companion_proposal', 'pro');
    $query->fields('pro', [
      'full_name',
      'proposal_status',
    ]);
    $query->fields('pre', [
      'id',
      'book',
      'isbn',
    ]);
    $query->innerJoin('textbook_companion_preference', 'pre', 'pre.proposal_id = pro.id');
    $query->condition('pro.university', $college);
    $or = $query->orConditionGroup();
    $or->condition('pro.proposal_status', 1);
    $or->condition('pro.proposal_status', 3);
    $query->condition($or);
    $query->condition('pre.approval_status', 1);
    $result = $query->execute();
    $rows = [];
    $sno = 1;
    while ($row = $result->fetchObject()) {
      if ($row->proposal_status == 1) {
        $status = [
          'data' => $this->t('Approved'),
          'style' => 'color: orange;',
        ];
        $book_cell = $row->book;
      }
      else {
        $status = [
          'data' => $this->t('Completed'),
          'style' => 'color: green;',
        ];
        $book_cell = Link::fromTextAndUrl($row->book, Url::fromRoute('textbook_companion.book_run_form_with_book', ['book_id' => $row->id]))->toString();
      }
      $rows[] = [
        $sno++,
        $row->full_name,
        $book_cell,
        str_replace('-', '', $row->isbn),
        $status,
      ];
    }
    if (!$rows) {
      return [
        '#type' => 'item',
        '#markup' => $this->t('No books available for the selected college.'),
      ];
    }
    return [
      '#type' => 'table',
      '#header' => [
        $this->t('SNO'),
        $this->t('Name'),
        $this->t('Book'),
        $this->t('ISBN'),
        $this->t('Status'),
      ],
      '#rows' => $rows,
    ];
  }

}
