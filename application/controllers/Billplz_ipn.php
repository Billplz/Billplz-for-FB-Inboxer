<?php

class Billplz_ipn extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('billplz_class');
        $this->load->model('basic');
        set_time_limit(0);

        //Tambah sini
        $this->load->library('billplz_api');
    }

    public function create_bill()
    {
        $where['where'] = array('deleted'=>'0');
        $payment_config = $this->basic->get_data('payment_config', $where, $select='');

        $billplz_api_key = $payment_config[0]['billplz_api_key'];
        $billplz_collection_id = $payment_config[0]['billplz_collection_id'];
        //$billplz_x_signature = $payment_config[0]['billplz_x_signature'];

        $amount = $this->session->userdata('billplz_amount');
        $this->session->unset_userdata('billplz_amount');

        $username = $this->session->userdata('username');
        $email = $this->session->userdata('user_login_email');

        $description = $this->session->userdata('billplz_description');
        $this->session->unset_userdata('billplz_description');

        $callback_url = site_url()."billplz_ipn/callbackredirect";
        $redirect_url = $callback_url;

        $reference_2 = $this->session->userdata('billplz_reference_2');
        $this->session->unset_userdata('billplz_reference_2');

        $user_id = $this->session->userdata('user_id');

        $billplz = new Billplz_API($billplz_api_key);
        $billplz    ->setCollection($billplz_collection_id)
            ->setAmount($amount)
            ->setName($username)
            ->setEmail($email)
            ->setDescription($description)
            ->setDeliver('0')
            ->setPassbackURL($callback_url, $redirect_url)
            ->setReference_2_Label('ID')
            ->setReference_2($user_id . '_'. $reference_2)
            ->create_bill(true);
        $this->load->helper('url');

        redirect($billplz->getURL());
    }

    public function callbackredirect()
    {
        $where['where'] = array('deleted'=>'0');
        $payment_config = $this->basic->get_data('payment_config', $where, $select='');
        $billplz_api_key = $payment_config[0]['billplz_api_key'];
        $billplz_x_signature = $payment_config[0]['billplz_x_signature'];
        try {
            if (isset($_POST['id'])) {
                $data= Billplz_API::getCallbackData($billplz_x_signature);
            } elseif (isset($_GET['billplz']['id'])) {
                sleep(5);
                $data= Billplz_API::getRedirectData($billplz_x_signature);
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            exit;
        }
        if (!$data['paid'] && isset($_POST['id'])) {
            exit;
        }

        $paymentHistory=base_url()."payment/member_payment_history";
        if (!$data['paid']) {
            redirect($paymentHistory);
            exit;
        }

        $billplz = new Billplz_API($billplz_api_key);
        $moreData = $billplz->check_bill($data['id']);

        $user_id_package_id=explode('_', $moreData['reference_2']);
        $user_id=$user_id_package_id[0];
        $package_id=$user_id_package_id[1];

        $simple_where['where'] = array('user_id'=>$user_id);
        $select = array('cycle_start_date','cycle_expired_date');

        $prev_payment_info = $this->basic->get_data('transaction_history', $simple_where, $select, $join='', $limit='1', $start=0, $order_by='ID DESC', $group_by='');

        $prev_cycle_expired_date="";

        $config_data=array();
        $price=0;
        $package_data=$this->basic->get_data("package", $where=array("where"=>array("package.id"=>$package_id)));
        if (is_array($package_data) && array_key_exists(0, $package_data)) {
            $price=$package_data[0]["price"];
        }
        $validity=$package_data[0]["validity"];

        $validity_str='+'.$validity.' day';

        foreach ($prev_payment_info as $info) {
            $prev_cycle_expired_date=$info['cycle_expired_date'];
        }

        if ($prev_cycle_expired_date=="") {
            $cycle_start_date=date('Y-m-d');
            $cycle_expired_date=date("Y-m-d", strtotime($validity_str, strtotime($cycle_start_date)));
        } elseif (strtotime($prev_cycle_expired_date) < strtotime(date('Y-m-d'))) {
            $cycle_start_date=date('Y-m-d');
            $cycle_expired_date=date("Y-m-d", strtotime($validity_str, strtotime($cycle_start_date)));
        } elseif (strtotime($prev_cycle_expired_date) > strtotime(date('Y-m-d'))) {
            $cycle_start_date=date("Y-m-d", strtotime('+1 day', strtotime($prev_cycle_expired_date)));
            $cycle_expired_date=date("Y-m-d", strtotime($validity_str, strtotime($cycle_start_date)));
        }

        $bill_id = '';
        $simple_where['where'] = array('transaction_id'=>$data['id']);
        $select = array('transaction_id');

        $prev_payment_info = $this->basic->get_data('transaction_history', $simple_where, $select, $join='', $limit='1', $start=0, $order_by='ID DESC', $group_by='');
        foreach ($prev_payment_info as $info) {
            $bill_id=$info['transaction_id'];
        }
        if (!empty($bill_id) && isset($_GET['billplz']['id'])) {
            redirect($paymentHistory);
            exit;
        } elseif (!empty($bill_id) && isset($_POST['id'])) {
            exit;
        }

        $insert_data=array(
                "verify_status"     =>'PASSED: BILLPLZ',
                "first_name"        =>$moreData['name'],
                "last_name"         =>'',
                "paypal_email"      =>$moreData['email'],
                "receiver_email"    =>$billplz_api_key . ': BILLPLZ',
                "country"           =>'MALAYSIA',
                "payment_date"      =>$billplz->get_bill_paid_time($data['id']),
                "payment_type"      =>'BILLPLZ',
                "transaction_id"    =>$data['id'],
                "user_id"           =>$user_id,
                "package_id"        =>$package_id,
                "cycle_start_date"  =>$cycle_start_date,
                "cycle_expired_date"=>$cycle_expired_date,
                "paid_amount"       =>$moreData['amount'] /100
            );

        $this->basic->insert_data('transaction_history', $insert_data);

        /** Update user table **/
        $table='users';
        $where=array('id'=>$user_id);
        $data=array('expired_date'=>$cycle_expired_date,"package_id"=>$package_id);
        $this->basic->update_data($table, $where, $data);


        $product_short_name = $this->config->item('product_short_name');
        $from = $this->config->item('institute_email');
        $mask = $this->config->item('product_name');
        $subject = "Payment Confirmation";
        $where = array();
        $where['where'] = array('id'=>$user_id);
        $user_email = $this->basic->get_data('users', $where, $select='');
        $to = $user_email[0]['email'];
        $message = "Congratulation,<br/> we have received your payment successfully. Now you are able to use {$product_short_name} system till {$cycle_expired_date}.<br/><br/>Thank you,<br/><a href='".base_url()."'>{$mask}</a> team";
        //send mail to user
        $this->_mail_sender($from, $to, $subject, $message, $mask, $html=0);

        $to = $from;
        $subject = "New Payment Made";
        $message = "New payment has been made by {$user_email[0]['name']}";
        //send mail to admin
        $this->_mail_sender($from, $to, $subject, $message, $mask, $html=0);

        if (isset($_POST['id'])) {
            echo 'ALL IS WELL';
            exit;
        }

        redirect($paymentHistory);
    }


    public function _mail_sender($from = '', $to = '', $subject = '', $message = '', $mask = "", $html = 0, $smtp = 1)
    {
        if ($from!= '' && $to!= '' && $subject!='' && $message!= '') {
            if (!is_array($to)) {
                $to=array($to);
            }

            if ($smtp == '1') {
                $where2 = array("where" => array('status' => '1'));
                $email_config_details = $this->basic->get_data(
                    "email_config",
                    $where2,
                    $select = '',
                    $join = '',
                    $limit = '',
                    $start = '',
                                                        $group_by = '',
                    $num_rows = 0
                );

                if (count($email_config_details) == 0) {
                    $this->load->library('email');
                } else {
                    foreach ($email_config_details as $send_info) {
                        $send_email = trim($send_info['email_address']);
                        $smtp_host = trim($send_info['smtp_host']);
                        $smtp_port = trim($send_info['smtp_port']);
                        $smtp_user = trim($send_info['smtp_user']);
                        $smtp_password = trim($send_info['smtp_password']);
                    }

                    /*****Email Sending Code ******/
                    $config = array(
                  'protocol' => 'smtp',
                  'smtp_host' => "{$smtp_host}",
                  'smtp_port' => "{$smtp_port}",
                  'smtp_user' => "{$smtp_user}", // change it to yours
                  'smtp_pass' => "{$smtp_password}", // change it to yours
                  'mailtype' => 'html',
                  'charset' => 'utf-8',
                  'newline' =>  '\r\n',
                  'smtp_timeout' => '30'
                 );

                    $this->load->library('email', $config);
                }
            } /*** End of If Smtp== 1 **/

            if (isset($send_email) && $send_email!= "") {
                $from = $send_email;
            }
            $this->email->from($from, $mask);
            $this->email->to($to);
            $this->email->subject($subject);
            $this->email->message($message);
            if ($html == 1) {
                $this->email->set_mailtype('html');
            }

            if ($this->email->send()) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}
