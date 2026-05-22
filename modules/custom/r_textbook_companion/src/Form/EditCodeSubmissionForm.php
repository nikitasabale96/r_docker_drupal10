<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\EditCodeSubmissionForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

class EditCodeSubmissionForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'edit_code_submission_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $preference_id = NULL) {
    $preference_id = $preference_id ?? \Drupal::routeMatch()->getParameter('preference_id');

    $preference_data = \Drupal::database()->select('textbook_companion_preference', 'tp')
      ->fields('tp')
      ->condition('id', $preference_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    $cancel_url = Url::fromUserInput('/textbook-companion/code-approval/edit-code-submission');

    if (!$preference_data) {
      $this->messenger()->addError($this->t('Invalid book selected. Please try again.'));
      $form_state->setRedirectUrl($cancel_url);
      return [];
    }

    $form['book'] = [
      '#type' => 'item',
      '#title' => $this->t('Title of the book'),
      '#markup' => $preference_data->book,
    ];
    $form['author'] = [
      '#type' => 'item',
      '#title' => $this->t('Author Name'),
      '#markup' => $preference_data->author,
    ];
    $form['isbn'] = [
      '#type' => 'item',
      '#title' => $this->t('ISBN No'),
      '#markup' => $preference_data->isbn,
    ];
    $form['publisher'] = [
      '#type' => 'item',
      '#title' => $this->t('Publisher & Place'),
      '#markup' => $preference_data->publisher,
    ];
    $form['edition'] = [
      '#type' => 'item',
      '#title' => $this->t('Edition'),
      '#markup' => $preference_data->edition,
    ];
    $form['year'] = [
      '#type' => 'item',
      '#title' => $this->t('Year of pulication'),
      '#markup' => $preference_data->year,
    ];
    $form['all_example_submitted'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable code submission interface for user'),
      '#description' => $this->t('Once you have submited this option user can upload more examples.'),
      '#required' => TRUE,
    ];
    $form['hidden_preference_id'] = [
      '#type' => 'hidden',
      '#value' => $preference_id,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    $form['cancel'] = [
      '#type' => 'item',
      '#markup' => Link::fromTextAndUrl($this->t('Cancel'), $cancel_url)->toString(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ((int) $form_state->getValue('all_example_submitted') !== 1) {
      $form_state->setErrorByName('all_example_submitted', $this->t('Please check the field if you are intrested to submit the all uploaded examples for review!'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ((int) $form_state->getValue('all_example_submitted') !== 1) {
      return;
    }

    $connection = \Drupal::database();
    $preference_id = (int) $form_state->getValue('hidden_preference_id');

    $updated = $connection->update('textbook_companion_preference')
      ->fields(['submited_all_examples_code' => 0])
      ->condition('id', $preference_id)
      ->execute();

    if (!$updated) {
      return;
    }

    $proposal_id = $connection->select('textbook_companion_preference', 'tp')
      ->fields('tp', ['proposal_id'])
      ->condition('id', $preference_id)
      ->range(0, 1)
      ->execute()
      ->fetchField();

    $proposal_data = $proposal_id ? $connection->select('textbook_companion_proposal', 'tpr')
      ->fields('tpr')
      ->condition('proposal_status', 1)
      ->condition('id', $proposal_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject() : NULL;

    if ($proposal_data) {
      $book_user = User::load($proposal_data->uid);
      $email_to = $book_user?->getEmail() ?? '';
      $config = \Drupal::config('textbook_companion.settings');
      $from = (string) $config->get('textbook_companion_from_email');
      $bcc = (string) $config->get('textbook_companion_emails');
      $cc = (string) $config->get('textbook_companion_cc_emails');

      $param['all_code_submitted_status_changed']['proposal_id'] = $proposal_id;
      $param['all_code_submitted_status_changed']['user_id'] = $this->currentUser()->id();
      $param['all_code_submitted_status_changed']['headers'] = [
        'From' => $from,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
        'Content-Transfer-Encoding' => '8Bit',
        'X-Mailer' => 'Drupal',
        'Cc' => $cc,
        'Bcc' => $bcc,
      ];

      if ($email_to !== '') {
        $langcode = $book_user?->getPreferredLangcode() ?? \Drupal::languageManager()->getDefaultLanguage()->getId();
        $result = \Drupal::service('plugin.manager.mail')->mail('textbook_companion', 'all_code_submitted_status_changed', $email_to, $langcode, $param, $from, TRUE);
        if (empty($result['result'])) {
          $this->messenger()->addError($this->t('Error sending email message.'));
        }
      }
    }

    $this->messenger()->addStatus($this->t('Enabled code submission interface for user'));
    $form_state->setRedirectUrl(Url::fromUserInput('/textbook-companion/code-approval/edit-code-submission'));
  }

}
