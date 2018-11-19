# Billplz for FB Inboxer
Accept payment using Billplz by using this plugin

# System Requirements

1. [FB Inboxer - Master Facebook Messenger Marketing Software](https://codecanyon.net/item/fb-inboxer-master-facebook-messenger-marketing-software/19578006?s_rank=1)
2. Build on release - **9 November 18**
3. **PHP 7.0** or newer

# Installation

1. Copy **all** folder to your  **installation directory**.
2. Study the code below and replicate it on **application/controllers/Payment.php**:
- Add new line after **stripe_class** and insert **billplz_class** as shown:
    ```php
    $this->load->library('stripe_class');
    // Load billplz class
    $this->load->library('billplz_class');
    ```
- Replace column and fields line with newer one as shown:

    ```php
    // Old: Remove this line
    $crud->columns('paypal_email', 'stripe_secret_key', 'stripe_publishable_key', 'currency');
    $crud->fields('paypal_email', 'stripe_secret_key', 'stripe_publishable_key', 'currency');

    // New: Replace with this one
    $crud->columns('paypal_email', 'stripe_secret_key', 'stripe_publishable_key', 'currency', 'billplz_api_key', 'billplz_x_signature', 'billplz_collection_id');
    $crud->fields('paypal_email', 'stripe_secret_key', 'stripe_publishable_key', 'currency', 'billplz_api_key', 'billplz_x_signature', 'billplz_collection_id');
    ```
- Add new line after **stripe_publishable_key** and insert **billplz_api_key**, **billplz_x_signature** and **billplz_x_signature** as shown:
    ```php
    $crud->display_as('stripe_publishable_key', $this->lang->line("Stripe Publishable Key"));

    // Display Billplz Settings
    $crud->display_as('billplz_api_key', $this->lang->line("Billplz API Key"));
    $crud->display_as('billplz_x_signature', $this->lang->line("Billplz X Signature Key"));
    $crud->display_as('billplz_collection_id', $this->lang->line("Billplz Collection ID"));
    ```
- Add new line after **echo $button** and insert **billplz_amount**, **billplz_description**, **billplz_reference_2** and **echo set_button** as shown:
    ```php
    if ($paypal_email!="") {
        echo $button = $this->paypal_class->set_button();
    }

    // Set session data to pay
    $this->session->set_userdata('billplz_amount', $payment_amount);
    $this->session->set_userdata('billplz_description', $this->config->item("product_name")." : ".$package_name." (".$package_validity." days)");
    $this->session->set_userdata('billplz_reference_2', $package_id);

    if ($payment_config[0]['billplz_api_key'] != "" & $payment_config[0]['billplz_x_signature'] !=="") {
        echo $this->billplz_class->set_button();
    }
    ```
    You may refer to example: [Wiki](https://github.com/wzul/Billplz-for-FB-Inboxer/wiki/Example-code-for-Payment.php)
3. Add column on table **payment_config** AFTER **deleted** column:
    - **billplz_api_key**: _VARCHAR(250)_
    - **billplz_x_signature**: _VARCHAR(250)_
    - **billplz_collection_id**: _VARCHAR(250)_

    You may refer to example: [Wiki](https://github.com/wzul/Billplz-for-FB-Inboxer/wiki/Table-Structure:-payment_config)

4. Set your **API Key**, **Collection ID** and **X Signature Key** on FB Inboxer as follows:
    - **Administration** >> **Payment** >> **Payment Settings**
    - Click on the **pencil icon**
5. **Update** & Done

# Notes

Facebook: [Billplz Dev Jam](https://www.facebook.com/groups/billplzdevjam/)
