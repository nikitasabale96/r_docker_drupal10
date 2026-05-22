<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\TextbookCompanionSearchForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

class TextbookCompanionSearchForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'textbook_companion_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#redirect'] = FALSE;
    $form['search'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search'),
      '#size' => 48,
    ];
    $form['search_by_title'] = [
      '#type' => 'checkbox',
      '#default_value' => TRUE,
      '#title' => $this->t('Search by Title of the Book'),
    ];
    $form['search_by_author'] = [
      '#type' => 'checkbox',
      '#default_value' => TRUE,
      '#title' => $this->t('Search by Author of the Book'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
    ];
    $form['cancel'] = [
      '#type' => 'item',
      '#markup' => Link::fromTextAndUrl($this->t('Cancel'), Url::fromRoute('<front>'))->toString(),
    ];

    if ($form_state->isSubmitted()) {
      $search = (string) $form_state->getValue('search');
      $search_by_title = (bool) $form_state->getValue('search_by_title');
      $search_by_author = (bool) $form_state->getValue('search_by_author');

      if (!$search_by_title && !$search_by_author) {
        $this->messenger()->addError($this->t('Please select whether to search by Title and/or Author of the Book.'));
        return $form;
      }

      $query = \Drupal::database()->select('textbook_companion_preference');
      $query->fields('textbook_companion_preference');
      $query->condition('approval_status', 1);

      if ($search_by_title && $search_by_author) {
        $or = $query->orConditionGroup();
        $or->condition('book', '%' . $search . '%', 'LIKE');
        $or->condition('author', '%' . $search . '%', 'LIKE');
        $query->condition($or);
      }
      elseif ($search_by_title) {
        $query->condition('book', '%' . $search . '%', 'LIKE');
      }
      elseif ($search_by_author) {
        $query->condition('author', '%' . $search . '%', 'LIKE');
      }

      $search_rows = [];
      $search_q = $query->execute();
      while ($search_data = $search_q->fetchObject()) {
        $search_rows[] = [
          Link::fromTextAndUrl($search_data->book, Url::fromRoute('textbook_companion.book_run_form_with_book', ['book_id' => $search_data->id]))->toString(),
          $search_data->author,
        ];
      }

      if ($search_rows) {
        $form['search_results'] = [
          '#type' => 'table',
          '#caption' => $this->t('Search results for "@term"', ['@term' => $search]),
          '#header' => [
            $this->t('Title of the Book'),
            $this->t('Author Name'),
          ],
          '#rows' => $search_rows,
        ];
      }
      else {
        $form['search_results'] = [
          '#type' => 'item',
          '#title' => $this->t('Search results for "@term"', ['@term' => $search]),
          '#markup' => $this->t('No results found'),
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

}
