<?php

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class DataentryEditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dataentry_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    $id = $id ?? $this->getRouteMatch()->getParameter('id');
    $id = (int) $id;

    $preference_data = \Drupal::database()->select('textbook_companion_preference')
      ->fields('textbook_companion_preference')
      ->condition('id', $id)
      ->execute()
      ->fetchObject();

    if (!$preference_data) {
      $this->messenger()->addError($this->t('Invalid book selected.'));
      return $form;
    }

    $form['id'] = [
      '#type' => 'hidden',
      '#required' => TRUE,
      '#value' => $id,
    ];
    $form['book'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title of the book'),
      '#size' => 30,
      '#maxlength' => 100,
      '#required' => TRUE,
      '#default_value' => $preference_data->book,
    ];
    $form['author'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Author Name'),
      '#size' => 30,
      '#maxlength' => 100,
      '#required' => TRUE,
      '#default_value' => $preference_data->author,
    ];
    $form['isbn'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ISBN No'),
      '#size' => 30,
      '#maxlength' => 25,
      '#required' => TRUE,
      '#attributes' => [
        'readonly' => 'readonly',
      ],
      '#default_value' => $preference_data->isbn,
    ];
    $form['publisher'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Publisher & Place'),
      '#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE,
      '#default_value' => $preference_data->publisher,
    ];
    $form['edition'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Edition'),
      '#size' => 4,
      '#maxlength' => 2,
      '#required' => TRUE,
      '#default_value' => $preference_data->edition,
    ];
    $form['year'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Year of pulication'),
      '#size' => 4,
      '#maxlength' => 4,
      '#required' => TRUE,
      '#default_value' => $preference_data->year,
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::database()->update('textbook_companion_preference')
      ->fields([
        'book' => $form_state->getValue('book'),
        'author' => $form_state->getValue('author'),
        'isbn' => $form_state->getValue('isbn'),
        'publisher' => $form_state->getValue('publisher'),
        'edition' => $form_state->getValue('edition'),
        'year' => $form_state->getValue('year'),
      ])
      ->condition('id', $form_state->getValue('id'))
      ->execute();

    $this->messenger()->addStatus($this->t('Book details updated successfully'));
    $form_state->setRedirect('textbook_companion._data_entry_proposal_all');
  }

}
