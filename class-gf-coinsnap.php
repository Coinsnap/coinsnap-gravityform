<?php

GFForms::include_payment_addon_framework();

class GFCoinsnap extends GFPaymentAddOn {
    
    private static $_instance = null;
    protected $_version = '1.1';
    protected $_min_gravityforms_version = '1.9.3';
    protected $_slug = 'gravityforms_coinsnap';
    protected $_path = 'gravityforms_coinsnap/coinsnap.php';
    protected $_full_path = __FILE__;
    protected $_url = 'http://www.gravityforms.com';
    protected $_title = 'Gravity Forms Coinsnap Add-On';
    protected $_short_title = 'Coinsnap';
    protected $_supports_callbacks = true;
    protected $_capabilities = array('gravityforms_coinsnap', 'gravityforms_coinsnap_uninstall');    
    protected $_capabilities_settings_page = 'gravityforms_coinsnap';    
    protected $_capabilities_form_settings = 'gravityforms_coinsnap';
    protected $_capabilities_uninstall = 'gravityforms_coinsnap_uninstall';
    protected $_enable_rg_autoupgrade = false;
    protected $_config= [];
    public const WEBHOOK_EVENTS = ['New','Expired','Settled','Processing'];	 
    
    
    public function __construct()
    {
        parent::__construct();
        $this->_config = get_option( 'gravityformsaddon_gravityforms_coinsnap_settings' );
    }
    public static function get_instance()
    {
        if (self::$_instance == null) {
            self::$_instance = new GFCoinsnap();
        }
        
        return self::$_instance;
    }
    public function pre_init() {        
        add_action('wp', array('GFCoinsnap', 'maybe_thankyou_page'), 5);        
    
        parent::pre_init();
      }

    public static function maybe_thankyou_page()
    {
        $instance = self::get_instance();
        if ( ! $instance->is_gravityforms_supported()) {
            return;
        }
        if ($str = rgget('gf_coinsnap_return')) {
            $str = base64_decode($str);
            parse_str($str, $query);
            if (wp_hash('ids=' . $query['ids']) == $query['hash']) {
                list($form_id, $lead_id) = explode('|', $query['ids']);
                $form = GFAPI::get_form($form_id);
                $lead = GFAPI::get_entry($lead_id);
                if ( ! class_exists('GFFormDisplay')) {
                    require_once(GFCommon::get_base_path() . '/form_display.php');
                }
                $confirmation = GFFormDisplay::handle_confirmation($form, $lead, false);
                if (is_array($confirmation) && isset($confirmation['redirect'])) {
                    header("Location: {$confirmation['redirect']}");
                    exit;
                }
                GFFormDisplay::$submission[$form_id] = array(
                    'is_confirmation'      => true,
                    'confirmation_message' => $confirmation,
                    'form'                 => $form,
                    'lead'                 => $lead
                );
            }
        }
    }

    public static function get_config_by_entry($entry)
    {
        $coinsnap = GFCoinsnap::get_instance();
        $feed    = $coinsnap->get_payment_feed($entry);
        if (empty($feed)) {
            return false;
        }

        return $feed['addon_slug'] == $coinsnap->_slug ? $feed : false;
    }


    
    

    public static function get_config($form_id)
    {
        $coinsnap = GFCoinsnap::get_instance();
        $feed    = $coinsnap->get_feeds($form_id);        
        if ( ! $feed) {
            return false;
        }

        return $feed[0]; 
    }

