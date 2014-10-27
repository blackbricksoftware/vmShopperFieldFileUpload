<?php defined('_JEXEC') or die('Direct access not allowed.');;
error_reporting(E_ALL);ini_set('display_errors',1);
/**
 * @name vmShopperFieldFileUpload
 * @description Allowing file uploads on virtuemart shopper fields
 * @author: David Hayes, david@blackbricksoftware.com
 * @company: Black Brick Software LLC, http://blackbricksoftware.com
 * @date: 8/2/12
 * @copyright	Copyright (C) 2012 Black Brick Software LLC. All rights reserved.
 * @license		GNU General Public License version 2 or later.
 * @package		Joomla.Plugin
 * @version		1.0.0
*/

if (!class_exists('vmUserfieldPlugin')) require(JPATH_VM_PLUGINS . DS . 'vmuserfieldtypeplugin.php');

class plgVmuserfieldVmShopperFieldFileUpload extends vmUserfieldPlugin {

	public $fileuser;
	public $uploadlocation;
	public $shownote;
	public $showuploaded;
	public $allowdelete;

	//constructor
	public function __construct(&$subject,$config) {

		parent::__construct($subject, $config);

		// load up the language
		JFactory::getLanguage()->load('plg_vmuserfield_vmShopperFieldFileUpload',JPATH_ADMINISTRATOR);

		// What user's data should we be looking at
		$this->fileuser = (JPATH_BASE==JPATH_ADMINISTRATOR) ? array_pop(JRequest::getVar('virtuemart_user_id',array())) : JFactory::getUser()->id;

		// Remove beginning and ending DS and switch out our DS for the Joomla DS
		$this->uploadlocation =	str_replace('/',DS,
							preg_replace('#^/+#','',
								preg_replace('#/+$#','',
									$this->params->get('uploadlocation')
								)
							)
						);

		// Decide if we should show the upload note
		$this->shownote = ($this->params->get('shownote') || JPATH_BASE==JPATH_ADMINISTRATOR);
		// Decide if we should show the history
		$this->showuploaded = ($this->params->get('showuploaded') || JPATH_BASE==JPATH_ADMINISTRATOR);
		// Decide if we are allowed to delete files
		$this->allowdelete = ($this->params->get('allowdelete') || JPATH_BASE==JPATH_ADMINISTRATOR);
    }

	// not usre about this function
/*
	public function plgVmOnStoreInstallPluginTable ($type,$data) {
		echo __METHOD__ .'<br />';
		echo "<pre>"; print_r($_prefix); echo "</pre>";
		echo "<pre>"; print_r($_fld); echo "</pre>";
		echo "<pre>"; print_r($virtuemart_user_id); echo "</pre>";
		echo "<pre>"; print_r($_return); echo "</pre>";
		exit;
	}
*/

