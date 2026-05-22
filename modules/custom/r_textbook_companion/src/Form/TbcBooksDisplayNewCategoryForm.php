<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\TbcBooksDisplayNewCategoryForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

class TbcBooksDisplayNewCategoryForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tbc_books_display_new_category_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $category_default_value = 0;
    $countresult = \Drupal::database()->query("SELECT count(DISTINCT pe.id) c
      FROM textbook_companion_preference pe
      LEFT JOIN textbook_companion_proposal po ON pe.proposal_id = po.id
      LEFT JOIN textbook_companion_book_main_subcategories tcbm ON pe.id = tcbm.pref_id
      LEFT JOIN list_of_category loc ON tcbm.main_category = loc.category_id
      WHERE po.proposal_status = 3 AND pe.approval_status = 1
      AND pe.id = tcbm.pref_id ORDER BY po.completion_date DESC");
    $count_row = $countresult->fetchObject();
    $book_count = $count_row->c ?? 0;
    $form['completed_book_count'] = [
      '#type' => 'item',
      '#markup' => "Total number of completed books: " . $book_count . "<br><span style='color:red;'>The list below is not the books as named but only are the solved example for R</span>",
    ];
    $form['category'] = [
      '#type' => 'select',
      '#title' => $this->t('Category'),
      '#options' => $this->listOfDisplayCategory(),
      '#default_value' => $category_default_value,
      '#ajax' => [
        'callback' => '::ajaxDisplayAllBookListCallback',
      ],
      '#attributes' => [
        'style' => 'word-break: break-all; white-space: normal;',
      ],
      '#validated' => TRUE,
    ];
    $form['subcategory'] = [
      '#type' => 'select',
      '#title' => $this->t('Sub Category'),
      '#options' => $this->listOfSubcategory($category_default_value),
      '#default_value' => $category_default_value,
      '#ajax' => [
        'callback' => '::ajaxDisplaySubcategoryBookListCallback',
      ],
      '#prefix' => '<div id="ajax-subcategory-list-replace">',
      '#suffix' => '</div>',
      '#validated' => TRUE,
      '#states' => [
        'invisible' => [
          ':input[name="category"]' => [
            'value' => 0,
          ],
        ],
      ],
    ];
    $form['book'] = [
      '#type' => 'item',
      '#prefix' => '<div id="ajax-book-list-replace">',
      '#suffix' => '</div>',
      '#markup' => $this->listOfAllCompletedBooks(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // No submit handler; this is an AJAX-driven filter form.
  }

  public function ajaxDisplayAllBookListCallback(array &$form, FormStateInterface $form_state) {
    $category_default_value = (int) $form_state->getValue('category');
    $response = new AjaxResponse();
    if ($category_default_value > 0) {
      $form['subcategory']['#options'] = $this->listOfSubcategory($category_default_value);
      $form['book']['#markup'] = $this->listOfAllCompletedBooks($category_default_value);
      $response->addCommand(new ReplaceCommand('#ajax-subcategory-list-replace', $form['subcategory']));
      $response->addCommand(new HtmlCommand('#ajax-book-list-replace', $form['book']['#markup']));
    }
    else {
      $form['book']['#markup'] = $this->listOfAllCompletedBooks();
      $response->addCommand(new HtmlCommand('#ajax-book-list-replace', $form['book']['#markup']));
      $response->addCommand(new HtmlCommand('#ajax-subcategory-list-replace', ''));
    }
    return $response;
  }

  public function ajaxDisplaySubcategoryBookListCallback(array &$form, FormStateInterface $form_state) {
    $category_default_value = (int) $form_state->getValue('category');
    $subcategory_default_value = (int) $form_state->getValue('subcategory');
    $response = new AjaxResponse();
    if ($category_default_value > 0) {
      $form['subcategory']['#options'] = $this->listOfSubcategory($category_default_value);
      if ($subcategory_default_value > 0) {
        $form['book']['#markup'] = $this->listOfAllCompletedBooks($category_default_value, $subcategory_default_value);
      }
      else {
        $form['book']['#markup'] = $this->listOfAllCompletedBooks($category_default_value);
      }
      $response->addCommand(new ReplaceCommand('#ajax-subcategory-list-replace', $form['subcategory']));
      $response->addCommand(new HtmlCommand('#ajax-book-list-replace', $form['book']['#markup']));
    }
    else {
      $form['book']['#markup'] = $this->listOfAllCompletedBooks();
      $response->addCommand(new HtmlCommand('#ajax-book-list-replace', $form['book']['#markup']));
      $response->addCommand(new HtmlCommand('#ajax-subcategory-list-replace', ''));
    }
    return $response;
  }

  private function listOfDisplayCategory($category_id = NULL) {
    $category = [
      0 => $this->t('All'),
    ];
    if ($category_id != NULL) {
      $query = \Drupal::database()->select('list_of_category');
      $query->fields('list_of_category');
      $query->condition('category_id', $category_id);
      $query->orderBy('id', 'ASC');
      $category_list = $query->execute();
    }
    else {
      $category_list = \Drupal::database()->query('SELECT * FROM {list_of_category} WHERE category_id != 0');
    }
    while ($category_list_data = $category_list->fetchObject()) {
      $category[$category_list_data->category_id] = ($category[$category_list_data->category_id] ?? '') . $category_list_data->maincategory;
    }
    return $category;
  }

  private function listOfAllCompletedBooks($category_default_value = NULL, $subcategory_default_value = NULL) {
    $output = '';
    $result = \Drupal::database()->query('SELECT COUNT(pe.book) AS book_count FROM {textbook_companion_preference} pe LEFT JOIN textbook_companion_proposal po ON pe.proposal_id = po.id WHERE po.proposal_status =3 AND pe.approval_status =1');
    $row = $result->fetchObject();
    $book_count = $row->book_count ?? 0;
    $i = $book_count;
    if ($category_default_value <= 0 && $subcategory_default_value == NULL) {
      $preference_q = \Drupal::database()->query("SELECT pe.id as pe_id,
      MIN(pe.book) as book, COUNT(pe.book) as c, MIN(tcbm.sub_category) as sub_category,
      MIN(tcbm.main_category) as category_id, MIN(pe.author) as author, MIN(pe.publisher) as publisher,
      MIN(pe.year) as year, MIN(pe.edition) as edition, MIN(po.approval_date) as approval_date,
      MIN(po.completion_date) as completion_date, MIN(po.full_name) as full_name, MIN(po.university) as university
      FROM {textbook_companion_preference} pe
      LEFT JOIN textbook_companion_proposal po ON pe.proposal_id = po.id
      LEFT JOIN textbook_companion_book_main_subcategories tcbm ON pe.id = tcbm.pref_id
      LEFT JOIN list_of_category loc ON tcbm.main_category = loc.category_id
      WHERE po.proposal_status = 3 AND pe.approval_status = 1 
      AND pe.id = tcbm.pref_id GROUP BY pe.id HAVING c >= 1 ORDER BY completion_date DESC");
    }
    elseif ($category_default_value > 0 && $subcategory_default_value == NULL) {
      $preference_q = \Drupal::database()->query("SELECT pe.id as pe_id,
      MIN(loc.category_id) as category_id, COUNT(pe.book) as c, MIN(tcbm.sub_category) as sub_category,
      MIN(loc.maincategory) as maincategory, MIN(pe.book) as book, MIN(pe.author) as author,
      MIN(pe.publisher) as publisher, MIN(pe.year) as year, MIN(pe.edition) as edition,
      MIN(po.approval_date) as approval_date, MIN(po.completion_date) as completion_date,
      MIN(po.full_name) as full_name, MIN(po.university) as university
      FROM {textbook_companion_preference} pe
      LEFT JOIN textbook_companion_proposal po ON pe.proposal_id = po.id
      LEFT JOIN textbook_companion_book_main_subcategories tcbm ON pe.id = tcbm.pref_id
      LEFT JOIN list_of_category loc ON tcbm.main_category = loc.category_id
      WHERE po.proposal_status = 3 AND pe.approval_status = 1 
      AND pe.id = tcbm.pref_id AND loc.category_id= :category_id GROUP BY pe.id HAVING c >= 1 ORDER BY completion_date DESC", [
        'category_id' => $category_default_value,
      ]);
    }
    else {
      $preference_q = \Drupal::database()->query("SELECT DISTINCT (loc.category_id),tcbm.sub_category,loc.maincategory,
      pe.book as book, pe.author as author, pe.publisher as publisher, pe.year as year, pe.id as pe_id, pe.edition,
      po.approval_date as approval_date, po.completion_date as completion_date, po.full_name as full_name, po.university as university
      FROM textbook_companion_preference pe
      LEFT JOIN textbook_companion_proposal po ON pe.proposal_id = po.id
      LEFT JOIN textbook_companion_book_main_subcategories tcbm ON pe.id = tcbm.pref_id
      LEFT JOIN list_of_category loc ON tcbm.main_category = loc.category_id
      WHERE po.proposal_status = 3 AND pe.approval_status = 1 
      AND pe.id = tcbm.pref_id AND loc.category_id= :category_id AND tcbm.sub_category = :sub_category ORDER BY po.completion_date DESC", [
        'category_id' => $category_default_value,
        'sub_category' => $subcategory_default_value,
      ]);
    }
    $proposal_rows = [];
    while ($preference_data = $preference_q->fetchObject()) {
      $title = $preference_data->book . ' by ' . $preference_data->author . ', ' . $preference_data->publisher . ', ' . $preference_data->year;
      $link = Link::fromTextAndUrl(
        $title,
        Url::fromRoute('textbook_companion.book_run_form_with_book', ['book_id' => $preference_data->pe_id])
      )->toString();
      $proposal_rows[] = [
        $i,
        $link,
        $preference_data->full_name,
        $preference_data->university,
        $preference_data->completion_date ? date('Y', $preference_data->completion_date) : '',
      ];
      $i--;
    }
    if (!$proposal_rows) {
      $output .= $this->t('There are no books availabe in this sub category.');
    }
    else {
      $table = [
        '#theme' => 'table',
        '#header' => [
          $this->t('No.'),
          $this->t('Title of the Book'),
          $this->t('Contributor Name'),
          $this->t('University / Institute'),
          $this->t('Year of Completion'),
        ],
        '#rows' => $proposal_rows,
      ];
      $output .= \Drupal::service('renderer')->render($table);
    }
    return $output;
  }

  private function listOfSubcategory($category_id = NULL) {
    $subcategory = [
      0 => $this->t('All'),
    ];
    $query = \Drupal::database()->select('textbook_companion_book_main_subcategories', 'tcbm');
    $query->fields('los', [
      'subcategory_id',
      'subcategory',
    ]);
    $query->distinct();
    $query->leftJoin('list_of_subcategory', 'los', 'tcbm.sub_category = los.subcategory_id');
    if ($category_id != NULL && $category_id > 0) {
      $query->condition('tcbm.main_category', $category_id);
    }
    $query->orderBy('los.subcategory', 'ASC');
    $category_list = $query->execute();
    while ($category_list_data = $category_list->fetchObject()) {
      $label = $category_list_data->subcategory ?: $category_list_data->subcategory_id;
      $subcategory[$category_list_data->subcategory_id] = $label;
    }
    return $subcategory;
  }

}
