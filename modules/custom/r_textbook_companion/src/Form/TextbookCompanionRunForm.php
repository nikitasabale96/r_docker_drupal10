<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\TextbookCompanionRunForm.
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

class TextbookCompanionRunForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'textbook_companion_run_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, int $book_id = NULL) {
    $url_book_pref_id = $book_id ? (int) $book_id : 0;
    //  var_dump($url_book_pref_id);die;
    if ($url_book_pref_id) {
      $query = \Drupal::database()->select('textbook_companion_preference');
      $query->fields('textbook_companion_preference', ['category']);
      $query->condition('id', $url_book_pref_id);
      $result = $query->execute()->fetchObject();
      $category_default_value = $result->category;
    }
    else {
      $category_default_value = 0;
    }
    if ($url_book_pref_id) {
      $form['category'] = [
        '#type' => 'hidden',
        '#title' => t('Category'),
        '#options' => $this->listOfCategory(),
        '#default_value' => $category_default_value,
        '#ajax' => [
          'callback' => '::ajaxBookListCallback',
          ],
        '#validated' => TRUE,
      ];
      $book_default_value = $url_book_pref_id;
      $form['book'] = [
        '#type' => 'select',
        '#title' => t('Title of the book'),
        '#options' => $this->listOfBooks($book_default_value),
        '#default_value' => $book_default_value,
        '#prefix' => '<div id="ajax-book-list-replace">',
        '#suffix' => '</div>',
        '#ajax' => [
          'callback' => '::ajaxChapterListCallback',
          ],
        '#validated' => TRUE,
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
        '#markup' => '<div id="ajax-download-book-pdf-replace">' . Link::fromTextAndUrl($this->t('Download PDF'), Url::fromRoute('textbook_companion.generate_book_pdf', ['book_id' => $book_default_value, 'include_unapproved' => 0]))->toString() . ' ' . $this->t('(Download the PDF file containing R codes for all the solved examples)') . '</div>',
      ];
      /*$book_pref_id_array = array("19");
        if(in_array($book_default_value, $book_pref_id_array)){
        $form['freeeda_download_book'] = array(
        '#type' => 'item',
        '#markup' => '<div id="ajax-download-freeeda-book-replace">'.l('Download (FreeEDA Version)', 'textbook-companion/uploads/Microelectronic_Circuits___Theory_And_Applications_FreeEDA_Version.zip') . ' ' . t('(Download the FreeEDA codes for all the solved examples)').'</div>',
        );
        }*/
      /* $form['download_pdf'] = array(
        '#type' => 'item',
        '#markup' => '<div id="ajax-download-book-pdf-replace">'.l('Download PDF', 'textbook-companion/generate-book/' . $book_default_value) . ' ' . t('(Download the PDF file containing eSim codes for all the solved examples)').'</div>',
        );*/
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
        /* '#states' => array(
                'invisible' => array(
                    ':input[name="category"]' => array(
                        'value' => 0
                    )
                )
            )*/
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
    }
    else {
      $form['category'] = [
        '#type' => 'hidden',
        '#title' => t('Category'),
        '#options' => $this->listOfCategory(),
        '#default_value' => $category_default_value,
        '#ajax' => [
          'callback' => '::ajaxBookListCallback',
          ],
        '#validated' => TRUE,
      ];
      $form['book'] = [
        '#type' => 'select',
        '#title' => t('Title of the book'),
        '#options' => $this->listOfBooks(),
        //'#default_value' => isset($form_state['values']['book']) ? $form_state['values']['book'] : 0,
            '#prefix' => '<div id="ajax-book-list-replace">',
        '#suffix' => '</div>',
        '#ajax' => [
          'callback' => '::ajaxChapterListCallback',
          ],
        '#validated' => TRUE,
        //'#states' => array('invisible' => array(':input[name="category"]' => array('value' => 0),),),  
      ];
      $form['book_details'] = [
        '#type' => 'item',
        '#markup' => '<div id="ajax-book-details-replace"></div>',
      ];
      $form['download_book'] = [
        '#type' => 'item',
        '#markup' => '<div id="ajax-download-book-replace"></div>',
      ];
      $form['freeeda_download_book'] = [
        '#type' => 'item',
        '#markup' => '<div id="ajax-download-freeeda-book-replace"></div>',
      ];
      $form['download_pdf'] = [
        '#type' => 'item',
        '#markup' => '<div id="ajax-download-book-pdf-replace"></div>',
      ];
      /* $form['download_pdf'] = array(
        '#type' => 'item',
        '#markup' => '<div id="ajax-download-book-pdf-replace"></div>',
        );*/
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
            ':input[name="book"]' => [
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
            ':input[name="book"]' => [
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

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Read-only form; actions are provided via links/AJAX callbacks.
  }

  public function ajaxBookListCallback(array &$form, FormStateInterface $form_state) {
    $category_default_value = (int) $form_state->getValue('category');
    $response = new AjaxResponse();
    if ($category_default_value == 0) {
      $form['book']['#options'] = $this->listOfBooks($category_default_value);
      $response->addCommand(new ReplaceCommand('#ajax-book-list-replace', $form['book']));
      $response->addCommand(new HtmlCommand('#ajax-chapter-list-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-example-list-replace', ''));
    }
    else {
      $form['book']['#options'] = $this->listOfBooks();
      $response->addCommand(new ReplaceCommand('#ajax-book-list-replace', $form['book']));
      $response->addCommand(new HtmlCommand('#ajax-book-list-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-chapter-list-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-example-list-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-book-details-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-download-book-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-download-book-pdf-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-download-freeeda-book-replace', ''));
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
      $form['chapter']['#options'] = $this->listOfChapters($book_list_default_value);
      $response->addCommand(new ReplaceCommand('#ajax-chapter-list-replace', $form['chapter']));
      $response->addCommand(new HtmlCommand('#ajax-chapter-list-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-download-book-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-download-book-pdf-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-download-chapter-replace', ''));
      $response->addCommand(new HtmlCommand('#ajax-download-freeeda-book-replace', ''));
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

  private function listOfCategory() {
    return [
      0 => 'Please select category ...',
      1 => 'Fluid Mechanics',
      2 => 'Control Theory & Control Systems',
      3 => 'Chemical Engineering',
      4 => 'Thermodynamics',
      5 => 'Mechanical Engineering',
      6 => 'Signal Processing',
      7 => 'Digital Communications',
      8 => 'Electrical Technology',
      9 => 'Mathematics & Pure Science',
      10 => 'Analog Electronics',
      11 => 'Digital Electronics',
      12 => 'Computer Programming',
      13 => 'Others',
    ];
  }

  private function listOfBooks($preference_id = NULL) {
    $book_titles = [
      0 => 'Please select ...',
    ];
    $query = \Drupal::database()->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    if ($preference_id != NULL) {
      $query->condition('id', $preference_id);
    }
    $query->condition('approval_status', 1);
    $subquery = \Drupal::database()->select('textbook_companion_proposal');
    $subquery->fields('textbook_companion_proposal', ['id']);
    $subquery->condition('proposal_status', 3);
    $query->condition('proposal_id', $subquery, 'IN');
    $query->orderBy('book', 'ASC');
    $book_titles_q = $query->execute();
    while ($book_titles_data = $book_titles_q->fetchObject()) {
      $book_titles[$book_titles_data->id] = $book_titles_data->book . ' (Written by ' . $book_titles_data->author . ')';
    }
    return $book_titles;
  }

  private function listOfChapters($preference_id) {
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

  private function bookInformation($preference_id) {
    $query = \Drupal::database()->select('textbook_companion_proposal', 'proposal');
    $query->fields('preference', [
      'book',
      'author',
      'isbn',
      'publisher',
      'edition',
      'year',
    ]);
    $query->fields('proposal', [
      'full_name',
      'faculty',
      'reviewer',
      'course',
      'branch',
      'university',
    ]);
    $query->leftJoin('textbook_companion_preference', 'preference', 'proposal.id = preference.proposal_id');
    $query->condition('preference.id', $preference_id);
    return $query->execute()->fetchObject();
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
