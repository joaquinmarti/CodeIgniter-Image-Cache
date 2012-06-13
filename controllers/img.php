<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Img extends MY_Controller {
  
  public $layout = '';

	public function _remap($dir, $params = array())
	{
	  $this->show_image($dir, $params);
	}
	
	public function show_image($dir, $params)
	{
	  // Memory adjust
	  ini_set("memory_limit", "64M");
    
    // Load model
	  $this->load->model('image_model','',TRUE);
		
		// Get params
		$settings['w'] = floor($this->input->get('w'));
		$settings['h'] = floor($this->input->get('h'));
		
		if ($settings['w'] > 0 || $settings['h'] > 0) {
		  // Image location
  		$image_location = $this->config->item('ic_upload_directory').'/'.$dir.((count($params) > 0) ? '/'.implode('/', $params) : '');

  		// Settings
  		$this->image_model->set_properties($image_location, $settings['w'], $settings['h'], $this->config->item('ic_resize_limit'));

  		// Get image
      $this->image_model->get_image();
		}
		else {
		  show_404();
		}
	}
	
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */