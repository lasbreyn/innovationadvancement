<?php
  function voguepay_help($path, $arg) {
      //$output = '<p>'.  t("voguepay is a module that extends functionality of sms framework.");
      //    The line above outputs in ALL admin/module pages
      $output = '';
      switch ($path) {
          case "admin/help/voguepay":
          $output = '<p>'.  t("eCommerce - Voguepay - Drupal Plugin.") .'</p>';
              break;
      }
      return $output;
  } // function voguepay_help

  /**
   * Valid permissions for this module
   * @return array An array of valid permissions for the voguepay module
   */
  function voguepay_perm() {
      return array('administer voguepay');
  }

  /**
   * Menu for this module
   * @return array An array with this module's settings.
   */
  function voguepay_menu() {
      $items = array();

      //Link to the sms_zone admin page:
      $items['admin/config/services/voguepay'] = array(
        'title' => 'Voguepay',
        'description' => 'Voguepay - Drupal Plugin',
        'page callback'    => 'drupal_get_form',
        'page arguments'   => array('voguepay_form'),
        'access arguments' => array('administer nodes'),
        'type' => MENU_NORMAL_ITEM,
      );

      $items['voguepay_notification'] = array(
        'page callback' => 'gauth_response_handler',
        'access callback' => TRUE,
        'type' => MENU_CALLBACK,
      );

      return $items;
  }

  function voguepay_form() {

     $form['voguepay']['merchant'] = array(
        '#type' => 'textfield',
        '#title' => t('VoguePay Merchant ID'),
        '#default_value' => variable_get('vogue_merchant',''),
        '#description' => t(''),
        '#required' => TRUE
  	  );

    	$form['voguepay']['color'] = array(
       '#type' => 'select',
       '#title' => t('Button Color'),
       '#default_value' => variable_get('vogue_color','blue'),
       '#options' => array(
            'blue' => t('Blue'),
            'red' => t('Red'),
            'green' => t('Green'),
            'grey' => t('Grey'),
          ),
       '#description' => t(''),
          '#required' => FALSE
      );

      $form['voguepay']['custom'] = array(
        '#type' => 'textfield',
        '#title' => t('Custom button'),
        '#default_value' => variable_get('vogue_custom',''),
        '#description' => t(''),
        '#required' => FALSE
  	  );
      $node_types = [];
      foreach(node_type_get_types() as $node_type) {
        $node_types[$node_type->type] =  $node_type->name;
      }

  	  $form['voguepay']['nodetypes'] = array(
    	  '#title' => t('Enable voguepay on these node types'),
    	  '#type' => 'checkboxes',
        '#options' => $node_types,
        '#default_value' => variable_get('vogue_nodetypes', ''),
        '#required' => TRUE
  	  );

      $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save Changes'),
    );

    return $form;
  }

  function voguepay_form_submit(&$form, $form_state) {

    $merchant=$form_state['values']['merchant'];
    $color=$form_state['values']['color'];
    $custom=$form_state['values']['custom'];
    $node_types=$form_state['values']['nodetypes'];

    variable_set('vogue_merchant',$merchant);
    variable_set('vogue_color',$color);
    variable_set('vogue_custom',$custom);
    variable_set('vogue_nodetypes',$node_types);

    drupal_set_message(t("Your changes were saved successfully."));

    $form_state['redirect'] = 'voguepay';
  }

  function voguepay_nodeapi(&$node, $op, $a3 = NULL, $a4 = NULL) {

    if($op=="alter") {

      $sitename  = variable_get('site_name', '');

      // Get the body
      $body = $node->body;

      // Regular expression to fetch the voguepay tags
      $regex = '/{voguepay\s*.*?}/i';
      preg_match_all( $regex, $body, $matches );


      // Fetch the default parameters
      $merchant_id= variable_get('vogue_merchant','');
      $alternate_button = variable_get('vogue_custom','');
      $button_color = variable_get('vogue_color','blue');
      $button = empty($alternate_button) ? 'http://voguepay.com/images/buttons/buynow_'.$button_color.'.png' : $alternate_button ;

      foreach($matches[0] as $key => $match) {

        $pattern = '/item\s*\((?<val>[^\(\)]+)\)/';
        preg_match($pattern, $match, $m);
        $item = $m['val'];
        $pattern = '/price\s*\((?<val>[^\(\)]+)\)/';
        preg_match($pattern, $match, $m);
        $price = $m['val'];
        $pattern = '/description\s*\((?<val>[^\(\)]+)\)/';
        preg_match($pattern, $match, $m);
        $description = empty($m['val']) ? $item.' at '.number_format($price,2) : $m['val'];

        $f = '<form method="POST" action="https://voguepay.com/pay/">
        <input type="hidden" name="v_merchant_id" value="'.$merchant_id.'" />
        <input type="hidden" name="memo" value="'.$item.' ('.number_format($price,2).') order from '.$sitename.'" />
        <input type="hidden" name="item_1" value="'.$item.'" />
        <input type="hidden" name="description_1" value="'.$description.'" />

        <input type="hidden" name="notify_url" value="'.$sitename.'/voguepay_notification" />
        <input type="hidden" name="success_url" value="'.$sitename.'/voguepay_thank_you" />
        <input type="hidden" name="fail_url" value="'.$sitename.'/voguepay_failed" />

        <input type="hidden" name="price_1" value="'.$price.'" />
        <input type="hidden" name="total" value="'.$price.'" />
        <input type="hidden" name="notify_url" value='.$sitename.'/voguepay_notification" />
        <input type="image" src="'.$button.'" alt="Pay with VoguePay" />
        </form>';

        $body = str_replace($match,$f,$body);
      }

      $node->body=$body;
    }
  }

  function voguepay_node_view($node, $view_mode, $langcode) {

    $node_types = variable_get('vogue_nodetypes', '');
    // if(in_array($node->type, $node_types, true)) {
    //   drupal_set_message( '<pre>'.print_r("Yes", TRUE). '</pre>');
    // }

/*
        $node->content['extra_link'] = array(
            '#weight' => 10,
            '#theme' => 'link',
            '#path' => 'path_to_content',
            '#text' => t('An extra link'),
            '#options' => array(
                'attributes' => array(),
                'html' => FALSE
            ),
        );
*/

  }

  function voguepay_getTransactionStatus() {
    ##Check if transaction ID has been submitted
    $merchant_id= variable_get('vogue_merchant','');

    if( count(trim($merchant_id)) < 2) exit;

    if(isset($_POST['transaction_id'])){
    	//get the full transaction details as an json from voguepay
    	$json = file_get_contents('https://voguepay.com/?v_transaction_id='.$_POST['transaction_id'].'&type=json');
    	//create new array to store our transaction detail
    	$transaction = json_decode($json, true);

    	/*
    	Now we have the following keys in our $transaction array
    	$transaction['merchant_id'],
    	$transaction['transaction_id'],
    	$transaction['email'],
    	$transaction['total'],
    	$transaction['merchant_ref'],
    	$transaction['memo'],
    	$transaction['status'],
    	$transaction['date'],
    	$transaction['referrer'],
    	$transaction['method']
    	*/

    	if($transaction['total'] == 0)die('Invalid total');
    	if($transaction['status'] != 'Approved')die('Failed transaction');
    	if($transaction['merchant_id'] != $merchant_id)die('Invalid merchant');

    	/*You can do anything you want now with the transaction details or the merchant reference.
    	You should query your database with the merchant reference and fetch the records you saved for this transaction.
    	Then you should compare the $transaction['total'] with the total from your database.*/
    }
  }

/*
  function voguepay_commerce_payment_method_info() {
    $payment_methods['paypal_wps'] = array(
      'base' => 'commerce_paypal_wps',
      'title' => t('PayPal WPS'),
      'short_title' => t('PayPal'),
      'description' => t('PayPal Website Payments Standard'),
      'terminal' => FALSE,
      'offsite' => TRUE,
      'offsite_autoredirect' => TRUE,
    );
  }

  function voguepay_commerce_payment_transaction_status_info() {
    $statuses[COMMERCE_PAYMENT_STATUS_SUCCESS] = array(
      'status' => COMMERCE_PAYMENT_STATUS_SUCCESS,
      'title' => t('Success'),
      'icon' => drupal_get_path('module', 'commerce_payment') . '/theme/icon-success.png',
      'total' => TRUE,
    );
  }
*/
