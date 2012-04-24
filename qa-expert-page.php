<?php

	class qa_expert_question_page {
		
		var $directory;
		var $urltoroot;
		
		function load_module($directory, $urltoroot)
		{
			$this->directory=$directory;
			$this->urltoroot=$urltoroot;
		}
		
		function suggest_requests() // for display in admin interface
		{	
			return array(
				array(
					'title' => qa_opt('expert_question_page_title'),
					'request' => qa_opt('expert_question_page_url'),
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		function match_request($request)
		{
			$this->expert_user = $this->is_expert_user();
			$expert = qa_opt('expert_question_page_url');
			if ($request==$expert && $this->expert_user)
				return true;
			
			if($request==$expert) {
				qa_redirect('ask', array(qa_opt('expert_question_page_url') => 'true'));
			}
			
			return false;
		}
		
		function process_request($request)
		{
			require_once QA_INCLUDE_DIR.'qa-db-selects.php';
			require_once QA_INCLUDE_DIR.'qa-app-format.php';
			require_once QA_INCLUDE_DIR.'qa-app-q-list.php';

			$start=qa_get_start();
			$userid=qa_get_logged_in_userid();			
			$categoryslugs=qa_request_parts(1);
			
			//$selectspec = qa_db_qs_selectspec($userid, 'created', $start, $categoryslugs, null, false, false, qa_opt_if_loaded('page_size_qs'));
			$selectspec=qa_db_posts_basic_selectspec($userid, false);

			$selectspec['source'].=' JOIN ^postmeta ON ^posts.postid=^postmeta.post_id AND ^postmeta.meta_key=$ AND ^postmeta.meta_value>0'.(is_array($this->expert_user)?' AND ^posts.categoryid IN (#)':' AND $');

			//$selectspec['source'].=' JOIN (SELECT postid FROM ^posts WHERE type=$ ORDER BY ^posts.created DESC LIMIT #,#) y ON ^posts.postid=y.postid';

			$selectspec['arguments'] = array_merge($selectspec['arguments'],array('is_expert_question',$this->expert_user));
			

			$questions = qa_db_select_with_pending($selectspec);
			$nonetitle=qa_lang_html('main/no_questions_found');
			global $qa_start;
			
		//	Prepare and return content for theme

			$qa_content=qa_q_list_page_content(
				$questions, // questions
				qa_opt('page_size_qs'), // questions per page
				$qa_start, // start offset
				count($questions), // total count
				qa_opt('expert_question_page_title'), // title if some questions
				$nonetitle, // title if no questions
				null, // categories for navigation
				null, // selected category id
				false, // show question counts in category navigation
				null, // prefix for links in category navigation
				null, // prefix for RSS feed paths
				null, // suggest what to do next
				null // extra parameters for page links
			);
			
			return $qa_content;
		}
		
		function is_expert_user() {

			if(!qa_permit_value_error(qa_opt('expert_question_roles'), qa_get_logged_in_userid(), qa_get_logged_in_level(), qa_get_logged_in_flags()))
				return true;
			
			$users = qa_opt('expert_question_users');
			$users = explode("\n",$users);
			$handle = qa_get_logged_in_handle();
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

	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/
