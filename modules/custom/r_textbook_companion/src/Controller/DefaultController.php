<?php /**
 * @file
 * Contains \Drupal\textbook_companion\Controller\DefaultController.
 */

namespace Drupal\textbook_companion\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Markup;
use Drupal\textbook_companion\Form\VerifyCertificatesForm;
use Drupal\textbook_companion\Helper\CertificateHelper;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use ZipArchive;

/**
 * Default controller for the textbook_companion module.
 */
class DefaultController extends ControllerBase {

  public function tbc_completed_books_display_new_category_all() {
    return $this->formBuilder()->getForm(\Drupal\textbook_companion\Form\TbcBooksDisplayNewCategoryForm::class);
  }

  public function tbc_books_in_progress_all() {
  $result = \Drupal::database()->query("
    SELECT po.creation_date, pe.book as book, pe.author as author, pe.publisher as publisher,
      pe.edition as edition, pe.isbn as isbn, pe.year as year, pe.id as pe_id,
      loc.maincategory as category
    FROM textbook_companion_preference pe
    LEFT JOIN textbook_companion_proposal po ON pe.proposal_id = po.id
    LEFT JOIN list_of_category loc ON pe.category = loc.category_id
    WHERE po.proposal_status IN (1, 4) AND pe.approval_status = 1
    ORDER BY po.creation_date DESC
  ");

  $rows = [];

  // Fetch all ONCE, then loop.
  $records = $result->fetchAll();
  $i = count($records);

  $date_formatter = \Drupal::service('date.formatter');
  foreach ($records as $row) {
    $proposal_date = $row->creation_date ? $date_formatter->format((int) $row->creation_date, 'custom', 'd-m-Y') : '';
    $category = $row->category ?: $this->t('Not assigned');
    $book_info = $this->t('@book<br><br>[ Author: @author, Publisher: @publisher, Year: @year, Edition: @edition, ISBN: @isbn ]', [
      '@book' => $row->book ?? '',
      '@author' => $row->author ?? '',
      '@publisher' => $row->publisher ?? '',
      '@year' => $row->year ?? '',
      '@edition' => $row->edition ?? '',
      '@isbn' => $row->isbn ?? '',
    ]);

    $rows[] = [
      $i,
      $proposal_date,
      [
        'data' => [
          '#markup' => $book_info,
        ],
      ],
      [
        'data' => [
          '#plain_text' => (string) $category,
        ],
      ],
    ];
    $i--;
  }

  if (!$rows) {
    $this->messenger()->addStatus($this->t('There are no books in progress.'));
    return ['#markup' => ''];
  }

  $header = ['No', 'Proposal Date', 'Book', 'Category'];

  return [
    '#type' => 'container',
    'separator' => ['#markup' => '<hr>'],
    'table' => [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ],
    '#cache' => [
      'tags' => ['textbook_companion:proposal_list', 'textbook_companion:preference_list', 'textbook_companion:category_list'],
      'contexts' => ['user.permissions'],
      'max-age' => Cache::PERMANENT,
    ],
  ];
}


  public function textbook_companion_proposal_all() {
    $user = $this->currentUser();
    $uid = (int) $user->id();
    if (!$uid) {
      $this->messenger()->addError($this->t('It is mandatory to login on this website to access the proposal form'));
      return $this->redirectToPath('');
    } //!$user->uid
	/* check if user has already submitted a proposal */
    /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE uid = %d ORDER BY id DESC LIMIT 1", $user->uid);*/
    $query = \Drupal::database()->select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('uid', $uid);
    $query->orderBy('id', 'DESC');
    $query->range(0, 1);
    $proposal_q = $query->execute();
    if ($proposal_q) {
      if ($proposal_data = $proposal_q->fetchObject()) {
        switch ($proposal_data->proposal_status) {
          case 0:
            $this->messenger()->addStatus($this->t('We have already received your proposal. We will get back to you soon.'));
            return $this->redirectToPath('');
            break;
          case 1:
            $code_link = $this->buildLinkString((string) $this->t('Code Submission'), 'textbook-companion/code');
            $this->messenger()->addStatus($this->t('Your proposal has been approved. Please go to @link to upload your code', [
              '@link' => $code_link,
            ]));
            return $this->redirectToPath('');
            break;
          case 2:
            $this->messenger()->addError($this->t('Your proposal has been dis-approved. Please create another proposal below.' . '</span>'));
            break;
          case 3:
            $this->messenger()->addStatus($this->t('Congratulations! You have completed your last book proposal. You can create another proposal below.'));
            break;
          default:
            $this->messenger()->addError($this->t('Invalid proposal state. Please contact site administrator for further information.'));
            return $this->redirectToPath('');
            break;
        } //$proposal_data->proposal_status
      } //$proposal_data = $proposal_q->fetchObject()
    } //$proposal_q
    $book_proposal_form = $this->formBuilder()->getForm(\Drupal\textbook_companion\Form\BookProposalForm::class);
    // drupal_goto("aicte_proposal");
    return $book_proposal_form;
  }

  public function textbook_companion_aicte_proposal_all() {
    $user = $this->currentUser();
    $uid = (int) $user->id();
    $page_content = "";
    if (!$uid) {
      /*$query = "
		SELECT * FROM textbook_companion_aicte
		WHERE status = 0
		";
		$result = db_query($query);*/
      $query = \Drupal::database()->select('textbook_companion_aicte');
      $query->fields('textbook_companion_aicte');
      $query->condition('status', 0);
      $result = $query->execute();
      $page_content .= "<ul>";
      $page_content .= "<li>These are the list of books available for <em>Textbook Companion</em> proposal.</li>";
      $page_content .= "<li>Please <a href='/user'><b><u>Login</u></b></a> to create a proposal.</li>";
      $page_content .= "</ul>";
      $page_content .= "Search :  <input type='text' id='searchtext' style='width:82%'/>";
      $page_content .= "<input type='button' value ='clear' id='search_clear'/>";
      $page_content .= "<div id='aicte-list-wrapper'>";
      $records = $result->fetchAll();
      $num_rows = count($records);
      if ($num_rows > 0) {
        $i = 1;
       foreach ($records as $row) {
          /* fixing title string */
          $title = "";
          $edition = "";
          $year = "";
          $title = Html::escape($row->book ?? '') . ' ' . $this->t('by') . ' ' . Html::escape($row->author ?? '');
          if ($row->edition) {
            $edition = "<i>ed</i>: " . Html::escape($row->edition);
          } //$row->edition
          if ($row->year) {
            if ($row->edition) {
              $year = ", <i>pub</i>: " . Html::escape($row->year);
            } //$row->edition
            else {
              $year = "<i>pub</i>: " . Html::escape($row->year);
            }
          } //$row->year
          if ($edition or $year) {
            $title .= "({$edition} {$year})";
          } //$edition or $year
          $page_content .= "<div class='title'>{$i}) {$title}</div>";
          $i++;
        } //$row = $result->fetchObject()
      } //$num_rows > 0
      $page_content .= "</div>";
      /* adding aicte report form */
      //$page_content .= drupal_get_form("textbook_companion_aicte_report_form");
      return [
        '#markup' => Markup::create($page_content),
        '#cache' => [
          'tags' => ['textbook_companion:aicte_list'],
          'contexts' => ['user.permissions'],
          'max-age' => Cache::PERMANENT,
        ],
      ];
    } //!$user->uid
	/* check if user has already submitted a proposal */
    /* $proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE uid = %d ORDER BY id DESC LIMIT 1", $user->uid);*/
    $query = \Drupal::database()->select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('uid', $uid);
    $query->orderBy('id', 'DESC');
    $query->range(0, 1);
    $proposal_q = $query->execute();
    if ($proposal_q) {
      if ($proposal_data = $proposal_q->fetchObject()) {
        switch ($proposal_data->proposal_status) {
          case 0:
            $this->messenger()->addStatus($this->t('We have already received your proposal. We will get back to you soon.'));
            return $this->redirectToPath('');
            break;
          case 1:
            $code_link = $this->buildLinkString((string) $this->t('Code Submission'), 'textbook-companion/code');
            $this->messenger()->addStatus($this->t('Your proposal has been approved. Please go to @link to upload your code', [
              '@link' => $code_link,
            ]));
            return $this->redirectToPath('');
            break;
          case 2:
            $this->messenger()->addError($this->t('Your proposal has been dis-approved. Please create another proposal below.'));
            break;
          case 3:
            $this->messenger()->addStatus($this->t('Congratulations! You have completed your last book proposal. You can create another proposal below.'));
            break;
          default:
            $this->messenger()->addError($this->t('Invalid proposal state. Please contact site administrator for further information.'));
            return $this->redirectToPath('');
            break;
        } //$proposal_data->proposal_status
      } //$proposal_data = $proposal_q->fetchObject()
    } //$proposal_q
    \Drupal::state()->delete('aicte_' . $uid);
    $page_content .= "<h5><b>* Please select any 3 books from the below list.</b></h5></br>";
    $page_content .= "Search :  <input type='text' id='searchtext' style='width:82%'/>";
    $page_content .= "<input type='button' value ='clear' id='search_clear'/>";
    $build = [
      '#type' => 'container',
      'intro' => [
        '#markup' => Markup::create($page_content),
      ],
      'form' => $this->formBuilder()->getForm(\Drupal\textbook_companion\Form\TextbookCompanionAicteProposalForm::class),
    ];
    return $build;
  }



public function _proposal_pending() {
  $pending_rows = [];

  $database = \Drupal::database();
  $date_formatter = \Drupal::service('date.formatter');

  $query = $database->select('textbook_companion_proposal', 'tcp');
  $query->fields('tcp');
  $query->condition('proposal_status', 0);
  $query->orderBy('id', 'DESC');

  $pending_q = $query->execute();

  while ($pending_data = $pending_q->fetchObject()) {

    $pending_rows[] = [
      $date_formatter->format((int) $pending_data->creation_date, 'custom', 'd-m-Y'),
      $this->buildLinkCell($pending_data->full_name, 'user/' . $pending_data->uid),

      $date_formatter->format((int) $pending_data->proposed_completion_date, 'custom', 'd-m-Y'),

      [
        'data' => [
          Link::fromTextAndUrl(
            $this->t('Approve'),
            Url::fromUri('internal:/textbook-companion/manage-proposal/approve/' . $pending_data->id)
          )->toRenderable(),
          ['#markup' => ' | '],
          Link::fromTextAndUrl(
            $this->t('Edit'),
            Url::fromUri('internal:/textbook-companion/manage-proposal/edit/' . $pending_data->id)
          )->toRenderable(),
        ],
      ],
    ];
  }

  return [
    '#type' => 'table',
    '#header' => [
      $this->t('Date of Submission'),
      $this->t('Contributor Name'),
      $this->t('Proposed Date of Completion'),
      $this->t('Action'),
    ],
    '#rows' => $pending_rows,
    '#empty' => $this->t('There are no pending proposals.'),
    '#cache' => [
      'tags' => ['textbook_companion:proposal_list'],
      'contexts' => ['user.permissions'],
      'max-age' => Cache::PERMANENT,
    ],
  ];
}

  public function _proposal_all() {
    /* get pending proposals to be approved */
    $proposal_rows = [];
    /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} ORDER BY id DESC");*/
    $query = \Drupal::database()->select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->orderBy('id', 'DESC');
    $proposal_q = $query->execute();
    while ($proposal_data = $proposal_q->fetchObject()) {
      /* get preference */
      /*$preference_q = db_query("SELECT * FROM textbook_companion_preference WHERE proposal_id = %d AND approval_status = 1 LIMIT 1", $proposal_data->id);   
        $preference_data = db_fetch_object($preference_q);*/
      $query = \Drupal::database()->select('textbook_companion_preference');
      $query->fields('textbook_companion_preference');
      $query->condition('proposal_id', $proposal_data->id);
      $query->condition('approval_status', 1);
      $query->range(0, 1);
      $preference_q = $query->execute();
      $preference_data = $preference_q->fetchObject();
      if (!$preference_data) {
        /* $preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE proposal_id = %d AND pref_number = 1 LIMIT 1", $proposal_data->id);  
            $preference_data = db_fetch_object($preference_q);*/
        $query = \Drupal::database()->select('textbook_companion_preference');
        $query->fields('textbook_companion_preference');
        $query->condition('proposal_id', $proposal_data->id);
        $query->condition('pref_number', 1);
        //$query->condition('approval_status', 0);
        $query->range(0, 1);
        $preference_q = $query->execute();
        $preference_data = $preference_q->fetchObject();
      }
      $code_submission_status = '';
      switch ($preference_data->submited_all_examples_code) {
        case 0:
          $code_submission_status = "<span style='color:red;'>In progress</span>";
          break;
        case 1:
          $code_submission_status = "<span style='color:blue;'>All code submitted</span>";
          break;
        case 2:
          $code_submission_status = "<span style='color:green;'>All code approved</span>";
          break;
        default:
          $code_submission_status = 'Unknown';
          break;
      }

      $proposal_status = '';
      switch ($proposal_data->proposal_status) {
        case 0:
          $proposal_status = "<span style='color:white;'>Pending</span>";
          break;
        case 1:
          $proposal_status = "<span style='color:red;'>Approved</span>";
          break;
        case 2:
          $proposal_status = "<span style='color:black;'>Dis-approved</span>";
          $code_submission_status = "-----";
          break;
        case 3:
          $proposal_status = "<span style='color:green;'>Completed</span>";
          break;
        case 4:
          $proposal_status = "<span style='color:gray;'>External</span>";
          break;
        case 5:
          $proposal_status = "<span style='color:yellow;'>Submitted all codes</span>";
          break;
        default:
          $proposal_status = "<span style='color:black;'>Unknown</span>";
          break;
      }

      if ($proposal_data->proposed_completion_date != 0) {
        $proposed_completion_date = date('d-m-Y', $proposal_data->proposed_completion_date);
      }
      else {
        $proposed_completion_date = "-----";
      }
      if ($proposal_data->completion_date != 0) {
        $completion_date = date('d-m-Y', $proposal_data->completion_date);
      }
      else {
        $completion_date = date('d-m-Y', $proposal_data->approval_date);
      }
      $proposal_rows[] = [
        date('d-m-Y', $proposal_data->creation_date),
        [
          'data' => [
            '#markup' => $this->t('@book <br><em>by @author</em>', [
              '@book' => $preference_data->book ?? '',
              '@author' => $preference_data->author ?? '',
            ]),
          ],
        ],
        $this->buildLinkCell($proposal_data->full_name, 'user/' . $proposal_data->uid),
        [
          'data' => [
            '#markup' => $proposal_status,
          ],
        ],
        [
          'data' => [
            '#markup' => $code_submission_status,
          ],
        ],
        [
          'data' => [
            'status' => $this->buildLinkRenderable($this->t('Status'), 'textbook-companion/manage-proposal/status/' . $proposal_data->id),
            'separator' => ['#markup' => ' | '],
            'edit' => $this->buildLinkRenderable($this->t('Edit'), 'textbook-companion/manage-proposal/edit/' . $proposal_data->id),
          ],
        ],
      ];
    }
    /* check if there are any pending proposals */
    if (!$proposal_rows) {
      $this->messenger()->addStatus($this->t('There are no proposals.'));
      return [
        '#markup' => '',
      ];
    }
    $proposal_header = [
      'Date of Submission',
      'Title of the Book',
      'Contributor Name',
      //'Date of Approval/Completion',
      'Status',
      'Is all code submitted',
      'Action',
    ];
    return [
      '#type' => 'table',
      '#header' => $proposal_header,
      '#rows' => $proposal_rows,
      '#cache' => [
        'tags' => ['textbook_companion:proposal_list', 'textbook_companion:preference_list'],
        'contexts' => ['user.permissions'],
        'max-age' => Cache::PERMANENT,
      ],
    ];
  }