    public function init_frontend()
    {
        parent::init_frontend();
        add_filter('gform_disable_post_creation', array($this, 'delay_post'), 10, 3);
        add_filter('gform_disable_notification', array($this, 'delay_notification'), 10, 4);
    }
    public function billing_info_fields() {		

		return array(
			array(
				'name'       => 'email',
				'label'      => __( 'Email address', 'gravityforms_coinsnap' ),
				'field_type' => array( 'email' ),
                'default_value' => '2',
				'required'   => true,
			),
			array(
				'name'       => 'full_name',
				'label'      => __( 'Full Name', 'gravityforms_coinsnap' ),
				'field_type' => array( 'name', 'text' ),
                'default_value' => '1',
				'required'   => true,
			),					
		);
	}
    public function plugin_settings_fields()
    {

        $sts = GFCommon::get_entry_payment_statuses();
        
        
        $statuses = [];
        foreach ($sts as $key => $val ){
            $statuses[] = array('label'=>$key, 'value'=>$val);
        }
        
        $settings_fields     = array(array(
            'title'       => esc_html__('Coinsnap Setting', 'gravityforms_coinsnap'),
            'description' => '',
            'fields'      => array(               
            array(
                'name'     => 'coinsnap_store_id',
                'label'    => __('Store Id', 'gravityforms_coinsnap'),
                'type'     => 'text',
                'class'    => 'medium',
                'required' => false,
                'tooltip'  =>  __('Enter Your Coinsnap Store ID.','gravityforms_coinsnap')
            ),
            array(
                'name'     => 'coinsnap_api_key',
                'label'    => __('API Key', 'gravityforms_coinsnap'),
                'type'     => 'text',
                'class'    => 'medium',                
                'required' => false,
                'tooltip'  =>  __('Enter Your Coinsnap API Key.','gravityforms_coinsnap')
                ),   
            array(
                'name'     => 'coinsnap_expired_status',
                'label'    => __('Expired Status', 'gravityforms_coinsnap'),
                'type'     => 'select',
                'choices'  => $statuses,
                'class'    => 'optin_select',                
                'default_value' => 'Failed',
                'tooltip'  =>  __('Select Expired Status.','gravityforms_coinsnap')
               ),                  
           array(
                'name'     => 'coinsnap_settled_status',
                'label'    => __('Settled Status', 'gravityforms_coinsnap'),
                'type'     => 'select',
                'choices'  => $statuses,
                'class'    => 'optin_select',                
                'default_value' => 'Paid',
                'tooltip'  =>  __('Select Settled Status.','gravityforms_coinsnap')
               ),      
           array(
              'name'     => 'coinsnap_processing_status',
              'label'    => __('Processing Status', 'gravityforms_coinsnap'),
              'type'     => 'select',
              'choices'  => $statuses,
              'class'    => 'optin_select',                
              'default_value' => 'Processing',
              'tooltip'  =>  __('Select Processing Status.','gravityforms_coinsnap')
             ),   
                                                           
               
        )
    )
);
        

        return $settings_fields;
    }

    public function feed_list_no_item_message()
    {
        $settings = $this->get_plugin_settings();
        if ( ! rgar($settings, 'gf_coinsnap_configured')) {
            return sprintf(
                __('To get started, configure your %sCoinsnap Settings%s!', 'gravityforms_coinsnap'),
                '<a href="' . admin_url('admin.php?page=gf_settings&subview=' . $this->_slug) . '">',
                '</a>'
            );
        } else {
            return parent::feed_list_no_item_message();
        }
    }

    public function feed_settings_fields()
    {
        $feed_settings_fields = parent::feed_settings_fields();
        
        
         unset( $feed_settings_fields[0]['fields'][1]['choices'][2] );    
         $feed_settings_fields[0]['fields'][1]['default_value'] = 'product';          

        return apply_filters('gform_coinsnap_feed_settings_fields', $feed_settings_fields);
    }

  
    

    public function field_map_title()
    {
        return __('Coinsnap Field', 'gravityforms_coinsnap');
    }



    public function option_choices()
    {
        return false;
    }

    

