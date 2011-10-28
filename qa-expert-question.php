<?php
	require_once QA_INCLUDE_DIR.'qa-app-format.php';
	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	require_once QA_INCLUDE_DIR.'qa-util-sort.php';
	require_once QA_INCLUDE_DIR.'qa-util-string.php';
	require_once QA_INCLUDE_DIR.'qa-app-captcha.php';
	
	$questionid=$pass_questionid; // picked up from index.php

//	Get information about this question
	
	global $qa_login_userid, $questionid, $question, $parentquestion, $answers, $commentsfollows,
			$relatedcount, $relatedquestions, $question, $categories;
	
	
	$usecaptcha=qa_user_use_captcha('captcha_on_anon_post');

	
	$permiterror=qa_user_permit_error('permit_view_q_page');
	

//	If we're responding to an HTTP POST, include file that handles all posting/editing/etc... logic
//	This is in a separate file because it's a *lot* of logic, and will slow down ordinary page views

	$pageerror=null;
	$formtype=null;
	$formpostid=null;
	$jumptoanchor=null;
	$focusonid=null;
	
	if (qa_is_http_post() || strlen($qa_state)) {
		require QA_INCLUDE_DIR.'qa-page-question-post.php';
		qa_page_q_load_q(); // reload since we may have changed something
	}
	
	$formrequested=isset($formtype);

	if ((!$formrequested) && $question['answerbutton']) {
		$immedoption=qa_opt('show_a_form_immediate');

		if ( ($immedoption=='always') || (($immedoption=='if_no_as') && (!$question['isbyuser']) && (!$question['acount'])) )
			$formtype='a_add'; // show answer form by default
	}
	
	
//	Get information on the users referenced

	$usershtml=qa_userids_handles_html(array_merge(array($question), $answers, $commentsfollows, $relatedquestions), true);
	
	
//	Prepare content for theme
	
	$qa_content=qa_content_prepare(true, array_keys(qa_category_path($categories, $question['categoryid'])));
	
	$qa_content['main_form_tags']='METHOD="POST" ACTION="'.qa_self_html().'"';
	
	if (isset($pageerror))
		$qa_content['error']=$pageerror; // might also show voting error set in qa-index.php
	
	if ($question['hidden'])
		$qa_content['hidden']=true;
	
	qa_sort_by($commentsfollows, 'created');


