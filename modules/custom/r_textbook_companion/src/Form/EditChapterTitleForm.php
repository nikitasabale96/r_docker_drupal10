<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\EditChapterTitleForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

class EditChapterTitleForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'edit_chapter_title_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $chapter_id = NULL) {
    $user = $this->currentUser();
    $uid = $user->id();
    $connection = \Drupal::database();

    $proposal_data = $connection->select('textbook_companion_proposal')
      ->fields('textbook_companion_proposal')
      ->condition('uid', $uid)
      ->orderBy('id', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if (!$proposal_data) {
      $proposal_link = Link::fromTextAndUrl($this->t('proposal'), Url::fromRoute('textbook_companion.proposal_all'))->toString();
      $this->messenger()->addError($this->t('Please submit a @proposal.', ['@proposal' => $proposal_link]));
      $form_state->setRedirect('textbook_companion.list_chapters');
      return [];
    }

    if ($proposal_data->proposal_status != 1 && $proposal_data->proposal_status != 4) {
      $here_link = Link::fromTextAndUrl($this->t('here'), Url::fromRoute('textbook_companion.proposal_all'))->toString();
      switch ($proposal_data->proposal_status) {
        case 0:
          $this->messenger()->addStatus($this->t('We have already received your proposal. We will get back to you soon.'));
          $form_state->setRedirect('textbook_companion.list_chapters');
          return [];
        case 2:
          $this->messenger()->addError($this->t('Your proposal has been dis-approved. Please create another proposal @here.', ['@here' => $here_link]));
          $form_state->setRedirect('textbook_companion.list_chapters');
          return [];
        case 3:
          $this->messenger()->addStatus($this->t('Congratulations! You have completed your last book proposal. You have to create another proposal @here.', ['@here' => $here_link]));
          $form_state->setRedirect('textbook_companion.list_chapters');
          return [];
        default:
          $this->messenger()->addError($this->t('Invalid proposal state. Please contact site administrator for further information.'));
          $form_state->setRedirect('textbook_companion.list_chapters');
          return [];
      }
    }

    $preference_data = $connection->select('textbook_companion_preference')
      ->fields('textbook_companion_preference')
      ->condition('proposal_id', $proposal_data->id)
      ->condition('approval_status', 1)
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if (!$preference_data) {
      $this->messenger()->addError($this->t('Invalid Book Preference status. Please contact site administrator for further information.'));
      $form_state->setRedirect('textbook_companion.list_chapters');
      return [];
    }

    $chapter_id = $chapter_id ?? $this->getRouteMatch()->getParameter('chapter_id');
    $chapter_id = (int) $chapter_id;
    $chapter_data = $connection->select('textbook_companion_chapter')
      ->fields('textbook_companion_chapter')
      ->condition('id', $chapter_id)
      ->condition('preference_id', $preference_data->id)
      ->execute()
      ->fetchObject();

    if (!$chapter_data) {
      $this->messenger()->addError($this->t('Invalid chapter.'));
      $form_state->setRedirect('textbook_companion.list_chapters');
      return [];
    }

    $form['book_details']['book'] = [
      '#type' => 'item',
      '#markup' => $preference_data->book,
      '#title' => $this->t('Title of the Book'),
    ];
    $form['contributor_name'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->full_name,
      '#title' => $this->t('Contributor Name'),
    ];
    $form['number'] = [
      '#type' => 'item',
      '#title' => $this->t('Chapter No'),
      '#markup' => $chapter_data->number,
    ];
    $form['chapter_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title of the Chapter'),
      '#size' => 40,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $chapter_data->name,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    $form['cancel'] = [
      '#type' => 'markup',
      '#value' => Link::fromTextAndUrl($this->t('Cancel'), Url::fromRoute('textbook_companion.list_chapters'))->toString(),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $chapter_title = (string) $form_state->getValue('chapter_title');
    if ($chapter_title === '' || !preg_match('/^[A-Za-z0-9 ]+$/', $chapter_title)) {
      $form_state->setErrorByName('chapter_title', $this->t('Title of the Chapter can contain only alphabets, numbers and spaces.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = $this->currentUser();
    $uid = $user->id();
    $connection = \Drupal::database();

    $proposal_data = $connection->select('textbook_companion_proposal')
      ->fields('textbook_companion_proposal')
      ->condition('uid', $uid)
      ->orderBy('id', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if (!$proposal_data) {
      $proposal_link = Link::fromTextAndUrl($this->t('proposal'), Url::fromRoute('textbook_companion.proposal_all'))->toString();
      $this->messenger()->addError($this->t('Please submit a @proposal.', ['@proposal' => $proposal_link]));
      $form_state->setRedirect('textbook_companion.list_chapters');
      return;
    }

    if ($proposal_data->proposal_status != 1 && $proposal_data->proposal_status != 4) {
      $here_link = Link::fromTextAndUrl($this->t('here'), Url::fromRoute('textbook_companion.proposal_all'))->toString();
      switch ($proposal_data->proposal_status) {
        case 0:
          $this->messenger()->addStatus($this->t('We have already received your proposal. We will get back to you soon.'));
          $form_state->setRedirect('textbook_companion.list_chapters');
          return;
        case 2:
          $this->messenger()->addError($this->t('Your proposal has been dis-approved. Please create another proposal @here.', ['@here' => $here_link]));
          $form_state->setRedirect('textbook_companion.list_chapters');
          return;
        case 3:
          $this->messenger()->addStatus($this->t('Congratulations! You have completed your last book proposal. You have to create another proposal @here.', ['@here' => $here_link]));
          $form_state->setRedirect('textbook_companion.list_chapters');
          return;
        default:
          $this->messenger()->addError($this->t('Invalid proposal state. Please contact site administrator for further information.'));
          $form_state->setRedirect('textbook_companion.list_chapters');
          return;
      }
    }

    $preference_data = $connection->select('textbook_companion_preference')
      ->fields('textbook_companion_preference')
      ->condition('proposal_id', $proposal_data->id)
      ->condition('approval_status', 1)
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if (!$preference_data) {
      $this->messenger()->addError($this->t('Invalid Book Preference status. Please contact site administrator for further information.'));
      $form_state->setRedirect('textbook_companion.list_chapters');
      return;
    }

    $chapter_id = (int) ($this->getRouteMatch()->getParameter('chapter_id') ?? 0);
    $chapter_data = $connection->select('textbook_companion_chapter')
      ->fields('textbook_companion_chapter')
      ->condition('id', $chapter_id)
      ->condition('preference_id', $preference_data->id)
      ->execute()
      ->fetchObject();

    if (!$chapter_data) {
      $this->messenger()->addError($this->t('Invalid chapter.'));
      $form_state->setRedirect('textbook_companion.list_chapters');
      return;
    }

    $connection->update('textbook_companion_chapter')
      ->fields(['name' => $form_state->getValue('chapter_title')])
      ->condition('id', $chapter_id)
      ->execute();

    $this->messenger()->addStatus($this->t('Title of the Chapter updated.'));
    $form_state->setRedirect('textbook_companion.list_chapters');
  }

}
