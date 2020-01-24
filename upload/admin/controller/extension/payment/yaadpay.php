<!-- Author : Gilad Levi - https://bytii.cloud --> 
<!-- github : https://github.com/Giladx/Yaadpay_1.0.0 -->
<?php
class ControllerExtensionPaymentYaadpay extends Controller {
	private $error = array();

	public function __construct($registry) {
        parent::__construct($registry);

        //IMPORTANT - DEFINE TYPE EXTENSION - MODULE, PAYMENT, SHIPPING...
        $this->extension_type = 'payment';
        $this->extension_name = 'yaadpay';
        $this->extension_path = version_compare(VERSION, '2.3.0.0', '>=') ? 'extension/'.$this->extension_type : $this->extension_type;

        $this->url_modules = '';
        if(version_compare(VERSION, '2.3.0.0', '>=') && version_compare(VERSION, '2.3.0.2', '<='))
        	$this->url_modules = 'extension/extension';

        $this->url_after_save = '';
        if(version_compare(VERSION, '2.3.0.0', '>=') && version_compare(VERSION, '2.3.0.2', '<='))
        	$this->url_after_save = 'extension/extension';
    }

	public function index() {
		$this->load->language($this->extension_path.'/'.$this->extension_name);

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_yaadpay', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		if (isset($this->request->post['payment_yaadpay_menu'])) {
			$data['payment_yaadpay_menu'] = $this->request->post['payment_yaadpay_menu'];
		}elseif(isset($store_info['payment_yaadpay_menu'])){
			$data['payment_yaadpay_menu'] = $store_info['payment_yaadpay_menu'];
		} else {
			$data['payment_yaadpay_menu'] = '';
		}

		$array_lang = array(
			//General texts
			'heading_title',
			'text_edit',
			'text_enabled',
			'text_disabled',
			'text_all_zones',
			'text_yes',
			'text_no',
			'text_yaadpay_menu',
			'button_save',
			'button_cancel',

			//Tabs
			'tab_general',
			'tab_order_status',
			'tab_login_to_yaadpay',

			//General tab
			'entry_status',
			'entry_postpone',
			'help_entry_postpone',
			'entry_terminal_id',
			'entry_sign',
			'entry_user',
			'entry_password',
			'your_callback_url',
			'help_your_callback_url',
			'entry_type',
				'text_type_redirect',
				'text_type_iframe',
				'entry_iframe_width',
				'entry_iframe_height',
				'help_entry_iframe_width_height',
			'entry_total',
				'help_total',
			'entry_tash',	

			//Order Status tab
			'entry_canceled_reversal_status',
			'entry_completed_status',
			'entry_denied_status',
			'entry_expired_status',
			'entry_failed_status',
			'entry_pending_status',
			'entry_processed_status',
			'entry_refunded_status',
			'entry_reversed_status',
			'entry_voided_status',
			'entry_geo_zone',
			'entry_sort_order',

			//login tab
			'entry_login',
			


		);

		foreach ($array_lang as $key => $lang) {
			$data[$lang] = $this->language->get($lang);
		}

		//Devman Extensions - info@devmanextensions.com - 2017-08-01 18:38:43 - Callback url
			$data['payment_callback_url'] = HTTP_CATALOG.'?index.php&route=extension/payment/yaadpay/callback';

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/payment/yaadpay', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		$array_config = array(
			'payment_yaadpay_status',
			'payment_yaadpay_menu',
			'payment_yaadpay_postpone',
			'payment_yaadpay_terminal_id',
			'payment_yaadpay_sign',
			'payment_yaadpay_user',
			'payment_yaadpay_password',
			'payment_yaadpay_type',
				'payment_yaadpay_iframe_width',
				'payment_yaadpay_iframe_height',
			'payment_yaadpay_geo_zone_id',
			'payment_yaadpay_total',
			'payment_yaadpay_sort_order',
            'payment_yaadpay_tash',
			'payment_yaadpay_canceled_reversal_status_id',
			'payment_yaadpay_completed_status_id',
			'payment_yaadpay_denied_status_id',
			'payment_yaadpay_expired_status_id',
			'payment_yaadpay_failed_status_id',
			'payment_yaadpay_pending_status_id',
			'payment_yaadpay_processed_status_id',
			'payment_yaadpay_refunded_status_id',
			'payment_yaadpay_reversed_status_id',
			'payment_yaadpay_voided_status_id',
		);

		foreach ($array_config as $key => $cong) {
			$data[$cong] = isset($this->request->post[$cong]) ? $this->request->post[$cong] : $this->config->get($cong);
		}

		$data['payment_yaadpay_iframe_width'] = !empty($data['payment_yaadpay_iframe_width']) ? $data['payment_yaadpay_iframe_width'] : '400px';
		$data['payment_yaadpay_iframe_height'] = !empty($data['payment_yaadpay_iframe_height']) ? $data['payment_yaadpay_iframe_height'] : '400px';

		$this->load->model('localisation/order_status');
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$this->load->model('localisation/geo_zone');
		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$data['terminal_id'] = $this->config->get('payment_yaadpay_terminal_id');
		$data['user'] = $this->config->get('payment_yaadpay_user');
		$data['password'] = $this->config->get('payment_yaadpay_password');
		$this->response->setOutput($this->load->view('extension/payment/yaadpay', $data));

	}

	private function validate() {
		if (!$this->user->hasPermission('modify', $this->extension_path.'/'.$this->extension_name)) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}
