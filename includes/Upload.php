<?php
!defined('DS') ? define('DS', DIRECTORY_SEPARATOR) : null;

/**
 * Simple PHP upload class
 *
 */
class Upload {
	/**
	 * Default directory persmissions (destination dir)
	 */
	protected $default_permissions = 750;

	/**
	 * File post array
	 */
	protected array $files_post = [];

	/**
	 * Destination directory
	 */
	protected string $destination;
	
	/**
	 * Fileinfo
	 *
	 * @var object
	 */
	protected $finfo;

	/**
	 * Data about file
	 */
	public array $file = [];

	public array $file_post = [];
	
	/**
	 * Max. file size
	 */
	protected int $max_file_size;

	/**
	 * Allowed mime types
	 */
	protected array $mimes = [];

	/**
	 * External callback object
	 *
	 * @var obejct
	 */
	protected $external_callback_object;

	/**
	 * External callback methods
	 */
	protected $external_callback_methods = [];

	/**
	 * Temp path
	 */
	protected string $tmp_name;
	/**
	 * Validation errors
	 */
	protected array $validation_errors = [];

	/**
	 * Filename (new)
	 */
	protected $filename;
	
	/**
	 * Internal callbacks (filesize check, mime, etc)
	 */
	private $callbacks = [];

	/**
	 * Root dir
	 */
	protected string $root;

	/**
	 * Return upload object
	 *
	 * $destination		= 'path/to/your/file/destination/folder';
	 *
	 * @param string $destination
	 * @param string $root - if not provided, root directory will be used
	 */
	public static function factory(string $destination, string|bool $root = false): Upload
	{
		return new Upload($destination, $root);
	}

	/**
	 *  Define ROOT constant and set & create destination path.
	 */
	public function __construct(string $destination, string|bool $root = false)
	{
		if ($root) {
			$this->root = $root;
		} else {
			$this->root = __DIR__ . DS . '..' . DS;
		}
		// set & create destination path
		if (!$this->set_destination($destination)) {
			throw new Exception('Upload: Can\'t create destination. '.$this->root . $this->destination);
		}
		//create finfo object
		$this->finfo = new \finfo();
	}

	/**
	 * Set target filename
	 */
	public function set_filename(string $filename): void
	{
		$this->filename = $filename;
	}

	/**
	 * Check & Save file
	 *
	 * Return data about current upload
	 *
	 * @return array
	 */
	public function upload($filename = ''): array
	{
		if ($this->check()) {
			$this->save();
		}
		// return state data
		return $this->get_state();
	}

	/**
	 * Save file on server
	 *
	 * Return state data
	 *
	 * @return array
	 */
	public function save()
	{
		$this->save_file();
		return $this->get_state();
	}

	/**
	 * Validate file (execute callbacks)
	 *
	 * Returns true if validation successful
	 *
	 * @return bool
	 */
	public function check()
	{
		//execute callbacks (check filesize, mime, also external callbacks
		$this->validate();
		//add error messages
		$this->file['errors'] = $this->get_errors();
		//change file validation status
		$this->file['status'] = empty($this->validation_errors);
		return $this->file['status'];
	}

	/**
	 * Get current state data
	 *
	 * @return array
	 */
	public function get_state()
	{
		return $this->file;
	}

	/**
	 * Set validation error
	 */
	public function set_error(string $message)
	{
		$this->validation_errors[] = $message;
	}

	/**
	 * Return validation errors
	 */
	public function get_errors(): array
	{
		return $this->validation_errors;
	}

	/**
	 * Set external callback methods
	 *
	 * @param object $instance_of_callback_object
	 * @param array $callback_methods
	 */
	public function callbacks($instance_of_callback_object, $callback_methods)
	{
		if (empty($instance_of_callback_object)) {
			throw new Exception('Upload: $instance_of_callback_object can\'t be empty.');
		}
		if (!is_array($callback_methods)) {
			throw new Exception('Upload: $callback_methods data type need to be array.');
		}
		$this->external_callback_object	 = $instance_of_callback_object;
		$this->external_callback_methods = $callback_methods;
	}

	/**
	 * Set allowed mime types
	 */
	public function set_allowed_mime_types(array $mimes): void
	{
		$this->mimes		= $mimes;
		//if mime types is set -> set callback
		$this->callbacks[]	= 'check_mime_type';
	}

	/**
	 * Set max. file size in MB
	 */
	public function set_max_file_size(int $size): void
	{
		$this->max_file_size	= $size;
		//if max file size is set -> set callback
		$this->callbacks[]	= 'check_file_size';
	}