//	Prepare content for the question...
	
	if ($formtype=='q_edit') { // ...in edit mode
		$qa_content['title']=qa_lang_html('question/edit_q_title');
		$qa_content['form_q_edit']=qa_page_q_edit_q_form();
		$qa_content['q_view']['raw']=$question;

	} else { // ...in view mode
		$htmloptions=qa_post_html_defaults('Q', true);
		$htmloptions['answersview']=false; // answer count is displayed separately so don't show it here
		$htmloptions['avatarsize']=qa_opt('avatar_q_page_q_size');
		
		$qa_content['q_view']=qa_post_html_fields($question, $qa_login_userid, $qa_cookieid, $usershtml, null, $htmloptions);
		
		$qa_content['title']=$qa_content['q_view']['title'];
		
		$qa_content['description']=qa_html(qa_shorten_string_line(qa_viewer_text($question['content'], $question['format']), 150));
		
		$qa_content['canonical']=qa_path_html(qa_q_request($question['postid'], $question['title']), null, qa_opt('site_url'));
		
		$categorykeyword=@$categories[$question['categoryid']]['title'];
		
		$qa_content['keywords']=qa_html(implode(',', array_merge(
			(qa_using_categories() && strlen($categorykeyword)) ? array($categorykeyword) : array(),
			qa_tagstring_to_tags($question['tags'])
		))); // as far as I know, META keywords have zero effect on search rankings or listings, but many people have asked for this
		

	//	Buttons for operating on the question
		
		if (!$formrequested) { // don't show if another form is currently being shown on page
			$qa_content['q_view']['form']=array(
				'style' => 'light',
				'buttons' => array(),
			);
			
			if ($question['editbutton'])
				$qa_content['q_view']['form']['buttons']['edit']=array(
					'tags' => 'NAME="doeditq"',
					'label' => qa_lang_html('question/edit_button'),
					'popup' => qa_lang_html('question/edit_q_popup'),
				);
				
			if ($question['flagbutton'])
				$qa_content['q_view']['form']['buttons']['flag']=array(
					'tags' => 'NAME="doflagq"',
					'label' => qa_lang_html($question['flagtohide'] ? 'question/flag_hide_button' : 'question/flag_button'),
					'popup' => qa_lang_html('question/flag_q_popup'),
				);

			if ($question['unflaggable'])
				$qa_content['q_view']['form']['buttons']['unflag']=array(
					'tags' => 'NAME="dounflagq"',
					'label' => qa_lang_html('question/unflag_button'),
					'popup' => qa_lang_html('question/unflag_popup'),
				);
				
			if ($question['clearflaggable'])
				$qa_content['q_view']['form']['buttons']['clearflags']=array(
					'tags' => 'NAME="doclearflagsq"',
					'label' => qa_lang_html('question/clear_flags_button'),
					'popup' => qa_lang_html('question/clear_flags_popup'),
				);

			if ($question['hideable'])
				$qa_content['q_view']['form']['buttons']['hide']=array(
					'tags' => 'NAME="dohideq"',
					'label' => qa_lang_html('question/hide_button'),
					'popup' => qa_lang_html('question/hide_q_popup'),
				);
				
			if ($question['reshowable'])
				$qa_content['q_view']['form']['buttons']['reshow']=array(
					'tags' => 'NAME="doshowq"',
					'label' => qa_lang_html('question/reshow_button'),
				);
				
			if ($question['deleteable'])
				$qa_content['q_view']['form']['buttons']['delete']=array(
					'tags' => 'NAME="dodeleteq"',
					'label' => qa_lang_html('question/delete_button'),
					'popup' => qa_lang_html('question/delete_q_popup'),
				);
				
			if ($question['claimable'])
				$qa_content['q_view']['form']['buttons']['claim']=array(
					'tags' => 'NAME="doclaimq"',
					'label' => qa_lang_html('question/claim_button'),
				);
			
			if ($question['answerbutton'] && ($formtype!='a_add')) // don't show if shown by default
				$qa_content['q_view']['form']['buttons']['answer']=array(
					'tags' => 'NAME="doanswerq"',
					'label' => qa_lang_html('question/answer_button'),
					'popup' => qa_lang_html('question/answer_q_popup'),
				);
			
			if ($question['commentbutton'])
				$qa_content['q_view']['form']['buttons']['comment']=array(
					'tags' => 'NAME="docommentq"',
					'label' => qa_lang_html('question/comment_button'),
					'popup' => qa_lang_html('question/comment_q_popup'),
				);
		}
		

	//	Information about the question of the answer that this question follows on from (or a question directly)
			
		if (isset($parentquestion)) {
			$parentquestion['title']=qa_block_words_replace($parentquestion['title'], qa_get_block_words_preg());

			$qa_content['q_view']['follows']=array(
				'label' => qa_lang_html(($question['parentid']==$parentquestion['postid']) ? 'question/follows_q' : 'question/follows_a'),
				'title' => qa_html($parentquestion['title']),
				'url' => qa_path_html(qa_q_request($parentquestion['postid'], $parentquestion['title']),
					null, null, null, ($question['parentid']==$parentquestion['postid']) ? null : qa_anchor('A', $question['parentid'])),
			);
		}
			
	}
	

//	Prepare content for an answer being edited (if any)

	if ($formtype=='a_edit')
		$qa_content['q_view']['a_form']=qa_page_q_edit_a_form($formpostid);


//	Prepare content for comments on the question, plus add or edit comment forms

	$qa_content['q_view']['c_list']=qa_page_q_comment_follow_list($question); // ...for viewing
	
	if (($formtype=='c_add') && ($formpostid==$questionid)) // ...to be added
		$qa_content['q_view']['c_form']=qa_page_q_add_c_form(null);
	
	elseif (($formtype=='c_edit') && (@$commentsfollows[$formpostid]['parentid']==$questionid)) // ...being edited
		$qa_content['q_view']['c_form']=qa_page_q_edit_c_form($formpostid, null);
	

