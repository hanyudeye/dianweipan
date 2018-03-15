<?php $this->regCss('jilu.css') ?>
<?php $this->regCss('manager.css') ?>

<a href="javascript:history.back(-1);" style="font-size: 16px;color:#ffffff;display: block;width: 100%;height: 40px;line-height: 40px;background-color: #d9333f;padding-left: 20px">返回</a>

<div class="outMoney">
<?= $this->render('_outMoney', compact('data')) ?>
</div>

<?php if ($pageCount < 2): ?>
    <div class="deta_more" id="deta_more_div">没有更多了</div>
<?php else: ?>
    <div class="addMany" style="text-align: center;margin-top: 60px;">
        <a style="" type="button" value="加载更多" id="loadMore" data-count="<?= $pageCount ?>" data-page="1">加载更多</a>
    </div>
<?php endif ?>

<script type="text/javascript">
$(".addMany").on('click', '#loadMore', function() {
    var $this = $(this),
        page = parseInt($this.data('page')) + 1;

    $.get('', {p:page}, function(msg) {
        $(".outMoney").append(msg.info);
        $this.data('page', page);
        if (page >= parseInt($this.data('count'))) {
            $('.addMany').hide();
        }
    });
});
</script>