  public function _failed_all($preference_id = 0, $confirm = "") {
    $page_content = "";
    if ($preference_id && $confirm == "yes") {
      /*$query = "
        SELECT *, pro.id as proposal_id FROM textbook_companion_proposal pro
        LEFT JOIN textbook_companion_preference pre ON pre.proposal_id = pro.id
        LEFT JOIN users usr ON usr.uid = pro.uid
        WHERE pre.id = {$preference_id}
        ";
        $result = db_query($query);
        $row = db_fetch_object($result);*/
      $query = \Drupal::database()->select('textbook_companion_proposal', 'pro');
      $query->addField('pro', 'id', 'proposal_id');
      $query->leftJoin('textbook_companion_preference', 'pre', 'pre.proposal_id = pro.id');
      $query->leftJoin('users', 'usr', 'usr.uid = pro.uid');
      $query->addField('usr', 'mail');
      $query->addField('usr', 'name');
      $query->condition('pre.id', $preference_id);
      $row = $query->execute()->fetchObject();
      /* increment failed_reminder */
      /*$query = "
        UPDATE textbook_companion_proposal
        SET failed_reminder = failed_reminder + 1
        WHERE id = {$row->proposal_id}
        ";
        db_query($query);*/
      $query = \Drupal::database()->update('textbook_companion_proposal');
      $query->expression('failed_reminder', 'failed_reminder + 1');
      $query->condition('id', $row->proposal_id);
      $query->execute();
      /* sending mail */
      $to = (string) ($row->mail ?? '');
      $subject = "Failed to upload the TBC codes on time";
      $body = "
    <p>
      Dear {$row->name},<br><br>
      This is to inform you that you have failed to upload the TBC codes on time.<br>
      Please note that the time you have taken is way past the deadline as well.<br>
      Kindly upload the TBC codes on the interface within 5 days from now.<br>
      Failure to submit the same will result in disapproval of your work and cancellation of your internship.<br><br>
      Regards,<br>
      R TBC Team,<br>
      FOSSEE.
    </p>
    ";
      $message = [
        'standard' => [
          'subject' => $subject,
          'body' => [$body],
          'headers' => [
            'From' => 'contact-r@fossee.in',
            'Bcc' => 'contact-r@fossee.in',
            'Content-Type' => 'text/html; charset=UTF-8; format=flowed',
          ],
        ],
      ];
      $result = \Drupal::service('plugin.manager.mail')->mail(
        'textbook_companion',
        'standard',
        $to,
        \Drupal::languageManager()->getDefaultLanguage()->getId(),
        $message,
        'contact-r@fossee.in',
        TRUE
      );
      if (empty($result['result'])) {
        $this->messenger()->addError($this->t('Error sending email message.'));
      }
      Cache::invalidateTags([
        'textbook_companion:proposal_list',
        'textbook_companion:preference_list',
        "textbook_companion:proposal:{$row->proposal_id}",
        "textbook_companion:preference:{$preference_id}",
      ]);
      $this->messenger()->addStatus($this->t('Reminder sent successfully.'));
      return $this->redirectToPath('textbook-companion/manage-proposal/failed');
    }
    else {
      if ($preference_id) {
        /*$query = "
        SELECT * FROM textbook_companion_preference pre
        LEFT JOIN textbook_companion_proposal pro ON pro.id = pre.proposal_id
        WHERE pre.id = {$preference_id}
        ";
        $result = db_query($query);
        $row = db_fetch_object($result);*/
        $query = \Drupal::database()->select('textbook_companion_preference', 'pre');
        $query->fields('pre');
        $query->leftJoin('textbook_companion_proposal', 'pro', 'pro.id = pre.proposal_id');
        $query->condition('pre.id', $preference_id);
        $result = $query->execute();
        $row = $result->fetchObject();
        $page_content .= $this->t('Are you sure you want to notify?') . '<br><br>';
        $page_content .= $this->t('Book: <b>@book</b>', ['@book' => $row->book ?? '']) . '<br>';
        $page_content .= $this->t('Author: <b>@author</b>', ['@author' => $row->author ?? '']) . '<br>';
        $page_content .= $this->t('Contributor: <b>@name</b>', ['@name' => $row->full_name ?? '']) . '<br>';
        $page_content .= $this->t('Expected Completion Date: <b>@date</b>', [
          '@date' => $row->completion_date ? date('d-m-Y', $row->completion_date) : '',
        ]) . '<br><br>';
        $page_content .= $this->buildLinkString($this->t('Yes'), "textbook-companion/manage-proposal/failed/{$preference_id}/yes") . ' | ';
        $page_content .= $this->buildLinkString($this->t('Cancel'), 'textbook-companion/manage-proposal/failed');
        return [
          '#markup' => Markup::create($page_content),
        ];
      }
      else {
        /*$query = "
        SELECT * FROM textbook_companion_proposal pro
        LEFT JOIN textbook_companion_preference pre ON pre.proposal_id = pro.id
        LEFT JOIN users usr ON usr.uid = pro.uid
        WHERE pro.proposal_status = 1 AND pre.approval_status = 1 AND pro.completion_date < %d
        ORDER BY failed_reminder
        ";
        $result = db_query($query, time());*/
        $query = \Drupal::database()->select('textbook_companion_proposal', 'pro');
        $query->fields('pro');
        $query->leftJoin('textbook_companion_preference', 'pre', 'pre.proposal_id = pro.id');
        $query->leftJoin('users', 'usr', 'usr.uid = pro.uid');
        $query->condition('pro.proposal_status', 1);
        $query->condition('pre.approval_status', 1);
        $query->condition('pro.completion_date', \Drupal::time()->getRequestTime(), '<');
        $query->orderBy('failed_reminder', 'ASC');
        $result = $query->execute();
        $headers = [
          "Date of Submission",
          "Book",
          "Contributor Name",
          "Expected Completion Date",
          "Remainders",
          "Action",
        ];
        $rows = [];
        while ($row = $result->fetchObject()) {
          $item = [
            date("d-m-Y", $row->creation_date),
            [
              'data' => [
                '#markup' => $this->t('@book<br><i>by</i> @author', [
                  '@book' => $row->book ?? '',
                  '@author' => $row->author ?? '',
                ]),
              ],
            ],
            $row->name,
            date("d-m-Y", $row->completion_date),
            $row->failed_reminder,
            $this->buildLinkCell($this->t('Remind'), "textbook-companion/manage-proposal/failed/{$row->id}"),
          ];
          array_push($rows, $item);
        }
        return [
          '#type' => 'table',
          '#header' => $headers,
          '#rows' => $rows,
          '#cache' => [
            'tags' => ['textbook_companion:proposal_list', 'textbook_companion:preference_list'],
            'contexts' => ['user.permissions'],
            'max-age' => Cache::PERMANENT,
          ],
        ];
      }
    }
    return [
      '#markup' => Markup::create($page_content),
    ];
  }

