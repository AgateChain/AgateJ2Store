<?php

// no direct access
defined('_JEXEC') or die('Restricted access');

//include necessary libraries for plugin
require_once JPATH_ADMINISTRATOR . '/components/com_j2store/library/plugins/payment.php';
require_once JPATH_ADMINISTRATOR . '/components/com_j2store/helpers/j2store.php';

/**
 * Class plgJ2StorePayment_agate
 */
class plgJ2StorePayment_agate extends J2StorePaymentPlugin {

    /**
     * Name of plugin
     * @var string
     */
    var $_element   = 'payment_agate';

    /**
     * Log errors
     * @var bool
     */
    var $_isLog     = false;

    /**
     * Status OK
     */
    const STATUS_OK = 'OK';
    /**
     * Status FAIL
     */
    const STATUS_FAIL = 'FAIL';

    /**
     * Default config for plugin
     * @var array
     */
    protected $_default = array(
        'api_version' => 'dev',
        'channel'   => '',
        'ch_lock'   => 0,
        'type'      => 0,
    );

    /**
     * @inheritdoc
     * @param object $subject
     * @param array $config
     */
    public function __construct($subject, $config) {
        parent::__construct($subject, $config);
        $this->loadLanguage( 'plg_j2store_payment_agate', JPATH_ADMINISTRATOR );
    }

    /**
     * @inheritdoc
     * Prepare for payment
     *
     * @param array $data
     * @return string
     */
    public function _prePayment( $data ) {
        $vars   = new JObject();
        $info   = $this->getOrderInformation($data);

        $price          = number_format($data['orderpayment_amount'], 2, '.', '');
        $baseUri        = "http://gateway.agate.services/";
        $convertUrl     = "http://gateway.agate.services/convert/";
        $api_key        = $this->params->get('api_key');
        $currencySymbol = $data['order']->currency_code;
        $redirect_url   = $this->getBaseUrl(). "index.php?option=com_j2store&task=checkout.confirmPayment&orderpayment_type=payment_agate";
        $order_total    = $price;

        error_log(" ".$baseUri." ".$api_key." ".$redirect_url." ".$currencySymbol);
        error_log("Cost = ". $price);

        $amount_iUSD = $this->convertCurToIUSD($convertUrl, $order_total, $api_key, $currencySymbol);

        //Needed for Agate
        $vars->baseUri        = "http://gateway.agate.services/";
        $vars->amount_iUSD    = $amount_iUSD;
        $vars->order_total    = $order_total;
        $vars->currencySymbol = $currencySymbol;
        $vars->api_key        = $api_key;
        $vars->redirect_url   = $redirect_url;



        $html = $this->_getLayout('prepayment', $vars);

        return $html;
    }

    /**
     * Get base url depend on option. This method override original base url
     * with https if it is necessary
     *
     * @return mixed|string
     */
    private function getBaseUrl()
    {
        if($this->params->get('ssl', 0) == 0){
            return JURI::base();
        }
        return  str_replace("http://", "https://", JURI::base());
    }

    /**
     * This method is responsible both form processing notification and
     *  generate 'thank you' message. Depend on notification parameter getting from
     *  $_GET it trigger processing notification or generateing 'thank you' message
     *
     * @inheritdoc
     * Response status
     * @param array $data
     * @throws Exception
     */
    public function _postPayment( $data ) {

        $app = JFactory::getApplication();
        $isNotification = $app->input->get->get('notification');

        if($isNotification){
            $this->processNotification($app->input);
            $app->close();
        }

        $status = $app->input->get->getString('status');

        error_log("Call back response => " . var_export($_REQUEST, TRUE) );
        $vars = new JObject();
        if($_REQUEST['stateId']==3)
          $vars->message = JText::_('J2STORE_CONFIRMED');
        else {
          $vars->message = JText::_('J2STORE_FAILED');
        }
        // $message =$this->createConfirmMessage($status);
        return  $this->_getLayout('postpayment', $vars);

    }


    /**
     * Set complete status. This method is internal j2store method which should
     * set everything what is necessary after complete payment
     *
     * @param $orderId
     */
    private function setCompleteStatus($orderId)
    {
        $order = $this->getOrder($orderId);
        $order->payment_complete();
        $this->save($order);
    }


    /**
     * This method change order status. Status is defined as $order_state_id
     * allowed id are:
     *  1 confirmed
     *  2 processing
     *  3 something goes wrong
     *  4 pending
     *  5 new
     *  6 canceled
     *
     * @param $orderId
     * @param $order_state_id
     */
    private function setWrongStatus($orderId , $order_state_id)
    {
        $order = $this->getOrder($orderId);
        $order->update_status( $order_state_id, true );
        $order->reduce_order_stock();
        $this->save($order);
    }

    /**
     * Saveing order model and trigger remove item from card if everything goes ok
     *
     * @param $order
     */
    private function save($order)
    {
        if($order->store()){
            $order->empty_cart();
        }
    }

    /**
     * Get order object from  model
     *
     * @param $orderId
     * @return static
     */
    private function getOrder($orderId)
    {
        F0FTable::addIncludePath ( JPATH_ADMINISTRATOR . '/components/com_j2store/tables' );
        $order = F0FTable::getInstance ( 'Order', 'J2StoreTable' )->getClone();
        $order->load(array('order_id' => $orderId));
        return $order;
    }

    /**
     * Based on status set error or ok message displaying to customer
     *
     * @param $status
     * @return JObject
     */
    private function createConfirmMessage($status)
    {
        $vars = new JObject();

        switch ($status){
            case self::STATUS_OK:
                $vars->message = JText::_('J2STORE_CONFIRMED');
                break;
            default:
                $vars->message = JText::_('J2STORE_FAILED');
        }
        return $vars;
    }

    /**
     * Get actual language for page
     * @return mixed
     */
    private function getLanguage() {
        $lang   = JFactory::getLanguage();
        $lang   = explode( '-', $lang->getTag() );
        return $lang[0];
    }

    /**
     * Get order information
     * @param $data
     * @return mixed
     */
    private function getOrderInformation( $data ) {
        $order = $data['order']->getOrderInformation();
        return $order;
    }

    /**
     * Get price from order
     * @param $order_id
     * @return int
     */
    private function getPrice($order_id) {
        $order = $this->getOrder($order_id);
        if($order){
            return $order->order_total;
        }
        return 0;
    }

    public function convertCurToIUSD($url, $amount, $api_key, $currencySymbol) {
        error_log("Entered into Convert Amount");
        // return Agate::convert_irr_to_btc($url, $amount, $signature);
        $ch = curl_init($url.'?api_key='.$api_key.'&currency='.$currencySymbol.'&amount='. $amount);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          'Content-Type: application/json')
      );

      $result = curl_exec($ch);
      $data = json_decode( $result , true);
      error_log("Response => ".var_export($data, TRUE));;
      // Return the equivalent bitcoin value acquired from Agate server.
      return (float) $data["result"];

      }

    }
