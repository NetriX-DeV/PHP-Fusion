<?php
/*-------------------------------------------------------+
| PHP-Fusion Content Management System
| Copyright (C) PHP-Fusion Inc
| https://www.php-fusion.co.uk/
+--------------------------------------------------------+
| Filename: Photo/Admin.php
| Author: Frederick MC Chan (Photo Gallery Admin UI)
| Implementing my idea of centralized Interface for Gallery of all sorts
+--------------------------------------------------------+
| This program is released as free software under the
| Affero GPL license. You can redistribute it and/or
| modify it under the terms of this license which you
| can read by viewing the included agpl.txt or online
| at www.gnu.org/licenses/agpl.html. Removal of this
| copyright header is strictly prohibited without
| written permission from the original author(s).
+--------------------------------------------------------*/

namespace PHPFusion\Gallery;

class AdminUI {

	private $image_upload_dir = '';

	private $photo_db = '';
	private $photo_cat_db = '';


	private $upload_settings = array(
		'thumbnail_folder'=>'thumbs',
		'thumbnail' => 1,
		'thumbnail_w' => 150,
		'thumbnail_h' => 150,
		'thumbnail_suffix' =>'_t1',
		'thumbnail2'=>1,
		'thumbnail2_w' 	=> 400,
		'thumbnail2_h' 	=> 400,
		'thumbnail2_suffix' => '_t2',
		'delete_original' => 1,
		'max_width'		=>	1800,
		'max_height'	=>	1600,
		'max_byte'		=>	1500000, // 1.5 million bytes is 1.5mb
		'multiple' => 0,
		);
	private $enable_comments = false;
	private $enable_ratings = false;
	private $allow_comments = false;
	private $allow_ratings = false;

	private $enable_album = true;

	private $albums_per_page = 30;
	private $gallery_rows = 6;
	private $photos_per_page = 30;

	private $album_id = 0;
	private $photo_id = 0;
	private $rowstart = 0;
	private $action = '';
	private $album_max_order = 0;
	/**
	 * For best view: to recommend thumbnail_1 size set at 260px min.
	 * @var array
	 */
	private $album_data = array(
		'album_id' => 0,
		'album_title' => '',
		'album_description' => '',
		'album_thumb' => '',
		'album_user' => 0,
		'album_access' => 0,
		'album_order' => 0,
		'album_datestamp'=> 0,
		'album_language' => '',
	);

	private $photo_data = array(
		'photo_id' => 0,
		'album_id' => 0,
		'photo_title' => '',
		'photo_description' => '',
		'photo_keywords' => '',
		'photo_filename' => '',
		'photo_thumb1' => '',
		'photo_thumb2' => '',
		'photo_datestamp' => '',
		'photo_user' => 0,
		'photo_views' => 0,
		'photo_order' => 0,
		'photo_allow_comments' => 0,
		'photo_allow_ratings' => 0,
	);

	/**
	 * Install Gallery if Table does not exist
	 */
	private function Install_Gallery() {

		if (!db_exists($this->photo_cat_db) && $this->enable_album) {
			$result = dbquery("CREATE TABLE ".$this->photo_cat_db." (
				album_id mediumint(11) unsigned not null auto_increment,
				album_title varchar(100) not null default '',
				album_description text not null,
				album_thumb varchar(100) not null default '',
				album_user mediumint(11) unsigned not null default '0',
				album_access bigint(3) unsigned not null default '901',
				album_order smallint(5) unsigned not null default '0',
				album_datestamp int(10) unsigned not null default '0',
				album_language varchar(50) not null default '',
				PRIMARY KEY (album_id)
			) ENGINE=MyISAM DEFAULT CHARSET=UTF8 COLLATE=utf8_unicode_ci");
			if ($result) {
				notify($this->photo_cat_db.' SQL', 'Table created successfully.');
			}
		}

		if (!db_exists($this->photo_db)) {
			$result = dbquery("CREATE TABLE ".$this->photo_db." (
				photo_id mediumint(11) unsigned not null auto_increment,
				".($this->enable_album ? "album_id mediumint(11) unsigned not null default '0'," : '')."
				photo_title text varchar(100) not null default '',
				photo_description text not null,
				photo_keywords varchar(250) not null default '',
				photo_filename varchar(100) not null default '',
				photo_thumb1 varchar(100) not null default '',
				photo_thumb2 varchar(100) not null default '',
				photo_datestamp int(10) unsigned not null default '0',
				photo_user mediumint(11) unsigned not null default '0',
				photo_views int(10) unsigned not null default '0',
				photo_order smallint(5) unsigned not null default '0',
				".($this->enable_comments ? "photo_allow_comments tinyint(1) unsigned not null default '0'," : '')."
				".($this->enable_ratings ? "photo_allow_ratings tinyint(1) unsigned not null default '0'," : '')."
				PRIMARY KEY (photo_id)
			) ENGINE=MyISAM DEFAULT CHARSET=UTF8 COLLATE=utf8_unicode_ci");
			if ($result) {
				notify($this->photo_db.' SQL', 'Table created successfully.');
			}
		}
	}


