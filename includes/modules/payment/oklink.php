<?php

class oklink
{

    /**
     * @var
     */
    public $code;

    /**
     * @var
     */
    public $title;

    /**
     * @var
     */
    public $description;

    /**
     * @var
     */
    public $enabled;

    /**
     * @var
     */
    private $response;

    /**
     */
    function oklink()
    {
        global $order;

        $this->code        = 'oklink';
        $this->title       = MODULE_PAYMENT_OKLINK_TEXT_TITLE;
        $this->description = MODULE_PAYMENT_OKLINK_TEXT_DESCRIPTION;
        $this->sort_order  = MODULE_PAYMENT_OKLINK_SORT_ORDER;
        $this->enabled     = ((MODULE_PAYMENT_OKLINK_STATUS == 'True') ? true : false);

        if ((int)MODULE_PAYMENT_OKLINK_ORDER_STATUS_ID > 0)
        {
            $this->order_status = MODULE_PAYMENT_OKLINK_ORDER_STATUS_ID;
            $payment='oklink';
        }
        else if ($payment=='oklink')
        {
            $payment='';
        }

        if (is_object($order))
        {
            $this->update_status();
        }

        $this->email_footer = MODULE_PAYMENT_OKLINK_TEXT_EMAIL_FOOTER;
    }