  public function code_approval() {
    /* get a list of unapproved chapters */
    /*$pending_chapter_q = db_query("SELECT c.id as c_id, c.number as c_number, c.name as c_name, c.preference_id as c_preference_id FROM {textbook_companion_example} as e JOIN {textbook_companion_chapter} as c ON c.id = e.chapter_id WHERE e.approval_status = 0");*/
    $query = \Drupal::database()->select('textbook_companion_example', 'e');
    $query->fields('c', [
      'id',
      'number',
      'name',
      'preference_id',
    ]);
    $query->addField('c', 'id', 'c_id');
    $query->addField('c', 'number', 'c_number');
    $query->addField('c', 'name', 'c_name');
    $query->addField('c', 'preference_id', 'c_preference_id');
    $query->innerJoin('textbook_companion_chapter', 'c', 'c.id = e.chapter_id');
    $query->condition('e.approval_status', 0);
    $pending_chapter_q = $query->execute();
    if (!$pending_chapter_q) {
      $this->messenger()->addStatus($this->t('There are no pending code approvals.'));
      return [
        '#markup' => '',
      ];
    }
    $rows = [];
    while ($row = $pending_chapter_q->fetchObject()) {
      /* get preference data */
      /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d", $row->c_preference_id);
        $preference_data = db_fetch_object($preference_q);*/
      $query = \Drupal::database()->select('textbook_companion_preference');
      $query->fields('textbook_companion_preference');
      $query->condition('id', $row->c_preference_id);
      $query->condition('approved_codable_example_files', 1);
      $query->condition('submitted_codable_examples_file', 1);
      $query->condition('submited_all_examples_code', 1);
      $result = $query->execute();
      $preference_data = $result->fetchObject();
      /* get proposal data */
      /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE id = %d", $preference_data->proposal_id);
        $proposal_data = db_fetch_object($proposal_q);*/
      if (!$preference_data) {
        //drupal_set_message('No data found.', 'status');
            //drupal_goto('textbook-companion/code-approval');
        //return;
      }
      else {
        $query = \Drupal::database()->select('textbook_companion_proposal');
        $query->fields('textbook_companion_proposal');
        $query->condition('id', $preference_data->proposal_id);
        $result = $query->execute();
        $proposal_data = $result->fetchObject();
        /* setting table row information */
      $rows[] = [
        ['data' => ['#plain_text' => (string) $preference_data->book]],
        ['data' => ['#plain_text' => (string) $row->c_number]],
        ['data' => ['#plain_text' => (string) $row->c_name]],
        ['data' => ['#plain_text' => (string) $proposal_data->full_name]],
        $this->buildLinkCell($this->t('Edit'), 'textbook-companion/code-approval/approve/' . $row->c_id),
      ];
      }
    }
    /* check if there are any pending proposals */
    if (!$rows) {
      $this->messenger()->addStatus($this->t('There are no pending proposals'));
      return [
        '#markup' => '',
      ];
    }
    $header = [
      'Title of the Book',
      'Chapter Number',
      'Title of the Chapter',
      'Contributor Name',
      'Actions',
    ];
    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#cache' => [
        'tags' => ['textbook_companion:example_list', 'textbook_companion:chapter_list', 'textbook_companion:preference_list'],
        'contexts' => ['user.permissions'],
        'max-age' => Cache::PERMANENT,
      ],
    ];
  }

  public function codable_example_approval() {
    $query = \Drupal::database()->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('approved_codable_example_files', 0);
    $query->condition('submited_all_examples_code', 1);
    $query->condition('submitted_codable_examples_file', 1);
    $result = $query->execute();
    $rows = [];
    while ($preference_data = $result->fetchObject()) {
      $query_pro = \Drupal::database()->select('textbook_companion_proposal');
      $query_pro->fields('textbook_companion_proposal');
      $query_pro->condition('id', $preference_data->proposal_id);
      $result_pro = $query_pro->execute();
      $proposal_data = $result_pro->fetchObject();
      /* setting table row information */
      $rows[] = [
        ['data' => ['#plain_text' => (string) $preference_data->book]],
        ['data' => ['#plain_text' => (string) $proposal_data->full_name]],
        $this->buildLinkCell($this->t('Edit'), 'textbook-companion/code-approval/approve-codable-examples/' . $preference_data->id),
      ];
    }

    /* check if there are any pending proposals */
    if (!$rows) {
      $this->messenger()->addStatus($this->t('There are no proposals with codable example file'));
      return [
        '#markup' => '',
      ];
    }
    $header = [
      'Title of the Book',
      'Contributor Name',
      'Actions',
    ];
    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#cache' => [
        'tags' => ['textbook_companion:preference_list', 'textbook_companion:proposal_list'],
        'contexts' => ['user.permissions'],
        'max-age' => Cache::PERMANENT,
      ],
    ];
  }

  public function list_chapters() {
    $user = $this->currentUser();
    $uid = (int) $user->id();
    /************************ start approve book details ************************/
    /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE uid = %d ORDER BY id DESC LIMIT 1", $user->uid);
    $proposal_data = db_fetch_object($proposal_q);*/
    $query = \Drupal::database()->select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('uid', $uid);
    $query->orderBy('id', 'DESC');
    $query->range(0, 1);
    $result = $query->execute();
    $proposal_data = $result->fetchObject();
    if (!$proposal_data) {
      $proposal_link = $this->buildLinkString($this->t('proposal'), 'textbook-companion/proposal');
      $this->messenger()->addError($this->t('Please submit a @proposal.', [
        '@proposal' => $proposal_link,
      ]));
      return $this->redirectToPath('');
    }
    if ($proposal_data->proposal_status != 1 && $proposal_data->proposal_status != 4) {
      switch ($proposal_data->proposal_status) {
        case 0:
          $this->messenger()->addStatus($this->t('We have already received your proposal. We will get back to you soon.'));
          return $this->redirectToPath('');
          break;
        case 2:
          $here_link = $this->buildLinkString($this->t('here'), 'textbook-companion/proposal');
          $this->messenger()->addError($this->t('Your proposal has been dis-approved. Please create another proposal @here.', [
            '@here' => $here_link,
          ]));
          return $this->redirectToPath('');
          break;
        case 3:
          $here_link = $this->buildLinkString($this->t('here'), 'textbook-companion/proposal');
          $this->messenger()->addStatus($this->t('Congratulations! You have completed your last book proposal. You have to create another proposal @here.', [
            '@here' => $here_link,
          ]));
          return $this->redirectToPath('');
          break;
        default:
          $this->messenger()->addError($this->t('Invalid proposal state. Please contact site administrator for further information.'));
          return $this->redirectToPath('');
          break;
      }
    }


    /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE proposal_id = %d AND approval_status = 1 LIMIT 1", $proposal_data->id);
    $preference_data = db_fetch_object($preference_q);*/
    $query = \Drupal::database()->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('proposal_id', $proposal_data->id);
    $query->condition('approval_status', 1);
    $query->range(0, 1);
    $result = $query->execute();
    $preference_data = $result->fetchObject();
    if (!$preference_data) {
      $this->messenger()->addError($this->t('Invalid Book Preference status. Please contact site administrator for further information.'));
      return $this->redirectToPath('');
    }
    if ($preference_data->submited_all_examples_code == 1) {
      $this->messenger()->addStatus($this->t('You have submited your all codes for this book to review, hence you can not upload more code, for any query please write us.'));
      return $this->redirectToPath('');
    }
    /************************ end approve book details **************************/
    $return_html = '<br />';
    $return_html .= $this->t('<strong>Title of the Book:</strong><br />@book<br /><br />', [
      '@book' => $preference_data->book ?? '',
    ]);
    $return_html .= $this->t('<strong>Contributor Name:</strong><br />@name<br /><br />', [
      '@name' => $proposal_data->full_name ?? '',
    ]);
    $return_html .= $this->buildLinkString($this->t('Upload Example Code'), 'textbook-companion/code/upload') . '<br />';
    /* get chapter list */
    $chapter_rows = [];
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE preference_id = %d ORDER BY number ASC", $preference_data->id);*/
    $query = \Drupal::database()->select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('preference_id', $preference_data->id);
    $query->orderBy('number', 'ASC');
    $chapter_q = $query->execute();
    while ($chapter_data = $chapter_q->fetchObject()) {
      /* get example list */
      /* $example_q = db_query("SELECT count(*) as example_count FROM {textbook_companion_example} WHERE chapter_id = %d", $chapter_data->id);
        $example_data = db_fetch_object($example_q);*/
      $query = \Drupal::database()->select('textbook_companion_example');
      $query->addExpression('count(*)', 'example_count');
      $query->condition('chapter_id', $chapter_data->id);
      $result = $query->execute();
      $example_data = $result->fetchObject();
      $chapter_rows[] = [
        ['data' => ['#plain_text' => (string) $chapter_data->number]],
        [
          'data' => [
            '#markup' => $this->t('@name (@link)', [
              '@name' => $chapter_data->name ?? '',
              '@link' => $this->buildLinkString($this->t('Edit'), 'textbook-companion/code/chapter/edit/' . $chapter_data->id),
            ]),
          ],
        ],
        ['data' => ['#plain_text' => (string) $example_data->example_count]],
        $this->buildLinkCell($this->t('View'), 'textbook-companion/code/list-examples/' . $chapter_data->id),
      ];
    }
    /* check if there are any chapters */
    if (!$chapter_rows) {
      $this->messenger()->addStatus($this->t('No uploads found.'));
      return [
        '#markup' => Markup::create($return_html),
      ];
    }
    $chapter_header = [
      'Chapter No.',
      'Title of the Chapter',
      'Uploaded Examples',
      'Actions',
    ];
    $build = [
      '#type' => 'container',
      'content' => [
        '#markup' => Markup::create($return_html),
      ],
      'table' => [
        '#type' => 'table',
        '#header' => $chapter_header,
        '#rows' => $chapter_rows,
      ],
      'form' => $this->formBuilder()->getForm(\Drupal\textbook_companion\Form\AllExampleSubmittedCheckForm::class, $preference_data->id),
      '#cache' => [
        'tags' => ["textbook_companion:proposal:{$proposal_data->id}", "textbook_companion:preference:{$preference_data->id}", 'textbook_companion:chapter_list'],
        'contexts' => ['user'],
        'max-age' => Cache::PERMANENT,
      ],
    ];
    return $build;
  }

  public function upload_examples() {
    return $this->formBuilder()->getForm(\Drupal\textbook_companion\Form\UploadExamplesForm::class);
  }

  public function _upload_examples_delete(int $example_id) {
    $user = $this->currentUser();
    $uid = (int) $user->id();
    $user_mail = $user->getEmail();
    $root_path = textbook_companion_path();
    //var_dump($example_id);die;
    /* check example */
    /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE id = %d LIMIT 1", $example_id);
    $example_data = db_fetch_object($example_q);*/
    $query = \Drupal::database()->select('textbook_companion_example');
    $query->fields('textbook_companion_example');
    $query->condition('id', $example_id);
    $query->range(0, 1);
    $result = $query->execute();
    $example_data = $result->fetchObject();
    if (!$example_data) {
      $this->messenger()->addError($this->t('Invalid example.'));
      return $this->redirectToPath('textbook-companion/code');
    }
    if ($example_data->approval_status != 0) {
      $this->messenger()->addError($this->t('You cannnot delete an example after it has been approved. Please contact site administrator if you want to delete this example.'));
      return $this->redirectToPath('textbook-companion/code');
    }
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d LIMIT 1", $example_data->chapter_id);
    $chapter_data = db_fetch_object($chapter_q);*/
    $query = \Drupal::database()->select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('id', $example_data->chapter_id);
    $query->range(0, 1);
    $result = $query->execute();
    $chapter_data = $result->fetchObject();
    if (!$chapter_data) {
      $this->messenger()->addError($this->t('You do not have permission to delete this example.'));
      return $this->redirectToPath('textbook-companion/code');
    }
    /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d LIMIT 1", $chapter_data->preference_id);
    $preference_data = db_fetch_object($preference_q);*/
    $query = \Drupal::database()->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('id', $chapter_data->preference_id);
    $query->range(0, 1);
    $result = $query->execute();
    $preference_data = $result->fetchObject();
    if (!$preference_data) {
      $this->messenger()->addError($this->t('You do not have permission to delete this example.'));
      return $this->redirectToPath('textbook-companion/code');
    }
    /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE id = %d AND uid = %d LIMIT 1", $preference_data->proposal_id, $user->uid);
    $proposal_data = db_fetch_object($proposal_q);*/
    $query = \Drupal::database()->select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('id', $preference_data->proposal_id);
    $query->condition('uid', $uid);
    $query->range(0, 1);
    $result = $query->execute();
    $proposal_data = $result->fetchObject();
    if (!$proposal_data) {
      $this->messenger()->addError($this->t('You do not have permission to delete this example.'));
      return $this->redirectToPath('textbook-companion/code');
    }
    /* deleting example files */
    if (delete_example($example_data->id)) {
      $this->messenger()->addStatus($this->t('Example deleted.'));
      /* sending email */


      $email_to = $user_mail;
      $config = \Drupal::config('textbook_companion.settings');
      $from = (string) $config->get('textbook_companion_from_email');
      $bcc = (string) $config->get('textbook_companion_emails');
      $cc = (string) $config->get('textbook_companion_cc_emails');
      $params['example_deleted_user']['book_title'] = $preference_data->book;
      $params['example_deleted_user']['chapter_title'] = $chapter_data->name;
      $params['example_deleted_user']['example_number'] = $example_data->number;
      $params['example_deleted_user']['example_caption'] = $example_data->caption;
      $params['example_deleted_user']['user_id'] = $uid;
      $params['example_deleted_user']['headers'] = [
        'From' => $from,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
        'Content-Transfer-Encoding' => '8Bit',
        'X-Mailer' => 'Drupal',
        'Cc' => $cc,
        'Bcc' => $bcc,
      ];
      $langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
      $result = \Drupal::service('plugin.manager.mail')->mail(
        'textbook_companion',
        'example_deleted_user',
        (string) $email_to,
        $langcode,
        $params,
        $from,
        TRUE
      );
      if (empty($result['result'])) {
        $this->messenger()->addError($this->t('Error sending email message.'));
      }
      Cache::invalidateTags([
        'textbook_companion:example_list',
        "textbook_companion:chapter:{$chapter_data->id}",
        "textbook_companion:preference:{$preference_data->id}",
        "textbook_companion:proposal:{$proposal_data->id}",
      ]);
    }
    else {
      $this->messenger()->addStatus($this->t('Error deleting example.'));
    }
    return $this->redirectToPath('textbook-companion/code');
  }

  public function list_examples(int $chapter_id) {
    $user = $this->currentUser();
    $uid = (int) $user->id();
    /************************ start approve book details ************************/
    /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE uid = %d ORDER BY id DESC LIMIT 1", $user->uid);
    $proposal_data = db_fetch_object($proposal_q);*/
    $query = \Drupal::database()->select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('uid', $uid);
    $query->orderBy('id', 'DESC');
    $query->range(0, 1);
    $result = $query->execute();
    $proposal_data = $result->fetchObject();
    if (!$proposal_data) {
      $proposal_link = $this->buildLinkString($this->t('proposal'), 'textbook-companion/proposal');
      $this->messenger()->addError($this->t('Please submit a @proposal.', [
        '@proposal' => $proposal_link,
      ]));
      return $this->redirectToPath('');
    }
    if ($proposal_data->proposal_status != 1 && $proposal_data->proposal_status != 4) {
      switch ($proposal_data->proposal_status) {
        case 0:
          $this->messenger()->addStatus($this->t('We have already received your proposal. We will get back to you soon.'));
          return $this->redirectToPath('');
          break;
        case 2:
          $here_link = $this->buildLinkString($this->t('here'), 'textbook-companion/proposal');
          $this->messenger()->addError($this->t('Your proposal has been dis-approved. Please create another proposal @here.', [
            '@here' => $here_link,
          ]));
          return $this->redirectToPath('');
          break;
        case 3:
          $here_link = $this->buildLinkString($this->t('here'), 'textbook-companion/proposal');
          $this->messenger()->addStatus($this->t('Congratulations! You have completed your last book proposal. You have to create another proposal @here.', [
            '@here' => $here_link,
          ]));
          return $this->redirectToPath('');
          break;
        default:
          $this->messenger()->addError($this->t('Invalid proposal state. Please contact site administrator for further information.'));
          return $this->redirectToPath('');
          break;
      }
    }
    /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE proposal_id = %d AND approval_status = 1 LIMIT 1", $proposal_data->id);
    $preference_data = db_fetch_object($preference_q);*/
    $query = \Drupal::database()->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('proposal_id', $proposal_data->id);
    $query->condition('approval_status', 1);
    $query->range(0, 1);
    $result = $query->execute();
    $preference_data = $result->fetchObject();
    if (!$preference_data) {
      $this->messenger()->addError($this->t('Invalid Book Preference status. Please contact site administrator for further information.'));
      return $this->redirectToPath('');
    }
    /************************ end approve book details **************************/
    /* get chapter details */
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d AND preference_id = %d LIMIT 1", $chapter_id, $preference_data->id);*/
    $query = \Drupal::database()->select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('id', $chapter_id);
    $query->condition('preference_id', $preference_data->id);
    $query->range(0, 1);
    $chapter_q = $query->execute();
    if ($chapter_data = $chapter_q->fetchObject()) {
      $return_html = '<br />';
      $return_html .= $this->t('<strong>Title of the Book:</strong><br />@book<br /><br />', [
        '@book' => $preference_data->book ?? '',
      ]);
      $return_html .= $this->t('<strong>Contributor Name:</strong><br />@name<br /><br />', [
        '@name' => $proposal_data->full_name ?? '',
      ]);
      $return_html .= $this->t('<strong>Chapter Number:</strong><br />@number<br /><br />', [
        '@number' => $chapter_data->number ?? '',
      ]);
      $return_html .= $this->t('<strong>Title of the Chapter:</strong><br />@title<br />', [
        '@title' => $chapter_data->name ?? '',
      ]);
    }
    else {
      $this->messenger()->addError($this->t('Invalid chapter.'));
      return $this->redirectToPath('textbook-companion/code');
    }
    $return_html .= '<br />' . $this->buildLinkString($this->t('Back to Chapter List'), 'textbook-companion/code');
    /* get example list */
    $example_rows = [];
    $query = \Drupal::database()->select('textbook_companion_example');
    $query->fields('textbook_companion_example');
    $query->condition('chapter_id', $chapter_id);
    $example_q = $query->execute();
    while ($example_data = $example_q->fetchObject()) {
      /* approval status */
      $approval_status = '';
      switch ($example_data->approval_status) {
        case 0:
          $approval_status = 'Pending';
          break;
        case 1:
          $approval_status = 'Approved';
          break;
        case 2:
          $approval_status = 'Rejected';
          break;
      }
      /* example files */
      $example_files = '';
      $query = \Drupal::database()->select('textbook_companion_example_files');
      $query->fields('textbook_companion_example_files');
      $query->condition('example_id', $example_data->id);
      $query->orderBy('filetype', 'ASC');
      $example_files_q = $query->execute();
      while ($example_files_data = $example_files_q->fetchObject()) {
        $file_type = '';
        switch ($example_files_data->filetype) {
          case 'S':
            $file_type = 'Main or Source';
            break;
          case 'D':
            $file_type = 'Dataset';
            break;
          case 'X':
            $file_type = 'xcos';
            break;
          default:
        }
        $example_files .= $this->buildLinkString($example_files_data->filename, 'textbook-companion/download/file/' . $example_files_data->id) . ' (' . $file_type . ')<br />';
      }
      if ($example_data->approval_status == 0) {
        $example_rows[] = [
          'data' => [
            ['data' => ['#plain_text' => (string) $example_data->number]],
            ['data' => ['#plain_text' => (string) $example_data->caption]],
            ['data' => ['#plain_text' => (string) $approval_status]],
            [
              'data' => [
                '#markup' => $example_files,
              ],
            ],
            [
              'data' => [
                'edit' => $this->buildLinkRenderable($this->t('Edit'), 'textbook-companion/code/edit/' . $example_data->id),
                'separator' => ['#markup' => ' | '],
                'delete' => $this->buildLinkRenderable($this->t('Delete'), 'textbook-companion/code/delete/' . $example_data->id, [
                  'attributes' => [
                    'onclick' => 'return confirm("Are you sure you want to delete the example?")',
                  ],
                ]),
              ],
            ],
          ],
          'valign' => 'top',
        ];
      }
      else {
        $example_rows[] = [
          'data' => [
            ['data' => ['#plain_text' => (string) $example_data->number]],
            ['data' => ['#plain_text' => (string) $example_data->caption]],
            ['data' => ['#plain_text' => (string) $approval_status]],
            [
              'data' => [
                '#markup' => $example_files,
              ],
            ],
            $this->buildLinkRenderable($this->t('Download'), 'textbook-companion/download/example/' . $example_data->id),
          ],
          'valign' => 'top',
        ];
      }
    }
    $example_header = [
      'Example No.',
      'Caption',
      'Status',
      'Files',
      'Action',
    ];
    return [
      '#type' => 'container',
      'content' => [
        '#markup' => Markup::create($return_html),
      ],
      'table' => [
        '#type' => 'table',
        '#header' => $example_header,
        '#rows' => $example_rows,
      ],
      '#cache' => [
        'tags' => [
          "textbook_companion:proposal:{$proposal_data->id}",
          "textbook_companion:preference:{$preference_data->id}",
          "textbook_companion:chapter:{$chapter_id}",
          'textbook_companion:example_list',
        ],
        'contexts' => ['user'],
        'max-age' => Cache::PERMANENT,
      ],
    ];
  }

  public function textbook_companion_browse_book(?string $query_character = NULL) {
    $return_html = $this->browseList('book');
    $return_html .= '<br /><br />';
    if (!$query_character) {
      /* all books */
      $return_html .= "Please select the starting character of the title of the book";
      return [
        '#markup' => Markup::create($return_html),
      ];
    }
    $book_rows = [];
    /*$book_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE book like '%s%%' AND approval_status = 1", $query_character);*/
    $query = \Drupal::database()->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('book', '' . $query_character . '%%', 'like');
    $query->condition('approval_status', 1);
    $book_q = $query->execute();
    while ($book_data = $book_q->fetchObject()) {
      $book_rows[] = [
        $this->buildLinkCell($book_data->book, 'textbook_run/' . $book_data->id),
        ['data' => ['#plain_text' => (string) $book_data->author]],
      ];
    }
    if (!$book_rows) {
      $return_html .= "Sorry no books are available with that title";
      return [
        '#markup' => Markup::create($return_html),
      ];
    }
    return [
      '#type' => 'container',
      'content' => [
        '#markup' => Markup::create($return_html),
      ],
      'table' => [
        '#type' => 'table',
        '#header' => [
          'Title of the Book',
          'Author Name',
        ],
        '#rows' => $book_rows,
      ],
      '#cache' => [
        'tags' => ['textbook_companion:preference_list'],
        'contexts' => ['url.path'],
        'max-age' => Cache::PERMANENT,
      ],
    ];
  }

  public function textbook_companion_browse_author(?string $query_character = NULL) {
    $return_html = $this->browseList('author');
    $return_html .= '<br /><br />';
    if (!$query_character) {
      /* all books */
      $return_html .= "Please select the starting character of the author's name";
      return [
        '#markup' => Markup::create($return_html),
      ];
    }
    $book_rows = [];
    /*$book_q = db_query("SELECT pe.book as book, pe.author as author, pe.publisher as publisher, pe.year as year, pe.id as id FROM {textbook_companion_preference} pe RIGHT JOIN  {textbook_companion_proposal} po on pe.proposal_id=po.id  WHERE po.proposal_status=3 and pe.approval_status = 1", $query_character);*/
    $query = \Drupal::database()->select('textbook_companion_preference', 'pe');
    $query->fields('pe', [
      'book',
      'author',
      'publisher',
      'year',
      'id',
    ]);
    $query->rightJoin('textbook_companion_proposal', 'po', 'pe.proposal_id = po.id');
    $query->condition('po.proposal_status', 3);
    $query->condition('pe.approval_status', 1);
    $book_q = $query->execute();
    while ($book_data = $book_q->fetchObject()) {
      /* Initial's fix algorithm */
      preg_match_all("/{$query_character}[a-z]+/", $book_data->author, $matches);
      if (count($matches) > 0) {
        /* Remove the word "And"/i from the match array and make match bold */
        if (count($matches[0]) > 0) {
          foreach ($matches[0] as $key => $value) {
            if (strtolower($value) == "and") {
              unset($matches[$key]);
            }
            else {
              $matches[0][$key] = "<b>" . $value . "</b>";
              $book_data->author = str_replace($value, $matches[0][$key], $book_data->author);
            }
          }
        }
        /* Check count of matches after removing And */
        if (count($matches[0]) > 0) {
          $book_rows[] = [
            $this->buildLinkCell($book_data->book, 'textbook_run/' . $book_data->id),
            [
              'data' => [
                '#markup' => $book_data->author,
              ],
            ],
          ];
        }
      }
    }
    if (!$book_rows) {
      $return_html .= "Sorry no books are available with that author's name";
      return [
        '#markup' => Markup::create($return_html),
      ];
    }
    return [
      '#type' => 'container',
      'content' => [
        '#markup' => Markup::create($return_html),
      ],
      'table' => [
        '#type' => 'table',
        '#header' => [
          'Title of the Book',
          'Author Name',
        ],
        '#rows' => $book_rows,
      ],
      '#cache' => [
        'tags' => ['textbook_companion:preference_list', 'textbook_companion:proposal_list'],
        'contexts' => ['url.path'],
        'max-age' => Cache::PERMANENT,
      ],
    ];
  }

  public function textbook_companion_browse_student(?string $query_character = NULL) {
    $return_html = $this->browseList('student');
    $return_html .= '<br /><br />';
    //print $query_character;
    //die();
    if (!$query_character) {
      /* all books */
      $return_html .= "Please select the starting character of the student's name";
      return [
        '#markup' => Markup::create($return_html),
      ];
    }
    $book_rows = [];
    /*$student_q = db_query("
    SELECT po.full_name, pe.book as book, pe.author as author, pe.publisher as publisher, pe.year as year, pe.id as pe_id, po.approval_date as approval_date
    FROM textbook_companion_preference pe LEFT JOIN textbook_companion_proposal po ON pe.proposal_id = po.id 
    WHERE po.proposal_status = 3 AND pe.approval_status = 1 AND full_name LIKE '%s%%'
    ", $query_character);*/
    $query = \Drupal::database()->select('textbook_companion_preference', 'pe');
    $query->fields('po', [
      'full_name',
      'approval_date',
    ]);
    $query->fields('pe', [
      'book',
      'author',
      'publisher',
      'year',
      'id',
    ]);
    $query->leftJoin('textbook_companion_proposal', 'po', 'pe.proposal_id = po.id');
    $query->condition('po.proposal_status', 3);
    $query->condition('pe.approval_status', 1);
    $query->condition('full_name', '' . $query_character . '%%', 'LIKE');
    $student_q = $query->execute();
    while ($student_data = $student_q->fetchObject()) {
      $book_rows[] = [
        $this->buildLinkCell($student_data->book, 'textbook_run/' . $student_data->pe_id),
        ['data' => ['#plain_text' => (string) $student_data->full_name]],
      ];
    }
    if (!$book_rows) {
      $return_html .= "Sorry no books are available with that student's name";
      return [
        '#markup' => Markup::create($return_html),
      ];
    }
    return [
      '#type' => 'container',
      'content' => [
        '#markup' => Markup::create($return_html),
      ],
      'table' => [
        '#type' => 'table',
        '#header' => [
          'Title of the Book',
          'Student Name',
        ],
        '#rows' => $book_rows,
      ],
      '#cache' => [
        'tags' => ['textbook_companion:preference_list', 'textbook_companion:proposal_list'],
        'contexts' => ['url.path'],
        'max-age' => Cache::PERMANENT,
      ],
    ];
  }

  public function textbook_companion_download_example_file(int $example_file_id) {
    $root_path = textbook_companion_path();
    /*$example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE id = %d LIMIT 1", $example_file_id);
    $example_file_data = db_fetch_object($example_files_q);*/
    /*$query = db_select('textbook_companion_example_files');
    $query->fields('textbook_companion_example_files');
    $query->condition('id', $example_file_id);
    $query->range(0, 1);
    $result = $query->execute();*/
    $example_files_q = \Drupal::database()->query("select * from textbook_companion_preference tcp join textbook_companion_chapter tcc on tcp.id=tcc.preference_id join textbook_companion_example tce ON tcc.id=tce.chapter_id join textbook_companion_example_files tcef on tce.id=tcef.example_id where tcef.id= :example_id LIMIT 1", [
      ':example_id' => $example_file_id
      ]);
    $example_file_data = $example_files_q->fetchObject();
    if (!$example_file_data) {
      throw new NotFoundHttpException();
    }
    $file_path = $root_path . $example_file_data->directory_name . '/' . $example_file_data->filepath;
    if (!is_file($file_path)) {
      throw new NotFoundHttpException();
    }
    $file_path = $this->assertWithinBasePath($root_path, $file_path);
    $response = new BinaryFileResponse($file_path);
    $response->headers->set('Content-Type', $example_file_data->filemime);
    $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $example_file_data->filename);
    return $response;
  }

  public function textbook_companion_download_sample_code(int $proposal_id) {
    $root_path = textbook_companion_samplecode_path();
    $query = \Drupal::database()->select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('id', $proposal_id);
    $query->range(0, 1);
    $result = $query->execute();
    $example_file_data = $result->fetchObject();
    if (!$example_file_data) {
      throw new NotFoundHttpException();
    }
    $samplecodename = substr($example_file_data->samplefilepath, strrpos($example_file_data->samplefilepath, '/') + 1);
    $file_path = $root_path . $example_file_data->samplefilepath;
    if (!is_file($file_path)) {
      throw new NotFoundHttpException();
    }
    $file_path = $this->assertWithinBasePath($root_path, $file_path);
    $response = new BinaryFileResponse($file_path);
    $response->headers->set('Content-Type', 'application/zip');
    $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $samplecodename);
    return $response;
  }

  public function textbook_companion_download_example(int $example_id) {
    $root_path = textbook_companion_path();
    $root_temp_path = textbook_companion_temp_path();
    /* get example data */
    /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE id = %d", $example_id);
    $example_data = db_fetch_object($example_q);*/
    $query = \Drupal::database()->select('textbook_companion_example');
    $query->fields('textbook_companion_example');
    $query->condition('id', $example_id);
    $result = $query->execute();
    $example_data = $result->fetchObject();
    if (!$example_data) {
      throw new NotFoundHttpException();
    }
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d", $example_data->chapter_id);
    $chapter_data = db_fetch_object($chapter_q);*/
    $query = \Drupal::database()->select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('id', $example_data->chapter_id);
    $result = $query->execute();
    $chapter_data = $result->fetchObject();
    if (!$chapter_data) {
      throw new NotFoundHttpException();
    }
    /*$example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $example_id);*/
    /* $query = db_select('textbook_companion_example_files');
    $query->fields('textbook_companion_example_files');
    $query->condition('example_id', $example_id);
    $example_files_q = $query->execute();*/
    $example_files_q = \Drupal::database()->query("select * from textbook_companion_preference tcp join textbook_companion_chapter tcc on tcp.id=tcc.preference_id join textbook_companion_example tce ON tcc.id=tce.chapter_id join textbook_companion_example_files tcef on tce.id=tcef.example_id where tcef.example_id= :example_id", [
      ':example_id' => $example_id
      ]);
    $EX_PATH = 'EX' . $example_data->number . '/';
    /* zip filename */
    if (!is_dir($root_temp_path . 'tbc_download_temp')) {
      mkdir($root_temp_path . 'tbc_download_temp');
    }
    $zip_filename = $root_temp_path . 'tbc_download_temp/' . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';
    /* creating zip archive on the server */
    $zip = new \ZipArchive();
    $zip->open($zip_filename, \ZipArchive::CREATE);
    while ($example_files_row = $example_files_q->fetchObject()) {
      $file_path = $root_path . $example_files_row->directory_name . '/' . $example_files_row->filepath;
      if (is_file($file_path)) {
        $file_path = $this->assertWithinBasePath($root_path, $file_path);
        $zip->addFile($file_path, $EX_PATH . $example_files_row->filename);
      }
    }
    $zip_file_count = $zip->numFiles;
    $zip->close();
    if ($zip_file_count > 0) {
      /* download zip file */
      $response = new BinaryFileResponse($zip_filename);
      $response->headers->set('Content-Type', 'application/octet-stream');
      $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'EX' . $example_data->number . '.zip');
      $response->deleteFileAfterSend(TRUE);
      return $response;
    }
    $this->messenger()->addError($this->t('There are no files in this examples to download'));
    return $this->redirectToPath('textbook-companion/textbook-run');
  }

  public function textbook_companion_download_codable_example_file(int $proposal_id) {
    $root_path = textbook_companion_path();
    $query = \Drupal::database()->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('proposal_id', $proposal_id);
    $query->range(0, 1);
    $result = $query->execute();
    $proposal_data = $result->fetchObject();
    $query = \Drupal::database()->select('textbook_companion_codable_example_files');
    $query->fields('textbook_companion_codable_example_files');
    $query->condition('proposal_id', $proposal_id);
    $query->range(0, 1);
    $result = $query->execute();
    $example_file_data = $result->fetchObject();
    if (!$proposal_data || !$example_file_data) {
      throw new NotFoundHttpException();
    }
    $filename = $example_file_data->filename;
    $file_path = $root_path . $proposal_data->directory_name . '/' . $example_file_data->filepath;
    if (!is_file($file_path)) {
      throw new NotFoundHttpException();
    }
    $file_path = $this->assertWithinBasePath($root_path, $file_path);
    $response = new BinaryFileResponse($file_path);
    $response->headers->set('Content-Type', $example_file_data->filemime);
    $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
    return $response;
  }

  public function textbook_companion_download_chapter(int $chapter_id) {
    //var_dump($chapter_id);die;
    $root_path = textbook_companion_path();
    /* get example data */
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d", $chapter_id);
    $chapter_data = db_fetch_object($chapter_q);*/
    $query = \Drupal::database()->select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('id', $chapter_id);
    $result = $query->execute();
    $chapter_data = $result->fetchObject();
    if (!$chapter_data) {
      throw new NotFoundHttpException();
    }
    $CH_PATH = 'CH' . $chapter_data->number . '/';
    /* zip filename */
    if (!is_dir($root_path . 'tbc_download_temp')) {
      mkdir($root_path . 'tbc_download_temp');
    }
    $zip_filename = $root_path . 'tbc_download_temp/' . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';

    /* creating zip archive on the server */
    $zip = new ZipArchive();
    $zip->open($zip_filename, \ZipArchive::CREATE);
    /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND approval_status = 1", $chapter_id);*/
    $query = \Drupal::database()->select('textbook_companion_example');
    $query->fields('textbook_companion_example');
    $query->condition('chapter_id', $chapter_id);
    $query->condition('approval_status', 1);
    $example_q = $query->execute();
    while ($example_row = $example_q->fetchObject()) {
      $EX_PATH = 'EX' . $example_row->number . '/';
      /*$example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $example_row->id);*/
      /*$query = db_select('textbook_companion_example_files');
        $query->fields('textbook_companion_example_files');
        $query->condition('example_id', $example_row->id);
        $example_files_q = $query->execute();*/
      $example_files_q = \Drupal::database()->query("select * from textbook_companion_preference tcp join textbook_companion_chapter tcc on tcp.id=tcc.preference_id join textbook_companion_example tce ON tcc.id=tce.chapter_id join textbook_companion_example_files tcef on tce.id=tcef.example_id where tcef.example_id= :example_id", [
        ':example_id' => $example_row->id
        ]);
      while ($example_files_row = $example_files_q->fetchObject()) {
        $file_path = $root_path . $example_files_row->directory_name . '/' . $example_files_row->filepath;
        if (is_file($file_path)) {
          $file_path = $this->assertWithinBasePath($root_path, $file_path);
          $zip->addFile($file_path, $CH_PATH . $EX_PATH . $example_files_row->filename);
        }
      }
    }
    $zip_file_count = $zip->numFiles;
    $zip->close();
    if ($zip_file_count > 0) {
      /* download zip file */
      $response = new BinaryFileResponse($zip_filename);
      $response->headers->set('Content-Type', 'application/zip');
      $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'CH' . $chapter_data->number . '.zip');
      $response->deleteFileAfterSend(TRUE);
      return $response;
    }
    $this->messenger()->addError($this->t('There are no examples in this chapter to download'));
    return $this->redirectToPath('textbook-companion/textbook-run');
  }

  public function textbook_companion_download_book(int $book_id, int $include_unapproved = 0) {
    $root_path = textbook_companion_path();
    $root_temp_path = textbook_companion_temp_path();
    /* get example data */
    /*$book_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d", $book_id);
    $book_data = db_fetch_object($book_q);*/
    $query = \Drupal::database()->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('id', $book_id);
    $result = $query->execute();
    $book_data = $result->fetchObject();
    if (!$book_data) {
      throw new NotFoundHttpException();
    }
    $zipname = str_replace(' ', '_', ($book_data->book));
    $directory_name = $book_data->directory_name;
    $BK_PATH = $zipname . '/';
    /* zip filename */
    if (!is_dir($root_temp_path . 'tbc_download_temp')) {
      mkdir($root_temp_path . 'tbc_download_temp');
    }
    $zip_filename = $root_temp_path . 'tbc_download_temp/' . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';
    /* creating zip archive on the server */
    $zip = new \ZipArchive();
    $zip->open($zip_filename, ZipArchive::CREATE);
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE preference_id = %d", $book_id);*/
    $query = \Drupal::database()->select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('preference_id', $book_id);
    $chapter_q = $query->execute();
    while ($chapter_row = $chapter_q->fetchObject()) {
      $CH_PATH = 'CH' . $chapter_row->number . '/';
      /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND approval_status = 1", $chapter_row->id);*/
      $query = \Drupal::database()->select('textbook_companion_example');
      $query->fields('textbook_companion_example');
      $query->condition('chapter_id', $chapter_row->id);
      if (!$include_unapproved) {
        $query->condition('approval_status', 1);
      }
      $example_q = $query->execute();
      while ($example_row = $example_q->fetchObject()) {
        $EX_PATH = 'EX' . $example_row->number . '/';
        /*$example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $example_row->id);*/
        $query = \Drupal::database()->select('textbook_companion_example_files');
        $query->fields('textbook_companion_example_files');
        $query->condition('example_id', $example_row->id);
        $example_files_q = $query->execute();
        while ($example_files_row = $example_files_q->fetchObject()) {
          $file_path = $root_path . $directory_name . '/' . $example_files_row->filepath;
          if (is_file($file_path)) {
            $file_path = $this->assertWithinBasePath($root_path, $file_path);
            $zip->addFile($file_path, $BK_PATH . $CH_PATH . $EX_PATH . $example_files_row->filename);
          }
        }
      }
    }
    $zip_file_count = $zip->numFiles;
    $zip->close();
    if ($zip_file_count > 0) {
      /* download zip file */
      $response = new BinaryFileResponse($zip_filename);
      $response->headers->set('Content-Type', 'application/zip');
      $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, str_replace(' ', '_', ($book_data->book)) . '.zip');
      $response->deleteFileAfterSend(TRUE);
      return $response;
    }
    $this->messenger()->addError($this->t('There are no examples in this book to download'));
    return $this->redirectToPath('textbook-companion/textbook-run');
  }

  public function textbook_companion_download_full_chapter(int $chapter_id) {
    $root_path = textbook_companion_path();
    $APPROVE_PATH = 'APPROVED/';
    $PENDING_PATH = 'PENDING/';
    /* get example data */
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d", $chapter_id);
    $chapter_data = db_fetch_object($chapter_q);*/
    $query = \Drupal::database()->select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('id', $chapter_id);
    $chapter_q = $query->execute();
    $chapter_data = $chapter_q->fetchObject();
    if (!$chapter_data) {
      throw new NotFoundHttpException();
    }
    $CH_PATH = 'CH' . $chapter_data->number . '/';
    /* zip filename */
    $zip_filename = $root_path . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';
    /* creating zip archive on the server */
    $zip = new \ZipArchive();
    $zip->open($zip_filename, ZipArchive::CREATE);
    /* approved examples */
    /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND approval_status = 1", $chapter_id);*/
    $query = \Drupal::database()->select('textbook_companion_example');
    $query->fields('textbook_companion_example');
    $query->condition('chapter_id', $chapter_id);
    $query->condition('approval_status', 1);
    $example_q = $query->execute();
    while ($example_row = $example_q->fetchObject()) {
      $EX_PATH = 'EX' . $example_row->number . '/';
      /*$example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $example_row->id);*/
      $query = \Drupal::database()->select('textbook_companion_example_files');
      $query->fields('textbook_companion_example_files');
      $query->condition('example_id', $example_row->id);
      $example_files_q = $query->execute();
      while ($example_files_row = $example_files_q->fetchObject()) {
        $file_path = $root_path . $example_files_row->filepath;
        if (is_file($file_path)) {
          $file_path = $this->assertWithinBasePath($root_path, $file_path);
          $zip->addFile($file_path, $APPROVE_PATH . $CH_PATH . $EX_PATH . $example_files_row->filename);
        }
      }
    }
    /* unapproved examples */
    /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND approval_status = 0", $chapter_id);*/
    $query = \Drupal::database()->select('textbook_companion_example');
    $query->fields('textbook_companion_example');
    $query->condition('chapter_id', $chapter_id);
    $query->condition('approval_status', 0);
    $example_q = $query->execute();
    while ($example_row = $example_q->fetchObject()) {
      $EX_PATH = 'EX' . $example_row->number . '/';
      /*$example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $example_row->id);*/
      $example_files_q = \Drupal::database()->query("select * from textbook_companion_preference tcp join textbook_companion_chapter tcc on tcp.id=tcc.preference_id join textbook_companion_example tce ON tcc.id=tce.chapter_id join textbook_companion_example_files tcef on tce.id=tcef.example_id where tcef.example_id= :example_id", [
        ':example_id' => $example_row->id
        ]);
      /*$query = db_select('textbook_companion_example_files');
        $query->fields('textbook_companion_example_files');
        $query->condition('example_id', $example_row->id);
        $example_files_q = $query->execute();*/
      while ($example_files_row = $example_files_q->fetchObject()) {
        $file_path = $root_path . $example_files_row->directory_name . '/' . $example_files_row->filepath;
        if (is_file($file_path)) {
          $file_path = $this->assertWithinBasePath($root_path, $file_path);
          $zip->addFile($file_path, $PENDING_PATH . $CH_PATH . $EX_PATH . $example_files_row->filename);
        }
      }
    }
    $zip_file_count = $zip->numFiles;
    $zip->close();
    if ($zip_file_count > 0) {
      /* download zip file */
      $response = new BinaryFileResponse($zip_filename);
      $response->headers->set('Content-Type', 'application/zip');
      $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'CH' . $chapter_data->number . '.zip');
      $response->deleteFileAfterSend(TRUE);
      return $response;
    }
    $this->messenger()->addError($this->t('There are no examples in this chapter to download'));
    return $this->redirectToPath('textbook-companion/code-approval/bulk');
  }

  public function textbook_companion_download_full_book(int $book_id) {
    $root_path = textbook_companion_path();
    $APPROVE_PATH = 'APPROVED/';
    $PENDING_PATH = 'PENDING/';
    /* get example data */
    /*$book_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d", $book_id);
    $book_data = db_fetch_object($book_q);*/
    $query = \Drupal::database()->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('id', $book_id);
    $book_q = $query->execute();
    $book_data = $book_q->fetchObject();
    if (!$book_data) {
      throw new NotFoundHttpException();
    }
    //$zipname = str_replace(' ','_',($book_data->book));
    //$BK_PATH = $zipname . '/';
    $BK_PATH = $book_data->book . '/';
    /* zip filename */
    $zip_filename = $root_path . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';
    /* creating zip archive on the server */
    $zip = new \ZipArchive();
    $zip->open($zip_filename, ZipArchive::CREATE);
    /* approved examples */
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE preference_id = %d", $book_id);*/
    $query = \Drupal::database()->select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('preference_id', $book_id);
    $chapter_q = $query->execute();
    while ($chapter_row = $chapter_q->fetchObject()) {
      $CH_PATH = 'CH' . $chapter_row->number . '/';
      /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND approval_status = 1", $chapter_row->id);*/
      $query = \Drupal::database()->select('textbook_companion_example');
      $query->fields('textbook_companion_example');
      $query->condition('chapter_id', $chapter_row->id);
      $query->condition('approval_status', 1);
      $example_q = $query->execute();
      while ($example_row = $example_q->fetchObject()) {
        $EX_PATH = 'EX' . $example_row->number . '/';
        /*$example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $example_row->id);*/
        $example_files_q = \Drupal::database()->query("select * from textbook_companion_preference tcp join textbook_companion_chapter tcc on tcp.id=tcc.preference_id join textbook_companion_example tce ON tcc.id=tce.chapter_id join textbook_companion_example_files tcef on tce.id=tcef.example_id where tcef.example_id= :example_id", [
          ':example_id' => $example_row->id
          ]);
        /*$query = db_select('textbook_companion_example_files');
            $query->fields('textbook_companion_example_files');
            $query->condition('example_id', $example_row->id);
            $example_files_q = $query->execute();*/
        while ($example_files_row = $example_files_q->fetchObject()) {
          $file_path = $root_path . $example_files_row->directory_name . '/' . $example_files_row->filepath;
          if (is_file($file_path)) {
            $file_path = $this->assertWithinBasePath($root_path, $file_path);
            $zip->addFile($file_path, $BK_PATH . $APPROVE_PATH . $CH_PATH . $EX_PATH . $example_files_row->filename);
          }
        }
      }
      /* unapproved examples */
      /* $example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND approval_status = 0", $chapter_row->id);*/
      $query = \Drupal::database()->select('textbook_companion_example');
      $query->fields('textbook_companion_example');
      $query->condition('chapter_id', $chapter_row->id);
      $query->condition('approval_status', 0);
      $example_q = $query->execute();
      while ($example_row = $example_q->fetchObject()) {
        $EX_PATH = 'EX' . $example_row->number . '/';
        /*$example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $example_row->id);*/
        $example_files_q = \Drupal::database()->query("select * from textbook_companion_preference tcp join textbook_companion_chapter tcc on tcp.id=tcc.preference_id join textbook_companion_example tce ON tcc.id=tce.chapter_id join textbook_companion_example_files tcef on tce.id=tcef.example_id where tcef.example_id= :example_id", [
          ':example_id' => $example_row->id
          ]);
        /*$query = db_select('textbook_companion_example_files');
            $query->fields('textbook_companion_example_files');
            $query->condition('example_id', $example_row->id);
            $example_files_q = $query->execute();*/
        while ($example_files_row = $example_files_q->fetchObject()) {
          $file_path = $root_path . $example_files_row->directory_name . '/' . $example_files_row->filepath;
          if (is_file($file_path)) {
            $file_path = $this->assertWithinBasePath($root_path, $file_path);
            $zip->addFile($file_path, $BK_PATH . $PENDING_PATH . $CH_PATH . $EX_PATH . $example_files_row->filename);
          }
        }
      }
    }
    $zip_file_count = $zip->numFiles;
    $zip->close();
    if ($zip_file_count > 0) {
      /* download zip file */
      $response = new BinaryFileResponse($zip_filename);
      $response->headers->set('Content-Type', 'application/zip');
      $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, str_replace(' ', '_', ($book_data->book)) . '.zip');
      $response->deleteFileAfterSend(TRUE);
      return $response;
    }
    $this->messenger()->addError($this->t('There are no examples in this book to download'));
    return $this->redirectToPath('textbook-companion/code-approval/bulk');
  }

  public function textbook_companion_delete_book(int $book_id) {
    del_book_pdf($book_id);
    $this->messenger()->addStatus($this->t('Book schedule for regeneration.'));
    return $this->redirectToPath('textbook-companion/code-approval/bulk');
  }

  public function textbook_companion_ajax(Request $request) {
    $query_type = $request->query->get('type') ?? $request->attributes->get('query_type');
    $response_text = '';
    if ($query_type == 'chapter_title') {
      $chapter_number = $request->query->get('chapter_number') ?? $request->attributes->get('chapter_number');
      $preference_id = $request->query->get('preference_id') ?? $request->attributes->get('preference_id');
      /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE number = %d AND preference_id = %d LIMIT 1", $chapter_number, $preference_id);*/
      $query = \Drupal::database()->select('textbook_companion_chapter');
      $query->fields('textbook_companion_chapter');
      $query->condition('number', $chapter_number);
      $query->condition('preference_id', $preference_id);
      $query->range(0, 1);
      $chapter_q = $query->execute();
      if ($chapter_data = $chapter_q->fetchObject()) {
        $response_text = $chapter_data->name;
      } //$chapter_data = $chapter_q->fetchObject()
    } //$query_type == 'chapter_title'
    else {
      if ($query_type == 'example_exists') {
        $chapter_number = $request->query->get('chapter_number') ?? $request->attributes->get('chapter_number');
        $preference_id = $request->query->get('preference_id') ?? $request->attributes->get('preference_id');
        $example_number = $request->query->get('example_number') ?? $request->attributes->get('example_number');
        $chapter_id = 0;
        /* $chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE number = %d AND preference_id = %d LIMIT 1", $chapter_number, $preference_id);*/
        $query = \Drupal::database()->select('textbook_companion_chapter');
        $query->fields('textbook_companion_chapter');
        $query->condition('number', $chapter_number);
        $query->condition('preference_id', $preference_id);
        $query->range(0, 1);
        $chapter_q = $query->execute();
        if (!$chapter_data = $chapter_q->fetchObject()) {
          return new Response('');
        } //!$chapter_data = $chapter_q->fetchObject()
        else {
          $chapter_id = $chapter_data->id;
        }
        /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND number = '%s' LIMIT 1", $chapter_id, $example_number);*/
        $query = \Drupal::database()->select('textbook_companion_example');
        $query->fields('textbook_companion_example');
        $query->condition('chapter_id', $chapter_id);
        $query->condition('number', $example_number);
        $query->range(0, 1);
        $example_q = $query->execute();
        if ($example_data = $example_q->fetchObject()) {
          if ($example_data->approval_status == 1) {
            $response_text = 'Warning! Example already approved. You cannot upload the same example again.';
          }
          else {
            $response_text = 'Warning! Example already uploaded. Delete the example and reupload it.';
          }
        } //$example_data = $example_q->fetchObject()
      }
    } //$query_type == 'example_exists'
    return new Response($response_text);
  }

  public function _data_entry_proposal_all() {
    /* get pending proposals to be approved */
    $proposal_rows = [];
    /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE approval_status = 1 ORDER BY book ASC");*/
    $query = \Drupal::database()->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('approval_status', 1);
    $query->orderBy('book', 'ASC');
    $preference_q = $query->execute();
    $sno = 1;
    while ($preference_data = $preference_q->fetchObject()) {
      $proposal_rows[] = [
        $sno++,
        ['data' => ['#plain_text' => (string) $preference_data->book]],
        ['data' => ['#plain_text' => (string) $preference_data->author]],
        ['data' => ['#plain_text' => (string) $preference_data->isbn]],
        $this->buildLinkCell($this->t('Edit'), 'textbook-companion/dataentry-edit/' . $preference_data->id),
      ];
    }
    /* check if there are any pending proposals */
    if (!$proposal_rows) {
      $this->messenger()->addStatus($this->t('There are no proposals.'));
      return [
        '#markup' => '',
      ];
    }
    $proposal_header = [
      'SNO',
      'Title of the Book',
      'Author',
      'ISBN',
      '',
    ];
    return [
      '#type' => 'table',
      '#header' => $proposal_header,
      '#rows' => $proposal_rows,
      '#cache' => [
        'tags' => ['textbook_companion:preference_list'],
        'contexts' => ['user.permissions'],
        'max-age' => Cache::PERMANENT,
      ],
    ];
  }

  public function dataentry_edit($id = NULL) {
    if ($id) {
      return $this->formBuilder()->getForm(\Drupal\textbook_companion\Form\DataentryEditForm::class, $id);
    }
    else {
      return [
        '#markup' => $this->t('Access denied'),
      ];
    }
  }

  public function cheque_proposal_all() {


    $form['#redirect'] = FALSE;
    $form['search_cheque'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search'),
      '#size' => 48,
    ];
    $form['submit_cheque'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
    ];
    $form['cancel_cheque'] = [
      '#type' => 'markup',
      '#markup' => $this->buildLinkString($this->t('Cancel'), ''),
    ];


    $count = 20;
    /* get pending proposals to be approved */
    $proposal_rows = [];

    /*$proposal_q = "SELECT * FROM {textbook_companion_proposal} ORDER BY id DESC";*/
    $query = \Drupal::database()->select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->orderBy('id', 'DESC');


    /*$pagerquery = pager_query($proposal_q, $count); */
    $pagerquery = $query->extend('PagerDefault')->limit($count)->execute();

    while ($proposal_data = $pagerquery->fetchObject()) {
      /* get preference */

      /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE proposal_id = %d AND approval_status = 1 LIMIT 1", $proposal_data->id);
    $preference_data = db_fetch_object($preference_q);*/

      $query = \Drupal::database()->select('textbook_companion_preference');
      $query->fields('textbook_companion_preference');
      $query->condition('proposal_id', $proposal_data->id);
      $query->condition('approval_status', 1);
      $query->range(0, 1);
      $result = $query->execute();
      $preference_data = $result->fetchObject();

      /*$preference_q1 = db_query("SELECT * FROM {textbook_companion_proposal} WHERE uid = %d AND approval_status = 1 LIMIT 1", $proposal_data->id);
    $preference_data1 = db_fetch_object($preference_q1);*/

      $query = \Drupal::database()->select('textbook_companion_proposal');
      $query->fields('textbook_companion_proposal');
      $query->condition('uid', $proposal_data->id);
      $query->condition('proposal_status', 1);
      $query->range(0, 1);
      $result = $query->execute();
      $preference_data1 = $result->fetchObject();


      $proposal_status = '';
      switch ($proposal_data->proposal_status) {
        case 0:
          $proposal_status = 'Pending';
          break;
        case 1:
          $proposal_status = 'Approved';
          break;
        case 2:
          $proposal_status = 'Dis-approved';
          break;
        case 3:
          $proposal_status = 'Completed';
          break;
        default:
          $proposal_status = 'Unknown';
          break;
      }
      $proposal_rows[] = [
        date('d-m-Y', $proposal_data->creation_date),
        $this->buildLinkCell($proposal_data->full_name, 'user/' . $proposal_data->uid),
        date('d-m-Y', $proposal_data->completion_date),
        [
          'data' => [
            'form_submission' => $this->buildLinkRenderable($this->t('Form Submission'), 'textbook-companion/manage-proposal/paper-submission/' . $proposal_data->id),
            'separator' => ['#markup' => ' | '],
            'cheque_details' => $this->buildLinkRenderable($this->t('Cheque Details'), 'textbook-companion/cheque-contact/status/' . $proposal_data->id),
          ],
        ],
      ];
    }

    /* check if there are any pending proposals */
    if (!$proposal_rows) {
      $this->messenger()->addStatus($this->t('There are no proposals.'));
      return [
        '#markup' => '',
      ];
    }

    $proposal_header = [
      'Date of Submission',
      'Contributor Name',
      'Expected Date of Completion',
      'Status',
    ];
    return [
      '#type' => 'container',
      'table' => [
        '#type' => 'table',
        '#header' => $proposal_header,
        '#rows' => $proposal_rows,
      ],
      'pager' => [
        '#type' => 'pager',
      ],
      '#cache' => [
        'tags' => ['textbook_companion:proposal_list'],
        'contexts' => ['user.permissions'],
        'max-age' => Cache::PERMANENT,
      ],
    ];
  }

  public function _list_all_certificates() {

    $user = $this->currentUser();
    $uid = (int) $user->id();
    if (!$uid) {
      $this->messenger()->addError($this->t('Log in to download the certificate'));
      return $this->redirectToPath('');
    }
    /*$query_id =db_query("SELECT id FROM textbook_companion_proposal WHERE proposal_status=3 AND uid=".$user->uid);
    $exist_id = db_fetch_object($query_id);*/
    $query = \Drupal::database()->select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal', ['id']);
    $query->condition('proposal_status', 3);
    $query->condition('uid', $uid);
    $result = $query->execute();
    $exist_id = $result->fetchObject();
    if ($exist_id && $exist_id->id) {
      $search_rows = [];
      $query3 = \Drupal::database()->query("SELECT prop.id,pref.isbn,pref.book,pref.author FROM textbook_companion_proposal as prop,textbook_companion_preference as pref WHERE prop.proposal_status=3 AND pref.approval_status=1 AND pref.proposal_id=prop.id AND prop.uid=:uid", [
        ":uid" => $uid
        ]);
      while ($search_data3 = $query3->fetchObject()) {
        if ($search_data3->id) {
          $search_rows[] = [
            ['data' => ['#plain_text' => (string) $search_data3->isbn]],
            ['data' => ['#plain_text' => (string) $search_data3->book]],
            ['data' => ['#plain_text' => (string) $search_data3->author]],
            $this->buildLinkCell($this->t('Download Certificate'), 'textbook-companion/certificate/generate-pdf/' . $search_data3->id),
          ];
        }
      }
      if ($search_rows) {
        $search_header = [
          'ISBN',
          'Book Name',
          'Author',
          'Download Certificates',
        ];
        return [
          '#type' => 'table',
          '#header' => $search_header,
          '#rows' => $search_rows,
          '#cache' => [
            'tags' => ['textbook_companion:proposal_list', 'textbook_companion:preference_list'],
            'contexts' => ['user'],
            'max-age' => Cache::PERMANENT,
          ],
        ];
      }
      return [
        '#markup' => $this->t('Error'),
      ];
    }
    else {
      $proposal_link = $this->buildLinkString($this->t('Book Proposal'), 'textbook-companion/proposal');
      $this->messenger()->addStatus($this->t('You need to propose a book @proposal', [
        '@proposal' => $proposal_link,
      ]));
      return [
        '#markup' => '',
      ];
    }
  }

  public function verify_certificates($qr_code = '') {
    if ($qr_code) {
      return [
        '#markup' => CertificateHelper::verifyQrCode($qr_code),
      ];
    }
    return $this->formBuilder()->getForm(VerifyCertificatesForm::class);
  }

  public function textbook_companion_nonaicte_proposal_all() {
    $user = $this->currentUser();
    $uid = (int) $user->id();
    $page_content = "";
    if (!$uid) {
      $login_link = $this->buildLinkString($this->t('Login'), 'user', [
        'attributes' => [
          'class' => ['tbc-login-link'],
        ],
      ]);
      $page_content .= '<ul>';
      $page_content .= $this->t('<li>Please @link to create a proposal.</li>', ['@link' => $login_link]);
      $page_content .= '</ul>';
      return [
        '#markup' => Markup::create($page_content),
      ];
    } //!$user->uid
	/* check if user has already submitted a proposal */
    /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE uid = %d ORDER BY id DESC LIMIT 1", $user->uid);*/
    $query = \Drupal::database()->select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('uid', $uid);
    $query->orderBy('id', 'DESC');
    $query->range(0, 1);
    $proposal_q = $query->execute();
    if ($proposal_q) {
      if ($proposal_data = $proposal_q->fetchObject()) {
        switch ($proposal_data->proposal_status) {
          case 0:
            $this->messenger()->addStatus($this->t('We have already received your proposal. We will get back to you soon.'));
            return $this->redirectToPath('');
            break;
          case 1:
            $code_link = $this->buildLinkString((string) $this->t('Code Submission'), 'textbook-companion/code');
            $this->messenger()->addStatus($this->t('Your proposal has been approved. Please go to @link to upload your code', [
              '@link' => $code_link,
            ]));
            return $this->redirectToPath('');
            break;
          case 2:
            $this->messenger()->addError($this->t('Your proposal has been dis-approved. Please create another proposal below.'));
            break;
          case 3:
            $this->messenger()->addStatus($this->t('Congratulations! You have completed your last book proposal. You can create another proposal below.'));
            break;
          case 5:
            $this->messenger()->addStatus($this->t('You have submitted your all codes.'));
            return $this->redirectToPath('');
            break;
          default:
            $this->messenger()->addError($this->t('Invalid proposal state. Please contact site administrator for further information.'));
            return $this->redirectToPath('');
            break;
        } //$proposal_data->proposal_status
      } //$proposal_data = $proposal_q->fetchObject()
    } //$proposal_q
    //variable_del("aicte_".$user->uid);
    $build = [
      '#type' => 'container',
      'intro' => [
        '#markup' => Markup::create($page_content),
      ],
      'form' => $this->formBuilder()->getForm('book_proposal_nonaicte_form'),
    ];
    return $build;
  }

  private function buildLinkString(string $text, string $path, array $options = []): MarkupInterface {
    $uri = str_starts_with($path, 'http') || str_starts_with($path, 'internal:') ? $path : 'internal:/' . ltrim($path, '/');
    $url = Url::fromUri($uri, $options);
    return Link::fromTextAndUrl($text, $url)->toString();
  }

  private function buildLinkRenderable(string $text, string $path, array $options = []): array {
    $uri = str_starts_with($path, 'http') || str_starts_with($path, 'internal:') ? $path : 'internal:/' . ltrim($path, '/');
    $url = Url::fromUri($uri, $options);
    return Link::fromTextAndUrl($text, $url)->toRenderable();
  }

  private function buildLinkCell(string $text, string $path, array $options = []): array {
    return [
      'data' => $this->buildLinkRenderable($text, $path, $options),
    ];
  }

  private function assertWithinBasePath(string $base_path, string $file_path): string {
    $base_real = realpath($base_path);
    $file_real = realpath($file_path);
    if ($base_real === FALSE || $file_real === FALSE) {
      throw new NotFoundHttpException();
    }
    if (!str_starts_with($file_real, rtrim($base_real, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)) {
      throw new NotFoundHttpException();
    }
    return $file_real;
  }

  private function redirectToPath(string $path = ''): RedirectResponse {
    $url = Url::fromUserInput('/' . ltrim($path, '/'));
    return new RedirectResponse($url->toString());
  }

  private function browseList($type) {
    $return_html = '';
    $char_list = [
      'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J',
      'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T',
      'U', 'V', 'W', 'X', 'Y', 'Z',
    ];
    foreach ($char_list as $char_name) {
      $return_html .= $this->buildLinkString($char_name, 'textbook-companion/textbook-search/' . $type . '/' . $char_name);
      if ($char_name != 'Z') {
        $return_html .= ' | ';
      }
    }
    return '<div id="filter-links">' . $return_html . '</div>';
  }

}