	public function __construct() {
		// Using GET to set the vars so it can be accessed in the entire class
		$this->album_id = isset($_GET['album_id']) && isnum($_GET['album_id']) ? $_GET['album_id'] : 0;
		$this->photo_id = isset($_GET['photo_id']) && isnum($_GET['photo_id']) ? $_GET['photo_id'] : 0;
		$this->rowstart = isset($_GET['rowstart']) && isnum($_GET['rowstart']) ? $_GET['rowstart'] : 0;
		$this->action = isset($_GET['action']) && $_GET['action'] ? $_GET['action'] : '';
		$this->album_data['album_language'] = LANGUAGE;
		$this->album_data['album_datestamp'] = time();

	}

	/**
	 * @param boolean $allow_comments
	 */
	public function setAllowComments($allow_comments) {
		$this->allow_comments = $allow_comments;
	}

	/**
	 * @param boolean $allow_ratings
	 */
	public function setAllowRatings($allow_ratings) {
		$this->allow_ratings = $allow_ratings;
	}

	/**
	 * @param boolean $enable_comments
	 */
	public function setEnableComments($enable_comments) {
		$this->enable_comments = $enable_comments;
	}

	/**
	 * @param boolean $enable_ratings
	 */
	public function setEnableRatings($enable_ratings) {
		$this->enable_ratings = $enable_ratings;
	}

	/**
	 * @param boolean $enable_album
	 */
	public function setEnableAlbum($enable_album) {
		$this->enable_album = $enable_album;
	}

	/**
	 * @param array $upload_settings
	 */
	public function setUploadSettings(array $upload_settings) {
		$this->upload_settings = $upload_settings;
	}

	/**
	 * @param string $image_upload_dir
	 */
	public function setImageUploadDir($image_upload_dir) {
		$this->image_upload_dir = $image_upload_dir;
	}

	/**
	 * @param string $photo_cat_db
	 */
	public function setPhotoCatDb($photo_cat_db) {
		$this->photo_cat_db = $photo_cat_db;
	}

	/**
	 * @param string $photo_db
	 */
	public function setPhotoDb($photo_db) {
		$this->photo_db = $photo_db;
	}

	/**
	 * Get Album Data - how to get
	 * @return array
	 */
	public function get_album($album_id = 0) {
		if (isnum($album_id)) {
			return dbarray(dbquery("SELECT * FROM ".$this->photo_cat_db." WHERE album_id='".intval($album_id)."'"));
		}
		return array();
	}

	public function get_photo($photo_id = 0) {
		if (isnum($photo_id)) {
			return dbarray(dbquery("SELECT * FROM ".$this->photo_db." WHERE photo_id='".intval($photo_id)."'"));
		}
		return array();
	}


	public function boot() {
		//self::Install_Gallery();
		define("SAFEMODE", @ini_get("safe_mode") ? TRUE : FALSE);
		define("GALLERY_PHOTO_DIR", $this->image_upload_dir.(!SAFEMODE ? "album_".$this->album_id."/" : ""));
		// set album max order
		$this->album_max_order = dbresult(dbquery("SELECT MAX(album_order) FROM ".$this->photo_cat_db." WHERE album_language='".LANGUAGE."'"), 0)+1;
		self::delete_album_thumb();
		self::set_albumDB();
		self::set_photoDB();
		self::display_gallery_filters();
		self::display_gallery();
	}

	private function validate_album($album_id) {
		if (isnum($album_id)) {
			return dbcount("('album_id')", $this->photo_cat_db, "album_id='".intval($album_id)."'");
		}
		return false;
	}

	private function validate_photo($photo_id) {
		if (isnum($photo_id)) {
			return dbcount("('photo_id')", $this->photo_db, "photo_id='".intval($photo_id)."'");
		}
		return false;
	}

