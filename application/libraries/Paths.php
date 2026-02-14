<?php defined('BASEPATH') or exit('No direct script access allowed');

/***
 * Paths Library to return specific paths
 */
class Paths
{
    // generic function for return eQsl path //
    function getPathEqsl()
    {
        $CI = &get_instance();
        $CI->load->model('Eqsl_images');
        return $CI->Eqsl_images->get_imagePath();
    }

    // generic function for return Qsl path //
    function getPathQsl()
    {
        $CI = &get_instance();
        $CI->load->model('Qsl_model');
        return $CI->Qsl_model->get_imagePath();
    }

    function make_update_path($path) {

		$CI = & get_instance();

		$path = "updates/" . $path;
        $datadir = $CI->config->item('datadir');
        if(!$datadir) {
            return $path;
        }
        return $datadir . "/" . $path;
	}

    function cache_buster($filepath) {
        // make sure $filepath starts with a slash
        if (substr($filepath, 0, 1) !== '/') $filepath = '/' . $filepath;

        // These files are not existent on purpose and should not trigger error logs
        $err_exceptions = [
            '/assets/json/datatables_languages/en-US.json',
        ];
    
        // Use DIRECTORY_SEPARATOR for cross-platform compatibility (aka Windows may needs special handling)
        $fullpath = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . str_replace('/', DIRECTORY_SEPARATOR, $filepath);
        
        if (file_exists($fullpath)) {
            return base_url($filepath) . '?v=' . filemtime($fullpath);
        } else {
            if (!in_array($filepath, $err_exceptions)) {
                log_message('error', 'CACHE BUSTER: File does not exist: ' . $fullpath);
            }
        }
        return base_url($filepath);
    }
}