	/**
	 * Set File array to object.
	 */
	public function file(array $file): void
	{
		$this->set_file_array($file);
	}

		/**
	 * Save file on server
	 */
	protected function save_file(): void
	{
		//create & set new filename
		if(empty($this->filename)){
			$this->create_new_filename();
		}
		//set filename
		$this->file['filename']	= $this->filename;
		//set full path
		$this->file['full_path'] = $this->root . $this->destination . $this->filename;
		$this->file['path'] = $this->destination . $this->filename;
		$status = move_uploaded_file($this->tmp_name, $this->file['full_path']);
		//checks whether upload successful
		if (!$status) {
			throw new Exception('Upload: Can\'t upload file.');
		}
		//done
		$this->file['status']	= true;
	}

	/**
	 * Set data about file
	 */
	protected function set_file_data(): void
	{
		$file_size = $this->get_file_size();
		$this->file = [
			'status'				=> false,
			'destination'			=> $this->destination,
			'size_in_bytes'			=> $file_size,
			'size_in_mb'			=> $this->bytes_to_mb($file_size),
			'mime'					=> $this->get_file_mime(),
			'original_filename'		=> $this->file_post['name'],
			'tmp_name'				=> $this->file_post['tmp_name'],
			'post_data'				=> $this->file_post,
		];
	}

	/**
	 * Validate and execute callbacks
	 */
	protected function validate()
	{
		//get curent errors
		$errors = $this->get_errors();
		if (empty($errors)) {
			//set data about current file
			$this->set_file_data();
			//execute internal callbacks
			$this->execute_callbacks($this->callbacks, $this);
			//execute external callbacks
			$this->execute_callbacks($this->external_callback_methods, $this->external_callback_object);
		}
	}

	/**
	 * Execute callbacks
	 */
	protected function execute_callbacks($callbacks, $object)
	{
		foreach ($callbacks as $method) {
			$object->$method($this);
		}
	}

	/**
	 * File mime type validation callback
	 *
	 * @param obejct $object
	 */
	protected function check_mime_type($object): void
	{
		if (!empty($object->mimes)) {
			if (!in_array($object->file['mime'], $object->mimes)) {
				$object->set_error('Mime type not allowed.');
			}
		}
	}

	/**
	 * File size validation callback
	 *
	 * @param object $object
	 */
	protected function check_file_size($object): void
	{
		if (!empty($object->max_file_size)) {
			$file_size_in_mb = $this->bytes_to_mb($object->file['size_in_bytes']);
			if ($object->max_file_size <= $file_size_in_mb) {
				$object->set_error('File is too big.');
			}
		}
	}

	/**
	 * Set file array.
	 */
	protected function set_file_array(array $file): void
	{
		//checks whether file array is valid
		if (!$this->check_file_array($file)) {
			//file not selected or some bigger problems (broken files array)
			$this->set_error('Please select file.');
		}
		//set file data
		$this->file_post = $file;
		//set tmp path
		$this->tmp_name  = $file['tmp_name'];
	}

	/**
	 * Checks whether Files post array is valid.
	 */
	protected function check_file_array($file): bool
	{
		return isset($file['error'])
			&& !empty($file['name'])
			&& !empty($file['type'])
			&& !empty($file['tmp_name'])
			&& !empty($file['size']);
	}

	/**
	 * Get file mime type
	 *
	 * @return string
	 */
	protected function get_file_mime()
	{
		return $this->finfo->file($this->tmp_name, FILEINFO_MIME_TYPE);
	}

	/**
	 * Get file size.
	 */
	protected function get_file_size(): int|false
	{
		return filesize($this->tmp_name);
	}

	/**
	 * Set destination path (return TRUE on success)
	 */
	protected function set_destination(string $destination): bool
	{
		$this->destination = $destination . DIRECTORY_SEPARATOR;
		return $this->destination_exist() ? true : $this->create_destination();
	}

	/**
	 * Checks whether destination folder exists
	 */
	protected function destination_exist(): bool
	{
		return is_writable($this->root . $this->destination);
	}

	/**
	 * Create folder for a set destination with default permissions.
	 */
	protected function create_destination(): bool
	{
		return mkdir($this->root . $this->destination, $this->default_permissions, true);
	}

	/**
	 * Set unique filename.
	 */
	protected function create_new_filename(): string
	{
		$filename = sha1(mt_rand(1, 9999) . $this->destination . uniqid()) . time();
		$this->set_filename($filename);

		return $filename;
	}

	/**
	 * Convert bytes to mb.
	 */
	protected function bytes_to_mb(int $bytes): float
	{
		return round(($bytes / 1048576), 2);
	}
}
