<?php

	class qa_expert_question_event {
		function process_event($event, $userid, $handle, $cookieid, $params) {
			if (qa_opt('expert_question_enable')) {
				switch ($event) {
					case 'q_post':
						if(qa_post_text('is_expert_question') == 'yes' ||  (in_array(qa_opt('expert_question_type'),array(1,2)) && !qa_get_logged_in_userid()) || qa_opt('expert_question_type') == 3) {
							require_once QA_INCLUDE_DIR.'qa-app-post-update.php';
							qa_question_set_hidden($params, true, $userid, $handle, $cookieid, array(), array());
							
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
	
							qa_db_query_sub(
								"INSERT INTO ^postmeta (post_id,meta_key,meta_value) VALUES (#,'is_expert_question','1')",
								$params['postid']
							);

							if(qa_opt('expert_question_email_experts')) {
								
								$subs = array(
									'^post_title'=> $params['title'],
									'^post_url'=> qa_path_html(qa_q_request($params['postid'], $params['title']), null, qa_opt('site_url')),
									'^questions_list'=> qa_path_html(qa_opt('expert_question_page_url'), null, qa_opt('site_url')),
									'^site_url'=> qa_opt('site_url'),
								);
								
								$experts = explode("\n",qa_opt('expert_question_users'));
								foreach($experts as $expert) {
									if(strpos($expert,'=')) {
										$expert = explode('=',$expert);
										$catnames = explode(',',$expert[1]);
										$cats = qa_db_read_all_values(
											qa_db_query_sub(
												'SELECT categoryid FROM ^categories WHERE title IN ($)',
												$catnames
											)
										);
										if(in_array($params['categoryid'],$cats))
											qa_send_notification($this->getuserfromhandle($expert[0]), '@', $expert[0], qa_opt('expert_question_email_subject'), qa_opt('expert_question_email_body'), $subs);
									}
									else {
										qa_send_notification($this->getuserfromhandle($expert), '@', $expert, qa_opt('expert_question_email_subject'), qa_opt('expert_question_email_body'), $subs);
									}
								}
							}
						}
						break;
					default:
						break;
				}
			}
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
			return $userid;
		}
	}
