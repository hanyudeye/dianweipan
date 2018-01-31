<?php use common\helpers\Html; ?>

<?= $html ?>
<p class="cl pd-5 mt-20">
    <span>当前总共入金了<span class="count" style="color:#E31;"><?= $count ?></span>元</span>
</p>
<?php if (u()->isSuper()): ?>
<a class="userExcel btn btn-success radius r">导出入金记录</a>
<?php endif ?>
<script>
$(function () {
    $(".userExcel").on('click', function () {
        var str = '';
        $('.search-form ul>li').each(function(){
            var $this = $(this).find('.input-text');
            if ($this.attr('name') != undefined) {
                var value = $this.val();
                if (value.length > 0) {          
                    str += $this.attr('name') + '=' + value + '&';
                }
            }
        });
        var url = "<?= url(['user/chargeExcel?']) ?>" + str;
        window.location.href = url;
    });


    $(".list-container").on('click', '.giveBtn', function () {
        var $this = $(this);
        var node=$(this).parents('tr').find('td').get(3);
        var value=$(node).text();
        var msg="确定充值"+value+'?';
        $.confirm(msg, function () {
            $.post($this.attr('href'), {amount: value}, function (msg) {

                if (msg.state) {
                    $.alert(msg.info || '充值成功', function () {
                        location.reload();
                    });
                } else {
                    $.alert(msg.info);
                }
            }, 'json');
        });
        return false;
    });
});
</script>