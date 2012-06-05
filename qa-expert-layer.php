<?php

	class qa_html_theme_layer extends qa_html_theme_base {
		
		var $expert_user = null;
		
		function doctype(){
			//qa_error_log($this->content);
			if($this->request == 'admin/permissions' && function_exists('qa_register_plugin_phrases') && qa_get_logged_in_level()>=QA_USER_LEVEL_ADMIN) {
				$permits[] = 'expert_question_ask';
				$permits[] = 'expert_question_roles';
				foreach($permits as $optionname) {
					$value = qa_opt($optionname);
					$optionfield=array(
						'id' => $optionname,
						'label' => qa_lang_html('expert_question/'.$optionname).':',
						'tags' => 'NAME="option_'.$optionname.'" ID="option_'.$optionname.'"',
						'error' => qa_html(@$errors[$optionname]),
					);					
					$widest=QA_PERMIT_USERS;
					$narrowest=QA_PERMIT_ADMINS;
					
					$permitoptions=qa_admin_permit_options($widest, $narrowest, (!QA_FINAL_EXTERNAL_USERS) && qa_opt('confirm_user_emails'));
					
					if (count($permitoptions)>1)
						qa_optionfield_make_select($optionfield, $permitoptions, $value,
							($value==QA_PERMIT_CONFIRMED) ? QA_PERMIT_USERS : min(array_keys($permitoptions)));
					$this->content['form']['fields'][$optionname]=$optionfield;

					$this->content['form']['fields'][$optionname.'_points']= array(
						'id' => $optionname.'_points',
						'tags' => 'NAME="option_'.$optionname.'_points" ID="option_'.$optionname.'_points"',
						'type'=>'number',
						'value'=>qa_opt($optionname.'_points'),
						'prefix'=>qa_lang_html('admin/users_must_have').'&nbsp;',
						'note'=>qa_lang_html('admin/points')
					);
					$checkboxtodisplay[$optionname.'_points']='(option_'.$optionname.'=='.qa_js(QA_PERMIT_POINTS).') ||(option_'.$optionname.'=='.qa_js(QA_PERMIT_POINTS_CONFIRMED).')';
				}
				qa_set_display_rules($this->content, $checkboxtodisplay);
			}
			
			$this->expert_user = $this->is_expert_user();
			if(!$this->expert_user) {
				foreach($this->content['navigation']['main'] as $key => $nav) {
					if($nav['url'] == qa_path_html(qa_opt('expert_question_page_url'))) unset($this->content['navigation']['main'][$key]);
				}
			}

			
			if(qa_clicked('do_expert_answeradd') && ($this->expert_user || $this->content['q_view']['raw']['userid'] === qa_get_logged_in_userid())) {
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
			
			if (qa_opt('expert_question_enable')) {

				if($this->expert_user && qa_opt('expert_question_show_count')) {
					$this->expertcount = qa_db_read_one_value(
						qa_db_query_sub(
							"SELECT COUNT(postid) FROM ^postmeta, ^posts WHERE ^postmeta.meta_key='is_expert_question' AND ^postmeta.post_id=^posts.postid AND ^posts.acount=0".(is_array($this->expert_user)?" AND ^posts.categoryid IN (#)":" AND $"),
							$this->expert_user
						), true
					);
					if($this->expertcount) {
						foreach($this->content['navigation']['main'] as $key => $nav) {
							if($nav['url'] == qa_path_html(qa_opt('expert_question_page_url'))) $this->content['navigation']['main'][$key]['label'] .= ' ('.$this->expertcount.')';
						}
					}					
				}
				
				global $qa_request;
				if($qa_request == qa_opt('expert_question_page_url')) {
					$this->content['navigation']['sub'] = array('special'=>1);
				}
				if($this->template == 'ask' && in_array(qa_opt('expert_question_type'),array(0,2)) &&  !qa_user_permit_error('permit_post_q') && !qa_permit_value_error(qa_opt('expert_question_ask'), qa_get_logged_in_userid(), qa_get_logged_in_level(), qa_get_logged_in_flags()) && !qa_opt('site_maintenance')) {
					$this->content['form']['fields'][] = array(
						'tags' => 'NAME="is_expert_question" ID="is_expert_question"',
						'value' => qa_get(qa_opt('expert_question_page_url'))=='true'?qa_opt('expert_question_yes'):qa_opt('expert_question_no'),
						'type' => 'select-radio',
						'options' => array('no'=>qa_opt('expert_question_no'),'yes'=>qa_opt('expert_question_yes'))
					);
				}

				if($this->template == 'user' && qa_get_logged_in_handle() === $this->_user_handle()) {
					if(!isset($this->content['navigation']['sub'])) {
						$this->content['navigation']['sub'] = array(
							'profile' => array(
								'url' => qa_path_html('user/'.$this->_user_handle(), null, qa_opt('site_url')),
								'label' => $this->_user_handle(),
								'selected' => !qa_get('tab')?true:false
							),
							qa_opt('expert_question_page_url') => array(
								'url' => qa_path_html('user/'.$this->_user_handle(), array('tab'=>qa_opt('expert_question_page_url')), qa_opt('site_url')),
								'label' => qa_opt('expert_question_page_title'),
								'selected' => qa_get('tab')==qa_opt('expert_question_page_url')?true:false
							),
						);
					}
					else {
						$this->content['navigation']['sub'][qa_opt('expert_question_page_url')] = array(
							'url' => qa_path_html('user/'.$this->_user_handle(), array('tab'=>qa_opt('expert_question_page_url')), qa_opt('site_url')),
							'label' => qa_opt('expert_question_page_title'),
							'selected' => qa_get('tab')==qa_opt('expert_question_page_url')?true:false
						);
					}

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

						$this->content['q_view']['tags'] .= ' class="qa-expert-question"';
						
						// remove hidden stuff
						
						unset($this->content['q_view']['hidden']);
						unset($this->content['hidden']);

					}
				}
			}
			qa_html_theme_base::doctype();
		}

		function head_custom() {
			qa_html_theme_base::head_custom();
			$this->output('<style>
'.qa_opt('expert_question_css').'
</style>');
		}
		

		function main_parts($content) {
		
			if($this->template == 'user' && qa_get_logged_in_handle() === $this->_user_handle()) {

				if(qa_get('tab')==qa_opt('expert_question_page_url')) {
					$our_form = $this->expert_question_form();
					$content = array();
					if($our_form) {
						$content['form-expert-list'] = $our_form;  
					}
				}
			 
			}
			qa_html_theme_base::main_parts($content);
		
		}
		
		function voting($post)
		{
			if (@$this->expert_question && qa_opt('expert_question_disable_voting')) {
				return;
			}
			qa_html_theme_base::voting($post);
		}
				
		function nav_list($navigation, $class, $level=null)
		{
			if($class == 'nav-sub' && in_array($this->template, array('plugin','questions')) && qa_opt('expert_question_enable') && $this->is_expert_user()) {
				$navigation[qa_opt('expert_question_page_url')] = array(
					  'label' => qa_opt('expert_question_page_title'),
					  'url' => qa_path_html(qa_opt('expert_question_page_url')),
				);
				if($this->request == qa_opt('expert_question_page_url')) {
					unset($navigation['special']);
					$newnav = qa_qs_sub_navigation(null,null);
					$navigation = array_merge($newnav, $navigation);
					unset($navigation['recent']['selected']);
					$navigation[qa_opt('expert_question_page_url')]['selected'] = true;
				}
				if(@$this->expertcount) {
					$navigation[qa_opt('expert_question_page_url')]['label'] .= ' ('.$this->expertcount.')';
				}		

			}
			if(count($navigation) > 1 || $class != 'nav-sub') qa_html_theme_base::nav_list($navigation, $class, $level=null);
		}

	// worker functions
		
		function is_expert_user() {
			$handle = qa_get_logged_in_handle();
			if(!$handle)
				return false;

			if(!qa_permit_value_error(qa_opt('expert_question_roles'), qa_get_logged_in_userid(), qa_get_logged_in_level(), qa_get_logged_in_flags()))
				return true;
			
			$users = qa_opt('expert_question_users');
			$users = explode("\n",$users);
			foreach($users as $idx => $user) {
				if ($user == $handle) 
					return true;
				if(strpos($user,'=')) {
					$user = explode('=',$user);
					if($user[0] == $handle) {
						$catnames = explode(',',$user[1]);
						$cats = qa_db_read_all_values(
							qa_db_query_sub(
								'SELECT categoryid FROM ^categories WHERE title IN ($)',
								$catnames
							)
						);
						return $cats;
					}
				}
			}
			return false;
		}

		function expert_question_form() {
			// displays expert_question_form form in user profile
			
			global $qa_request;
			
			$handle = $this->_user_handle();

			$uid = $this->getuserfromhandle($handle);
			
			if(!$uid) return;

			$questions = $this->get_expert_question_for_user($uid);
			if(empty($questions)) return;
			
			$output = '<div class="expert_question_container">';
			$qs = qa_db_read_all_assoc(
				qa_db_query_sub(
					"SELECT title,postid,acount FROM ^posts WHERE postid in (".implode(',',$questions).")"
				)
			);
			
			foreach ( $qs as $question) {
				
				$title=$question['title'];
				
				$length = 60;
				
				$text = (strlen($title) > $length ? substr($title,0,$length).'...' : $title);
				
				$acount =($question['acount']==1) ? qa_lang_html('main/1_answer') : qa_lang_html_sub('main/x_answers', $question['acount']);
				
				$output .= '<div class="expert_question-row" id="expert_question-row-'.$idx.'"><a href="'.qa_path_html(qa_q_request($question['postid'],$title),NULL,qa_opt('site_url')).'">'.qa_html($text).'</a> ('.$acount.')</div>';
			}
			$output.='</div>';
			
			$fields[] = array(
				'type' => 'static',
				'label' => $output,
			);


			$form=array(
				'style' => 'tall',
				
				'tags' => 'id="expert_question_form"',
				
				'title' => '<a id="expert_question_title">'.qa_opt('expert_question_page_title').'</a>',

				'fields' => $fields,
			);
			return $form;
		}
		
		function get_expert_question_for_user($uid) {
			qa_db_query_sub(
				'CREATE TABLE IF NOT EXISTS ^postmeta (
				meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				post_id bigint(20) unsigned NOT NULL,
				meta_key varchar(255) DEFAULT \'\',
				meta_value longtext,
				PRIMARY KEY (meta_id),
				KEY post_id (post_id),
				KEY meta_key (meta_key)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8'
			);		
			$questions = qa_db_read_all_values(
				qa_db_query_sub(
					"SELECT ^posts.postid FROM ^postmeta, ^posts WHERE ^postmeta.meta_key='is_expert_question' AND ^postmeta.post_id=^posts.postid AND ^posts.userid=#",
					$uid
				),true
			);
			return $questions;
		}
		
		function getuserfromhandle($handle) {
			require_once QA_INCLUDE_DIR.'qa-app-users.php';
			
			if (QA_FINAL_EXTERNAL_USERS) {
				$publictouserid=qa_get_userids_from_public(array($handle));
				$userid=@$publictouserid[$handle];
				
			} 
			else {
				$userid = qa_db_read_one_value(
					qa_db_query_sub(
						'SELECT userid FROM ^users WHERE handle = $',
						$handle
					),
					true
				);
			}
			if (!isset($userid)) return;
			return $userid;
		}
		// grab the handle of the profile you're looking at
		function _user_handle()
		{
			preg_match( '#user/([^/]+)#', $this->request, $matches );
			return !empty($matches[1]) ? $matches[1] : null;
		}
	}

