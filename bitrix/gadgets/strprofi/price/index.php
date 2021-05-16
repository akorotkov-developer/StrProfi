<?




?>

<style type="text/css">
    p a{
        display: none;
    }
    p span{
        display: none;
    }
</style>




<p><button class="download">Скачать прайс-лист</button> <button class="generate">Сгенерировать кэш</button></p>
<p><button class="clear">Очистить кэш</button></p>


<script type="text/javascript">




    $('.download').click(function(){
        //if ($(this).is('.generate')) return false;
        window.open("/price-print.php", '_blank');

    })

    $('.clear').click(function (e) {

        if ($('.download').is('[disabled]')) {
            alert('Нельзя очистить кэш во время генерации прайс-листа');
            return false;
        }
        t = $(this);
        t.attr('disabled', 'disabled');
        t.html('Идет очистка кэша прайс-листа...');
        g = {'clear_cache':true}
        $.get('/price-print.php', g, function () {
            t.html('Кэш очищен');
            $('.download').hide();
            $('.generate').show();
        })
    });
    $('.generate').click(function (e) {

        t = $(this);

        t.addClass('active');
        t.html('Генерируется прайс-лист...').attr('disabled', 'disabled');
        g = {}
        $.get('/price-print.php', g, function () {
            t.removeClass('active').removeAttr('disabled').hide();
            $('.download').show();
            $('.clear').html('Очистить кэш').removeAttr('disabled');
        })
    });
    if (UrlExists('https://strprofi.ru/tmp/cats2.xml')) {
        $('.generate').hide();
        $('.download').show()
    }
    else {
        $('.download').hide()
        $('.generate').show();
        $('.clear').html('Кэш очищен').attr('disabled', 'disabled');
        //$('.download').html('Сгенерировать прайс-лист').addClass('generate').removeClass('download');
    }


    function UrlExists(url) {
        var http = new XMLHttpRequest();
        http.open('HEAD', url, false);
        http.send();
        return http.status != 404;
    }
</script>