    public function redirect_url($feed, $submission_data, $form, $entry)
    {        

        //Don't process redirect url if request is a Coinsnap return
        if ( ! rgempty('gf_coinsnap_return', $_GET)) {            
            return false;
        }
        
        $payment_amount = $submission_data['payment_amount'];
        $currency  = rgar( $entry, 'currency' );
        

        
        $buyerEmail = $submission_data['email'];		
        $buyerName = $submission_data['full_name'];  		
        

        $webhook_url = $this->get_webhook_url();		
        
				
        if (! $this->webhookExists($this->getStoreId(), $this->getApiKey(), $webhook_url)){
            if (! $this->registerWebhook($this->getStoreId(), $this->getApiKey(),$webhook_url)) {                
                echo (__('unable to set Webhook url.', 'gravityforms_coinsnap'));
                exit;
            }
         }      

        //updating lead's payment_status to Pending
        GFAPI::update_entry_property($entry['id'], 'payment_status', 'Pending');
        $return_mode = '2';

        $return_url = $this->return_url($form['id'], $entry['id']) . "&rm={$return_mode}";              

        $invoice_no =  $entry['id'];		

		$amount = round($payment_amount, 2);
        
              						    	

        $metadata = [];
        $metadata['orderNumber'] = $invoice_no;
        $metadata['customerName'] = $buyerName;
				

        $checkoutOptions = new \Coinsnap\Client\InvoiceCheckoutOptions();
        $checkoutOptions->setRedirectURL( $return_url );
        $client =new \Coinsnap\Client\Invoice($this->getApiUrl(), $this->getApiKey());
        $camount = \Coinsnap\Util\PreciseNumber::parseFloat($amount,2);
								
        $csinvoice = $client->createInvoice(
				    $this->getStoreId(),  
			    	strtoupper( $currency ),
			    	$camount,
			    	$invoice_no,
			    	$buyerEmail,
			    	$buyerName, 
			    	$return_url,
			    	COINSNAP_REFERRAL_CODE,     
			    	$metadata,
			    	$checkoutOptions
		    	);
				
		
        $payurl = $csinvoice->getData()['checkoutLink'] ;

		

        return $payurl ;
    }


        

    public function return_url($form_id, $lead_id)
    {
        $pageURL     = GFCommon::is_ssl() ? 'https://' : 'http://';
        $server_port = apply_filters('gform_coinsnap_return_url_port', $_SERVER['SERVER_PORT']);
        if ($server_port != '80') {
            $pageURL .= $_SERVER['SERVER_NAME'] . ':' . $server_port . $_SERVER['REQUEST_URI'];
        } else {
            $pageURL .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
        }
        $ids_query = "ids={$form_id}|{$lead_id}";
        $ids_query .= '&hash=' . wp_hash($ids_query);

        return add_query_arg('gf_coinsnap_return', base64_encode($ids_query), $pageURL);
    }

        

    public function delay_post($is_disabled, $form, $entry)
    {
        $feed            = $this->get_payment_feed($entry);
        $submission_data = $this->get_submission_data($feed, $form, $entry);
        if ( ! $feed || empty($submission_data['payment_amount'])) {
            return $is_disabled;
        }

        return ! rgempty('delayPost', $feed['meta']);
    }

    

    public function delay_notification($is_disabled, $notification, $form, $entry)
    {
        $feed            = $this->get_payment_feed($entry);
        $submission_data = $this->get_submission_data($feed, $form, $entry);
        if ( ! $feed || empty($submission_data['payment_amount'])) {
            return $is_disabled;
        }
        $selected_notifications = is_array(rgar($feed['meta'], 'selectedNotifications')) ? rgar(
            $feed['meta'],
            'selectedNotifications'
        ) : array();

        return isset($feed['meta']['delayNotification']) && in_array(
            $notification['id'],
            $selected_notifications
        ) ? true : $is_disabled;
    }

    public function get_payment_feed($entry, $form = false)
    {
        $feed = parent::get_payment_feed($entry, $form);
        if (empty($feed) && ! empty($entry['id'])) {            
            $feed = $this->get_coinsnap_feed_by_entry($entry['id']);
        }

        return apply_filters('gform_coinsnap_get_payment_feed', $feed, $entry, $form);
    }

    public function get_coinsnap_feed_by_entry($entry_id)
    {
        $feed_id = gform_get_meta($entry_id, 'coinsnap_feed_id');
        $feed    = $this->get_feed($feed_id);

        return ! empty($feed) ? $feed : false;
    }

