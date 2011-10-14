<?php

	class qa_html_theme_layer extends qa_html_theme_base {
		
		function doctype(){
			//qa_error_log($this->content);
			
			if(qa_clicked('do_expert_answeradd') && $this->is_expert_user()) {
				global $qa_login_userid, $questionid, $question, $answers, $question, $qa_request;
				
				$innotify=qa_post_text('notify') ? true : false;
				$inemail=qa_post_text('email');
				
				qa_get_post_content('editor', 'content', $ineditor, $incontent, $informat, $intext);
				
				$isduplicate=false;
				foreach ($answers as $answer)
					if (!$answer['hidden'])
						if (implode(' ', qa_string_to_words($answer['content'])) == implode(' ', qa_string_to_words($incontent)))
							$isduplicate=true;
				
				if (!$isduplicate) {
					if (!isset($qa_login_userid))
						$qa_cookieid=qa_cookie_get_create(); // create a new cookie if necessary
		
					$answerid=qa_answer_create($qa_login_userid, qa_get_logged_in_handle(), $qa_cookieid,
						$incontent, $informat, $intext, $innotify, $inemail, $question);
					qa_report_write_action($qa_login_userid, $qa_cookieid, 'a_post', $questionid, $answerid, null);
					qa_redirect($qa_request, null, null, null, qa_anchor('A', $answerid));
					
				} else {
					$pageerror=qa_lang_html('question/duplicate_content');
				}

				qa_page_q_load_q(); // reload since we may have changed something
			}
			
			if (qa_opt('expert_questions_enable')) {
				global $qa_request;
				if($qa_request == 'expert') {
					$this->content['navigation']['sub'] = array('special'=>1);
				}
				if($this->template == 'ask' && !qa_user_permit_error('permit_post_q')) {
					$this->content['form']['fields'][] = array(
						'tags' => 'NAME="is_expert_question" ID="is_expert_question"',
						'value' => qa_opt('expert_question_no'),
						'type' => 'select-radio',
						'options' => array('no'=>qa_opt('expert_question_no'),'yes'=>qa_opt('expert_question_yes'))
					);
				}
				if($this->template == 'question') {
					$qid = $this->content['q_view']['raw']['postid'];
					$expert = qa_db_read_one_value(
						qa_db_query_sub(
							"SELECT meta_value FROM ^postmeta WHERE meta_key='is_expert_question' AND post_id=#",
							$qid
						), true
					);

					if($expert) { // is expert question
					
						$this->expert_question = 1;
						
					// modify post elements
					
						// title

						$this->content['title'] .= ' '.qa_opt('expert_question_title');

						// css class
						
						$this->content['main_form_tags'] .= ' class="qa-expert-question"';
						
						// remove hidden stuff
						
						unset($this->content['q_view']['form']['buttons']['reshow']);
						unset($this->content['q_view']['hidden']);
						unset($this->content['hidden']);
						
						// readd buttons
						
						if($this->is_expert_user()) {
							$answerform=null;
							
							$editorname=isset($ineditor) ? $ineditor : qa_opt('editor_for_as');
							$editor=qa_load_editor(@$incontent, @$informat, $editorname);

							$answerform=array(
								'title' => qa_lang_html('question/your_answer_title'),
								
								'style' => 'tall',
								
								'fields' => array(
									'content' => array_merge(
										$editor->get_field($this->content, @$incontent, @$informat, 'content', 12, $formrequested),
										array(
											'error' => qa_html(@$errors['content']),
										)
									),
								),
								
								'buttons' => array(
									'answer' => array(
										'tags' => 'NAME="do_expert_answeradd"',
										'label' => qa_lang_html('question/add_answer_button'),
									),
								),
								
								'hidden' => array(
									'editor' => qa_html($editorname),
								),
							);
							
							qa_set_up_notify_fields($qa_content, $answerform['fields'], 'A', qa_get_logged_in_email(),
								isset($innotify) ? $innotify : qa_opt('notify_users_default'), @$inemail, @$errors['email']);
								
							if ($usecaptcha)
								qa_set_up_captcha_field($qa_content, $answerform['fields'], @$errors,
									qa_insert_login_links(qa_lang_html(isset($qa_login_userid) ? 'misc/captcha_confirm_fix' : 'misc/captcha_login_fix')));
							
							if (empty($this->content['a_list']['as']))
								$this->content['q_view']['a_form']=$answerform; // show directly under question
							else {
								$answerkeys=array_keys($this->content['a_list']['as']);
								$this->content['a_list']['as'][$answerkeys[count($answerkeys)-1]]['c_form']=$answerform; // under last answer
							}

							$this->content['q_view']['form']['buttons']['comment'] = array(
							  'tags' => 'NAME="docommentq"',
							  'label' => qa_lang('question/comment_button'),
							  'popup' =>  qa_lang('question/comment_q_popup'),
							);
						}
						
					}
				}
			}
			qa_html_theme_base::doctype();
		}
		
		function voting($post)
		{
			if (@$this->expert_question && qa_opt('expert_questions_disable_voting')) {
				return;
			}
			qa_html_theme_base::voting($post);
		}
				
		function nav_list($navigation, $class, $level=null)
		{
			if($class == 'nav-sub' && qa_opt('expert_questions_enable') && $this->is_expert_user()) {
				$navigation['expert'] = array(
					  'label' => qa_opt('expert_questions_page_title'),
					  'url' => qa_path_html('expert'),
				);
				if($this->request == 'expert') {
					unset($navigation['special']);
					$newnav = qa_qs_sub_navigation(null);
					$navigation = array_merge($newnav, $navigation);
					unset($navigation['recent']['selected']);
					$navigation['expert']['selected'] = true;
				}
			}
			qa_html_theme_base::nav_list($navigation, $class, $level=null);
		}

	// worker functions
		
		function is_expert_user() {

			if(qa_get_logged_in_level() >= qa_opt('expert_question_roles'))
				return true;
			
			$users = qa_opt('expert_question_users');
			$users = explode('\n',$users);
			$handle = qa_get_logged_in_handle();
			return in_array($handle, $users);
		}
		
	}