	// this function is fired before a users user field list is displayed
	// Display the file input //Upload me a file to eat! Munch!
	public function plgVmOnUserfieldDisplay($_prefix,$_fld,$virtuemart_user_id,&$_return) {
/*
echo "<pre>"; print_r($_SESSION); echo "</pre>";
*/
/*
		echo __METHOD__ .'<br />';
		echo "<pre>"; print_r($_prefix); echo "</pre>";
		echo "<pre>"; print_r($_fld); echo "</pre>";
		echo "<pre>"; print_r($virtuemart_user_id); echo "</pre>";
		echo "<pre>"; print_r($_return); echo "</pre>";
		exit;
*/

		// Database!
		$db = JFactory::getDBO();

		switch ($_fld->type) {

			case 'pluginvmShopperFieldFileUpload':

				// Start out our file uploads and have a hidden element to save the ids of the files associated with this address
				$_return['fields'][$_fld->name]['formcode'] = '<input type="text" name="'.$_prefix.$_fld->name.'" id="'.$_prefix.$_fld->name.'_field" value="'.htmlspecialchars($_return['fields'][$_fld->name]['value']).'" /><div class="fileuploadcontainer">';

				// If we are displaying something more than just the file upload, show a heading
				$_return['fields'][$_fld->name]['formcode'] .= ($this->shownote||$this->showuploaded)?'<div class="uploadnotedesc">'.JText::_('PLG_VM_FILE_FILE_UPLOAD').'</div>':'';

				// Add a script so that when the file upload is clicked it sets the entype on the form so that files can be uploaded
				$_return['fields'][$_fld->name]['formcode'] .=
					'<script type="text/javascript"> function plgVmuserfieldVmShopperFieldFileUpload_setenctype() { var ele; if (ele = document.getElementById(\'adminForm\')) ele.setAttribute(\'enctype\',\'multipart/form-data\'); if (ele = document.getElementById(\'userForm\')) ele.setAttribute(\'enctype\',\'multipart/form-data\'); } </script>';

				// Display file input
				$_return['fields'][$_fld->name]['formcode'] .=
					'<input	type="file" '.
							'onclick="plgVmuserfieldVmShopperFieldFileUpload_setenctype();" '. //make sure we can upload files
							'id="'.$_prefix.$_fld->name.'_field" '.
							'name="plgVmuserfieldVmShopperFieldFileUpload['.$_prefix.$_fld->name.']" '.
							($_fld->required?' class="required" ':'').
							($_fld->readonly?' readonly="readonly" ':'').
					' /></div><div class="clear"></div>';

				// Display note if applicable
				if ($this->shownote)
					$_return['fields'][$_fld->name]['formcode'] .=	'
						<div class="fileuploadnotecontainer">
							<div class="uploadnotedesc">'.JText::_('PLG_VM_FILE_UPLOAD_DESCRIPTION').'</div>
							<textarea '.
								'cols="35" '.
								'rows="3" '.
								'class="uploadnote" '.
								'name="plgVmuserfieldVmShopperFieldFileUpload_note['.$_prefix.$_fld->name.']" '.
							'></textarea>'.
						'</div><div class="clear"></div>';

				// Show previously uploaded files if applicable
				if ( $this->showuploaded && $this->fileuser>0 ) {

					// find a list of files tied to this address
					$fileslist = array_map(function($val){return (int)$val;},array_filter(explode(',',$_return['fields'][$_fld->name]['value'])));

					if (count($fileslist)>0) {
						// Select all records from the database
						$files = $db->setQuery("
												SELECT *
												FROM `#__plgVmuserfieldVmShopperFieldFileUpload_files`
												WHERE `uid`=".$db->quote($this->fileuser)."
													AND `f_id` IN ('".implode("','",$fileslist)."')
													AND `fieldname`=".$db->quote($_prefix.$_fld->name).";
												")->loadObjectList();

						// Iterate over all records
						if (count($files)>0) {
							$_return['fields'][$_fld->name]['formcode'] .=	'
													<div class="fileuploadhistorycontainer">
														<div class="uploadnotedesc">'.JText::_('PLG_VM_FILE_UPLOADED_FILES').'</div>
														<table class="uploadedfiles">
															<thead>
																<tr>';
							$_return['fields'][$_fld->name]['formcode'] .= ($this->allowdelete)?'<th>'.JText::_('PLG_VM_FILE_DEL').'</th>':'';
							$_return['fields'][$_fld->name]['formcode'] .= '<th>'.JText::_('PLG_VM_FILE_FILENAME').'</th>';
							$_return['fields'][$_fld->name]['formcode'] .= ($this->shownote)?'<th>'.JText::_('PLG_VM_FILE_DESC').'</th>':'';
							$_return['fields'][$_fld->name]['formcode'] .=	'
																</tr>
															</thead>
															<tbody>';
							foreach ($files as $file) {
								$_return['fields'][$_fld->name]['formcode'] .= '<tr>';
								$_return['fields'][$_fld->name]['formcode'] .= ($this->allowdelete)?
											'<td><input type="checkbox" name="plgVmuserfieldVmShopperFieldFileUpload_edit['.htmlspecialchars($file->f_id).'][delete]" value="1" /></td>':'';
								$_return['fields'][$_fld->name]['formcode'] .=
											'<td><a href="'.JRoute::_(JURI::root(true).'/'.str_replace(DS,'/',$this->uploadlocation).'/'.$file->f_id.'_'.$file->filename).'" target="_blank">'.htmlspecialchars($file->f_id.'_'.$file->filename).'</a></td>';
								$_return['fields'][$_fld->name]['formcode'] .= ($this->shownote)?
																					'<td><textarea '.
																						'cols="35" '.
																						'rows="3" '.
																						'class="uploadnote" '.
																						'name="plgVmuserfieldVmShopperFieldFileUpload_edit['.htmlspecialchars($file->f_id).'][note]">'.htmlspecialchars($file->note).'</textarea></td>'
																			:'';
								$_return['fields'][$_fld->name]['formcode'] .= '</tr>';
							}
							$_return['fields'][$_fld->name]['formcode'] .=	'</table></div><div class="clear"></div>';
						}
					}
				}
/*
				$_return['fields'][$_fld->name]['value'] = '';
*/
				break;
		}

		return true;

	}

	// Call when the extra when generating the userfield incase you need extra parameters for it
/*
	 public function plgVmDeclarePluginParamsUserfield($type,$plgName,$userfield_jplugin_id,&$data) {

		echo __METHOD__ .'<br />';
		echo "<pre>"; print_r($type); echo "</pre>";
		echo "<pre>"; print_r($plgName); echo "</pre>";
		echo "<pre>"; print_r($userfield_jplugin_id); echo "</pre>";
		echo "<pre>"; print_r($data); echo "</pre>";
		exit;

		return true;

	}
*/

	// this function is fired when a userfield is created or saved (not the userfield data, the actual userfield)
	/* public function plgVmOnBeforeUserfieldSave( $plgName , &$data, &$field ) {

		echo __METHOD__ .'<br />';
		echo "<pre>"; print_r($plgName); echo "</pre>";
		echo "<pre>"; print_r($data); echo "</pre>";
		echo "<pre>"; print_r($field); echo "</pre>";
		exit;

		return true;

	} */

	// this function fired everytime userfield data is saved; it is fired once for each field of our type
	// it will run through the data and store our file; additionally, it will update the notes and delete files is applicable
	public function plgVmPrepareUserfieldDataSave($fieldType, $fieldName, &$post, &$value, $params) {


/*
		echo __METHOD__ .'<br />';
		echo "<pre>"; print_r($fieldType); echo "</pre>";
		echo "<pre>"; print_r($fieldName); echo "</pre>";
		echo "<pre>"; print_r($post); echo "</pre>";
		echo "<pre>"; print_r($value); echo "</pre>";
		echo "<pre>"; print_r($params); echo "</pre>";
		exit;
*/


		// Database!
		$db =& JFactory::getDBO();

		switch ($fieldType) {

			case 'pluginvmShopperFieldFileUpload':

				// Find all the uploaed file data
				$files = JRequest::getVar('plgVmuserfieldVmShopperFieldFileUpload',array(),'FILES');
				// Grab all the notes if we are supposed to
				if ($this->shownote) $notes = JRequest::getVar('plgVmuserfieldVmShopperFieldFileUpload_note');

				/*
				 *		Add new files / notes
				 */

				// Leave if there aren't any files
				if (count($files)>0) {

					// Check if file uploaded correctly
					if ($files['error'][$fieldName]) {
						// If if no file submitted, continue on with our lives
						if ($files['error'][$fieldName]!=4) {
							// Otherwise make a stink
							JError::raiseWarning(500,JText::_('PLG_VM_FILE_ERROR_PREFIX').JText::_('PLG_VM_FILE_UPLOAD_ERROR'));
							return;
						}
					}

					// Clean up filename
					$files['name'][$fieldName] = JFile::makeSafe($files['name'][$fieldName]);

					// Find and validate our file extension and mime type
					$ext = JFile::getExt($files['name'][$fieldName]);
					// Find what extensions we are allowed to upload
					$allowedexts = explode(',',$this->params->get('allowedtypes'));

/*
echo "<pre>"; print_r($files); echo "</pre>";
echo "<pre>"; print_r($ext); echo "</pre>";
echo "<pre>"; print_r($allowedexts); echo "</pre>";
echo "<pre>"; print_r(plgVmuserfieldVmShopperFieldFileUpload::$mimes); echo "</pre>";
exit;
*/
					if (
						// Check if we are allowed to upload a file with this extensions
						!in_array($ext,$allowedexts) ||
						(
							// If we are allowed to upload a file with the extension, check it mime against our list
							array_key_exists($ext,plgVmuserfieldVmShopperFieldFileUpload::$mimes) &&
							!in_array($files['type'][$fieldName],plgVmuserfieldVmShopperFieldFileUpload::$mimes[$ext])
						)
					) {
						JError::raiseWarning(500,JText::_('PLG_VM_FILE_ERROR_PREFIX').JText::_('PLG_VM_FILE_EXTENSION_ERROR'));
						return;
					}

					// Insert database info and get unique ID to save file
					$ins = (object)array(
											'uid'						=> $this->fileuser,
											'time'						=> time(),
											'ip'						=> JRequest::getVar('REMOTE_ADDR','','SERVER'),
											'fieldname'					=> $fieldName,
											'filename'					=> $files['name'][$fieldName],
											'mime'						=> $files['type'][$fieldName],
											'size'						=> $files['size'][$fieldName],
											'note'						=> '',
										);

					// Add the note to the insert if we have it enabled
					if ($this->shownote) $ins->note = $notes[$fieldName];

					// Insert the file information into the database
					if (!$db->insertObject('#__plgVmuserfieldVmShopperFieldFileUpload_files',$ins,'f_id')) {
						JError::raiseWarning(500,JText::_('PLG_VM_FILE_ERROR_PREFIX').JText::_('PLG_VM_FILE_DATABASE_ERROR'));
						return;
					}

					// Moved file to permanant storage position
					if (!JFile::upload($files['tmp_name'][$fieldName],JPATH_SITE.DS.$this->uploadlocation.DS.$ins->f_id.'_'.$files['name'][$fieldName])) {
						//remove file if we fail!
						$db->setQuery("DELETE FROM `#__plgVmuserfieldVmShopperFieldFileUpload_files` WHERE `f_id`=".$db->quote($ins->f_id).";")->query();
						JError::raiseWarning(500,JText::_('PLG_VM_FILE_ERROR_PREFIX').JText::_('PLG_VM_FILE_MOVE_ERROR'));
						return;
					}

/*
					echo "1 $value<br>";
*/
				// set the value so that we have all the files of this address, basically this chunk gets all the current files tied to this address, makes sure they are all nice integer values and adds this new file to the list; its probably a good idea to keep good track of waht data is here and of what type since it is used in db queries up above
				$value = implode(',',array_merge(array_map(function($val){return (int)$val;},array_filter(explode(',',$value))),array($ins->f_id)));
/*
echo "2<br>";
*/

				}

				// Unset all of these extra variables as to not confuse VM
/*
				JRequest::setVar('plgVmuserfieldVmShopperFieldFileUpload',null,'FILES');
				if ($this->shownote) JRequest::setVar('plgVmuserfieldVmShopperFieldFileUpload_note',null);
*/

				break;
		}

		return;

	}

	// this function fired everytime userfield data is saved; it is used to make sure userfields are valid; were using it perform file uploads and deletes
	public function plgVmOnBeforeUserfieldDataSave(&$valid,$id,&$data,$user) {

/*
		echo __METHOD__ .'<br />';
		echo "<pre>"; print_r($valid); echo "</pre>";
		echo "<pre>"; print_r($id); echo "</pre>";
		echo "<pre>"; print_r($data); echo "</pre>";
		echo "<pre>"; print_r($user); echo "</pre>";
		exit;
*/

		// Database!
		$db =& JFactory::getDBO();

		/*
		 *		Edit files / notes
		 */


		// Grab all the edits
		$edits = JRequest::getVar('plgVmuserfieldVmShopperFieldFileUpload_edit');

		// Check if we have any notes that need editing
		if ($this->showuploaded && count($edits)>0) {

			// Iterate edits
			foreach ($edits as $f_id => $edit) {

				$current = $db->setQuery("
											SELECT *
											FROM `#__plgVmuserfieldVmShopperFieldFileUpload_files`
											WHERE `f_id`=".$db->quote($f_id).";
										")->loadObject();

				if ( $this->allowdelete && isset($edit['delete']) ) {
					if (
						!JFile::delete(JPATH_SITE.DS.$this->uploadlocation.DS.$f_id.'_'.$current->filename) ||
						!$db->setQuery("DELETE FROM `#__plgVmuserfieldVmShopperFieldFileUpload_files` WHERE `f_id`=".$db->quote($f_id).";")->query()
					) {
						JError::raiseWarning(500,JText::_('PLG_VM_FILE_ERROR_PREFIX').JText::_('PLG_VM_FILE_DELETE_ERROR'));
					}
					return true;
				}

				if ($this->shownote) {

					$ins = (object)array(
										'f_id'			=> $f_id,
										'note'			=> $edit['note'],
									);
					$db->updateObject('#__plgVmuserfieldVmShopperFieldFileUpload_files',$ins,'f_id');
				}

			}

/*
			if ($this->shownote) JRequest::setVar('plgVmuserfieldVmShopperFieldFileUpload_edit',null);
*/
		}

		return true;
	}


	// A list of extensions and mimes for validation
	static $mimes = array(
							'pdf'	=> array(
												'application/pdf',
												'application/x-pdf',
												'application/vnd.pdf',
												'text/pdf',
											),
							'png'	=> array(
												'image/png',
											),
							'gif'	=> array(
												'image/gif'
											),
							'jpg'	=> array(
												'image/jpg',
												'image/jpeg',
												'image/pjpg',
												'image/pjpeg',
											),
							'jpeg'	=> array(
												'image/jpg',
												'image/jpeg',
												'image/pjpg',
												'image/pjpeg',
											),
							'bmp'	=> array(
												'image/bmp',
											),
							'wbmp'	=> array(
												'image/vnd.wap.wbmp',
											),
							'doc'	=> array(
												'application/msword',
											),
							'docx'	=> array(
												'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
											),
							'odt'	=> array(
												'application/vnd.oasis.opendocument.text',
												'application/x-vnd.oasis.opendocument.text',

											),
							'rtf'	=> array(
												'text/rtf',
											),
						);

}

