<?php
    class qa_expert_question_admin {

	function option_default($option) {
		
	    switch($option) {
		case 'expert_question_page_title':
		    return 'Expert Questions';
		case 'expert_question_roles':
		    return 100;
		case 'expert_question_no':
		    return '<b>Public</b> - share question with entire community';
		case 'expert_question_yes':
		    return '<b>Private</b> - ask the experts (hidden from the community)';
		case 'expert_question_title':
		    return '[expert question]';
		case 'expert_question_users':
		    return '';
		default:
		    return null;				
	    }
		
	}
        
        function allow_template($template)
        {
            return ($template!='admin');
        }       
            
        function admin_form(&$qa_content)
        {                       
                            
        // Process form input
            
            $ok = null;
            
            if (qa_clicked('expert_question_save')) {
		if((bool)qa_post_text('expert_question_enable') && !qa_opt('expert_question_enable')) {
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
    
		}
                qa_opt('expert_question_enable',(bool)qa_post_text('expert_question_enable'));
                qa_opt('expert_question_disable_voting',(bool)qa_post_text('expert_question_disable_voting'));
                
		qa_opt('expert_question_roles',qa_post_text('expert_question_roles'));
                qa_opt('expert_question_users',qa_post_text('expert_question_users'));
		
                qa_opt('expert_question_no',qa_post_text('expert_question_no'));
                qa_opt('expert_question_yes',qa_post_text('expert_question_yes'));

                qa_opt('expert_question_title',qa_post_text('expert_question_title'));
		
		$ok = qa_lang('admin/options_saved');
            }
            else if (qa_clicked('expert_question_reset')) {
		foreach($_POST as $i => $v) {
		    $def = $this->option_default($i);
		    if($def !== null) qa_opt($i,$def);
		}
		$ok = qa_lang('admin/options_reset');
	    }
  
        // Create the form for display
            
            $fields = array();
            
            $fields[] = array(
                'label' => 'Enable expert questions',
                'tags' => 'NAME="expert_question_enable"',
                'value' => qa_opt('expert_question_enable'),
                'type' => 'checkbox',
            );
            $fields[] = array(
                'label' => 'Disable voting on expert questions',
                'tags' => 'NAME="expert_question_disable_voting"',
                'value' => qa_opt('expert_question_disable_voting'),
                'type' => 'checkbox',
            );

	    $permitoptions=qa_admin_permit_options(QA_PERMIT_EXPERTS, QA_PERMIT_ADMINS, (!QA_FINAL_EXTERNAL_USERS) && qa_opt('confirm_user_emails'));

	    $fields[] = array(
		    'id' => 'expert_question_roles',
		    'label' => 'Roles considered as expert',
		    'tags' => 'NAME="expert_question_roles" ID="expert_question_roles"',
		    'type' => 'select',
		    'options' => $permitoptions,
		    'value' => $permitoptions[qa_opt('expert_question_roles')],
	    );
	    
            $fields[] = array(
                'label' => 'Custom expert users:',
                'tags' => 'NAME="expert_question_users"',
                'value' => qa_opt('expert_question_users'),
                'note' => 'add usernames of expert users, one per line',
		'rows' => 10,
                'type' => 'textarea',
            );

            $fields[] = array(
                'label' => 'Text for selecting expert question on ask form',
                'tags' => 'NAME="expert_question_yes"',
                'value' => qa_opt('expert_question_yes'),
            );
	    
            $fields[] = array(
                'label' => 'Text for selecting ordinary question on ask form',
                'tags' => 'NAME="expert_question_no"',
                'value' => qa_opt('expert_question_no'),
            );
	    
	    
            $fields[] = array(
                'label' => 'Text to add to expert question title',
                'tags' => 'NAME="expert_question_title"',
                'value' => qa_opt('expert_question_title'),
            );
	    
            return array(           
                'ok' => ($ok && !isset($error)) ? $ok : null,
                    
                'fields' => $fields,
             
                'buttons' => array(
                    array(
                        'label' => qa_lang_html('main/save_button'),
                        'tags' => 'NAME="expert_question_save"',
                    ),
                    array(
                        'label' => qa_lang_html('admin/reset_options_button'),
                        'tags' => 'NAME="expert_question_reset"',
                    ),
                ),
            );
        }
    }

