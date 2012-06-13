<?php
class Image_model extends CI_Model
{
  
  private $config = array(
    'image_library' => 'gd2',
    'create_thumb' => FALSE,
    'maintain_ratio' => TRUE,
    'dynamic_output' => FALSE
  );

  public function set_properties($image_location, $width = 0, $height = 0, $resize_limit)
  {
    $this->width = $width;
    $this->height = $height;
    $this->resize_limit = $resize_limit;
    
    $this->config['width'] = $this->width;
    $this->config['height'] = $this->height;

    /* Files and directories */
    $this->base_path = realpath(BASEPATH.'..');
    $this->uploads_dir = dirname($image_location);
    $this->base_name = substr($image_location, strrpos($image_location, '/') + 1, strrpos($image_location, '.') - strrpos($image_location, '/') - 1);
    $this->file_type = substr($image_location, strrpos($image_location, '.') + 1);
    $this->new_file_name = $this->base_name.
    (($this->config['width']) ? '_w'.$this->config['width'] : '').
    (($this->config['height']) ? '_h'.$this->config['height'] : '').
    '.'.$this->file_type;
    
    // Cache folder
    $this->cache_folder = $this->base_name.'_'.$this->file_type.'_cache';
    
    // Source image path
    $this->config['source_image'] = $this->base_path.$image_location;
    
    // New image path
    $this->cache_image = $this->base_path.$this->uploads_dir.
    '/'.$this->cache_folder.
    '/'.$this->new_file_name;
    
    // Cache directory path
    $this->cache_dir = $this->base_path.$this->uploads_dir.'/'.$this->cache_folder;

  }
  
  public function get_image()
  {

    if(file_exists($this->cache_image)) // If image is cached, show it
    {
        $this->show_img($this->cache_image);
    }
    else // If it's not cahced, generate it
    {
      
      // Control if the original image exists
      if (!file_exists($this->config['source_image']))
      {
        show_404();
      }
      
      if(!is_dir($this->cache_dir)) // Create if it doesn't exist
      {
        mkdir($this->cache_dir, 0777);
      }
      else {
        // Control resize limit
        $scandir = array_diff(scandir($this->cache_dir), array('..', '.', '.DS_Store'));
        if (count($scandir) >= $this->resize_limit) {
          show_404();
          return false;
        }
      }
      
      // Load libraries
      $this->load->library('image_lib');
      $this->load->helper('file');
      
      // Generate image
      $this->config['new_image'] = $this->cache_image;
      
      // Get original sizes
      $original_size = getimagesize($this->config['source_image']);
      
      if ($this->config['width'] > 0 && $this->config['height'] > 0) // If it has the two parameters, first resize depending of the ratio and then crop
      {
        $original_ratio = $original_size[0] / $original_size[1];
        $new_ratio = $this->config['width'] / $this->config['height'];
        
        if ($new_ratio > $original_ratio) { // If new ratio is bigger than original ratio, then resize the width and crop the height
          $this->config['height'] = $original_size[1] * $this->config['width'] / $original_size[0];
          $this->config['master_dim'] = 'width';          
          
          $this->image_lib->initialize($this->config);
          $this->image_lib->resize();
          $this->image_lib->clear();
          
          $original_size_2 = getimagesize($this->cache_image);
          $this->config['maintain_ratio'] = FALSE;
          $this->config['width'] = $this->width;
          $this->config['height'] = $this->height;
          $this->config['source_image'] = $this->cache_image;
          $this->config['new_image'] = $this->cache_image;
          $this->config['x_axis'] = 0; // Pixels from the left
          $this->config['y_axis'] = ($original_size_2[1] - $this->height) / 2; // Pixels from the top

          $this->image_lib->initialize($this->config);
          $this->image_lib->crop();
        }
        else { // Else (If new ratio is smaller than original ratio, or is same ratio), then resize the height and crop the width
          $this->config['width'] = $original_size[0] * $this->config['height'] / $original_size[1];
          $this->config['master_dim'] = 'height';
          $this->image_lib->initialize($this->config);
          $this->image_lib->resize();
          $this->image_lib->clear();
          
          $original_size_2 = getimagesize($this->cache_image);
          $this->config['maintain_ratio'] = FALSE;
          $this->config['width'] = $this->width;
          $this->config['height'] = $this->height;
          $this->config['source_image'] = $this->cache_image;
          $this->config['new_image'] = $this->cache_image;
          $this->config['x_axis'] = ($original_size_2[0] - $this->width) / 2; // Pixels from the left
          $this->config['y_axis'] = 0; // Pixels from the top

          $this->image_lib->initialize($this->config);
          $this->image_lib->crop();
          
        }
      }
      else // If only one parameter, just resize
      {
        if ($this->config['width'] > 0 && $this->config['height'] == 0) { // If width is setted, figure out the height
          $this->config['height'] = $original_size[1] * $this->config['width'] / $original_size[0];
          $this->config['master_dim'] = 'width';
        }
        elseif ($this->config['width'] == 0 && $this->config['height'] > 0) { // If height is setted, figure out the width
          $this->config['width'] = $original_size[0] * $this->config['height'] / $original_size[1];
          $this->config['master_dim'] = 'height';
        }
        $this->image_lib->initialize($this->config);
        $this->image_lib->resize();
      }

    }

    $this->show_img($this->cache_image);
  }
  
  public function show_img($path)
  {
    $this->load->helper('file'); 
    $data = read_file($path);
    header("Content-Disposition: filename=".$this->new_file_name.";");
    $stuff = get_mime_by_extension($path);
    header("Content-Type: {$stuff}");
    header('Content-Transfer-Encoding: binary');
    header('Last-Modified: '.gmdate('D, d M Y H:i:s', time()).' GMT');
    echo $data;

  }

}