<?php

class Url{


	private $pTarget;
	private $oTemplate;

	/**
	*Constructor function to set the target
	* @param string $pTarget: target of the page
	* @param Templater $oTempl : Templater  class object
	*/
	public function __construct($pTarget, Templater &$oTmpel){
		$this->pTarget = $pTarget;
		$this->oTemplate = $oTmpel;
		$this->destination();
	}

	/**
	*Function to redirect urls to destinations
	*/
	function destination(){
		switch($this->pTarget){
			case 'home':
				LoggerPrime::info("Accessing home page");
				require_once __DIR__."/files/manage_files.php";
				$uf =  new ManageFiles($this->oTemplate);
				$uf->renderUploadForm();
				break;
			case 'settings':
			case 'update_user_details_settings':
			case 'update_user_pass_settings':
				LoggerPrime::info("Accessing settings page");
				require_once __DIR__."/users/settings.php";
				$settings =  new Settings($this->oTemplate);
				break;
			case 'contact':
				LoggerPrime::info("Accessing settings page");
				require_once __DIR__."/contact.php";
				$contact = new Contact($this->oTemplate);
				break;
			case 'blocked':
				LoggerPrime::info("User is blocked");
				require_once __DIR__."/users/blocked.php";
				$blocked = new Blocked($this->oTemplate);
				break;
			case 'add_files':
				LoggerPrime::info("Accessing manage_files.php. Atempting to add files");
				require_once __DIR__."/files/manage_files.php";
				$uf =  new ManageFiles($this->oTemplate);
				$uf->addFile();
				break;
			case  'delete_files':
				LoggerPrime::info("Accessing manage_files.php. Atempting to delete files");
				$file_id = $_POST["d_fid"];
				require_once __DIR__."/files/manage_files.php";
				$uf =  new ManageFiles($this->oTemplate);
				$uf->deleteFile($file_id);

				break;
			case 'undo_delete':
				LoggerPrime::info("Accessing manage_files.php. Atempting to undo delete files");
				require_once __DIR__."/files/manage_files.php";
				$uf =  new ManageFiles($this->oTemplate);
				$uf->undoDelete();
				break;
			case 'add_to_cart':
			case 'save_to_cart':
				LoggerPrime::info("Accessing add_to_cart.php. ");
				require_once __DIR__."/cart/add_to_cart.php";
				$addToCart = new  AddtoCart($this->oTemplate);
				break;
			case 'ajax';
				require_once __DIR__."/ajax/ajax.php";
				$options = $_GET['options'];
				$ajax = new Ajax($options, $this->oTemplate);
				$ajax->makeAjaxCall();
				break;
			case 'view_file':
				LoggerPrime::info("Accessing view_file.php. ");
				$fTarget = UserData::create('fid');
				require_once __DIR__."/files/view_file.php";
				$fId = $fTarget->getString();
				$view_file = new ViewFile($this->oTemplate, $fId);
				break;
			case 'stream_file':
				LoggerPrime::info("Accessing stream_file.php. ");
				require_once __DIR__."/files/stream_file.php";
				$stream_file = new StreamFile($this->oTemplate);
				break;
			case 'export_stats':
				require_once __DIR__."/stats/export_stats.php";
				$export_stats = new ExportStats();
				break;
			case 'print':
				require_once __DIR__."/jobs/print_job.php";
				$print_job = new PrintJob($this->oTemplate);
				break;
			case 'print_label':
				require_once __DIR__."/jobs/print_job_label.php";
				$print_job_label = new PrintJobLabel($this->oTemplate);
				break;
			case 'remove_file_from_job':
			case 'undo_remove_file_from_job':
				require_once __DIR__."/jobs/remove_file_from_job.php";
				$print_job = new RemoveFileFromJob($this->oTemplate);
				break;
			case 'view_cart':
			case 'remove_cart_item':
			case 'undo_remove_citem':
			case 'remove_cart_all':
			case 'undo_remove_all_citem':
				LoggerPrime::info("Accessing view_cart.php. ");
				require_once __DIR__."/cart/view_cart.php";
				$new_cart = new ViewCart($this->oTemplate);
				break;
			case 'workflow':
			case 'setprice':
			case 'setdeliverydate':
			case 'update_print_details':
			case 'move_to_step':
				LoggerPrime::info("Accessing process_workflow.php. ");
				require_once __DIR__."/workflows/process_workflow.php";
				$workflow_step =  new ProcessWorkflow($this->oTemplate);
				break;
			case 'my_jobs':
			case 'all_jobs':
            case 'go_to_job':
				LoggerPrime::info("Accessing view_jobs.php. ");
				require_once __DIR__."/jobs/view_jobs.php";
				$view_jobs = new ViewJobs($this->oTemplate);
				break;
			case 'job_details':
				LoggerPrime::info("Accessing job_details.php. ");
				require_once __DIR__."/jobs/job_details.php";
				$job_details =  new JobDetails($this->oTemplate);
				break;
			case 'job_updates':
				require_once __DIR__."/jobs/job_updates.php";
				$job_details = new JobUpdates($this->oTemplate);
				break;
			case 'messages':
				LoggerPrime::info("Accessing view_messages.php. ");
				require_once __DIR__."/messages/view_messages.php";
				$messages = new ViewMessages($this->oTemplate);
				break;
			case 'message_details':
				LoggerPrime::info("Accessing message_details.php. ");
				require_once __DIR__."/messages/message_details.php";
				$message_details = new MessageDetails($this->oTemplate);
				break;
            case 'send_message':
				LoggerPrime::info("Accessing send_message.php. ");
				require_once __DIR__."/messages/send_message.php";
                $send_message = new SendMessage($this->oTemplate);
                break;
			case 'manage_users':
				LoggerPrime::info("Accessing manage_users.php. ");
				require_once __DIR__."/users/manage_users.php";
				$manage_users = new ManageUsers($this->oTemplate);
				break;
			case 'search_users':
				LoggerPrime::info("Accessing search_users.php. ");
				require_once __DIR__."/users/search_users.php";
				$search_users =  new SearchUsers($this->oTemplate);
				break;
			case 'user_jobs':
				LoggerPrime::info("Accessing user_jobs.php. ");
				require_once __DIR__."/jobs/user_jobs.php";
				$user_jobs = new UserJobs($this->oTemplate);
				break;
			case 'user_details':
			case 'edit_user_details':
			case 'edit_user_status':
			case 'block_user':
			case 'unblock_user':
			case 'verify_user':
				LoggerPrime::info("Accessing user_details.php. ");
				require_once __DIR__."/users/user_details.php";
				$user_details = new UserDetails($this->oTemplate);
				break;
			case 'stats':
				LoggerPrime::info("Accessing stats.php. ");
				require_once __DIR__."/stats/stats.php";
				$stats = new Stats($this->oTemplate);
				break;
			case 'manage_infrastructure':
			case 'edit_workflow':
			case 'remove_workflow':
			case 'undo_workflow_delete':
			case 'add_workflow':
				LoggerPrime::info("Accessing manage_infrastructure.php. ");
				require_once __DIR__."/infrastructure/manage_infrastructure.php";
				$infrastructure = new ManageInfrastructure($this->oTemplate);
				break;
			case 'remove_group':
			case 'undo_group_delete':
			case 'edit_group':
			case 'add_group';
			case 'manage_groups':
				require_once __DIR__."/infrastructure/manage_groups.php";
				$group_manage = new ManageGroups($this->oTemplate);
				break;
			case 'manage_printers':
			case 'update_printer':
			case 'remove_printer':
			case 'undo_remove_printer':
			case 'add_printer':
				LoggerPrime::info("Accessing manage_printers.php. ");
				require_once __DIR__."/infrastructure/manage_printers.php";
				$printers = new ManagePrinters($this->oTemplate);
				break;
			case 'manage_steps':
			case 'edit_workflow_steps':
			case 'remove_workflow_step':
			case 'undo_remove_workflow_step':
			case 'add_workflow_steps':
				LoggerPrime::info("Accessing manage_workflow_steps.php. ");
				require_once __DIR__."/infrastructure/manage_workflow_steps.php";
				$manage_steps = new ManageWorkflowSteps($this->oTemplate);
				break;
			case 'manage_options':
			case 'remove_options':
			case 'undo_remove_options':
			case 'edit_printer_options':
			case 'add_printer_options':
            case 'add_price_options':
            case 'remove_price_options':
				LoggerPrime::info("Accessing manage_options.php. ");
				require_once __DIR__."/infrastructure/manage_options.php";
				$manage_options = new ManageOptions($this->oTemplate);
				break;
			default:
				LoggerPrime::info("Navigating to homepage");
				header("Location: /?t=home");

				break;


		}

	}

}



?>