//	Prepare content for existing answers

	$qa_content['a_list']['as']=array();
	
	if (qa_opt('sort_answers_by')=='votes') {
		foreach ($answers as $answerid => $answer)
			$answers[$answerid]['sortvotes']=$answer['downvotes']-$answer['upvotes'];

		qa_sort_by($answers, 'sortvotes', 'created');

	} else
		qa_sort_by($answers, 'created');

	$priority=0;

	foreach ($answers as $answerid => $answer)
		if ($answer['viewable'] && !(($formtype=='a_edit') && ($formpostid==$answerid))) {
			$htmloptions=qa_post_html_defaults('A', true);
			$htmloptions['isselected']=$answer['isselected'];
			$htmloptions['avatarsize']=qa_opt('avatar_q_page_a_size');
			$a_view=qa_post_html_fields($answer, $qa_login_userid, $qa_cookieid, $usershtml, null, $htmloptions);
			

		//	Selection/unselect buttons and others for operating on the answer

			if (!$formrequested) { // don't show if another form is currently being shown on page
				if ($question['aselectable'] && !$answer['hidden']) {
					if ($answer['isselected'])
						$a_view['unselect_tags']='TITLE="'.qa_lang_html('question/unselect_popup').'" NAME="select_"';
					elseif (!isset($question['selchildid']))
						$a_view['select_tags']='TITLE="'.qa_lang_html('question/select_popup').'" NAME="select_'.qa_html($answerid).'"';
				}
				
				$a_view['form']=array(
					'style' => 'light',
					'buttons' => array(),
				);
				
				if ($answer['editbutton'])
					$a_view['form']['buttons']['edit']=array(
						'tags' => 'NAME="doedita_'.qa_html($answerid).'"',
						'label' => qa_lang_html('question/edit_button'),
						'popup' => qa_lang_html('question/edit_a_popup'),
					);
					
				if ($answer['flagbutton'])
					$a_view['form']['buttons']['flag']=array(
						'tags' => 'NAME="doflaga_'.qa_html($answerid).'"',
						'label' => qa_lang_html($answer['flagtohide'] ? 'question/flag_hide_button' : 'question/flag_button'),
						'popup' => qa_lang_html('question/flag_a_popup'),
					);

				if ($answer['unflaggable'])
					$a_view['form']['buttons']['unflag']=array(
						'tags' => 'NAME="dounflaga_'.qa_html($answerid).'"',
						'label' => qa_lang_html('question/unflag_button'),
						'popup' => qa_lang_html('question/unflag_popup'),
					);
					
				if ($answer['clearflaggable'])
					$a_view['form']['buttons']['clearflags']=array(
						'tags' => 'NAME="doclearflagsa_'.qa_html($answerid).'"',
						'label' => qa_lang_html('question/clear_flags_button'),
						'popup' => qa_lang_html('question/clear_flags_popup'),
					);
	
				if ($answer['hideable'])
					$a_view['form']['buttons']['hide']=array(
						'tags' => 'NAME="dohidea_'.qa_html($answerid).'"',
						'label' => qa_lang_html('question/hide_button'),
						'popup' => qa_lang_html('question/hide_a_popup'),
					);
					
				if ($answer['reshowable'])
					$a_view['form']['buttons']['reshow']=array(
						'tags' => 'NAME="doshowa_'.qa_html($answerid).'"',
						'label' => qa_lang_html('question/reshow_button'),
					);
					
				if ($answer['deleteable'])
					$a_view['form']['buttons']['delete']=array(
						'tags' => 'NAME="dodeletea_'.qa_html($answerid).'"',
						'label' => qa_lang_html('question/delete_button'),
						'popup' => qa_lang_html('question/delete_a_popup'),
					);
					
				if ($answer['claimable'])
					$a_view['form']['buttons']['claim']=array(
						'tags' => 'NAME="doclaima_'.qa_html($answerid).'"',
						'label' => qa_lang_html('question/claim_button'),
					);

				if ($answer['followable'])
					$a_view['form']['buttons']['follow']=array(
						'tags' => 'NAME="dofollowa_'.qa_html($answerid).'"',
						'label' => qa_lang_html('question/follow_button'),
						'popup' => qa_lang_html('question/follow_a_popup'),
					);

				if ($answer['commentbutton'])
					$a_view['form']['buttons']['comment']=array(
						'tags' => 'NAME="docommenta_'.qa_html($answerid).'"',
						'label' => qa_lang_html('question/comment_button'),
						'popup' => qa_lang_html('question/comment_a_popup'),
					);

			}
			

		//	Prepare content for comments on this answer, plus add or edit comment forms
			
			$a_view['c_list']=qa_page_q_comment_follow_list($answer); // ...for viewing

			if (($formtype=='c_add') && ($formpostid==$answerid)) // ...to be added
				$a_view['c_form']=qa_page_q_add_c_form($answerid);

			else if (($formtype=='c_edit') && (@$commentsfollows[$formpostid]['parentid']==$answerid)) // ...being edited
				$a_view['c_form']=qa_page_q_edit_c_form($formpostid, $answerid);


		//	Determine this answer's place in the order on the page

			if ($answer['hidden'])
				$a_view['priority']=10000+($priority++);
			elseif ($answer['isselected'] && qa_opt('show_selected_first'))
				$a_view['priority']=0;
			else
				$a_view['priority']=5000+($priority++);
				

		//	Add the answer to the list
				
			$qa_content['a_list']['as'][]=$a_view;
		}
		
	qa_sort_by($qa_content['a_list']['as'], 'priority');
	
	$countanswers=$question['acount'];
	
	if ($countanswers==1)
		$qa_content['a_list']['title']=qa_lang_html('question/1_answer_title');
	else
		$qa_content['a_list']['title']=qa_lang_html_sub('question/x_answers_title', $countanswers);


