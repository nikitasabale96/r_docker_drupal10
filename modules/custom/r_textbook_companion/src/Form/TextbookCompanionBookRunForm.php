<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\TextbookCompanionBookRunForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;

class TextbookCompanionBookRunForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'textbook_companion_book_run_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, int $book_id = NULL) {
    $url_book_pref_id = $book_id ? (int) $book_id : 0;
    $category_default_value = 0;
    $subcategory_default_value = 0;
    if ($url_book_pref_id) {
      [
        $category_default_value,
        $subcategory_default_value,
      ] = $this->getBookCategoryDefaults($url_book_pref_id);
    }
    if ($url_book_pref_id) {
      $form['category'] = [
        '#type' => 'select',
        '#title' => t('Category'),
        '#options' => $this->listOfCategory(),
        '#default_value' => $category_default_value,
        '#ajax' => [
          'callback' => '::ajaxSubcategoryListCallback',
          ],
        '#validated' => TRUE,
      ];
      $form['subcategory'] = [
        '#type' => 'select',
        '#title' => t('Sub Category'),
        '#options' => $this->listOfSubcategory($category_default_value),
        '#default_value' => $subcategory_default_value,
        '#ajax' => [
          'callback' => '::ajaxBookListCallback',
          ],
        '#prefix' => '<div id="ajax-subcategory-list-replace">',
        '#suffix' => '</div>',
        '#validated' => TRUE,
        '#states' => [
          'invisible' => [
            ':input[name="category"]' => [
              'value' => 0
              ]
            ]
          ],
      ];
      $book_default_value = $url_book_pref_id;
      $form['book'] = [
        '#type' => 'select',
        '#title' => t('Title of the book'),
        '#options' => $this->listOfBooks($category_default_value, $subcategory_default_value),
        '#default_value' => $book_default_value,
        '#prefix' => '<div id="ajax-book-list-replace">',
        '#suffix' => '</div>',
        '#ajax' => [
          'callback' => '::ajaxChapterListCallback',
          ],
        '#validated' => TRUE,
        '#states' => [
          'invisible' => [
            ':input[name="subcategory"]' => [
              'value' => 0
              ]
            ]
          ],
      ];
      /*$form['book_details'] = array(
		'#prefix' => '<div id="ajax-book-details-replace"></div>',
		'#suffix' => '</div>',
		'#markup' => '',
		
		);*/
      $form['book_details'] = [
        '#type' => 'item',
        '#markup' => '<div id="ajax-book-details-replace">' . $this->htmlBookInfo($book_default_value) . '</div>',
      ];
      $form['download_book'] = [
        '#type' => 'item',
        '#markup' => '<div id="ajax-download-book-replace">' . Link::fromTextAndUrl($this->t('Download'), Url::fromRoute('textbook_companion.download_book', ['book_id' => $book_default_value]))->toString() . ' ' . $this->t('(Download the R codes for all the solved examples)') . '</div>',
      ];
      $form['download_pdf'] = [
        '#type' => 'item',
        '#markup' => '<div id="ajax-download-book-pdf-replace">' . Link::fromTextAndUrl($this->t('Download PDF'), Url::fromRoute('textbook_companion.generate_book_pdf', ['book_id' => $book_default_value, 'include_unapproved' => 0]))->toString() . ' ' . $this->t('(Download the PDF file containing R codes for all the solved examples)') . '<br><span style="color:red;">' . $this->t('The generated PDF is not the PDF of the book as named but only is the PDF of the solved example for R') . '</span></div>',
      ];
      $form['chapter'] = [
        '#type' => 'select',
        '#title' => t('Title of the chapter'),
        '#options' => $this->listOfChapters($book_default_value),
        //'#default_value' => isset($form_state['values']['chapter']) ? $form_state['values']['chapter'] : '',
			'#prefix' => '<div id="ajax-chapter-list-replace">',
        '#suffix' => '</div>',
        '#ajax' => [
          'callback' => '::ajaxExampleListCallback',
          ],
        '#validated' => TRUE,
        '#states' => [
          'invisible' => [
            ':input[name="category"]' => [
              'value' => 0
              ]
            ]
          ],
      ];
      $form['download_chapter'] = [
        '#type' => 'item',
        '#markup' => '<div id="ajax-download-chapter-replace"></div>',
      ];
      $example_default_value = !$form_state->getValue(['chapter']) ? $form_state->getValue(['chapter']) : '';
      $form['examples'] = [
        '#type' => 'select',
        '#title' => t('Example No. (Caption): '),
        '#options' => $this->listOfExamples($example_default_value),
        '#default_value' => !$form_state->getValue(['examples']) ? $form_state->getValue(['examples']) : '',
        '#prefix' => '<div id="ajax-example-list-replace">',
        '#suffix' => '</div>',
        '#ajax' => [
          'callback' => '::ajaxExampleFilesCallback',
          ],
        '#states' => [
          'invisible' => [
            ':input[name="chapter"]' => [
              'value' => 0
              ]
            ]
          ],
      ];
      $form['download_example_code'] = [
        '#type' => 'item',
        '#markup' => '<div id="ajax-download-example-code-replace"></div>',
      ];
      $form['example_files'] = [
        '#type' => 'item',
        '#markup' => '<div id="ajax-download-example-files-replace"></div>',
      ];
    } //$url_book_pref_id
    else {
      $form['category'] = [
        '#type' => 'select',
        '#title' => t('Category'),
        '#options' => $this->listOfCategory(),
        '#default_value' => $category_default_value,
        '#ajax' => [
          'callback' => '::ajaxSubcategoryListCallback',
          ],
        '#validated' => TRUE,
      ];
      //var_dump(_list_of_subcategory($category_default_value));
      $form['subcategory'] = [
        '#type' => 'select',
        '#title' => t('Sub Category'),
        '#options' => $this->listOfSubcategory($category_default_value),
        '#default_value' => $subcategory_default_value,
        '#ajax' => [
          'callback' => '::ajaxBookListCallback',
          ],
        '#prefix' => '<div id="ajax-subcategory-list-replace">',
        '#suffix' => '</div>',
        '#validated' => TRUE,
        '#states' => [
          'invisible' => [
            ':input[name="category"]' => [
              'value' => 0
              ]
            ]
          ],
      ];
      $form['book'] = [
        '#type' => 'select',
        '#title' => t('Title of the book'),
        '#options' => $this->listOfBooks($category_default_value, $subcategory_default_value),
        //'#default_value' => isset($form_state['values']['book']) ? $form_state['values']['book'] : 0,
			'#ajax' => [
          'callback' => '::ajaxChapterListCallback',
          ],
        '#validated' => TRUE,
        '#prefix' => '<div id="ajax-book-list-replace">',
        '#suffix' => '</div>',
        '#states' => [
          'invisible' => [
            ':input[name="category"]' => [
              'value' => 0
              ]
            ]
          ],
      ];
      $form['book_details'] = [
        '#type' => 'item',
        '#markup' => '<div id="ajax-book-details-replace"></div>',
      ];
      $form['download_book'] = [
        '#type' => 'item',
        '#markup' => '<div id="ajax-download-book-replace"></div>',
      ];
      $form['download_pdf'] = [
        '#type' => 'item',
        '#markup' => '<div id="ajax-download-book-pdf-replace"></div>',
      ];
      $book_default_value = !$form_state->getValue(['book']) ? $form_state->getValue(['book']) : '';
      $form['chapter'] = [
        '#type' => 'select',
        '#title' => t('Title of the chapter'),
        '#options' => $this->listOfChapters($book_default_value),
        //'#default_value' => isset($form_state['values']['chapter']) ? $form_state['values']['chapter'] : '',
			'#prefix' => '<div id="ajax-chapter-list-replace">',
        '#suffix' => '</div>',
        '#ajax' => [
          'callback' => '::ajaxExampleListCallback',
          ],
        '#validated' => TRUE,
        '#states' => [
          'invisible' => [
            ':input[name="category"]' => [
              'value' => 0
              ]
            ]
          ],
      ];
      $form['download_chapter'] = [
        '#type' => 'item',
        '#markup' => '<div id="ajax-download-chapter-replace"></div>',
      ];
      $example_default_value = !$form_state->getValue(['chapter']) ? $form_state->getValue(['chapter']) : '';
      $form['examples'] = [
        '#type' => 'select',
        '#title' => t('Example No. (Caption): '),
        '#options' => $this->listOfExamples($example_default_value),
        '#default_value' => !$form_state->getValue(['examples']) ? $form_state->getValue(['examples']) : '',
        '#prefix' => '<div id="ajax-example-list-replace">',
        '#suffix' => '</div>',
        '#ajax' => [
          'callback' => '::ajaxExampleFilesCallback',
          ],
        '#states' => [
          'invisible' => [
            ':input[name="category"]' => [
              'value' => 0
              ]
            ]
          ],
      ];
      $form['download_example_code'] = [
        '#type' => 'item',
        '#markup' => '<div id="ajax-download-example-code-replace"></div>',
      ];
      $form['example_files'] = [
        '#type' => 'item',
        '#markup' => '<div id="ajax-download-example-files-replace"></div>',
      ];
    }
    return $form;
  }

  private function getBookCategoryDefaults(int $preference_id): array {
    $query = \Drupal::database()->select('textbook_companion_preference', 'p');
    $query->addField('p', 'category', 'preference_category');
    $query->addExpression('MIN(tcbm.main_category)', 'mapped_category');
    $query->addExpression('MIN(tcbm.sub_category)', 'mapped_subcategory');
    $query->leftJoin('textbook_companion_book_main_subcategories', 'tcbm', 'p.id = tcbm.pref_id');
    $query->condition('p.id', $preference_id);
    $query->groupBy('p.id');
    $query->groupBy('p.category');
    $pref = $query->execute()->fetchObject();

    if (!$pref) {
      return [0, 0];
    }

    $category_default_value = (int) ($pref->mapped_category ?? $pref->preference_category ?? 0);
    $subcategory_default_value = (int) ($pref->mapped_subcategory ?? 0);

    return [$category_default_value, $subcategory_default_value];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This form is read-only; all actions are handled via links/AJAX callbacks.
  }

  public function ajaxBookListCallback(array &$form, FormStateInterface $form_state) {
    $category_default_value = (int) $form_state->getValue('category');
    $subcategory_default_value = (int) $form_state->getValue('subcategory');
    $response = new AjaxResponse();
    if ($category_default_value != 0 && $subcategory_default_value == 0) {
      $response->addCommand(new HtmlCommand('#ajax-book-list-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-chapter-list-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-example-list-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-book-details-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-download-book-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-download-book-pdf-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-download-chapter-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-download-example-code-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-download-example-files-replace', ''));
    }
    elseif ($category_default_value != 0 && $subcategory_default_value != 0) {
      $form['book']['#options'] = $this->listOfBooks($category_default_value, $subcategory_default_value);
      $response->addCommand(new ReplaceCommand('#ajax-book-list-replace', $form['book']));
      $response->addCommand(new HtmlCommand('#ajax-chapter-list-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-example-list-replace', ''));
    }
    else {
      $response->addCommand(new HtmlCommand('#ajax-book-list-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-subcategory-list-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-chapter-list-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-example-list-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-book-details-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-download-book-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-download-book-pdf-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-download-chapter-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-download-example-code-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-download-example-files-replace', ''));
    }
    return $response;
  }

  public function ajaxSubcategoryListCallback(array &$form, FormStateInterface $form_state) {
    $category_default_value = (int) $form_state->getValue('category');
    $response = new AjaxResponse();
    if ($category_default_value > 0) {
      $form['subcategory']['#options'] = $this->listOfSubcategory($category_default_value);
      $response->addCommand(new ReplaceCommand('#ajax-subcategory-list-replace', $form['subcategory']));
      $response->addCommand(new HtmlCommand('#ajax-book-list-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-chapter-list-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-example-list-replace', ''));
    }
    else {
      $response->addCommand(new HtmlCommand('#ajax-subcategory-list-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-book-list-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-chapter-list-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-example-list-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-book-details-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-download-book-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-download-book-pdf-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-download-chapter-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-download-example-code-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-download-example-files-replace', ''));
    }
    return $response;
  }

  public function ajaxChapterListCallback(array &$form, FormStateInterface $form_state) {
    $book_list_default_value = (int) $form_state->getValue('book');
    $response = new AjaxResponse();
    if ($book_list_default_value > 0) {
      $response->addCommand(new HtmlCommand('#ajax-book-details-replace', $this->htmlBookInfo($book_list_default_value)));
      $response->addCommand(new HtmlCommand('#ajax-download-book-replace', Link::fromTextAndUrl($this->t('Download'), Url::fromRoute('textbook_companion.download_book', ['book_id' => $book_list_default_value]))->toString() . ' ' . $this->t('(Download the R codes for all the solved examples)')));
      $response->addCommand(new HtmlCommand('#ajax-download-book-pdf-replace', Link::fromTextAndUrl($this->t('Download PDF'), Url::fromRoute('textbook_companion.generate_book_pdf', ['book_id' => $book_list_default_value, 'include_unapproved' => 0]))->toString() . ' ' . $this->t('(Download the PDF file containing R codes for all the solved examples)')));
      $form['chapter']['#options'] = $this->listOfChapters($book_list_default_value);
      $response->addCommand(new ReplaceCommand('#ajax-chapter-list-replace', $form['chapter']));
      $response->addCommand(new HtmlCommand('#ajax-example-list-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-download-example-code-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-download-example-files-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-download-chapter-replace', ''));
    }
    else {
      $response->addCommand(new HtmlCommand('#ajax-book-details-replace', ''));
      $form['chapter']['#options'] = $this->listOfChapters();
      $response->addCommand(new ReplaceCommand('#ajax-chapter-list-replace', $form['chapter']));
      $response->addCommand(new HtmlCommand('#ajax-chapter-list-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-download-book-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-download-chapter-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-download-book-pdf-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-example-list-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-download-example-code-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-download-example-files-replace', ''));
    }
    return $response;
  }

  public function ajaxExampleListCallback(array &$form, FormStateInterface $form_state) {
    $chapter_list_default_value = (int) $form_state->getValue('chapter');
    $response = new AjaxResponse();
    if ($chapter_list_default_value > 0) {
      $form['examples']['#options'] = $this->listOfExamples($chapter_list_default_value);
      $response->addCommand(new ReplaceCommand('#ajax-example-list-replace', $form['examples']));
      $response->addCommand(new HtmlCommand('#ajax-download-example-code-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-download-example-files-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-download-chapter-replace', Link::fromTextAndUrl($this->t('Download Chapter'), Url::fromRoute('textbook_companion.download_chapter', ['chapter_id' => $chapter_list_default_value]))->toString()));
    }
    else {
      $form['examples']['#options'] = $this->listOfExamples();
      $response->addCommand(new ReplaceCommand('#ajax-example-list-replace', $form['examples']));
      $response->addCommand(new HtmlCommand('#ajax-example-list-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-download-chapter-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-download-example-code-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-download-example-files-replace', ''));
    }
    return $response;
  }

  public function ajaxExampleFilesCallback(array &$form, FormStateInterface $form_state) {
    $example_list_default_value = (int) $form_state->getValue('examples');
    $response = new AjaxResponse();
    if ($example_list_default_value != 0) {
      $query = \Drupal::database()->select('textbook_companion_example_files');
      $query->fields('textbook_companion_example_files');
      $query->condition('example_id', $example_list_default_value);
      $example_list_q = $query->execute();
      $example_files = '';
      if ($example_list_q) {
        while ($example_list_data = $example_list_q->fetchObject()) {
          $example_file_type = '';
          switch ($example_list_data->filetype) {
            case 'S':
              $example_file_type = 'Source or Main file';
              break;
            case 'R':
              $example_file_type = 'Result file';
              break;
            case 'X':
              $example_file_type = 'xcos file';
              break;
            default:
              $example_file_type = 'Unknown';
              break;
          }
          $example_files .= Link::fromTextAndUrl($example_list_data->filename, Url::fromRoute('textbook_companion.download_example_file', ['example_file_id' => $example_list_data->id]))->toString() . ' (' . $example_file_type . ')<br />';
        }
      }
      $response->addCommand(new HtmlCommand('#ajax-download-example-code-replace', Link::fromTextAndUrl($this->t('Download Example'), Url::fromRoute('textbook_companion.download_example', ['example_id' => $example_list_default_value]))->toString()));
      $response->addCommand(new HtmlCommand('#ajax-download-example-files-replace', $example_files));
    }
    else {
      $response->addCommand(new HtmlCommand('#ajax-download-example-code-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-download-example-files-replace', ''));
    }
    return $response;
  }

  private function listOfCategory($category_id = NULL) {
    $category = [
      0 => 'Please select',
    ];
    if ($category_id == NULL) {
      $query = \Drupal::database()->select('list_of_category');
      $query->fields('list_of_category');
      $query->orderBy('category_id', 'ASC');
      $category_list = $query->execute();
    }
    else {
      $query = \Drupal::database()->select('list_of_category');
      $query->fields('list_of_category');
      $query->condition('category_id', $category_id);
      $query->orderBy('id', 'ASC');
      $category_list = $query->execute();
    }
    while ($category_list_data = $category_list->fetchObject()) {
      $category[$category_list_data->category_id] = ($category[$category_list_data->category_id] ?? '') . $category_list_data->maincategory;
    }
    return $category;
  }

  private function listOfSubcategory($category_id = NULL) {
    $subcategory = [
      0 => 'Please select',
    ];
    $query = \Drupal::database()->select('list_of_subcategory');
    $query->fields('list_of_subcategory');
    $query->condition('maincategory_id', $category_id);
    $query->orderBy($category_id == NULL ? 'subcategory_id' : 'id', 'ASC');
    $subcategory_list = $query->execute();
    while ($subcategory_list_data = $subcategory_list->fetchObject()) {
      $subcategory[$subcategory_list_data->subcategory_id] = $subcategory_list_data->subcategory;
    }
    return $subcategory;
  }

  private function listOfBooks($category_default_value, $subcategory_default_value) {
    $book_titles = [
      0 => 'Please select ...',
    ];
    $book_titles_q = \Drupal::database()->query("SELECT DISTINCT (tcbm.sub_category), los.subcategory, loc.category_id,loc.maincategory,
      pe.book as book, pe.author as author, pe.publisher as publisher, pe.year as year, pe.id as pe_id,
      po.approval_date as approval_date
      FROM textbook_companion_preference pe
      LEFT JOIN textbook_companion_proposal po ON pe.proposal_id = po.id
      LEFT JOIN textbook_companion_book_main_subcategories tcbm ON pe.id = tcbm.pref_id
      LEFT JOIN list_of_category loc ON tcbm.main_category = loc.category_id
      LEFT JOIN list_of_subcategory los ON tcbm.sub_category = los.subcategory_id
      WHERE po.proposal_status = 3 AND pe.approval_status = 1
      AND pe.id = tcbm.pref_id AND tcbm.sub_category= :sub_category AND tcbm.main_category = :main_category", [
      ":sub_category" => $subcategory_default_value,
      "main_category" => $category_default_value,
    ]);
    $found_books = FALSE;
    while ($book_titles_data = $book_titles_q->fetchObject()) {
      $found_books = TRUE;
      $book_titles[$book_titles_data->pe_id] = $book_titles_data->book . ' (Written by ' . $book_titles_data->author . ')';
    }
    if (!$found_books) {
      $book_titles[0] = "There are no books availabe in this sub category";
    }
    return $book_titles;
  }

  private function listOfChapters($preference_id = 0) {
    $book_chapters = [
      0 => 'Please select...',
    ];
    $query = \Drupal::database()->select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('preference_id', $preference_id);
    $query->orderBy('number', 'ASC');
    $book_chapters_q = $query->execute();
    while ($book_chapters_data = $book_chapters_q->fetchObject()) {
      $book_chapters[$book_chapters_data->id] = $book_chapters_data->number . '. ' . $book_chapters_data->name;
    }
    return $book_chapters;
  }

  private function listOfExamples($chapter_id = 0) {
    $book_examples = [
      0 => 'Please select...',
    ];
    $query = \Drupal::database()->select('textbook_companion_example');
    $query->fields('textbook_companion_example');
    if ($chapter_id) {
      $query->condition('chapter_id', $chapter_id);
    }
    $query->condition('approval_status', 1);
    $book_examples_q = $query->execute();
    while ($book_examples_data = $book_examples_q->fetchObject()) {
      $book_examples[$book_examples_data->id] = $book_examples_data->number . ' (' . $book_examples_data->caption . ')';
    }
    return $book_examples;
  }

  private function htmlBookInfo($preference_id) {
    $query = \Drupal::database()->select('textbook_companion_proposal', 'proposal');
    $query->addField('preference', 'book', 'preference_book');
    $query->addField('preference', 'author', 'preference_author');
    $query->addField('preference', 'isbn', 'preference_isbn');
    $query->addField('preference', 'publisher', 'preference_publisher');
    $query->addField('preference', 'edition', 'preference_edition');
    $query->addField('preference', 'year', 'preference_year');
    $query->addField('proposal', 'full_name', 'proposal_full_name');
    $query->addField('proposal', 'faculty', 'proposal_faculty');
    $query->addField('proposal', 'reviewer', 'proposal_reviewer');
    $query->addField('proposal', 'course', 'proposal_course');
    $query->addField('proposal', 'branch', 'proposal_branch');
    $query->addField('proposal', 'university', 'proposal_university');
    $query->fields('proposal', [
      'full_name',
      'faculty',
      'reviewer',
      'course',
      'branch',
      'university',
    ]);
    $query->leftJoin('textbook_companion_preference', 'preference', 'proposal.id = preference.proposal_id');
    $query->fields('preference', [
      'book',
      'author',
      'isbn',
      'publisher',
      'edition',
      'year',
    ]);
    $query->condition('preference.id', $preference_id);
    $book_details = $query->execute()->fetchObject();
    $html_data = '';
    if ($book_details) {
      $html_data = '<table cellspacing="1" cellpadding="1" border="0" style="width: 100%;" valign="top">'
        . '<tr><td style="width: 35%;"><span style="color: rgb(128, 0, 0);"><strong>About the Book</strong></span></td>'
        . '<td style="width: 35%;"><span style="color: rgb(128, 0, 0);"><strong>About the Contributor</strong></span></td>'
        . '<tr><td valign="top"><ul>'
        . '<li><strong>Author:</strong> ' . Html::escape($book_details->preference_author ?? '') . '</li>'
        . '<li><strong>Title of the Book:</strong> ' . Html::escape($book_details->preference_book ?? '') . '</li>'
        . '<li><strong>Publisher:</strong> ' . Html::escape($book_details->preference_publisher ?? '') . '</li>'
        . '<li><strong>Year:</strong> ' . Html::escape($book_details->preference_year ?? '') . '</li>'
        . '<li><strong>Edition:</strong> ' . Html::escape($book_details->preference_edition ?? '') . '</li>'
        . '</ul></td><td valign="top"><ul>'
        . '<li><strong>Contributor Name: </strong>' . Html::escape($book_details->proposal_full_name ?? '') . ', '
        . Html::escape($book_details->proposal_course ?? '') . ', '
        . Html::escape($book_details->proposal_branch ?? '') . ', '
        . Html::escape($book_details->proposal_university ?? '') . '</li>'
        . '<li><strong>Reviewer: </strong>' . Html::escape($book_details->proposal_reviewer ?? '') . '</li>'
        . '</ul></td></tr>'
        . '</table>';
    }
    return $html_data;
  }

}
?>