    /**
     */
    function update_status () {
        global $order;

        if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_OKLINK_ZONE > 0) )
        {
            $check_flag  = false;
            $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . intval(MODULE_PAYMENT_OKLINK_ZONE) . "' and zone_country_id = '" . intval($order->billing['country']['id']) . "' order by zone_id");

            while ($check = tep_db_fetch_array($check_query))
            {
                if ($check['zone_id'] < 1)
                {
                    $check_flag = true;
                    break;
                }
                elseif ($check['zone_id'] == $order->billing['zone_id'])
                {
                    $check_flag = true;
                    break;
                }
            }

            if ($check_flag == false)
            {
                $this->enabled = false;
            }
        }

        // check supported currency
        $currencies = array_map('trim',explode(",",MODULE_PAYMENT_OKLINK_CURRENCIES));

        if (array_search($order->info['currency'], $currencies) === false)
        {
            $this->enabled = false;
        }

        // check that api key and secrect is not blank
        if (!MODULE_PAYMENT_OKLINK_APIKEY OR !strlen(MODULE_PAYMENT_OKLINK_APIKEY) OR !MODULE_PAYMENT_OKLINK_APISECRET OR !strlen(MODULE_PAYMENT_OKLINK_APISECRET))
        {
            print 'no secret '.MODULE_PAYMENT_OKLINK_APIKEY;
            $this->enabled = false;
        }
    }

    /**
     * @return boolean
     */
    function javascript_validation ()
    {
        return false;
    }

    /**
     * @return array
     */
    function selection ()
    {
        return array('id' => $this->code, 'module' => $this->title);
    }

    /**
     * @return boolean
     */
    function pre_confirmation_check ()
    {
        return false;
    }

    /**
     * @return boolean
     */
    function confirmation ()
    {
        return false;
    }

    /**
     * @return boolean
     */
    function process_button ()
    {
        return false;
    }

    /**
     * @return false
     */
    function before_process ()
    {
        return false;
    }

    /**
     * @return false
     */
    function after_process ()
    {
        global $insert_id, $order;

        require_once 'oklink/Oklink.php';

        tep_db_query("update ". TABLE_ORDERS. " set orders_status = " . intval(MODULE_PAYMENT_OKLINK_UNPAID_STATUS_ID) . " where orders_id = ". intval($insert_id));
        
        $params = array(
            'name'              => "Order #{$insert_id}",
            'price'             => $order->info['total'],
            'price_currency'    => $order->info['currency'],
            'custom'            => $insert_id, 
            // 'callback_url'      => tep_href_link('oklink_callback.php'),
            // 'success_url'       => tep_href_link(FILENAME_ACCOUNT),
        );

        $client = OklinkSDK::withApiKey(MODULE_PAYMENT_OKLINK_APIKEY, MODULE_PAYMENT_OKLINK_APISECRET);

        $this->response = $client->buttonsButton($params);

        if ( $this->response && $this->response->button){
               $button_id = $this->response->button->id;
               $_SESSION['cart']->reset(true);
               $url = OklinkBase::WEB_BASE.'merchant/mPayOrderStemp1.do?buttonid='.$button_id;
               tep_redirect($url);               
        }else{
            tep_db_query("delete from " . TABLE_ORDERS . " where orders_id = '" . (int)$insert_id . "'");
            tep_db_query("delete from " . TABLE_ORDERS_PRODUCTS . " where orders_id = '" . (int)$insert_id . "'");
            tep_db_query("delete from " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . " where orders_id = '" . (int)$insert_id . "'");
            tep_db_query("delete from " . TABLE_ORDERS_STATUS_HISTORY . " where orders_id = '" . (int)$insert_id . "'");
            tep_db_query("delete from " . TABLE_ORDERS_TOTAL . " where orders_id = '" . (int)$insert_id . "'");
            tep_redirect(tep_href_link(FILENAME_SHOPPING_CART, 'error_message=' . urlencode('There was a problem processing your payment: unknown error or response.')));
        }
        return false;
    }

    /**
     * @return boolean
     */
    function get_error ()
    {
        return false;
    }

    /**
     * @return integer
     */
    function check ()
    {
        if (!isset($this->_check))
        {
            $check_query  = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_OKLINK_STATUS'");
            $this->_check = tep_db_num_rows($check_query);
        }

        return $this->_check;
    }

    /**
     */
    function install ()
    {
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) "
            ."values ('Enable BitPay Module', 'MODULE_PAYMENT_OKLINK_STATUS', 'False', 'Do you want to accept bitcoin payments via oklink.com?', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now());");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) "
            ."values ('API Key', 'MODULE_PAYMENT_OKLINK_APIKEY', '', 'Enter you API Key which you generated at oklink.com', '6', '0', now());");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) "
            ."values ('API Secret', 'MODULE_PAYMENT_OKLINK_APISECRET', '', 'Enter you API Secret which you generated at oklink.com', '6', '0', now());");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) "
            ."values ('Unpaid Order Status', 'MODULE_PAYMENT_OKLINK_UNPAID_STATUS_ID', '" . intval(DEFAULT_ORDERS_STATUS_ID) .  "', 'Automatically set the status of unpaid orders to this value.', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) "
            ."values ('Paid Order Status', 'MODULE_PAYMENT_OKLINK_PAID_STATUS_ID', '2', 'Automatically set the status of paid orders to this value.', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) "
            ."values ('Currencies', 'MODULE_PAYMENT_OKLINK_CURRENCIES', 'BTC, USD, CNY', 'Only enable Oklink payments if one of these currencies is selected (note: currency must be supported by oklink.com).', '6', '0', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) "
            ."values ('Payment Zone', 'MODULE_PAYMENT_OKLINK_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', 'tep_get_zone_class_title', 'tep_cfg_pull_down_zone_classes(', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) "
            ."values ('Sort Order of Display.', 'MODULE_PAYMENT_OKLINK_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '2', now())");
    }

    /**
     */
    function remove ()
    {
        tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    /**
     * @return array
     */
    function keys()
    {
        return array(
            'MODULE_PAYMENT_OKLINK_STATUS',
            'MODULE_PAYMENT_OKLINK_APIKEY',
            'MODULE_PAYMENT_OKLINK_APISECRET',
            'MODULE_PAYMENT_OKLINK_UNPAID_STATUS_ID',
            'MODULE_PAYMENT_OKLINK_PAID_STATUS_ID',
            'MODULE_PAYMENT_OKLINK_SORT_ORDER',
            'MODULE_PAYMENT_OKLINK_ZONE',
            'MODULE_PAYMENT_OKLINK_CURRENCIES');
    }
}
