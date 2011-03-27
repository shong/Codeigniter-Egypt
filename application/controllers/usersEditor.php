<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * system application add/edit/delete users and users groups
 *
 * it has the ability of creating users, activate/deactivate users
 * and create/modify/delete users groups
 *
 * @copyright  2011 Emad Elsaid a.k.a Blaze Boy
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt   GPL License 2.0
 * @link       https://github.com/blazeeboy/Codeigniter-Egypt
 */ 
class UsersEditor extends Application {

	public function __construct(){
		
		parent::__construct();

		$this->perm 			= 'admin';
		$this->name 			= lang('system_users_editor');
		$this->author 			= "Emad Elsaid";
		$this->website 			= "http://blazeeboy.blogspot.com";
		$this->version 			= "0.1";
		$this->show_toolbar 	= TRUE;
		$this->pages 			= array(
					'index'=>lang('system_active_users'),
					'inactive'=>lang('system_inactive_users'),
					'newGroup'=>lang('system_new_group'),
					'newUser'=>lang('system_new_user')
		);

		$this->load->library('gui');
	}

	/**
	 * view users groups tree and redirect to edit page when click
	 * on any node depending on it's type
	 */
	public function index(){

		theme_add('dojo.data.ItemFileReadStore');
		theme_add('dijit.tree.ForestStoreModel');
		theme_add('dijit.Tree');
		
		$this->print_text('<div dojoType="dojo.data.ItemFileReadStore" url="'.site_url('usersEditor/queryGroups').'" jsId="ordJson"></div>');
		$this->print_text('<div dojoType="dijit.tree.ForestStoreModel" childrenAttrs="line" store="ordJson" jsId="ordModel"></div>');
		$this->print_text('<div dojoType="dijit.Tree" id="ordTree" model="ordModel" showRoot="false" >
		<script type="dojo/method" event="onClick" args="item">
		if( item.type=="group" )
			document.location.href = "'.site_url('usersEditor/editGroup').'/"+item.id;
		else
			document.location.href = "'.site_url('usersEditor/editUser').'/"+item.id;
		</script>
		</div>');
		
	}
	
	/**
	 * get JSON tree of users groups and it's users
	 */
	public function queryGroups(){
		
		$this->ajax = TRUE;
		$groups = $this->ion_auth->get_groups();
		foreach( $groups as $key=>$group ){
			$groups[$key]->type = 'group';
			$groups[$key]->ident = 'g'.$group->id;
			$groups[$key]->line = array();
			
			$users = $this->ion_auth->get_active_users_array($group->name);
			foreach( $users as $uk=>$user ){
				$u = array();
				$u['ident'] = 'u'.$user['id'];
				$u['id'] = $user['id'];
				$u['name'] = $user['username'].' ('.$user['email'].')';
				$u['type'] = 'user';
				$groups[$key]->line[] = $u;
			}
		}
		
		$this->print_text( json_encode(array( 'identifier'=>'ident', 'label'=>'name','items'=>$groups)) );
		
	}
	
	/**
	 * shows the inactive users tree
	 */
	public function inactive(){

		theme_add('dojo.data.ItemFileReadStore');
		theme_add('dijit.tree.ForestStoreModel');
		theme_add('dijit.Tree');
		
		$this->print_text('<div dojoType="dojo.data.ItemFileReadStore" url="'.site_url('usersEditor/queryInactiveGroups').'" jsId="ordJson"></div>');
		$this->print_text('<div dojoType="dijit.tree.ForestStoreModel" childrenAttrs="line" store="ordJson" jsId="ordModel"></div>');
		$this->print_text('<div dojoType="dijit.Tree" id="ordTree" model="ordModel" showRoot="false" >
		<script type="dojo/method" event="onClick" args="item">
		if( item.type=="group" )
			document.location.href = "'.site_url('usersEditor/editGroup').'/"+item.id;
		else
			document.location.href = "'.site_url('usersEditor/editUser').'/"+item.id;
		</script>
		</div>');
		
	}
	
	/**
	 * get a tree of groups and its inactive users
	 */
	public function queryInactiveGroups(){
		
		$this->ajax = TRUE;
		$groups = $this->ion_auth->get_groups();
		
		foreach( $groups as $key=>$group ){
			$groups[$key]->type = 'group';
			$groups[$key]->ident = 'g'.$group->id;
			$groups[$key]->line = array();
			
			$users = $this->ion_auth->get_inactive_users_array($group->name);
			foreach( $users as $uk=>$user ){
				$u = array();
				$u['ident'] = 'u'.$user['id'];
				$u['id'] = $user['id'];
				$u['name'] = $user['username'].' ('.$user['email'].')';
				$u['type'] = 'user';
				$groups[$key]->line[] = $u;
			}
		}
		
		$this->print_text( json_encode(array( 'identifier'=>'ident', 'label'=>'name','items'=>$groups)) );
		
	}
	
	/**
	 * create new users group page
	 */
	public function newGroup(){
		
		$this->print_text(
			$this->gui->form('usersEditor/newGroupAction',
			array(
				lang('system_name_label') => $this->gui->textbox('name'),
				lang('system_description_label') => $this->gui->textbox('description'),
				''=>$this->gui->button('submit', lang('system_save'), array('type'=>'submit'))
			)
		));
		
	}
	
	/**
	 * create new users group page action 
	 */
	public function newGroupAction(){
		
		if(is_object($this->ion_auth->get_group_by_name($this->input->post('name'))))
			$this->add_error(lang('system_group_exists'));
		else{
			$group = new Group();
			$group->name = $this->input->post('name');
			$group->description = $this->input->post('description');
			$group->save();
			$this->add_info(lang('system_group_added'));
		}
		
	}
	
	/**
	 * edit group information page 
	 * it contains a form with group data
	 * 
	 * @param integer $id group id which needs to be edited
	 */
	public function editGroup($id){
		
		$group = new Group($id);
		if( !$group->exists() )
			show_error(lang('system_group_not_found'));
		
		$this->print_text(
			$this->gui->form('usersEditor/editGroupAction',
			array(
				lang('system_name_label')=>$this->gui->textbox('name',$group->name),
				lang('system_description_label')=>$this->gui->textbox('description',$group->description),
				''=>$this->gui->button('submit',lang('system_save'),array('type'=>'submit')).
					anchor('usersEditor/deleteGroup/'.$id,lang('system_delete_group'))
			),
			'',
			array('id'=>$id)
		));
		
	}
	
	/**
	 * edit group action pagge
	 */
	public function editGroupAction(){
		
		$group = new Group($this->input->post('id'));
		if( !$group->exists() )
			show_error(lang('system_group_not_found'));
			
		$group->name = $this->input->post('name');
		$group->description = $this->input->post('description');
		$group->save();
		redirect('usersEditor');
		
	}
	
	/**
	 * delete group from system with all it's users and all related data
	 * 
	 * @param integer $id the group needed to be deleted
	 */
	public function deleteGroup($id){
		
		$group = new Group($id);
		$group->delete();
		
		redirect('usersEditor');
		
	}
	
	/**
	 * create new user page, and it has one form 
	 * with needed data
	 * and it has already the action itself,
	 * that is copied from ion-auth Auth controller
	 */
	public function newUser(){

		$this->load->library('form_validation');
		//validate form input
		$this->form_validation->set_rules('first_name', lang('system_first_name'), 'required|xss_clean');
		$this->form_validation->set_rules('last_name', lang('system_last_name'), 'required|xss_clean');
		$this->form_validation->set_rules('email', lang('system_email'), 'required|valid_email');
		$this->form_validation->set_rules('phone1', lang('system_phone_first'), 'xss_clean|min_length[3]|max_length[3]');
		$this->form_validation->set_rules('phone2', lang('system_phone_second'), 'xss_clean|min_length[3]|max_length[3]');
		$this->form_validation->set_rules('phone3', lang('system_phone_third'), 'xss_clean|min_length[4]|max_length[4]');
		$this->form_validation->set_rules('company', lang('system_company'), 'xss_clean');
		$this->form_validation->set_rules('password', lang('system_password'), 'required|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']|max_length[' . $this->config->item('max_password_length', 'ion_auth') . ']|matches[password_confirm]');
		$this->form_validation->set_rules('password_confirm', lang('system_password_conf'), 'required');

		if ($this->form_validation->run() == true)
		{
			$username = strtolower($this->input->post('first_name')) . ' ' . strtolower($this->input->post('last_name'));
			$email = $this->input->post('email');
			$password = $this->input->post('password');

			$additional_data = array('first_name' => $this->input->post('first_name'),
				'last_name' => $this->input->post('last_name'),
				'company' => $this->input->post('company'),
				'phone' => $this->input->post('phone1') . '-' . $this->input->post('phone2') . '-' . $this->input->post('phone3'),
			);
		}
		if ($this->form_validation->run() == true 
			&& $this->ion_auth->register($username, $password, $email, $additional_data,$this->input->post('group')))
		{ //check to see if we are creating the user
			//redirect them back to the admin page
			$this->session->set_flashdata('message', lang('system_user_created'));
			redirect("usersEditor");
		}else{ //display the create user form
			//set the flash data error message if there is one
			$groups = new Group();
			$groups->get();
			$groups_array = array();
			foreach( $groups as $group )
				$groups_array[$group->name] = $group->name;
				
			$this->print_text(
				$this->gui->form('usersEditor/newUser',
					array(
					' ' => (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message'))),
					lang('system_group_label') => $this->gui->dropdown('group','',$groups_array),
					lang('system_first_name').'*' => $this->gui->textbox('first_name',$this->form_validation->set_value('first_name')),
					lang('system_last_name').'*' => $this->gui->textbox('last_name',$this->form_validation->set_value('last_name')),
					lang('system_email').'*' => $this->gui->textbox( 'email', $this->form_validation->set_value('email')),
					lang('system_password').'*' => $this->gui->password( 'password', $this->form_validation->set_value('password')),
					lang('system_password_conf').'*' => $this->gui->password( 'password_confirm', $this->form_validation->set_value('password_confirm')),
					lang('system_company') => $this->gui->textbox( 'company', $this->form_validation->set_value('company')),
					lang('system_phone') => $this->gui->textbox( 'phone1', $this->form_validation->set_value('phone1')).' - '.
					$this->gui->textbox( 'phone2', $this->form_validation->set_value('phone2')).' - '.
					$this->gui->textbox( 'phone3', $this->form_validation->set_value('phone3')),
					'' => $this->gui->button('submit',lang('system_create_user'), array('type'=>'submit'))
			)));
		}
	}
	
	/**
	 * edit user information
	 * 
	 * @param integer $id the user id needed to be edited
	 */
	public function editUser($id){
		
		$groups = new Group();
		$groups->get();
		$groups_array = array();
		foreach( $groups as $group )
			$groups_array[$group->id] = $group->name;
			
		$user = $this->ion_auth->get_user($id);
		$this->print_text(
			$this->gui->form('usersEditor/editUserAction',
				array(
					lang('system_group_label') => $this->gui->dropdown('group',$user->group_id,$groups_array),
					lang('system_group_desc_label') => $user->group_description,
					lang('system_last_ip') => $user->ip_address,
					lang('system_username') => $user->username,
					lang('system_password') => $this->gui->password('password'),
					lang('system_password_salt') => $user->salt,
					lang('system_email') => $user->email,
					lang('system_active_code') => $user->activation_code,
					lang('system_forgot_password_code') => $user->forgotten_password_code,
					lang('system_remember_code') => $user->remember_code,
					lang('system_created_on') => $user->created_on,
					lang('system_last_login') => $user->last_login,
					lang('system_active') => $this->gui->checkbox('active','active',$user->active),
					lang('system_first_name') => $this->gui->textbox('first_name',$user->first_name),
					lang('system_last_name') => $this->gui->textbox('last_name',$user->last_name),
					'' => $this->gui->button('submit', lang('system_update_user'), array('type'=>'submit')).
						anchor('usersEditor/deleteUser/'.$id,lang('system_delete_user'))
				),'',
				array('id'=>$user->id)
			)
		);
		
	}
	
	/**
	 * delete user page
	 * 
	 * @param integer $id the user that will be deleted
	 */
	public function deleteUser($id){
		
		$user = new User($id);
		$user->delete();
		redirect('usersEditor');
		
	}
	
	/**
	 * edit user information action page
	 */
	public function editUserAction(){
		
		$user = new User($this->input->post('id'));
		$user->group_id = $this->input->post('group');
		$user->active = ($this->input->post('active')===false)? 0:1;
		$user->save();
		$result = $this->input->post('password')==''
					?$this->ion_auth->update_user($user->id,
				array(
					'first_name'=>$this->input->post('first_name'),
					'last_name'=>$this->input->post('last_name')
				))
				:$this->ion_auth->update_user($user->id,
				array(
					'first_name'=>$this->input->post('first_name'),
					'last_name'=>$this->input->post('last_name'),
					'password'=>$this->input->post('password')
				));
		if($result)
			redirect('usersEditor');
		else
			$this->add_error($this->ion_auth->errors());
			
	}
}
