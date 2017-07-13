<?php 

namespace SandCage;

class SandCage {
	// Your SandCage API Key
	// This can be retrieved from https://www.sandcage.com/panel/api_key
	protected $sandcage_api_key = '[YOUR SANDCAGE API KEY]';
	
	// SandCage API version
	protected $sandcage_api_version = '0.2';
	protected $sandcage_api_endpoint_base;
	protected $user_agent;
	protected $follow_location = false;
	protected $timeout = 30;
	protected $post_fields;
	public $status;
	public $response;

	public function __construct($sandcage_api_key = null) {
		if (!is_null($sandcage_api_key)) {
			$this->sandcage_api_key = $sandcage_api_key;
		}
		// Handle open_basedir & safe mode
		if (!ini_get('safe_mode') && !ini_get('open_basedir')) {
			$this->follow_location = true;
		}
		$this->sandcage_api_endpoint_base = 'https://api.sandcage.com/' . $this->sandcage_api_version . '/';
		$this->user_agent = 'SandCage - ' . $this->sandcage_api_version;
	}

	/** 
	 * Send a requst using cURL 
	 * @param string $service endpoint to request
	 * @param array $payload values to send
	 * @param string $callback_endpoint to send the callback to. Optional
	 */ 
	public function call($service, $payload, $callback_endpoint = '') {
		$this->payloadArray($payload, $callback_endpoint);
		$this->curlCall($this->sandcage_api_endpoint_base . $service);
	}

	/** 
	 * Send a requst using cURL 
	 * @param string $service_endpoint to request
	 */ 
	public function curlCall($service_endpoint) {
		$ch = curl_init($service_endpoint);
		curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $this->follow_location);
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->post_fields));
		$this->response = curl_exec($ch);
		// Retry if certificates are missing.
		if (curl_errno($ch) == CURLE_SSL_CACERT) {
			curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/cacert.pem');
			// Retry execution after setting CURLOPT_CAINFO
			$this->response = curl_exec($ch);
		}
		$this->status = curl_getinfo($ch);
		curl_close($ch);
	}

	/** 
	 * Build the payload array
	 * @param array $payload values to send
	 * @param string $callback_endpoint to send the callback to
	 */ 
	private function payloadArray($payload, $callback_endpoint) {
		$this->post_fields = array('key'=>$this->sandcage_api_key) + $payload;

		if ($callback_endpoint != '') {
			$this->post_fields['callback_url'] = $callback_endpoint;
		}
	}
}
