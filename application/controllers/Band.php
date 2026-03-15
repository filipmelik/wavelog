<?php if (! defined('BASEPATH')) exit('No direct script access allowed');

/*
	Handles Displaying of band information
*/

class Band extends CI_Controller {

	function __construct() {
		parent::__construct();
		$this->load->helper(array('form', 'url'));

		$this->load->model('user_model');
		if (!$this->user_model->authorize(2) || !clubaccess_check(9)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}
	}

	public function index() {
		$this->load->model('bands');

		$data['bands'] = $this->bands->get_all_bands_for_user();

		// Render Page
		$data['page_title'] = __("Bands");
		$this->load->view('interface_assets/header', $data);
		$this->load->view('bands/index');
		$this->load->view('interface_assets/footer');
	}

	public function edges() {
		$this->load->model('bands');

		$data['bands'] = $this->bands->get_all_bandedges_for_user();

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/sections/bandedges.js',
		];

		// Render Page
		$data['page_title'] = __("Bands");
		$this->load->view('interface_assets/header', $data);
		$this->load->view('bands/bandedges');
		$this->load->view('interface_assets/footer', $footerData);
	}

	// API endpoint to get band edges for the logged-in user
	public function get_user_bandedges() {
		$this->load->model('bands');

		// Get region parameter from query string, default to 1 (IARU Region 1)
		// Note: Region selection is not yet fully implemented in the code
		$region = $this->input->get('region');
		$region = is_numeric($region) ? (int)$region : 1;

		$data = $this->bands->get_all_bandedges_for_user($region);

		header('Content-Type: application/json');
		echo json_encode($data);
		return;
	}

	public function create() {
		$this->load->model('bands');
		$this->load->library('form_validation');

		$this->form_validation->set_rules('band', 'Band', 'required');

		if ($this->form_validation->run() == FALSE) {
			$data['page_title'] = __("Create Mode");
			$this->load->view('bands/create', $data);
		} else {
			$band_data = array(
				'band' 		=> $this->input->post('band', true),
				'bandgroup' => $this->input->post('bandgroup', true),
				'ssb'	 	=> $this->input->post('ssbqrg', true),
				'data' 		=> $this->input->post('dataqrg', true),
				'cw' 		=> $this->input->post('cwqrg', true),
			);

			$this->bands->add($band_data);
		}
	}

	public function edit() {
		$this->load->model('bands');

		$item_id_clean = $this->input->post('id');

		$band_query = $this->bands->getband($item_id_clean);

		$data['my_band'] = $band_query->row();

		$data['page_title'] = __("Edit Band");

		$this->load->view('bands/edit', $data);
	}

	public function saveupdatedband() {
		$this->load->model('bands');

		$id = $this->input->post('id', true);
		$band['band'] 		= $this->input->post('band', true);
		$band['bandgroup'] 	= $this->input->post('bandgroup', true);
		$band['ssbqrg'] 	= $this->input->post('ssbqrg', true);
		$band['dataqrg'] 	= $this->input->post('dataqrg', true);
		$band['cwqrg'] 		= $this->input->post('cwqrg', true);

		$this->bands->saveupdatedband($id, $band);
		echo json_encode(array('message' => 'OK'));
		return;
	}

	public function delete() {
		$id = $this->input->post('id', true);
		$userid = $this->session->userdata('user_id');
		$this->load->model('bands');
		$this->bands->delete($id, $userid);
	}

	public function activate() {
		$id = $this->input->post('id', true);
		$this->load->model('bands');
		$this->bands->activate($id);
		header('Content-Type: application/json');
		echo json_encode(array('message' => 'OK'));
		return;
	}

	public function deactivate() {
		$id = $this->input->post('id', true);
		$this->load->model('bands');
		$this->bands->deactivate($id);
		header('Content-Type: application/json');
		echo json_encode(array('message' => 'OK'));
		return;
	}

	public function activateall() {
		$this->load->model('bands');
		$this->bands->activateall();
		header('Content-Type: application/json');
		echo json_encode(array('message' => 'OK'));
		return;
	}

	public function deactivateall() {
		$this->load->model('bands');
		$this->bands->deactivateall();
		header('Content-Type: application/json');
		echo json_encode(array('message' => 'OK'));
		return;
	}

	public function saveBand() {
		$id 				= $this->input->post('id', true);
		$band['status'] 	= $this->input->post('status', true);
		$band['cq'] 		= $this->input->post('cq', true);
		$band['dok'] 		= $this->input->post('dok', true);
		$band['dxcc'] 		= $this->input->post('dxcc', true);
		$band['helvetia'] 	= $this->input->post('helvetia', true);
		$band['iota'] 		= $this->input->post('iota', true);
		$band['jcc'] 		= $this->input->post('jcc', true);
		$band['pota'] 		= $this->input->post('pota', true);
		$band['rac'] 		= $this->input->post('rac', true);
		$band['sig'] 		= $this->input->post('sig', true);
		$band['sota']		= $this->input->post('sota', true);
		$band['uscounties'] = $this->input->post('uscounties', true);
		$band['wap'] 		= $this->input->post('wap', true);
		$band['wapc'] 		= $this->input->post('wapc', true);
		$band['was'] 		= $this->input->post('was', true);
		$band['wwff'] 		= $this->input->post('wwff', true);
		$band['vucc'] 		= $this->input->post('vucc', true);
		$band['waja'] 		= $this->input->post('waja', true);

		$this->load->model('bands');
		$this->bands->saveBand($id, $band);

		header('Content-Type: application/json');
		echo json_encode(array('message' => 'OK'));
		return;
	}

	public function saveBandAward() {
		$award  = $this->input->post('award', true);
		$status	= $this->input->post('status', true);

		$this->load->model('bands');
		$this->bands->saveBandAward($award, $status);

		header('Content-Type: application/json');
		echo json_encode(array('message' => 'OK'));
		return;
	}

	public function saveBandUnit() {
		$unit = $this->input->post('unit', true);
		$band_id = $this->input->post('band_id', true);

		$this->load->model('bands');
		$band = $this->bands->getband($band_id)->row()->band;

		$this->user_options_model->set_option('frequency', 'unit', array($band => $unit));
		$this->session->set_userdata('qrgunit_' . $band, $unit);
	}

	public function deletebandedge() {
		$id = $this->input->post('id', true);
		$this->load->model('bands');
		$this->bands->deletebandedge($id);
		header('Content-Type: application/json');
		echo json_encode(array('message' => 'OK'));
		return;
	}

	public function saveBandEdge() {
		$this->load->model('bands');

		$id = $this->input->post('id', true);
		$frequencyfrom = $this->input->post('frequencyfrom', true);
		$frequencyto = $this->input->post('frequencyto', true);
		$mode = $this->input->post('mode', true);
		if ((is_numeric($frequencyfrom)) && (is_numeric($frequencyfrom))) {
			$overlap = $this->bands->check4overlapEdges($id, $frequencyfrom, $frequencyto, $mode);
			if (!($overlap)) {
				$this->bands->saveBandEdge($id, $frequencyfrom, $frequencyto, $mode);
				echo json_encode(array('message' => 'OK'));
			} else {
				echo json_encode(array('message' => 'Overlapping'));
			}
		} else {
			echo json_encode(array('message' => 'No Number entered'));
		}
		return;
	}
}
