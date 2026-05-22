<?php

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class TextbookCompanionAicteProposalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'textbook_companion_aicte_proposal_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = [];
    $result = \Drupal::database()->select('textbook_companion_aicte')
      ->fields('textbook_companion_aicte')
      ->condition('status', 0)
      ->execute();
    while ($row = $result->fetchObject()) {
      $label = $row->book . ' by ' . $row->author;
      if (!empty($row->edition)) {
        $label .= ' (ed: ' . $row->edition . ')';
      }
      if (!empty($row->year)) {
        $label .= ' (pub: ' . $row->year . ')';
      }
      $key = isset($row->id) ? $row->id : md5($row->book . '|' . $row->author . '|' . $row->edition . '|' . $row->year);
      $options[$key] = $label;
    }
    if (!$options) {
      $form['message'] = [
        '#type' => 'markup',
        '#markup' => $this->t('No AICTE books are currently available for proposal.'),
      ];
      return $form;
    }
    $form['aicte_books'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select 3 books'),
      '#options' => $options,
      '#required' => TRUE,
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
    $selected = array_filter($form_state->getValue('aicte_books') ?: []);
    if (count($selected) !== 3) {
      $form_state->setErrorByName('aicte_books', $this->t('Please select exactly 3 books.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = $this->currentUser();
    $selected = array_filter($form_state->getValue('aicte_books') ?: []);
    \Drupal::state()->set('aicte_' . $user->id(), $selected);
    $this->messenger()->addStatus($this->t('Your AICTE book selections have been saved.'));
    $form_state->setRedirect('textbook_companion.proposal_all');
  }

}