	private function set_albumDB() {
		global $userdata;
		if (isset($_POST['upload_album'])) {
			$this->album_data =	array(
				'album_id' => isset($_POST['album_id']) ? form_sanitizer($_POST['album_id'], '', 'album_id') : 0,
				'album_title' => isset($_POST['album_title']) ? form_sanitizer($_POST['album_title'], '', 'album_title') : $this->album_data['album_title'],
				'album_description' => isset($_POST['album_description']) ? form_sanitizer($_POST['album_description'], '', 'album_description') : $this->album_data['album_description'],
				'album_user' => $userdata['user_id'],
				'album_access' => isset($_POST['album_title']) ? form_sanitizer($_POST['album_access'], 0, 'album_access') : $this->album_data['album_access'],
				'album_order' => isset($_POST['album_order']) ? form_sanitizer($_POST['album_order'], 0, 'album_order') : $this->album_data['album_order'],
				'album_datestamp'=> time(),
				'album_language' => isset($_POST['album_language']) ? form_sanitizer($_POST['album_language'], '', 'album_language') : $this->album_data['album_language'],
			);

			if (!$this->album_data['album_order']) $this->album_data['album_order'] = dbresult(dbquery("SELECT MAX(album_order) FROM ".$this->photo_cat_db." WHERE album_language='".LANGUAGE."'"), 0)+1;

			$upload_result = form_sanitizer($_FILES['album_file'], '', 'album_file');

			/** Note: Ensure your hidden field return does not bear the same input name as the fileinput name else form sanitizer will not sanitize properely as both bears same identifier */
			$this->album_data['album_thumb'] = form_sanitizer($_POST['album_hfile'], '', 'album_hfile');

			if (isset($upload_result['error']) && $upload_result['error'] !=='0') {
				// upload success
				$this->album_data['album_thumb'] = $upload_result['thumb1_name'];
				// only exist in new upload
				$image_name = $upload_result['image_name'];
				$thumb1_name = $upload_result['thumb1_name'];
				$thumb2_name = $upload_result['thumb2_name'];
			}

			/**
			 * Photo_data sourced from 2 place. If Album history exist, photo_data will follow sql. if not, follow the OOP field construct we initialized.
			 * Either way, there is no need to !empty() or isset() check.
			 */
			$album_history = self::get_album($this->album_data['album_id']);
			if (!empty($album_history)) {
				$thumb_photo = dbquery("SELECT photo_id, album_id, photo_filename, photo_thumb1, photo_thumb2, photo_views, photo_order, photo_allow_comments, photo_allow_ratings FROM ".$this->photo_db." WHERE photo_thumb1='".$album_history['album_thumb']."'");
				if (dbrows($thumb_photo)>0) {
					$this->photo_data = dbarray($thumb_photo); // use back old records
				}
				// ok. now we need to delete the old picture set if changed
				if ($this->album_data['album_thumb'] !== $album_history['album_thumb']) {
					@unlink(rtrim($this->image_upload_dir, '/').'/'.rtrim($this->upload_settings['thumbnail_folder'], '/').'/'.$this->photo_data['photo_thumb1']);
					@unlink(rtrim($this->image_upload_dir, '/').'/'.rtrim($this->upload_settings['thumbnail_folder'], '/').'/'.$this->photo_data['photo_thumb2']);
					@unlink(rtrim($this->image_upload_dir, '/').'/'.$this->photo_data['photo_filename']);
				}
			}

			/**
			 * Recompile New Photo Data Output.
			 * 3 Elements in play for key - filename, thumb1 and thumb2.
			 * a. $image_name exist when we upload a new file. (First precedence).
			 * b. $this->photo_data will be `overwritten` if we have an album thumb change.
			 * c. If both a & b does not exist, it will follow the blank defaults.
			 */
			$this->photo_data = array(
				'photo_id' => $this->photo_data['photo_id'],
				'album_id' => $this->photo_data['album_id'],
				'photo_title' => $this->album_data['album_title'],
				'photo_description' => $this->album_data['album_description'],
				'photo_keywords' => $this->album_data['album_title'],
				'photo_filename' => isset($image_name) ? $image_name : $this->photo_data['photo_filename'],
				'photo_thumb1' => isset($thumb1_name) ? $thumb1_name : $this->photo_data['photo_thumb1'],
				'photo_thumb2' => isset($thumb2_name) ? $thumb2_name : $this->photo_data['photo_thumb2'],
				'photo_datestamp' => $this->album_data['album_datestamp'],
				'photo_user' => $userdata['user_id'],
				'photo_views' => $this->photo_data['photo_views'],
				'photo_order' => $this->photo_data['photo_order'],
				'photo_allow_comments' => $this->photo_data['photo_allow_comments'],
				'photo_allow_ratings' => $this->photo_data['photo_allow_ratings'],
			);

			//if (!$this->album_data['album_thumb']) redirect(clean_request('file_error', array('gallery_edit', 'gallery_type'), false));

			if ($this->album_data['album_id'] && self::validate_album($this->album_data['album_id'])) {
				$result = dbquery_order($this->photo_cat_db, $this->album_data['album_order'], 'album_order', $this->album_data['album_id'], 'album_id',  false, false, 1, 'album_language', 'update');
				if ($result) {
					dbquery_insert($this->photo_cat_db, $this->album_data, 'update');
					if (!empty($this->photo_data) && self::validate_photo($this->photo_data['photo_id'])) {
						dbquery_insert($this->photo_db, $this->photo_data, 'update');
					}
					if (!defined('FUSION_NULL')) redirect(clean_request('status=au', array('gallery_edit', 'gallery_type'), false));
				}
			} else {
				// new saves
				$result = dbquery_order($this->photo_cat_db, $this->album_data['album_order'], 'album_order', false, false, false, false, 1, 'album_language', 'save');
				if ($result) {
					dbquery_insert($this->photo_cat_db, $this->album_data, 'save');
					$this->album_data['album_id'] = dblastid();
					if (!empty($photo_data) && $this->album_data['album_id']) {
						if (!$this->photo_data['photo_order']) $this->photo_data['photo_order'] = $this->photo_data['photo_order'] = dbresult(dbquery("SELECT MAX(photo_order) FROM ".$this->photo_db." WHERE album_id='".intval($this->album_data['album_id'])."'"), 0)+1;
						$this->photo_data['album_id'] = $this->album_data['album_id'];
						$result = dbquery_order($this->photo_db, $this->photo_data['photo_order'], 'photo_order', false, false, $this->photo_data['album_id'], 'album_id', false, false, 'save');
						if ($result) {
							dbquery_insert($this->photo_db, $this->photo_data, 'save');
						}
					}
					if (!defined('FUSION_NULL')) redirect(clean_request('status=an', array('gallery_edit', 'gallery_type'), false));
				}
			}
		}
	}

