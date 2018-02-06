<form name="form1" id="form1" method="post" action="<?= $html['tjurl'] ?>" target="_self">
          <input type="hidden" name="pay_memberid" value="<?= $html['pay_memberid'] ?>">
          <input type="hidden" name="pay_orderid" value="<?= $html['pay_orderid'] ?>">
          <input type="hidden" name="pay_amount" value="<?= $html['pay_amount'] ?>">
        <input type="hidden" name="pay_orderid" value="<?= $html['pay_orderid'] ?>">
          <input type="hidden" name="pay_applydate" value="<?= $html['pay_applydate'] ?>">
          <input type="hidden" name="pay_bankcode" value="<?= $html['pay_bankcode'] ?>">
          <input type="hidden" name="pay_notifyurl" value="<?= $html['pay_notifyurl'] ?>">
          <input type="hidden" name="pay_callbackurl" value="<?= $html['pay_callbackurl'] ?>">
          <input type="hidden" name="pay_md5sign" value="<?= $html['pay_md5sign'] ?>">
          <input type="hidden" name="pay_attach" value="<?= $html['pay_attach'] ?>">
          <input type="hidden" name="pay_productname" value="<?= $html['pay_productname'] ?>">

</form>
<script language="javascript">document.form1.submit();</script>

