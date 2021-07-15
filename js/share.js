;(function ($) {

    $.switcher = function (filter) {

        var $haul = $(filter);

        $haul.each(function () {

            var $checkbox = $(this).hide(),
                $switcher = $(document.createElement('div'))
                    .addClass('ui-switcher')
                    .attr('aria-checked', $checkbox.is(':checked'));

            if ('radio' === $checkbox.attr('type')) {
                $switcher.attr('data-name', $checkbox.attr('name'));
            }

            var toggleSwitch = function (e) {
                if (e.target.type === undefined) {
                    $checkbox.trigger(e.type);
                }
                $switcher.attr('aria-checked', $checkbox.is(':checked'));
                if ('radio' === $checkbox.attr('type')) {
                    $('.ui-switcher[data-name=' + $checkbox.attr('name') + ']')
                        .not($switcher.get(0))
                        .attr('aria-checked', false);
                }
            };

            $switcher.on('click', toggleSwitch);
            $checkbox.on('click', toggleSwitch);

            $switcher.insertBefore($checkbox);
        });

    };

})(jQuery);

function Share() {
    var link = $(this);
    if (link.prop("disabled")) return;
    link.prop("disabled", true);
    setTimeout(function(){link.prop("disabled", false);},500);

    var sh_div = $( this );
    var ROW = sh_div.parents( "tr" );
    if (!ROW) return;
    var Name = ROW.find( "a" ).prop("name");
    var is_file = true;
    if (ROW.hasClass('dir')) {
        is_file = false;        
    }
    
    if (!sh_div.hasClass('live')) {
        $.post("/actions.php", { action: "share", path: Name, is_file: is_file, rand: Math.random() })
        .done(function(data) {
            if (data.length === 16) {
                sh_div.addClass('live');
                sh_div.attr("id", data);
                ShowShare(data, is_file);
            }
        });
    } else {
        ShowShare(sh_div.attr("id"), is_file);
    }
}

function ShowShare(token, is_file) {
    $.post("/actions.php", { action: "share-get-date", token: token, rand: Math.random() })
        .done(function(data) {
            $( "#kill-date span" ).html(data);
        });
    var Link = location.origin + '/' + token;
    var HTML = '<span class="add-on" title="Ссылка"><span class="ui-icon ui-icon-link"></span></span>'
    HTML += '<input class="dialog-input" type="text" id="link" readonly value="'+Link+'"/>';
    HTML += '<div class="fss" id="kill-date">Срок размещения: <span></span></div>';

    if (!is_file) {
        var checked = $('#'+token).hasClass("write") ? 'checked' : '';
        HTML += '<label style="font-size: small;" for="write">Разрешить контрагенту запись в папку </label>';
        HTML += '<input type="checkbox" name="write" id="write" '+checked+'>';  
        HTML += '<div class="help" title="Разрешает создавать папки, загружать файлы, удалять файлы/папки"><span class="ui-icon ui-icon-help"></span></div>'
    }
    
    $( "#msg" ).dialog({
        autoOpen: true,
        width: 430,
        resizable: false,
        title: "Ссылка для скачивания",
        modal: true,
        buttons: {
            "Отключить ссылку": function() {
                $.post("/actions.php", { action: "share-del", token: token, rand: Math.random() })
                .done(function() {
                    location.reload();  
                });
                $( this ).dialog( "close" );
            },
            Закрыть: function() {
                $( this ).dialog( "close" );
            }
        },
        close: function() { $( this ).dialog( "destroy" ).text('').html(''); }
    }).html(HTML);
    
    if(!is_file) {
        $.switcher("#write");
        $( "#write" ).on( "change", function() {
            var write = $( "#write" ).is(':checked');
            $.post("/actions.php", { action: "share-write", token: token, write: write, rand: Math.random() })
            .done(function(data) {
                if(data !== 'ok') {
                    ErrMsg('Ошибка! Обратитесь к администратору');
                } else {
                    $('#'+token).toggleClass("write",write); 
                }
            });            
        } );
        $( ".help" ).tooltip({tooltipClass: "tooltip"});
    }
    
    $( "#link" ).focus().select().on( "click", function() {$(this).select();} );   
}

$(document).ready(function(){
    $( '.share' ).on( "click", Share );     
});