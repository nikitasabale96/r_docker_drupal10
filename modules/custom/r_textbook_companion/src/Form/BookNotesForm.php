<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\BookNotesForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

class BookNotesForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'book_notes_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $preference_id = NULL) {
    if ($preference_id === NULL) {
      $preference_id = $this->getRouteMatch()->getParameter('preference_id');
    }
    $preference_id = (int) $preference_id;
    if ($preference_id <= 0) {
      $this->messenger()->addError($this->t('Invalid book selected. Please try again.'));
      $form_state->setRedirect('textbook_companion.bulk_approval_form');
      return [];
    }
    $result = \Drupal::database()->select('textbook_companion_preference')
      ->fields('textbook_companion_preference')
      ->condition('id', $preference_id)
      ->execute();
    if ($result) {
      if ($row = $result->fetchObject()) {
        /* everything ok */
      }
      else {
        $this->messenger()->addError($this->t('Invalid book selected. Please try again.'));
        $form_state->setRedirect('textbook_companion.bulk_approval_form');
        return [];
      }
    }
    else {
      $this->messenger()->addError($this->t('Invalid book selected. Please try again.'));
      $form_state->setRedirect('textbook_companion.bulk_approval_form');
      return [];
    }
    /* get current notes */
    $notes = '';
    $notes_q = \Drupal::database()->select('textbook_companion_notes')
      ->fields('textbook_companion_notes')
      ->condition('preference_id', $preference_id)
      ->range(0, 1)
      ->execute();
    if ($notes_q) {
      $notes_data = $notes_q->fetchObject();
      $notes = $notes_data->notes;
    }
    $book_details = $this->bookInformation($preference_id);
    $form['book_details'] = [
      '#type' => 'item',
      '#markup' => '<span style="color: rgb(128, 0, 0);"><strong>About the Book</strong></span><br />' . '<strong>Author:</strong> ' . $book_details->author . '<br />' . '<strong>Title of the Book:</strong> ' . $book_details->book . '<br />' . '<strong>Publisher:</strong> ' . $book_details->publisher . '<br />' . '<strong>Year:</strong> ' . $book_details->year . '<br />' . '<strong>Edition:</strong> ' . $book_details->edition . '<br /><br />' . '<span style="color: rgb(128, 0, 0);"><strong>About the Contributor</strong></span><br />' . '<strong>Contributor Name:</strong> ' . $book_details->full_name . ', ' . $book_details->course . ', ' . $book_details->branch . ', ' . $book_details->university . '<br />',
    ];
    $form['notes'] = [
      '#type' => 'textarea',
      '#rows' => 20,
      '#title' => $this->t('Notes for Reviewers'),
      '#default_value' => $notes,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    $form['cancel'] = [
      '#type' => 'markup',
      '#value' => Link::fromTextAndUrl($this->t('Back'), Url::fromRoute('textbook_companion.bulk_approval_form'))->toString(),
    ];
    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $preference_id = $this->getRouteMatch()->getParameter('preference_id');
    $preference_id = (int) $preference_id;
    if ($preference_id <= 0) {
      $this->messenger()->addError($this->t('Invalid book selected. Please try again.'));
      $form_state->setRedirect('textbook_companion.bulk_approval_form');
      return;
    }
    $result = \Drupal::database()->select('textbook_companion_preference')
      ->fields('textbook_companion_preference')
      ->condition('id', $preference_id)
      ->execute();
    if ($result) {
      if ($row = $result->fetchObject()) {
        /* everything ok */
      }
      else {
        $this->messenger()->addError($this->t('Invalid book selected. Please try again.'));
        $form_state->setRedirect('textbook_companion.bulk_approval_form');
        return;
      }
    }
    else {
      $this->messenger()->addError($this->t('Invalid book selected. Please try again.'));
      $form_state->setRedirect('textbook_companion.bulk_approval_form');
      return;
    }
    /* find existing notes */
    $notes_q = \Drupal::database()->select('textbook_companion_notes')
      ->fields('textbook_companion_notes')
      ->condition('preference_id', $preference_id)
      ->range(0, 1)
      ->execute();
    $notes_data = $notes_q->fetchObject();
    /* add or update notes in database */
    if ($notes_data) {
      \Drupal::database()->update('textbook_companion_notes')
        ->fields(['notes' => $form_state->getValue('notes')])
        ->condition('id', $notes_data->id)
        ->execute();
      $this->messenger()->addStatus($this->t('Notes updated successfully.'));
    }
    else {
      \Drupal::database()->insert('textbook_companion_notes')
        ->fields([
          'preference_id' => $preference_id,
          'notes' => $form_state->getValue('notes'),
        ])
        ->execute();
      $this->messenger()->addStatus($this->t('Notes added successfully.'));
    }
  }

  private function bookInformation($preference_id) {
    return \Drupal::database()->select('textbook_companion_proposal', 'proposal')
      ->fields('preference', [
        'book',
        'author',
        'isbn',
        'publisher',
        'edition',
        'year',
      ])
      ->fields('proposal', [
        'full_name',
        'faculty',
        'reviewer',
        'course',
        'branch',
        'university',
      ])
      ->leftJoin('textbook_companion_preference', 'preference', 'proposal.id=preference.proposal_id')
      ->condition('preference.id', $preference_id)
      ->execute()
      ->fetchObject();
  }

}