	private function set_photoDB() {
		global $userdata;
		if (isset($_POST['upload_photo'])) {
			$this->photo_data =	array(
				'photo_id' => isset($_POST['photo_id']) ? form_sanitizer($_POST['photo_id'], '', 'photo_id') : 0,
				'album_id' => isset($_POST['album_id']) ? form_sanitizer($_POST['album_id'], '', 'album_id') : 0,
				'photo_title' => isset($_POST['photo_title']) ? form_sanitizer($_POST['photo_title'], '', 'photo_title') : $this->photo_data['photo_title'],
				'photo_description' => isset($_POST['photo_description']) ? form_sanitizer($_POST['photo_description'], '', 'photo_description') : $this->photo_data['photo_description'],
				'photo_keywords' => isset($_POST['photo_keywords']) ? form_sanitizer($_POST['photo_keywords'], '', 'photo_keywords') : $this->photo_data['photo_keywords'],
				'photo_allow_comments' => isset($_POST['photo_allow_comments']) ? 1 : $this->photo_data['photo_comments'],
				'photo_allow_ratings' => isset($_POST['photo_allow_ratings']) ? 1 : $this->photo_data['photo_ratings'],
				'photo_user' => $userdata['user_id'],
				'photo_order' => isset($_POST['photo_order']) ? form_sanitizer($_POST['photo_order'], 0, 'photo_order') : $this->photo_data['photo_order'],
				'photo_datestamp'=> time(),
			);

			$upload_result = form_sanitizer($_FILES['photo_file'], '', 'photo_file');
			$this->photo_data['photo_filename'] = form_sanitizer($_POST['photo_hfile'], '', 'photo_hfile');
			$this->photo_data['photo_thumb1'] = form_sanitizer($_POST['photo_hthumb1'], '', 'photo_hthumb1');
			$this->photo_data['photo_thumb2'] = form_sanitizer($_POST['photo_hthumb2'], '', 'photo_hthumb2');
			if (isset($upload_result['error']) && $upload_result['error'] !=='0') {
				// upload success
				$this->photo_data['photo_filename'] = $upload_result['image_name'];
				$this->photo_data['photo_thumb1'] = $upload_result['thumb1_name'];
				$this->photo_data['photo_thumb2'] = $upload_result['thumb2_name'];
			}
			// photo ordering.
			if (!$this->photo_data['photo_order']) $this->photo_data['photo_order'] = dbresult(dbquery("SELECT MAX(photo_order) FROM ".$this->photo_db." WHERE album_id='".$this->photo_data['album_id']."'"), 0)+1;
			// fetch old data and compare if changed photo.
			$photo_history = self::get_photo($this->photo_data['photo_id']);
			if (!empty($photo_history)) {
				// ok. now we need to delete the old picture set if changed
				if ($this->photo_data['photo_filename'] !== $photo_history['photo_filename']) {
					@unlink(rtrim($this->image_upload_dir, '/').'/'.rtrim($this->upload_settings['thumbnail_folder'], '/').'/'.$photo_history['photo_thumb1']);
					@unlink(rtrim($this->image_upload_dir, '/').'/'.rtrim($this->upload_settings['thumbnail_folder'], '/').'/'.$photo_history['photo_thumb2']);
					@unlink(rtrim($this->image_upload_dir, '/').'/'.$photo_history['photo_filename']);
				}
			}
			if ($this->photo_data['photo_id'] && self::validate_photo($this->photo_data['photo_id'])) {
				$result = dbquery_order($this->photo_db, $this->photo_data['photo_order'], 'photo_order', $this->photo_data['photo_id'], 'photo_id',  $this->photo_data['album_id'], 'album_id', false, false, 'update');
				if ($result) {
					dbquery_insert($this->photo_db, $this->photo_data, 'update');
					if (!defined('FUSION_NULL')) redirect(clean_request('status=pu', array('gallery_edit', 'gallery_type'), false));
				}
			} else {
				// new saves
				$result = dbquery_order($this->photo_db, $this->photo_data['photo_order'], 'photo_order', false, false,  $this->photo_data['album_id'], 'album_id', false, false, 'save');
				if ($result) {
					dbquery_insert($this->photo_db, $this->photo_data, 'save');
					if (!defined('FUSION_NULL')) redirect(clean_request('status=pn', array('gallery_edit', 'gallery_type'), false));
				}
			}
		}
	}


