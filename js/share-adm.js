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
    var sh_div = $( this );
    var ROW = sh_div.parents( "tr" );
    if (!ROW) return;
    var Name = ROW.find( "a" ).prop("name");
    if (!sh_div.hasClass('live')) {
        $.post("/actions.php", { action: "account-create", path: Name, rand: Math.random() })
        .done(function(data) {
            if ($.isNumeric(data)) {
                sh_div.addClass('live');
                sh_div.attr("id", data);
                GetShareInfo(data);
            }
        });
    } else {
        GetShareInfo(sh_div.attr("id"));
    }        
}

function GetShareInfo(id) {
    $.ajax({
        url: "/actions.php",
        method: "POST",
        cache: false,
        dataType: 'json',
        data: { action: "account-get", id: id, rand: Math.random() },
        success: function(data) {
            ShowShare(data);
        }
    });   
}

function ShowShare(info) {
    var HTML = '<span class="add-on" title="Login"><span class="ui-icon ui-icon-person"></span></span>'
    HTML += '<input class="dialog-input" type="text" id="name" value="'+info.login+'"/>';
    
    HTML += '<span class="add-on" title="Password"><span class="ui-icon ui-icon-key"></span></span>'  
    HTML += '<input class="dialog-input" type="text" id="pass" value="'+info.pass+'"/>';
    
    HTML += '<span class="add-on" title="date (yyyy-mm-dd)"><span class="ui-icon ui-icon-calendar"></span></span>'  
    HTML += '<input class="dialog-input" type="text" id="date" value="'+info.date+'"/>';

    var checked = (info.write == 1) ? 'checked' : '';
    HTML += '<label style="font-size: small;" for="write">Write </label>';
    HTML += '<input class="switcher" type="checkbox" name="write" id="write" '+checked+'>';
    
    checked = (info.share == 1) ? 'checked' : '';
    HTML += '<label style="font-size: small;" for="share">Share </label>';
    HTML += '<input class="switcher" type="checkbox" name="share" id="share" '+checked+'>';  
    
    HTML += '<a name="'+info.id+'" style="margin-top: 15px; width: 244px" id="update" href="#">Сохранить</a>';

    $( "#msg" ).dialog({
        autoOpen: true,
        resizable: false,
        title: "Аккаунт",
        modal: true,
        buttons: {
            Прекратить: function() {
                $.post("/actions.php", { action: "account-del", id: info.id, rand: Math.random() })
                .done(function(data) {
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
    
    $.switcher(".switcher");
    $( "#update" ).button().on( "click", Update );   
}

function Update() {
    var data = {
        id: this.name,
        login: $( '#name' ).val(),
        pass: $( '#pass' ).val(),
        date: $( '#date' ).val(),        
        write: ($( '#write' ).is(':checked')) ? '1':'0',
        share: ($( '#share' ).is(':checked')) ? '1':'0'
    };
    $.post("/actions.php", { action: "account-mod", data: data, rand: Math.random() })
    .done(function(data) {
        if (data !== 'ok') {
            alert("Error: "+data);
        }
    });
}

$(document).ready(function(){

    var rows = $('#filestable tr');
    rows.each(function () {
        this.cells[2].innerHTML = '';
    });

    var names = [];
    rows = $('#filestable a.folder');
    rows.each(function () {
        names.push( this.name );
    });
    
    $.ajax({
        url: "/actions.php",
        method: "POST",
        cache: false,
        dataType: 'json',
        data: { action: "accounts-get-id", paths: names, rand: Math.random() },
        success: function(data) {
            data.forEach(function (item) {
                var name = item.path;
                var id = item.id;
                var link = $( 'a[name ="'+name+'"]' );
                var html = '<div class="share"></div>';
                if (id>0) {
                    html = '<div id="'+id+'" class="share live"></div>';
                }
                link.parents( "tr" ).children('td:eq(2)').html(html);
            });
            $( '.share' ).on( "click", Share );
        }
    });       
});