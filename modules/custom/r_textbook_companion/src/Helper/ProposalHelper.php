<?php

namespace Drupal\textbook_companion\Helper;

use Drupal\Core\Url;

class ProposalHelper {

  public static function createReadmeFileTextbookCompanion($proposal_id) {
    $result = \Drupal::database()->query("
      SELECT tcc.id AS proposal_id, tcc.full_name, tcc.course, tcc.branch, tcc.university,
        tcp.id as pref_id, tcp.directory_name, tcp.*
      FROM textbook_companion_preference tcp
      JOIN textbook_companion_proposal tcc ON tcp.proposal_id = tcc.id
      WHERE tcc.proposal_status = 3 AND tcp.approval_status = 1 AND tcc.id = :proposal_id
      ", [
      ':proposal_id' => $proposal_id,
    ]);
    $proposal_data = $result->fetchObject();
    if (!$proposal_data) {
      return;
    }
    $root_path = textbook_companion_path();
    $readme_path = $root_path . $proposal_data->directory_name . '/README.txt';
    $readme_file = fopen($readme_path, 'w');
    if (!$readme_file) {
      return;
    }
    $txt = '';
    $txt .= "About The Contributor\n\n";
    $txt .= 'Contributed By: ' . ucwords(strtolower($proposal_data->full_name)) . "\n";
    $txt .= 'Course: ' . ucwords(strtolower($proposal_data->course)) . "\n";
    $txt .= 'Branch: ' . ucwords(strtolower($proposal_data->branch)) . "\n";
    $txt .= 'College/Institute/Organization: ' . ucwords(strtolower($proposal_data->university)) . "\n\n";
    $txt .= "About The Book\n\n";
    $txt .= 'Book: ' . ucwords(strtolower($proposal_data->book)) . "\n";
    $txt .= 'Author: ' . ucwords(strtolower($proposal_data->author)) . "\n";
    $txt .= 'Publisher: ' . ucwords(strtolower($proposal_data->publisher)) . "\n";
    $txt .= 'Year Of Publication: ' . $proposal_data->year . "\n";
    $txt .= 'ISBN: ' . $proposal_data->isbn . "\n";
    $txt .= 'Edition: ' . ucwords(strtolower($proposal_data->edition)) . "\n\n\n";
    $txt .= "Textbook Companion Project By FOSSEE, IIT Bombay\n";
    fwrite($readme_file, $txt);
    fclose($readme_file);
  }

  public static function renameDir($preference_id, $dir_name) {
    $query = \Drupal::database()->query("SELECT directory_name, proposal_id, id FROM textbook_companion_preference WHERE id = :preference_id", [
      ':preference_id' => $preference_id,
    ]);
    $result = $query->fetchObject();
    if (!$result) {
      \Drupal::messenger()->addMessage('Book names directory not present in databse');
      return;
    }
    $old_file_dir = textbook_companion_path() . $result->directory_name;
    $new_file_dir = textbook_companion_path() . $dir_name;
    if (is_dir($old_file_dir)) {
      return rename($old_file_dir, $new_file_dir);
    }
    $files_id_dir = textbook_companion_path() . $result->id;
    if (is_dir($files_id_dir)) {
      return rename($files_id_dir, $new_file_dir);
    }
    \Drupal::messenger()->addMessage('Can not rename the directory. If you are editing proposal before approving the proposal directory is not present because the code has been not uploaded yet. For more information please contact to administrator');
    return;
  }

  public static function normalizeExternalUri(?string $uri): ?string {
    $uri = trim((string) $uri);
    if ($uri === '') {
      return NULL;
    }

    if (preg_match('/^[a-z][a-z0-9+.-]*:\/\//i', $uri)) {
      return $uri;
    }

    if (str_starts_with($uri, '//')) {
      return 'https:' . $uri;
    }

    if (preg_match('/^(?:www\.|[A-Za-z0-9.-]+\.[A-Za-z]{2,})(?:[\/:?#].*)?$/', $uri)) {
      return 'https://' . $uri;
    }

    return NULL;
  }

  public static function buildExternalUrl(?string $uri): ?Url {
    $normalized_uri = self::normalizeExternalUri($uri);
    if ($normalized_uri === NULL) {
      return NULL;
    }

    try {
      return Url::fromUri($normalized_uri);
    }
    catch (\InvalidArgumentException $exception) {
      return NULL;
    }
  }

}