	/* This is way too unfriendly approach - Not going to be used */
	private function delete_album_thumb() {
		if (isset($_POST['delete_album_thumb'])) {
			$album_id = form_sanitizer($_POST['album_id'], '', 'album_id');
			// delete the thumbnail and the existing picture record
			if (self::validate_album($album_id)) {
				$album_data = self::get_album($album_id);
				@unlink(rtrim($this->image_upload_dir, '/').'/'.rtrim($this->upload_settings['thumbnail_folder'], '/').'/'.$album_data['album_thumb']);
				dbquery("UPDATE ".$this->photo_cat_db." SET album_thumb='' WHERE album_id='".intval($album_id)."'");
				$result = dbquery("SELECT photo_filename, photo_thumb1, photo_thumb2 FROM ".$this->photo_db." WHERE photo_thumb1 = '".$album_data['album_thumb']."'");
				if (dbrows($result)>0) {
					$photo_data = dbarray($result);
					@unlink(rtrim($this->image_upload_dir, '/').'/'.rtrim($this->upload_settings['thumbnail_folder'], '/').'/'.$photo_data['photo_thumb']);
					@unlink(rtrim($this->image_upload_dir, '/').'/'.rtrim($this->upload_settings['thumbnail_folder'], '/').'/'.$photo_data['photo_thumb2']);
					@unlink(rtrim($this->image_upload_dir, '/').'/'.rtrim($this->upload_settings['thumbnail_folder'], '/').'/'.$photo_data['photo_filename']);
					dbquery_insert($this->photo_db, $photo_data, 'delete');
				}
			}
		}
	}