    public function process_webhook()
    {
     
        $notify_json = file_get_contents('php://input');        

        $this->log_debug("coinsnap webhook : ".$notify_json);                
        $notify_ar = json_decode($notify_json, true);
        $invoice_id = $notify_ar['invoiceId'];

        try {
			$client = new \Coinsnap\Client\Invoice( $this->getApiUrl(), $this->getApiKey() );			
			$csinvoice = $client->getInvoice($this->getStoreId(), $invoice_id);
			$status = $csinvoice->getData()['status'] ;
			$entry_id = $csinvoice->getData()['orderId'] ;				
		
		}catch (\Throwable $e) {													
				echo "Error";
				exit;
		}
	
        
        $entry = GFAPI::get_entry( $entry_id );
        $feed  = $this->get_payment_feed( $entry );
        $form   = GFFormsModel::get_form_meta($entry['form_id']);
        
        
        $this->log_debug( __METHOD__ . "(): Entry ID #" . $entry['id'] . " is set to Feed ID #" . $feed['id'] ); 

        $order_status = 'Pending';        
        
        if ($status == 'Expired') $order_status = $this->_config['coinsnap_expired_status'];
        else if ($status == 'Processing') $order_status = $this->_config['coinsnap_processing_status'];
        else if ($status == 'Settled') $order_status = $this->_config['coinsnap_settled_status'];	
        

        GFAPI::update_entry_property($entry_id, 'payment_status', $order_status);
        if ($order_status == 'Paid'){                        
            GFAPI::send_notifications($form, $entry, 'complete_payment');
            GFAPI::update_entry_property( $entry_id, 'transaction_id', $invoice_id );            
        }
        echo "OK";
        exit;
    }

    
    public function is_callback_valid(): bool
    {
        if (rgget('page') != 'gf_coinsnap_webhook') {
            return false;
        }
        $this->process_webhook();

        return true;
    }

        
    
    public function update_feed_id($old_feed_id, $new_feed_id)
    {
        global $wpdb;
        $sql = $wpdb->prepare(
            "UPDATE {$wpdb->prefix}rg_lead_meta SET meta_value=%s WHERE meta_key='coinsnap_feed_id' AND meta_value=%s",
            $new_feed_id,
            $old_feed_id
        );
        $wpdb->query($sql);
    }

    
    public function update_payment_gateway()
    {
        global $wpdb;
        $sql = $wpdb->prepare(
            "UPDATE {$wpdb->prefix}rg_lead_meta SET meta_value=%s WHERE meta_key='payment_gateway' AND meta_value='coinsnap'",
            $this->_slug
        );
        $wpdb->query($sql);
    }

    

   
    
    public function get_webhook_url() {		
        return get_bloginfo('url') . '/?page=gf_coinsnap_webhook';
    }
	public function getStoreId() {
        return $this->_config['coinsnap_store_id'];
    }
    public function getApiKey() {
        return $this->_config['coinsnap_api_key'] ;
    }
    
    public function getApiUrl() {
        return COINSNAP_SERVER_URL;
    }	

    public function webhookExists(string $storeId, string $apiKey, string $webhook): bool {	
        try {		
            $whClient = new \Coinsnap\Client\Webhook( $this->getApiUrl(), $apiKey );		
            $Webhooks = $whClient->getWebhooks( $storeId );
            
			
            
            foreach ($Webhooks as $Webhook){					
                //self::deleteWebhook($storeId,$apiKey, $Webhook->getData()['id']);
                if ($Webhook->getData()['url'] == $webhook) return true;	
            }
        }catch (\Throwable $e) {			
            return false;
        }
    
        return false;
    }
    public  function registerWebhook(string $storeId, string $apiKey, string $webhook): bool {	
        try {			
            $whClient = new \Coinsnap\Client\Webhook($this->getApiUrl(), $apiKey);
            
            $webhook = $whClient->createWebhook(
                $storeId,   //$storeId
                $webhook, //$url
                self::WEBHOOK_EVENTS,   
                null    //$secret
            );		
            
            return true;
        } catch (\Throwable $e) {
            return false;	
        }

        return false;
    }

    public function deleteWebhook(string $storeId, string $apiKey, string $webhookid): bool {	    
        
        try {			
            $whClient = new \Coinsnap\Client\Webhook($this->getApiUrl(), $apiKey);
            
            $webhook = $whClient->deleteWebhook(
                $storeId,   //$storeId
                $webhookid, //$url			
            );					
            return true;
        } catch (\Throwable $e) {
            
            return false;	
        }
    }    


    public function uninstall() {
        $option_names = array(
          'coinsnap_store_id',
          'coinsnap_api_key',
          'coinsnap_expired_status',
          'coinsnap_settled_status',
          'coinsnap_processing_status'          
        );
        
        foreach( $option_names as $option_name ){
          delete_option( $option_name );
        }
    
        parent::uninstall();
      }
}
