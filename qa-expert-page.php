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
			
		//	Get list of questions, plus category information

			$nonetitle=qa_lang_html('main/no_questions_found');
			
			$categorypathprefix=null; // only show category list and feed when sorting by date
			$feedpathprefix=null;

			$selectspec=array(
				'columns' => array(
					'^posts.postid', '^posts.categoryid', '^posts.type', 'basetype' => 'LEFT(^posts.type,1)', 'hidden' => "INSTR(^posts.type, '_HIDDEN')>0",
					'^posts.acount', '^posts.selchildid', '^posts.upvotes', '^posts.downvotes', '^posts.netvotes', '^posts.views', '^posts.hotness',
					'^posts.flagcount', 'title' => 'BINARY ^posts.title', 'tags' => 'BINARY ^posts.tags', 'created' => 'UNIX_TIMESTAMP(^posts.created)',
					'categoryname' => 'BINARY ^categories.title', 'categorybackpath' => "BINARY ^categories.backpath",
				),
				
				'arraykey' => 'postid',
				'source' => '^posts LEFT JOIN ^categories ON ^categories.categoryid=^posts.categoryid JOIN ^postmeta ON ^posts.postid=^postmeta.post_id AND ^postmeta.meta_key=$ AND ^postmeta.meta_value>0'.(is_array($this->expert_user)?' AND ^posts.categoryid IN (#)':' AND $'),
				'arguments' => array('is_expert_question',$this->expert_user),
			);			
			$selectspec['columns']['content']='BINARY ^posts.content';
			$selectspec['columns']['notify']='BINARY ^posts.notify';
			$selectspec['columns']['updated']='UNIX_TIMESTAMP(^posts.updated)';
			$selectspec['columns'][]='^posts.format';
			$selectspec['columns'][]='^posts.lastuserid';
			$selectspec['columns']['lastip']='INET_NTOA(^posts.lastip)';
			$selectspec['columns'][]='^posts.parentid';
			$selectspec['columns']['lastviewip']='INET_NTOA(^posts.lastviewip)';

			$selectspec['columns'][]='^posts.userid';
			$selectspec['columns'][]='^posts.cookieid';
			$selectspec['columns']['createip']='INET_NTOA(^posts.createip)';
			$selectspec['columns'][]='^userpoints.points';

			if (!QA_FINAL_EXTERNAL_USERS) {
				$selectspec['columns'][]='^users.flags';
				$selectspec['columns'][]='^users.level';
				$selectspec['columns']['email']='BINARY ^users.email';
				$selectspec['columns']['handle']='CONVERT(^users.handle USING BINARY)'; // because of MySQL bug #29205
				$selectspec['columns'][]='^users.avatarblobid';
				$selectspec['columns'][]='^users.avatarwidth';
				$selectspec['columns'][]='^users.avatarheight';
				$selectspec['source'].=' LEFT JOIN ^users ON ^posts.userid=^users.userid';
			}
			
			$selectspec['source'].=' LEFT JOIN ^userpoints ON ^posts.userid=^userpoints.userid';
			
			$selectspec['source'].=' ORDER BY ^posts.created DESC';
			
			$questions = qa_db_select_with_pending($selectspec);
			
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