	/**
	 * Ui design regarding photo dropping. no dropping. can only be replaced.
	 */
	private function display_gallery_filters() {
		global $locale;
		$list = array();
		foreach(getusergroups() as $groups) {
			$list[$groups[0]] = $groups[1];
		}
		$album_list = array();
		$album_edit = 0;
		$photo_edit = 0;
		if (isset($_GET['gallery_edit']) && isnum($_GET['gallery_edit']) && isset($_GET['gallery_type']) && isnum($_GET['gallery_type'])) {
			// have type and edit
			switch($_GET['gallery_type']) {
				case '1': // album type
					if (self::validate_album($_GET['gallery_edit'])) {
						$this->album_data = self::get_album($_GET['gallery_edit']);
						$album_edit = 1;
						add_to_jquery("$('#add_album-Modal').modal('show');");
					}
					break;
				case '2': // picture type
					if (self::validate_photo($_GET['gallery_edit'])) {
						$this->photo_data = self::get_photo($_GET['gallery_edit']);
						$photo_edit = 1;
						// poll album list
						$result = dbquery("SELECT album_id, album_title FROM ".$this->photo_cat_db." ORDER BY album_order ASC");
						if (dbrows($result)>0) {
							while ($data = dbarray($result)) {
								$album_list[$data['album_id']] = $data['album_title'];
							}
						}
						add_to_jquery("$('#add_photo-Modal').modal('show');");
					}
			}

		}

		$this->upload_settings += array('inline'=>1, 'type'=>'image', 'required'=> !$album_edit ? 1 : 0);

		echo "<div class='m-t-10 m-b-20'>\n";
		echo form_button('Create Albums', 'add_album', 'add_album', 'add_album', array('class'=>'btn-primary btn-sm m-r-10', 'icon'=>'fa fa-image'));
		echo form_button('Add Photos', 'add_photo', 'add_photo', 'add_photo', array('class'=>'btn-sm btn-default', 'icon'=>'fa fa-camera'));
		echo "</div>\n";

		echo openmodal('add_album', $album_edit ? 'Edit Gallery Album' : 'Create Gallery Album', array('button_id'=>'add_album'));
		echo openform('albumform', 'albumform', 'post', FUSION_REQUEST, array('downtime'=>1, 'enctype'=>1));
		if ($album_edit) {
			echo "<div class='row'>\n<div class='col-xs-12 col-sm-9'>\n";
		}
		echo form_text('Title', 'album_title', 'album_title', $this->album_data['album_title'], array('placeholder'=>'Name of Gallery', 'inline'=>1, 'required'=>1));
		echo form_textarea('Description', 'album_description', 'album_description', $this->album_data['album_description'], array('placeholder'=>'What is your Gallery about?', 'inline'=>1));
		echo form_fileinput('Upload Picture', 'album_file', 'album_file', $this->image_upload_dir, '', $this->upload_settings);
		echo form_hidden('', 'album_hfile', 'album_hfile', $this->album_data['album_thumb']);
		echo form_select('Access', 'album_access', 'album_access', $list, $this->album_data['album_access'], array('inline'=>1));
		echo form_hidden('', 'album_id', 'album_id', $this->album_data['album_id']);
		echo form_select('Language', 'album_language', 'album_language', fusion_get_enabled_languages(), $this->album_data['album_language'], array('inline'=>1));
		echo form_select('Album Order', 'album_order', 'album_order', range(0,$this->album_max_order), $this->album_data['album_order'], array('inline'=>1, 'width'=>'150px')); // 0 picture, 1. ok.
		echo form_button($locale['save_changes'], 'upload_album', 'upload_album', 'upload_album', array('class'=>'btn-success'));
		if ($album_edit) {
			echo "</div>\n<div class='col-xs-12 col-sm-3 text-center'>\n";
			echo "<div class='well'>\n";
			$img_path = rtrim($this->image_upload_dir, '/')."/".rtrim($this->upload_settings['thumbnail_folder'], '/')."/".$this->album_data['album_thumb'];
			echo "<img class='img-responsive' style='margin:0 auto;' src='$img_path' alt='".$this->album_data['album_title']."'/>\n";
			echo "</div>\n";
			echo "</div>\n</div>\n";
		}
		echo closeform();
		echo closemodal();

		echo openmodal('add_photo', $photo_edit ? 'Edit a Photo' : 'Upload A Photo', array('button_id'=>'add_photo'));
		echo openform('photoform', 'photoform', 'post', FUSION_REQUEST, array('downtime'=>1, 'enctype'=>1));
		if ($photo_edit) {
			echo "<div class='row'>\n<div class='col-xs-12 col-sm-9'>\n";
		}
		echo form_text('Photo Title', 'photo_title', 'photo_title', $this->photo_data['photo_title'], array('placeholder'=>'Name of Gallery', 'inline'=>1));
		echo form_select('Photo Album', 'album_id', 'album_ids', $album_list, $this->photo_data['album_id'], array('inline'=>1));
		echo form_hidden('', 'photo_id', 'photo_id', $this->photo_data['photo_id']);
		echo form_fileinput('Upload Picture', 'photo_file', 'photo_file', $this->image_upload_dir, '', $this->upload_settings);
		echo form_hidden('', 'photo_hfile', 'photo_hfile', $this->photo_data['photo_filename']);
		echo form_hidden('', 'photo_hthumb1', 'photo_hthumb1', $this->photo_data['photo_thumb1']);
		echo form_hidden('', 'photo_hthumb2', 'photo_hthumb2', $this->photo_data['photo_thumb2']);
		echo form_select('Photo Keywords', 'photo_keywords', 'photo_keywords', array(), $this->photo_data['photo_keywords'], array('placeholder'=>'Keywords', 'inline'=>1, 'multiple'=>1, 'width'=>'100%', 'tags'=>1));
		echo form_textarea('Photo Description', 'photo_description', 'photo_description', $this->photo_data['photo_description'], array('placeholder'=>'What is your Gallery about?', 'inline'=>1));
		echo form_select('Allow Comments', 'photo_allow_comments', 'photo_allow_comments', array($locale['yes'], $locale['no']), $this->photo_data['photo_allow_comments'], array('inline'=>1));
		echo form_select('Allow Ratings', 'photo_allow_ratings', 'photo_allow_ratings', array($locale['yes'], $locale['no']), $this->photo_data['photo_allow_ratings'], array('inline'=>1));
		echo form_button('Upload Photo', 'upload_photo', 'upload_photo', 'upload_photo', array('class'=>'btn-primary'));
		if ($photo_edit) {
			echo "</div>\n<div class='col-xs-12 col-sm-3 text-center'>\n";
			echo "<div class='well'>\n";
			$img_path = rtrim($this->image_upload_dir, '/')."/".rtrim($this->upload_settings['thumbnail_folder'], '/')."/".$this->photo_data['photo_thumb1'];
			echo "<img class='img-responsive' style='margin:0 auto;' src='$img_path' alt='".$this->photo_data['photo_title']."'/>\n";
			echo "</div>\n";
			echo "</div>\n</div>\n";
		}
		echo closeform();
		echo closemodal();
	}