//	Prepare content for form to add an answer

	if ($formtype=='a_add') { // Form for adding answers
		$answerform=null;
		
		switch (qa_user_permit_error('permit_post_a')) {
			case 'login':
				$answerform=array(
					'style' => 'tall',
					'title' => qa_insert_login_links(qa_lang_html('question/answer_must_login'), $qa_request)
				);
				break;
				
			case 'confirm':
				$answerform=array(
					'style' => 'tall',
					'title' => qa_insert_login_links(qa_lang_html('question/answer_must_confirm'), $qa_request)
				);
				break;
			
			case false:
				$editorname=isset($ineditor) ? $ineditor : qa_opt('editor_for_as');
				$editor=qa_load_editor(@$incontent, @$informat, $editorname);

				$answerform=array(
					'title' => qa_lang_html('question/your_answer_title'),
					
					'style' => 'tall',
					
					'fields' => array(
						'content' => array_merge(
							$editor->get_field($qa_content, @$incontent, @$informat, 'content', 12, $formrequested),
							array(
								'error' => qa_html(@$errors['content']),
							)
						),
					),
					
					'buttons' => array(
						'answer' => array(
							'tags' => 'NAME="doansweradd"',
							'label' => qa_lang_html('question/add_answer_button'),
						),
					),
					
					'hidden' => array(
						'editor' => qa_html($editorname),
					),
				);
				
				if ($formrequested) { // only show cancel button if user explicitly requested the form
					$answerform['buttons']['cancel']=array(
						'tags' => 'NAME="docancel"',
						'label' => qa_lang_html('main/cancel_button'),
					);
				}
				
				qa_set_up_notify_fields($qa_content, $answerform['fields'], 'A', qa_get_logged_in_email(),
					isset($innotify) ? $innotify : qa_opt('notify_users_default'), @$inemail, @$errors['email']);
					
				if ($usecaptcha)
					qa_set_up_captcha_field($qa_content, $answerform['fields'], @$errors,
						qa_insert_login_links(qa_lang_html(isset($qa_login_userid) ? 'misc/captcha_confirm_fix' : 'misc/captcha_login_fix')));
				break;
		}
		
		if ($formrequested || empty($qa_content['a_list']['as']))
			$qa_content['q_view']['a_form']=$answerform; // show directly under question
		else {
			$answerkeys=array_keys($qa_content['a_list']['as']);
			$qa_content['a_list']['as'][$answerkeys[count($answerkeys)-1]]['c_form']=$answerform; // under last answer
		}
	}


//	List of related questions
	
	if (($relatedcount>1) && !$question['hidden']) {
		$minscore=qa_match_to_min_score(qa_opt('match_related_qs'));
		
		foreach ($relatedquestions as $key => $related)
			if ( ($related['postid']==$questionid) || ($related['score']<$minscore) ) // related questions will include itself so remove that
				unset($relatedquestions[$key]);
		
		if (count($relatedquestions))
			$qa_content['q_list']['title']=qa_lang('main/related_qs_title');
		else
			$qa_content['q_list']['title']=qa_lang('main/no_related_qs_title');
			
		$qa_content['q_list']['qs']=array();
		foreach ($relatedquestions as $related)
			$qa_content['q_list']['qs'][]=qa_post_html_fields($related, $qa_login_userid, $qa_cookieid, $usershtml, null, qa_post_html_defaults('Q'));
	}
	

//	Some generally useful stuff
	
	if (qa_using_categories() && count($categories))
		$qa_content['navigation']['cat']=qa_category_navigation($categories, $question['categoryid']);

	if (isset($jumptoanchor))
		$qa_content['script_onloads'][]=array(
			"window.location.hash=".qa_js($jumptoanchor).";",
		);
		
	if (isset($focusonid))
		$qa_content['script_onloads'][]=array(
			"document.getElementById(".qa_js($focusonid).").focus();"
		);
		
		
//	Determine whether the page view should be counted
	
	if (
		qa_opt('do_count_q_views') &&
		(!$formrequested) &&
		(!qa_is_http_post()) &&
		qa_is_human_probably() &&
		( (!$question['views']) || ( // if it has more than zero views
			( ($question['lastviewip']!=qa_remote_ip_address()) || (!isset($question['lastviewip'])) ) && // then it must be different IP from last view
			( ($question['createip']!=qa_remote_ip_address()) || (!isset($question['createip'])) ) && // and different IP from the creator
			( ($question['userid']!=$qa_login_userid) || (!isset($question['userid'])) ) && // and different user from the creator
			( ($question['cookieid']!=$qa_cookieid) || (!isset($question['cookieid'])) ) // and different cookieid from the creator
		) )
	)
		$qa_content['inc_views_postid']=$questionid;

		
	$this->content = $qa_content;

