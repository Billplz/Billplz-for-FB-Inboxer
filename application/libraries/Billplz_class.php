<?php
class Billplz_class
{
    public $api_key;
    public $x_signature;
    public $collection_id;
    public $amount;
    public $name;
    public $email;
    public $description;
    public $callback_url;
    public $redirect_url;
    public $id;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();

        $databae_name= $this->CI->db->database;
    }

    public function set_button()
    {
        $createbill =site_url()."billplz_ipn/create_bill";
        $button="";

        $button.= "<form action='{$createbill}' method='get' style='padding: 0; margin: 0;'>";

        $button_url=base_url()."assets/images/billplz_btn.png";
        $button.= "<input type='image' src='{$button_url}' border='0' name='submit' alt='Billplz'>";
        $button.= "<img alt='' border='0' src='{$button_url}' width='1' height='1'>";
        $button.= "</form>";

        return $button;
    }

    /****
         This run_ipn() function will return the verified status that is payment is VERIFIED or NOTVERIFIED. And some correspoding
         data of the payment.

             $payment_info=$billplz_ipn->run_ipn();
            $verify_status=$payment_info['verify_status'];
            $first_name=$payment_info['data']['first_name'];
            $last_name=$payment_info['data']['last_name'];
            $buyer_email=$payment_info['data']['payer_email'];
            $receiver_email=$payment_info['data']['receiver_id'];
            $country=$payment_info['data']['address_country'];
            $payment_date=$payment_info['data']['payment_date'];
            $transaction_id=$payment_info['data']['txn_id'];
            $payment_type=$payment_info['data']['payment_type'];

    ***/
}