	private function display_gallery() {
		global $locale;
		self::gallery_css();

		$list = array();
		$rows = isset($_GET['gallery']) && isnum($_GET['gallery']) ? dbcount("('photo_id')", $this->photo_db, "album_id='".intval($_GET['gallery'])."'") : dbcount("('album_id')", $this->photo_cat_db);
		$multiplier = $rows > $this->albums_per_page ? $this->albums_per_page : $rows;
		$max_items_per_col = $multiplier/$this->gallery_rows;
		if ($rows) {
			if (isset($_GET['gallery']) && isnum($_GET['gallery'])) {
				$result = dbquery("SELECT photos.*, photos.photo_user as user_id, album.*, album.album_id as gallery_id, u.user_name, u.user_status, u.user_avatar
				FROM ".$this->photo_db." photos
				INNER JOIN ".$this->photo_cat_db." album on photos.album_id = album.album_id
				INNER JOIN ".DB_USERS." u on u.user_id = photos.photo_user
				WHERE ".groupaccess('album.album_access')." AND album_language='".LANGUAGE."' AND photos.album_id = '".intval($_GET['gallery'])."'
				ORDER BY photos.photo_order ASC, photos.photo_datestamp DESC LIMIT ".$this->rowstart.", $this->albums_per_page");
			} else {
				$result = dbquery("SELECT album.*, album.album_user as user_id, u.user_name, u.user_status, u.user_avatar
				FROM ".$this->photo_cat_db." album
				INNER JOIN ".DB_USERS." u on u.user_id=album.album_user
				WHERE ".groupaccess('album.album_access')." AND album_language='".LANGUAGE."'
				ORDER BY album.album_order ASC, album.album_datestamp DESC LIMIT ".$this->rowstart.", $this->albums_per_page");
			}
			if (dbrows($result)>0) {
				$i = 1; $count = 1;
				$list = array();
				while($data = dbarray($result)) {
					$list[$i][$data['album_id']] = $data;
					if ($count >= $max_items_per_col) {
						$i++;
						$count = 1;
					}
					$count++;
				}
			}
		}
		$albums = $list;
		$container_span = 12/$this->gallery_rows;
		if (!empty($albums)) {
		?>

		<div class='row'>
			<?php for ($i=1; $i<=$this->gallery_rows; $i++) { // construct columns ?>
				<div class='col-xs-12 col-sm-<?php echo $container_span ?>'>
					<?php
					if (!empty($albums[$i])) {
						foreach($albums[$i] as $albumData) {
							self::gallery_album($albumData, isset($_GET['gallery']) && isnum($_GET['gallery']) ? 2 : 1);
						}
					}
					?>
				</div>
			<?php } ?>
		</div>
		<?php
		} else {
			echo "<div class='well text-center'>There are no photos in this album</div>";
		}
	}

	private function gallery_css() {
		add_to_head("
		<style>
		.gallery_album {
			-webkit-border-radius: 6px;
			-moz-border-radius: 6px;
			border-radius: 6px;
		}
		.gallery_album > .gallery_actions > .image_container > img {
			width: 100%;
		}
		.gallery_album > .gallery_actions > .gallery_overlay {
			background-color: rgb(0, 0, 0);
			border-radius: 6px 6px 0px 0px;
			position: absolute;
			opacity: 0;
			width:100%;
			height:100%;
			transition: opacity 0.04s linear 0s;
			cursor: zoom-in;
		}
		.gallery_album > .gallery_actions {
			position: relative;
			bottom: 0px;
			left: 0px;
			right: 0px;
			top: 0px;
			overflow: hidden;
			-webkit-border-radius: 6px 6px 0 0;
			-moz-border-radius: 6px 6px 0 0;
			border-radius: 6px 6px 0 0;
		}
		.gallery_album > .gallery_actions:hover {
			opacity: 1;
		}
		 .gallery_album > .gallery_actions:hover > .gallery_overlay {
			opacity: 0.25;
		}
		.gallery_album > .gallery_actions > .gallery_buttons {
			position: absolute;
			top: 8px;
			left: 8px;
			opacity: 0;
		}
		.gallery_album > .gallery_actions > .gallery_writer {
			position: absolute;
			right: 8px;
			top: 8px;
			opacity: 0;
		}
		.gallery_album > .gallery_actions:hover > .gallery_buttons, .gallery_album > .gallery_actions:hover > .gallery_writer {
			opacity: 1;
		}
		.gallery_album .gallery_profile_link {
			line-height: 115%;
		}
		</style>
		");
	}

	private function gallery_album(array $data = array(), $type = 1) {
		global $userdata;
		$request = $type == 1 ? clean_request("gallery=".$data['album_id'], array('gallery'), false) : clean_request('photo='.$data['photo_id'], array('photo'), false);
		?>

		<div class='gallery_album panel panel-default'>
			<div class='gallery_actions'>
				<a href='<?php echo $request ?>' class='gallery_overlay'></a>
				<?php if (($this->enable_comments || $this->enable_ratings) && $type == 2) {?>
				<div class='gallery_buttons'>
					<a class='btn button btn-sm btn-primary' href='<?php  ?>'><i class='fa fa-star-o'></i></a>
					<a class='btn button btn-sm btn-success' href='<?php ?>'><i class='fa fa-comment'></i></a>
				</div>
				<?php } ?>
				<div class='gallery_writer pull-right'>
					<a class='btn button btn-sm btn-default' href='<?php echo clean_request("&amp;gallery_edit=".($type == 1 ? $data['album_id'] : $data['photo_id'])."&amp;gallery_type=$type", array('gallery_edit', 'gallery_type'), false) ?>'>
						<i class='fa fa-pencil'></i></a>
				</div>
				<div class='image_container'>
					<?php if ($type == 1) {
						echo "<img src='".$this->image_upload_dir.$this->upload_settings['thumbnail_folder']."/".$data['album_thumb']."' alt=''/>";
					} elseif ($type == 2) {
						echo "<img src='".$this->image_upload_dir.$this->upload_settings['thumbnail_folder']."/".$data['photo_thumb1']."' alt=''/>";
					} ?>
				</div>
			</div>
			<div class='panel-body'>
				<span class='gallery_title'><?php echo $type == 1 ? $data['album_title'] : $data['photo_title'] ?></span><br/>
						<span class='text-smaller text-lighter'>
							<span class='mid-opacity m-r-10'><i class='fa fa-comment'></i> 6</span>
							<span class='mid-opacity m-r-10'><i class='fa fa-star'></i> 6/10</span>
						</span>
			</div>
			<div class='panel-footer text-smaller clearfix'>
				<div class='pull-left m-r-5'>
					<?php
					echo display_avatar($data, '30px', '', '', 'img-rounded') ?>
				</div>
				<div class='gallery_profile_link overflow-hide text-lighter'>
					<?php echo profile_link($data['user_id'], $data['user_name'], $data['user_status']) ?>
					<span class='text-lighter display-block'><i class='fa fa-clock-o m-r-10'></i><?php echo showdate('shortdate', $type == 1 ? $data['album_datestamp'] : $data['photo_datestamp']) ?></span>
				</div>
			</div>
		</div>
		<?php
	}


}

