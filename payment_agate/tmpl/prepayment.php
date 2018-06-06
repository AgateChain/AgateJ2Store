<?php

//no direct access
defined('_JEXEC') or die('Restricted access');

$config = JFactory::getConfig();

?>
<style type="text/css">
    #agate_form { width: 100%; }
    #agate_form td { padding: 5px; }
    #agate_form .field_name { font-weight: bold; }
</style>
<form id="j2store_agate_form"
      action="<?php echo $vars->$baseUri."?api_key=" . $api_key; ?>"
      method="post"
      name="adminForm"
    >
    <input type='hidden' autocomplete='off' name='amount' value='<?php echo$vars->order_total ?>'/>
    <input type='hidden' autocomplete='off' name='amount_iUSD' value='<?php echo $vars->amount_iUSD ?>'/>
    <input type='hidden' autocomplete='off' name='callBackUrl' value='<?php echo $vars->redirect_url ?>'/>
    <input type='hidden' autocomplete='off' name='api_key' value='<?php echo $vars->api_key ?>'/>
    <input type='hidden' autocomplete='off' name='cur' value='<?php echo $vars->currencySymbol ?>'/>

    <button type="submit" class="button btn btn-success"><?php echo JText::_('J2STORE_CHECKOUT_CONTINUE'); ?></button>
</form>
