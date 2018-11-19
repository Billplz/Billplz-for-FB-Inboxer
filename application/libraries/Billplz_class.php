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
        $this->CI =&get_instance();
        $this->CI->load->database();

        $databae_name= $this->CI->db->database;
    }

    public function set_button()
    {
        $createbill = site_url()."billplz_ipn/create_bill";
        $button="";

        $button.= "<form action='{$createbill}' method='get' style='padding: 0; margin: 0;'>";

        $button_url=base_url()."assets/images/billplz_btn.png";
        $button.= "<input type='image' src='{$button_url}' border='0' name='submit' alt='Billplz'>";
        $button.= "<img alt='' border='0' src='{$button_url}' width='1' height='1'>";
        $button.= "</form>";

        return $button;
    }
}
