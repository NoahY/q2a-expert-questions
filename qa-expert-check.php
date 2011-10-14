<?php

	class qa_expert_questions_event {
		function process_event($event, $userid, $handle, $cookieid, $params) {
			if (qa_opt('expert_questions_enable')) {
				switch ($event) {
					case 'q_post':
						if(qa_post_text('is_expert_question') == 'yes') {
							qa_db_query_sub(
								"UPDATE ^posts SET type='Q_HIDDEN' WHERE postid=#",
								$params['postid']
							);
							
							$table_exists = qa_db_read_one_value(qa_db_query_sub("SHOW TABLES LIKE '^postmeta'"),true);
							if(!$table_exists) {
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
							}		    
	
							qa_db_query_sub(
								"INSERT INTO ^postmeta (post_id,meta_key,meta_value) VALUES (#,'is_expert_question','1')",
								$params['postid']
							);

						}
						break;
					default:
						break;
				}
			}
		}
	}
