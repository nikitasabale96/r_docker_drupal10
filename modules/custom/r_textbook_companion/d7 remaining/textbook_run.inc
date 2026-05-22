<?php
function textbook_companion_book_run_form($form, &$form_state)
{
	$url_category_id = (int) arg(3);
	$url_subcategory_id = (int) arg(2);
	$url_book_pref_id = (int) arg(1);
	if ($url_book_pref_id != NULL && $url_subcategory_id != NULL && $url_category_id != NULL)
	{
		$category_default_value = $url_category_id;
		$subcategory_default_value = $url_subcategory_id;
	} //$url_book_pref_id
	else
	{
		$category_default_value = 0;
		$subcategory_default_value = 0;
	}
	if ($url_book_pref_id && $url_subcategory_id && $url_category_id)
	{
		$form['category'] = array(
			'#type' => 'select',
			'#title' => t('Category'),
			'#options' => _list_of_category(),
			'#default_value' => $category_default_value,
			'#ajax' => array(
				'callback' => 'ajax_subcategory_list_callback'
			),
			'#validated' => TRUE
		);
		$form['subcategory'] = array(
			'#type' => 'select',
			'#title' => t('Sub Category'),
			'#options' => _list_of_subcategory($category_default_value),
			'#default_value' => $subcategory_default_value,
			'#ajax' => array(
				'callback' => 'ajax_book_list_callback'
			),
			'#prefix' => '<div id="ajax-subcategory-list-replace">',
			'#suffix' => '</div>',
			'#validated' => TRUE,
			'#states' => array(
				'invisible' => array(
					':input[name="category"]' => array(
						'value' => 0
					)
				)
			)
		);
		$book_default_value = $url_book_pref_id;
		$form['book'] = array(
			'#type' => 'select',
			'#title' => t('Title of the book'),
			'#options' => _list_of_books($category_default_value, $subcategory_default_value),
			'#default_value' => $book_default_value,
			'#prefix' => '<div id="ajax-book-list-replace">',
			'#suffix' => '</div>',
			'#ajax' => array(
				'callback' => 'ajax_chapter_list_callback'
			),
			'#validated' => TRUE,
			'#states' => array(
				'invisible' => array(
					':input[name="subcategory"]' => array(
						'value' => 0
					)
				)
			)
		);
		/*$form['book_details'] = array(
		'#prefix' => '<div id="ajax-book-details-replace"></div>',
		'#suffix' => '</div>',
		'#markup' => '',
		
		);*/
		$form['book_details'] = array(
			'#type' => 'item',
			'#markup' => '<div id="ajax-book-details-replace">' . _html_book_info($book_default_value) . '</div>'
		);
		// @FIXME
// l() expects a Url object, created from a route name or external URI.
// $form['download_book'] = array(
// 			'#type' => 'item',
// 			'#markup' => '<div id="ajax-download-book-replace">' . l('Download', 'textbook-companion/download/book/' . $book_default_value) . ' ' . t('(Download the R codes for all the solved examples)') . '</div>'
// 		);

		// @FIXME
// l() expects a Url object, created from a route name or external URI.
// $form['download_pdf'] = array(
// 			'#type' => 'item',
// 			'#markup' => '<div id="ajax-download-book-pdf-replace">' . l('Download PDF', 'textbook-companion/generate-book/' . $book_default_value) . ' ' . t('(Download the PDF file containing R codes for all the solved examples)') . '<br><span style="color:red;">The generated PDF is not the PDF of the book as named but only is the PDF of the solved example for R</span></div>'
// 		);

		$form['chapter'] = array(
			'#type' => 'select',
			'#title' => t('Title of the chapter'),
			'#options' => _list_of_chapters($book_default_value),
			//'#default_value' => isset($form_state['values']['chapter']) ? $form_state['values']['chapter'] : '',
			'#prefix' => '<div id="ajax-chapter-list-replace">',
			'#suffix' => '</div>',
			'#ajax' => array(
				'callback' => 'ajax_example_list_callback'
			),
			'#validated' => TRUE,
			'#states' => array(
				'invisible' => array(
					':input[name="category"]' => array(
						'value' => 0
					)
				)
			)
		);
		$form['download_chapter'] = array(
			'#type' => 'item',
			'#markup' => '<div id="ajax-download-chapter-replace"></div>'
		);
		$example_default_value = isset($form_state['values']['chapter']) ? $form_state['values']['chapter'] : '';
		$form['examples'] = array(
			'#type' => 'select',
			'#title' => t('Example No. (Caption): '),
			'#options' => _list_of_examples($example_default_value),
			'#default_value' => isset($form_state['values']['examples']) ? $form_state['values']['examples'] : '',
			'#prefix' => '<div id="ajax-example-list-replace">',
			'#suffix' => '</div>',
			'#ajax' => array(
				'callback' => 'ajax_example_files_callback'
			),
			'#states' => array(
				'invisible' => array(
					':input[name="chapter"]' => array(
						'value' => 0
					)
				)
			)
		);
		$form['download_example_code'] = array(
			'#type' => 'item',
			'#markup' => '<div id="ajax-download-example-code-replace"></div>'
		);
		$form['example_files'] = array(
			'#type' => 'item',
			'#markup' => '<div id="ajax-download-example-files-replace"></div>'
		);
	} //$url_book_pref_id
	else
	{
		$form['category'] = array(
			'#type' => 'select',
			'#title' => t('Category'),
			'#options' => _list_of_category(),
			'#default_value' => $category_default_value,
			'#ajax' => array(
				'callback' => 'ajax_subcategory_list_callback'
			),
			'#validated' => TRUE
		);
		//var_dump(_list_of_subcategory($category_default_value));
		$form['subcategory'] = array(
			'#type' => 'select',
			'#title' => t('Sub Category'),
			'#options' => _list_of_subcategory($category_default_value),
			'#default_value' => $subcategory_default_value,
			'#ajax' => array(
				'callback' => 'ajax_book_list_callback'
			),
			'#prefix' => '<div id="ajax-subcategory-list-replace">',
			'#suffix' => '</div>',
			'#validated' => TRUE,
			'#states' => array(
				'invisible' => array(
					':input[name="category"]' => array(
						'value' => 0
					)
				)
			)
		);
		$form['book'] = array(
			'#type' => 'select',
			'#title' => t('Title of the book'),
			'#options' => _list_of_books($category_default_value, $subcategory_default_value),
			//'#default_value' => isset($form_state['values']['book']) ? $form_state['values']['book'] : 0,
			'#ajax' => array(
				'callback' => 'ajax_chapter_list_callback'
			),
			'#validated' => TRUE,
			'#prefix' => '<div id="ajax-book-list-replace">',
			'#suffix' => '</div>',
			'#states' => array(
				'invisible' => array(
					':input[name="category"]' => array(
						'value' => 0
					)
				)
			)
		);
		$form['book_details'] = array(
			'#type' => 'item',
			'#markup' => '<div id="ajax-book-details-replace"></div>'
		);
		$form['download_book'] = array(
			'#type' => 'item',
			'#markup' => '<div id="ajax-download-book-replace"></div>'
		);
		$form['download_pdf'] = array(
			'#type' => 'item',
			'#markup' => '<div id="ajax-download-book-pdf-replace"></div>'
		);
		$book_default_value = isset($form_state['values']['book']) ? $form_state['values']['book'] : '';
		$form['chapter'] = array(
			'#type' => 'select',
			'#title' => t('Title of the chapter'),
			'#options' => _list_of_chapters($book_default_value),
			//'#default_value' => isset($form_state['values']['chapter']) ? $form_state['values']['chapter'] : '',
			'#prefix' => '<div id="ajax-chapter-list-replace">',
			'#suffix' => '</div>',
			'#ajax' => array(
				'callback' => 'ajax_example_list_callback'
			),
			'#validated' => TRUE,
			'#states' => array(
				'invisible' => array(
					':input[name="category"]' => array(
						'value' => 0
					)
				)
			)
		);
		$form['download_chapter'] = array(
			'#type' => 'item',
			'#markup' => '<div id="ajax-download-chapter-replace"></div>'
		);
		$example_default_value = isset($form_state['values']['chapter']) ? $form_state['values']['chapter'] : '';
		$form['examples'] = array(
			'#type' => 'select',
			'#title' => t('Example No. (Caption): '),
			'#options' => _list_of_examples($example_default_value),
			'#default_value' => isset($form_state['values']['examples']) ? $form_state['values']['examples'] : '',
			'#prefix' => '<div id="ajax-example-list-replace">',
			'#suffix' => '</div>',
			'#ajax' => array(
				'callback' => 'ajax_example_files_callback'
			),
			'#states' => array(
				'invisible' => array(
					':input[name="category"]' => array(
						'value' => 0
					)
				)
			)
		);
		$form['download_example_code'] = array(
			'#type' => 'item',
			'#markup' => '<div id="ajax-download-example-code-replace"></div>'
		);
		$form['example_files'] = array(
			'#type' => 'item',
			'#markup' => '<div id="ajax-download-example-files-replace"></div>'
		);
	}
	return $form;
}
/********************* Ajax callback ***************************/
function ajax_book_list_callback($form, $form_state)
{
	$category_default_value = $form_state['values']['category'];
	$subcategory_default_value = $form_state['values']['subcategory'];
	if ($category_default_value != 0 && $subcategory_default_value == 0)
	{
		$commands[] = ajax_command_html("#ajax-book-list-replace", '');
		$commands[] = ajax_command_html("#ajax-chapter-list-replace", '');
		$commands[] = ajax_command_html("#ajax-example-list-replace", '');
		$commands[] = ajax_command_html("#ajax-book-details-replace", '');
		$commands[] = ajax_command_html("#ajax-download-book-replace", '');
		$commands[] = ajax_command_html("#ajax-download-book-pdf-replace", '');
		$commands[] = ajax_command_html("#ajax-download-chapter-replace", '');
		$commands[] = ajax_command_html("#ajax-download-example-code-replace", '');
		$commands[] = ajax_command_html("#ajax-download-example-files-replace", '');
	} //$category_default_value > 0
	elseif ($category_default_value != 0 && $subcategory_default_value != 0)
	{
		$form['book']['#options'] = _list_of_books($category_default_value, $subcategory_default_value);
		$commands[] = ajax_command_replace("#ajax-book-list-replace", \Drupal::service("renderer")->render($form['book']));
		$commands[] = ajax_command_html("#ajax-chapter-list-replace", '');
		$commands[] = ajax_command_html("#ajax-example-list-replace", '');
	} //$category_default_value != 0 && $subcategory_default_value != 0
	else
	{
		$commands[] = ajax_command_html("#ajax-book-list-replace", '');
		$commands[] = ajax_command_html("#ajax-subcategory-list-replace", '');
		$commands[] = ajax_command_html("#ajax-chapter-list-replace", '');
		$commands[] = ajax_command_html("#ajax-example-list-replace", '');
		$commands[] = ajax_command_html("#ajax-book-details-replace", '');
		$commands[] = ajax_command_html("#ajax-download-book-replace", '');
		$commands[] = ajax_command_html("#ajax-download-book-pdf-replace", '');
		$commands[] = ajax_command_html("#ajax-download-chapter-replace", '');
		$commands[] = ajax_command_html("#ajax-download-example-code-replace", '');
		$commands[] = ajax_command_html("#ajax-download-example-files-replace", '');
	}
	return array(
		'#type' => 'ajax',
		'#commands' => $commands
	);
}
/*************************************************************************/
/********************* Ajax callback ***************************/
function ajax_subcategory_list_callback($form, $form_state)
{
	$category_default_value = $form_state['values']['category'];
	if ($category_default_value > 0)
	{
		$form['subcategory']['#options'] = _list_of_subcategory($category_default_value);
		$commands[] = ajax_command_replace("#ajax-subcategory-list-replace", \Drupal::service("renderer")->render($form['subcategory']));
		$commands[] = ajax_command_html("#ajax-book-list-replace", '');
		$commands[] = ajax_command_html("#ajax-chapter-list-replace", '');
		$commands[] = ajax_command_html("#ajax-example-list-replace", '');
	} //$category_default_value > 0
	else
	{
		//$form['subcategory']['#options'] = _list_of_subcategory();
		//$commands[] = ajax_command_replace("#ajax-subcategory-list-replace", drupal_render($form['subcategory']));
		$commands[] = ajax_command_html("#ajax-subcategory-list-replace", '');
		$commands[] = ajax_command_html("#ajax-book-list-replace", '');
		$commands[] = ajax_command_html("#ajax-chapter-list-replace", '');
		$commands[] = ajax_command_html("#ajax-example-list-replace", '');
		$commands[] = ajax_command_html("#ajax-book-details-replace", '');
		$commands[] = ajax_command_html("#ajax-download-book-replace", '');
		$commands[] = ajax_command_html("#ajax-download-book-pdf-replace", '');
		$commands[] = ajax_command_html("#ajax-download-chapter-replace", '');
		$commands[] = ajax_command_html("#ajax-download-example-code-replace", '');
		$commands[] = ajax_command_html("#ajax-download-example-files-replace", '');
	}
	return array(
		'#type' => 'ajax',
		'#commands' => $commands
	);
}
function ajax_chapter_list_callback($form, $form_state)
{
	$book_list_default_value = $form_state['values']['book'];
	if ($book_list_default_value > 0)
	{
		$commands[] = ajax_command_html("#ajax-book-details-replace", _html_book_info($book_list_default_value));
		$form['chapter']['#options'] = _list_of_chapters($book_list_default_value);
		// @FIXME
// l() expects a Url object, created from a route name or external URI.
// $commands[] = ajax_command_html("#ajax-download-book-replace", l('Download', 'textbook-companion/download/book/' . $book_list_default_value) . ' ' . t('(Download the R codes for all the solved examples)'));

		// @FIXME
// l() expects a Url object, created from a route name or external URI.
// $commands[] = ajax_command_html("#ajax-download-book-pdf-replace", l('Download PDF', 'textbook-companion/generate-book/' . $book_list_default_value) . ' ' . t('(Download the PDF file containing R codes for all the solved examples)') . t('<br><span style="color:red;">The generated PDF is not the PDF of the book as named but only is the PDF of the solved example for R</span>'));

		$commands[] = ajax_command_replace("#ajax-chapter-list-replace", \Drupal::service("renderer")->render($form['chapter']));
		$commands[] = ajax_command_html("#ajax-example-list-replace", '');
		$commands[] = ajax_command_html("#ajax-download-example-code-replace", '');
		$commands[] = ajax_command_html("#ajax-download-example-files-replace", '');
		$commands[] = ajax_command_html("#ajax-download-chapter-replace", '');
	} //$book_list_default_value > 0
	else
	{
		$commands[] = ajax_command_html("#ajax-book-details-replace", '');
		$form['chapter']['#options'] = _list_of_chapters();
		$commands[] = ajax_command_replace("#ajax-chapter-list-replace", \Drupal::service("renderer")->render($form['chapter']));
		$commands[] = ajax_command_html("#ajax-chapter-list-replace", '');
		$commands[] = ajax_command_html("#ajax-download-book-replace", '');
		$commands[] = ajax_command_html("#ajax-download-chapter-replace", '');
		$commands[] = ajax_command_html("#ajax-download-book-pdf-replace", '');
		$commands[] = ajax_command_html("#ajax-example-list-replace", '');
		$commands[] = ajax_command_html("#ajax-download-example-code-replace", '');
		$commands[] = ajax_command_html("#ajax-download-example-files-replace", '');
	}
	return array(
		'#type' => 'ajax',
		'#commands' => $commands
	);
}
function ajax_example_list_callback($form, $form_state)
{
	$chapter_list_default_value = $form_state['values']['chapter'];
	if ($chapter_list_default_value > 0)
	{
		$form['examples']['#options'] = _list_of_examples($chapter_list_default_value);
		$commands[] = ajax_command_replace("#ajax-example-list-replace", \Drupal::service("renderer")->render($form['examples']));
		// @FIXME
// l() expects a Url object, created from a route name or external URI.
// $commands[] = ajax_command_html("#ajax-download-chapter-replace", l('Download', 'textbook-companion/download/chapter/' . $chapter_list_default_value) . ' ' . t('(Download the R codes for all the solved examples from the Chapter)'));

		$commands[] = ajax_command_html("#ajax-download-example-code-replace", '');
		$commands[] = ajax_command_html("#ajax-download-example-files-replace", '');
	} //$chapter_list_default_value > 0
	else
	{
		$form['examples']['#options'] = _list_of_examples();
		$commands[] = ajax_command_replace("#ajax-example-list-replace", \Drupal::service("renderer")->render($form['examples']));
		$commands[] = ajax_command_html("#ajax-example-list-replace", '');
		$commands[] = ajax_command_html("#ajax-download-chapter-replace", '');
		$commands[] = ajax_command_html("#ajax-download-example-code-replace", '');
		$commands[] = ajax_command_html("#ajax-download-example-files-replace", '');
	}
	return array(
		'#type' => 'ajax',
		'#commands' => $commands
	);
}
/*****************************************************/
function ajax_example_files_callback($form, $form_state)
{
	$example_list_default_value = $form_state['values']['examples'];
	if ($example_list_default_value != 0)
	{
		//  $example_list_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $form_state['values']['run']['example']);
		$query = \Drupal::database()->select('textbook_companion_example_files');
		$query->fields('textbook_companion_example_files');
		$query->condition('example_id', $example_list_default_value);
		$example_list_q = $query->execute();
		if ($example_list_q)
		{
			$example_files_rows = array();
			while ($example_list_data = $example_list_q->fetchObject())
			{
				$example_file_type = '';
				switch ($example_list_data->filetype)
				{
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
				} //$example_list_data->filetype
				// @FIXME
// l() expects a Url object, created from a route name or external URI.
// $example_files_rows[] = array(
// 					l($example_list_data->filename, 'textbook-companion/download/file/' . $example_list_data->id),
// 					$example_file_type
// 				);

			} //$example_list_data = $example_list_q->fetchObject()
			/* creating list of files table */
			$example_files_header = array(
				'Filename',
				'Type'
			);
			// @FIXME
// theme() has been renamed to _theme() and should NEVER be called directly.
// Calling _theme() directly can alter the expected output and potentially
// introduce security issues (see https://www.drupal.org/node/2195739). You
// should use renderable arrays instead.
// 
// 
// @see https://www.drupal.org/node/2195739
// $example_files = theme('table', array(
// 				'header' => $example_files_header,
// 				'rows' => $example_files_rows
// 			));

		} //$example_list_q
		// @FIXME
// l() expects a Url object, created from a route name or external URI.
// $commands[] = ajax_command_html("#ajax-download-example-code-replace", l('Download R code for the example', 'textbook-companion/download/example/' . $example_list_default_value) .' '. '(<font color="red">You have to download all the files one at a time, for successful execution on your computer. Alternately you can also download the chapter.</font>)');

		$commands[] = ajax_command_html("#ajax-download-example-files-replace", $example_files);
	} //$example_list_default_value != 0
	else
	{
		$commands[] = ajax_command_html("#ajax-download-example-code-replace", '');
		$commands[] = ajax_command_html("#ajax-download-example-files-replace", '');
	}
	return array(
		'#type' => 'ajax',
		'#commands' => $commands
	);
}
/*******************************************************************/
function bootstrap_table_format($headers, $rows)
{
	$thead = "";
	$tbody = "";
	foreach ($headers as $header)
	{
		$thead .= "<th>{$header}</th>";
	} //$headers as $header
	foreach ($rows as $row)
	{
		$tbody .= "<tr>";
		foreach ($row as $data)
		{
			$tbody .= "<td>{$data}</td>";
		} //$row as $data
		$tbody .= "</tr>";
	} //$rows as $row
	$table = "
            <table class='table table-bordered table-hover' style='margin-left:-140px'>
                <thead>{$thead}</thead>
                <tbody>{$tbody}</tbody>
            </table>
        ";
	return $table;
}
/***********************************************************************************/
function _list_of_category($category_id = NULL)
{

	$category[0] = "Please select";
	if ($category_id == NULL)
	{
		$query = \Drupal::database()->select('list_of_category');
		$query->fields('list_of_category');
		$query->orderBy('category_id', 'ASC');
		$category_list = $query->execute();
	} //$category_id == NULL
	else
	{
		$query = \Drupal::database()->select('list_of_category');
		$query->fields('list_of_category');
		$query->condition('category_id', $category_id);
		$query->orderBy('id', 'ASC');
		$category_list = $query->execute();
	}
	while ($category_list_data = $category_list->fetchObject())
	{
		$category[$category_list_data->category_id] = $category[$category_list_data->category_id].$category_list_data->maincategory;
	} //$category_list_data = $category_list->fetchObject()
	return $category;
}
function _list_of_subcategory($category_id = NULL)
{
	$subcategory[0] = "Please select";
	if ($category_id == NULL)
	{
		$query = \Drupal::database()->select('list_of_subcategory');
		$query->fields('list_of_subcategory');
		$query->condition('maincategory_id', $category_id);
		$query->orderBy('subcategory_id', 'ASC');
		$subcategory_list = $query->execute();
	} //$category_id == NULL
	else
	{
		$query = \Drupal::database()->select('list_of_subcategory');
		$query->fields('list_of_subcategory');
		$query->condition('maincategory_id', $category_id);
		$query->orderBy('id', 'ASC');
		$subcategory_list = $query->execute();
	}
	while ($subcategory_list_data = $subcategory_list->fetchObject())
	{
		$subcategory[$subcategory_list_data->subcategory_id] = $subcategory_list_data->subcategory;
	} //$category_list_data = $category_list->fetchObject()
	return $subcategory;
}
function _list_of_books($category_default_value, $subcategory_default_value)
{
	$book_titles = array(
		0 => 'Please select ...'
	);
	//var_dump($category_default_value,$subcategory_default_value);
	$book_titles_q = \Drupal::database()->query("SELECT DISTINCT (tcbm.sub_category), los.subcategory, loc.category_id,loc.maincategory,
      pe.book as book, pe.author as author, pe.publisher as publisher, pe.year as year, pe.id as pe_id,
      po.approval_date as approval_date
      FROM textbook_companion_preference pe
      LEFT JOIN textbook_companion_proposal po ON pe.proposal_id = po.id
      LEFT JOIN textbook_companion_book_main_subcategories tcbm ON pe.id = tcbm.pref_id
      LEFT JOIN list_of_category loc ON tcbm.main_category = loc.category_id
      LEFT JOIN list_of_subcategory los ON tcbm.sub_category = los.subcategory_id
      WHERE po.proposal_status = 3 AND pe.approval_status = 1
      AND pe.id = tcbm.pref_id AND tcbm.sub_category= :sub_category AND tcbm.main_category = :main_category", array(
		":sub_category" => $subcategory_default_value,
		"main_category" => $category_default_value
	));
	if ($book_titles_q->rowCount() != 0)
	{
		while ($book_titles_data = $book_titles_q->fetchObject())
		{
			$book_titles[$book_titles_data->pe_id] = $book_titles_data->book . ' (Written by ' . $book_titles_data->author . ')';
		} //$book_titles_data = $book_titles_q->fetchObject()
	} //$book_titles_q->rowCount() != 0
	else
	{
		$book_titles[0] = "There are no books availabe in this sub category";
	}
	return $book_titles;
}
function _list_of_chapters($preference_id = 0)
{
	$book_chapters = array(
		0 => 'Please select...'
	);
	//$book_chapters_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE preference_id = %d ORDER BY number ASC", $preference_id);
	$query = \Drupal::database()->select('textbook_companion_chapter');
	$query->fields('textbook_companion_chapter');
	$query->condition('preference_id', $preference_id);
	$query->orderBy('number', 'ASC');
	$book_chapters_q = $query->execute();
	while ($book_chapters_data = $book_chapters_q->fetchObject())
	{
		$book_chapters[$book_chapters_data->id] = $book_chapters_data->number . '. ' . $book_chapters_data->name;
	} //$book_chapters_data = $book_chapters_q->fetchObject()
	return $book_chapters;
}
function _list_of_examples($chapter_id = 0)
{
	$book_examples = array(
		0 => 'Please select...'
	);
	//$book_examples_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND approval_status = 1 ORDER BY
	// CAST(SUBSTRING_INDEX(number, '.', 1) AS BINARY) ASC,
	// CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(number , '.', 2), '.', -1) AS UNSIGNED) ASC,
	//  CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(number , '.', -1), '.', 1) AS UNSIGNED) ASC", $chapter_id);
	$query = \Drupal::database()->select('textbook_companion_example');
	$query->fields('textbook_companion_example');
	$query->condition('chapter_id', $chapter_id);
	$query->condition('approval_status', 1);
	//$query->orderBy('', '');
	$book_examples_q = $query->execute();
	while ($book_examples_data = $book_examples_q->fetchObject())
	{
		$book_examples[$book_examples_data->id] = $book_examples_data->number . ' (' . $book_examples_data->caption . ')';
	} //$book_examples_data = $book_examples_q->fetchObject()
	return $book_examples;
}
function _book_information($preference_id)
{
	/*$book_data = db_fetch_object(db_query("SELECT
	preference.book as preference_book, preference.author as preference_author, preference.isbn as preference_isbn, preference.publisher as preference_publisher, preference.edition as preference_edition, preference.year as preference_year,
	proposal.full_name as proposal_full_name, proposal.faculty as proposal_faculty, proposal.reviewer as proposal_reviewer, proposal.course as proposal_course, proposal.branch as proposal_branch, proposal.university as proposal_university
	FROM {textbook_companion_proposal} proposal LEFT JOIN {textbook_companion_preference} preference ON proposal.id = preference.proposal_id WHERE preference.id = %d", $preference_id));*/
	$query = \Drupal::database()->select('textbook_companion_proposal', 'proposal');
	$query->fields('preference', array(
		'book',
		'author',
		'isbn',
		'publisher',
		'edition',
		'year'
	));
	$query->fields('proposal', array(
		'full_name',
		'faculty',
		'reviewer',
		'course',
		'branch',
		'university'
	));
	$query->leftJoin('textbook_companion_preference', 'preference', 'proposal.id = preference.proposal_id');
	$query->condition('preference.id', $preference_id);
	$book_data = $query->execute()->fetchObject();
	return $book_data;
}
function _html_book_info($preference_id)
{
	/*$book_details = db_fetch_object(db_query("SELECT
	preference.book as preference_book, preference.author as preference_author, preference.isbn as preference_isbn, preference.publisher as preference_publisher, preference.edition as preference_edition, preference.year as preference_year,
	proposal.full_name as proposal_full_name, proposal.faculty as proposal_faculty, proposal.reviewer as proposal_reviewer, proposal.course as proposal_course, proposal.branch as proposal_branch, proposal.university as proposal_university
	FROM {textbook_companion_proposal} proposal LEFT JOIN {textbook_companion_preference} preference ON proposal.id = preference.proposal_id WHERE preference.id=".$preference_id));*/
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
	$query->fields('proposal', array(
		'full_name',
		'faculty',
		'reviewer',
		'course',
		'branch',
		'university'
	));
	$query->leftJoin('textbook_companion_preference', 'preference', 'proposal.id = preference.proposal_id');
	$query->fields('preference', array(
		'book',
		'author',
		'isbn',
		'publisher',
		'edition',
		'year'
	));
	$query->condition('preference.id', $preference_id);
	$book_details = $query->execute()->fetchObject();
	$html_data = '';
	if ($book_details)
	{
		if ($book_details->proposal_faculty == "None")
		{
			$html_data = '<table cellspacing="1" cellpadding="1" border="0" style="width: 100%;" valign="top">' . '<tr><td style="width: 35%;"><span style="color: rgb(128, 0, 0);"><strong>About the Book</strong></span></td>
	<td style="width: 35%;"><span style="color: rgb(128, 0, 0);"><strong>About the Contributor</strong></span></td>' . '<tr><td valign="top"><ul>' . '<li><strong>Author:</strong> ' . $book_details->preference_author . '</li>' . '<li><strong>Title of the Book:</strong> ' . $book_details->preference_book . '</li>' . '<li><strong>Publisher:</strong> ' . $book_details->preference_publisher . '</li>' . '<li><strong>Year:</strong> ' . $book_details->preference_year . '</li>' . '<li><strong>Edition:</strong> ' . $book_details->preference_edition . '</li>' . '<li><strong>ISBN:</strong> ' . $book_details->preference_isbn . '</li>' . '</ul></td><td valign="top"><ul>' . '<li><strong>Contributor Name: </strong>' . $book_details->proposal_full_name . ', ' . $book_details->proposal_course . ', ' . $book_details->proposal_branch . ', ' . $book_details->proposal_university . '</li>' . '<li><strong>Reviewer: </strong>' . $book_details->proposal_reviewer . '</li>' . '</ul></td></tr>' . '</table>';
		} //$book_details->proposal_faculty == "None"
		else
		{
			$html_data = '<table cellspacing="1" cellpadding="1" border="0" style="width: 100%;" valign="top">' . '<tr><td style="width: 35%;"><span style="color: rgb(128, 0, 0);"><strong>About the Book</strong></span></td>
		<td style="width: 35%;"><span style="color: rgb(128, 0, 0);"><strong>About the Contributor</strong></span></td>' . '<tr><td valign="top"><ul>' . '<li><strong>Author:</strong> ' . $book_details->preference_author . '</li>' . '<li><strong>Title of the Book:</strong> ' . $book_details->preference_book . '</li>' . '<li><strong>Publisher:</strong> ' . $book_details->preference_publisher . '</li>' . '<li><strong>Year:</strong> ' . $book_details->preference_year . '</li>' . '<li><strong>Edition:</strong> ' . $book_details->preference_edition . '</li>' . '<li><strong>ISBN:</strong> ' . $book_details->preference_isbn . '</li>' . '</ul></td><td valign="top"><ul>' . '<li><strong>Contributor Name: </strong>' . $book_details->proposal_full_name . ', ' . $book_details->proposal_course . ', ' . $book_details->proposal_branch . ', ' . $book_details->proposal_university . '</li>' . '<li><strong>College Teacher: </strong>' . $book_details->proposal_faculty . '</li>' . '<li><strong>Reviewer: </strong>' . $book_details->proposal_reviewer . '</li>' . '</ul></td></tr>' . '</table>';
		}
	} //$book_details
	return $html_data;
}
