<?php
    class qa_expert_question_admin {

	function option_default($option) {
		
	    switch($option) {
		case 'expert_question_page_url':
		    return 'expert';
		case 'expert_question_page_title':
		    return 'Expert Questions';
		case 'expert_question_roles':
		    return 100;
		case 'expert_question_css':
		    return '
.qa-expert-question .qa-a-item-main {
    margin-left:0;
}
';
		case 'expert_question_email_subject':
		    return '[^site_title] Expert Question Posted';
		case 'expert_question_email_body':
		    return 'An expert question has been posted at ^site_title:

^post_title
^post_url

Please visit the above link if you have an answer, or visit:

^questions_list

to see the list of all expert questions.

You are receiving this email because you are registered as an expert at ^site_title.  If you feel this email has been sent in error, please let us know by replying to this email.

Thank you for your help!';
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
                qa_opt('expert_question_enable',(bool)qa_post_text('expert_question_enable'));
                qa_opt('expert_question_disable_voting',(bool)qa_post_text('expert_question_disable_voting'));
                qa_opt('expert_question_type',qa_post_text('expert_question_type'));
                
                qa_opt('expert_question_page_title',qa_post_text('expert_question_page_title'));
                qa_opt('expert_question_page_url',qa_post_text('expert_question_page_url'));
                qa_opt('expert_question_show_count',(bool)qa_post_text('expert_question_show_count'));

		qa_opt('expert_question_roles',qa_post_text('expert_question_roles'));
                qa_opt('expert_question_users',qa_post_text('expert_question_users'));

                qa_opt('expert_question_email_experts',(bool)qa_post_text('expert_question_email_experts'));
                qa_opt('expert_question_email_subject',qa_post_text('expert_question_email_subject'));
                qa_opt('expert_question_email_body',qa_post_text('expert_question_email_body'));
		
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
	    
	    $question_types = array(
		'specified when asking',
		'anonymous questions',
		'anonymous and specified',
		'all'
	    );
	    
	    $fields[] = array(
		'id' => 'expert_question_type',
		'label' => 'Type of questions to consider expert',
		'tags' => 'NAME="expert_question_type" ID="expert_question_type"',
		'type' => 'select',
		'options' => $question_types,
		'value' => @$question_types[qa_opt('expert_question_type')],
	    );

	    $fields[] = array(
		'type' => 'blank',
	    );
            
	    $fields[] = array(
                'label' => 'Custom css:',
                'tags' => 'NAME="expert_question_css"',
                'value' => qa_opt('expert_question_css'),
		'rows' => 10,
                'type' => 'textarea',
            );
	    
	    $fields[] = array(
		'type' => 'blank',
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
                'note' => 'Add usernames of expert users, one per line.  To make a user expert for a specific set of categories, use the following syntax:<br/><br/>username=category1,category2,category3',
		'rows' => 10,
                'type' => 'textarea',
            );
	    
	    $fields[] = array(
		'type' => 'blank',
	    );
            $fields[] = array(
                'label' => 'Email experts on new expert question',
		'note' => 'only those specified in the above box will be emailed.',
                'tags' => 'NAME="expert_question_email_experts"',
                'type' => 'checkbox',
                'value' => qa_opt('expert_question_email_experts'),
            );	    
	    $fields[] = array(
		'label' => 'Email Subject',
		'tags' => 'NAME="expert_question_email_subject"',
		'value' => qa_opt('expert_question_email_subject'),
		'type' => 'text',
	    );

	    $fields[] = array(
		'label' =>  'Email Body',
		'tags' => 'name="expert_question_email_body"',
		'value' => qa_opt('expert_question_email_body'),
		'type' => 'textarea',
		'rows' => 20,
		'note' => 'Available replacement text:<br/><br/><i>^site_title<br/>^handle<br/>^email<br/>^post_title<br/>^post_url<br/>^site_url',
	    );
	    
	    $fields[] = array(
		'type' => 'blank',
	    );
	    
            $fields[] = array(
                'label' => 'Show unanswered expert question count in nav tab',
                'tags' => 'NAME="expert_question_show_count"',
                'type' => 'checkbox',
                'value' => qa_opt('expert_question_show_count'),
            );

            $fields[] = array(
                'label' => 'Expert question page title',
                'tags' => 'NAME="expert_question_page_title"',
                'value' => qa_opt('expert_question_page_title'),
            );

            $fields[] = array(
                'label' => 'Expert question page url',
                'note' => '(set this in admin/pages as well!)',
                'tags' => 'NAME="expert_question_page_url"',
                'value' => qa_opt('expert_question_page_url'),
            );

	    $fields[] = array(
		'type' => 'blank',
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

