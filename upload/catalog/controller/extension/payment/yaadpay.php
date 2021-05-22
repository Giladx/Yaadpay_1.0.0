<?php
class ControllerExtensionPaymentYaadpay extends Controller {
	public function index() {
		$this->load->language('extension/payment/yaadpay');

		$data['button_confirm'] = $this->language->get('button_confirm');
		$data['text_tash'] = $this->language->get('text_tash');
		$data['text_tmp'] = $this->language->get('text_tmp');

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);


		if ($order_info) {
			//General
				//Language code - If is not a Israeli customer have send a new param to remove need for identity
					$israeli_lang_code = 'he-il';
					//$israeli_lang_code = 'en-gb';

					$data['israeli_customer'] = $this->language->get('code') == $israeli_lang_code;
					$data['page_lang'] = $this->language->get('code') == $israeli_lang_code ? 'ENG' : 'HEB';

				//Currecies:  1.- NIS 2.- Dollar 3.- Euro
				  if($order_info['currency_code'] == 'ILS')
					$currency_code = 1;
					elseif($order_info['currency_code'] == 'USD')
						$currency_code = 2;
					elseif($order_info['currency_code'] == 'EUR')
						$currency_code = 3;
					elseif($order_info['currency_code'] == 'GBP')
						$currency_code = 4;

				$data['currency_code'] = $currency_code;
				$data['menu'] = $this->config->get('payment_yaadpay_menu');
				$data['postpone'] = $this->config->get('payment_yaadpay_postpone');
				$data['type'] = $this->config->get('payment_yaadpay_type');
				$data['iframe_width'] = $this->config->get('payment_yaadpay_iframe_width') ? $this->config->get('payment_yaadpay_iframe_width') : '400px';
				$data['iframe_height'] = $this->config->get('payment_yaadpay_iframe_height') ? $this->config->get('payment_yaadpay_iframe_height') : '400px';
				$buyer_name = html_entity_decode($order_info['payment_firstname'], ENT_QUOTES, 'UTF-8');
				$buyer_full_name = $buyer_name.' '.html_entity_decode($order_info['payment_lastname'], ENT_QUOTES, 'UTF-8');

			//Fields to payment form
				$data['terminal_id'] = $this->config->get('payment_yaadpay_terminal_id');
				$data['passp'] = $this->config->get('payment_yaadpay_passp');
				$data['order_total'] = $order_info['total'];
				$data['customer_name'] = $buyer_full_name;
				$data['tash'] = $this->config->get('payment_yaadpay_tash');
				$data['tmp'] = $this->config->get('payment_yaadpay_tmp');
				$data['order_id'] = $this->session->data['order_id'];
				$data['payment_address'] = html_entity_decode($order_info['payment_address_1'], ENT_QUOTES, 'UTF-8');
				$data['payment_city'] = html_entity_decode($order_info['payment_city'], ENT_QUOTES, 'UTF-8');
				$data['payment_postcode'] = html_entity_decode($order_info['payment_postcode'], ENT_QUOTES, 'UTF-8');
				$data['payment_phone'] = html_entity_decode($order_info['telephone'], ENT_QUOTES, 'UTF-8');
				$data['payment_email'] = html_entity_decode($order_info['email'], ENT_QUOTES, 'UTF-8');

			return $this->load->view('extension/payment/yaadpay', $data);
		}
	}

	public function callback() {
		$this->load->language('extension/payment/yaadpay');

		$deal = $this->request->get['Id'];
		$CCode = $this->request->get['CCode'];
		$Amount = $this->request->get['Amount'];
		$ACode = $this->request->get['ACode'];
		$token = $this->request->get['Order'];
		$fullname = $this->request->get['Fild1'];
		$email = $this->request->get['Fild2'];
		$phone = $this->request->get['Fild3'];
		$Sign = $this->request->get['Sign'];

		$sign_array = array(
			'Id' => $deal,
			'CCode' => $CCode,
			'Amount' => $Amount,
			'ACode' => $ACode,
			'Order' => $token,
			'Fild1' => rawurlencode($fullname),
			'Fild2' => rawurlencode($email),
			'Fild3' => rawurlencode($phone)
		);


		$string = '';
		foreach($sign_array as $key => $val)
		{
			$string .= $key .'=' . $val .'&';
		}
		$string = substr($string, 0, -1);

		$verify = hash_hmac('SHA256',$string, $this->config->get('payment_yaadpay_sign'));

		if($verify == $Sign)
		{
			//Transaction number in YaadPay
				$id = !empty($this->request->get['Id']) ? $this->request->get['Id'] : '';
			//CCode Answer credit company's reply
				$order_id = !empty($this->request->get['Order']) ? $this->request->get['Order'] : 0;

			//CCode Answer credit company's reply
				$status_code = !empty($this->request->get['CCode']) ? $this->request->get['CCode'] : 0;

			$this->load->model('checkout/order');

			$order_info = $this->model_checkout_order->getOrder($order_id);

			$this->log->write('YaadPay - Return to callback function: '.json_encode($this->request->get));

			$order_success = false;

			if ($order_info) {
				/*
					4 Refusal
					6 A Error in an identity card
					3 Call the credit company
					902 error Referr
					36 Expired
					33 Card Error
				*/
				switch($status_code) {
					case 0:
					case 800:
						$order_status_id = $this->config->get('payment_yaadpay_completed_status_id');
						$this->log->write('YaadPay - Order success!');
						$order_success = true;
					break;

					case 4:
					case 6:
					case 902:
					case 33:
						$order_status_id = $this->config->get('payment_yaadpay_failed_status_id');
						$this->log->write('YaadPay - Order error, yaadpay code: '.$status_code);
					break;

					case 36:
						$order_status_id = $this->config->get('payment_yaadpay_expired_status_id');
						$this->log->write('YaadPay - Order error, yaadpay code: '.$status_code);
					break;

					default:
						$order_status_id = $this->config->get('payment_config_order_status_id');
						$this->log->write('YaadPay - Order error, yaadpay code: '.$status_code);
					break;
				}
			}

			if($order_success)
			{
				$this->model_checkout_order->addOrderHistory($order_id, $order_status_id);
				$this->response->redirect($this->url->link('checkout/success', '', true));
			}
			else
			{
				$error = !in_array($status_code, array(4,6,902,33,36)) ? $this->language->get('error_yaadpay_unknow') : $this->language->get('error_yaadpay_'.$status_code);

				$this->session->data['error'] = $this->language->get('error_yaadpay_begin').$status_code.' - '.$error;
				$this->response->redirect($this->url->link('checkout/cart', '', true));
			}
		}
		else
		{
			$this->log->write('YaadPay - Order HASH security error');
			$this->session->data['error'] = $this->language->get('error_yaadpay_begin').' - '.$this->language->get('error_yaadpay_security');
			$this->response->redirect($this->url->link('checkout/cart', '', true));
		}
	}
}
