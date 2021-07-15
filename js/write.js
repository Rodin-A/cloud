var file_queue = [];
var fCnt = 0;
var cFile = {
    id: 0,
    name: '',
    file: null,
    size: '',
    offset: 0,
    uploaders: [],
    errors: 0,
    chunks_send: 0
};
var BYTES_PER_CHUNK = 1048576; // 1MB chunk sizes.

function Delete() {
    var link = $(this);
    if (link.prop("disabled")) return;
    link.prop("disabled", true);
    setTimeout(function(){link.prop("disabled", false);},500);

    $( "#msg" ).dialog({
        autoOpen: true,
        resizable: false,
        title: "Подтверждение",
        modal: true,
        buttons: {
            Да: function() {
                $.post("/actions.php", { action: "del", data: GetSelected(), rand: Math.random() })
                .done(function() {
                    location.reload();  
                });
                $( this ).dialog( "close" );
            },
            Нет: function() {
                $( this ).dialog( "close" );
            }
        },
        close: function() { $( this ).dialog( "destroy" ).text('').html(''); }
    }).text('Удалить выбранные файлы?');
}

function Add() {
    var link = $(this);
    if (link.prop("disabled")) return;
    link.prop("disabled", true);
    setTimeout(function(){link.prop("disabled", false);},500);

    $( "#msg" ).dialog({
        autoOpen: true,
        resizable: false,
        title: "Выбор",
        modal: true,
        width: 212,
        close: function() { $( this ).dialog( "destroy" ).text('').html(''); }
    }).html('<div id="upload">Загрузить файлы</div><div id="create">Создать папку</div><input type="file" multiple id="file_upload" style="display: none;" />');
    $( '#upload' ).button({
        icons: { primary: "ui-icon-plusthick" }
    }).on( "click", SelectUploadFile );
    $( '#create' ).button({
        icons: { primary: "ui-icon-folder-collapsed" }
    }).on( "click", CreateFolderDlg );    
}

function CreateFolderDlg() {
    $( "#msg" ).dialog({
        autoOpen: true,
        resizable: false,
        title: "Создать папку",
        modal: true,
        width: 230,
        buttons: {
            Ok: function() {
                CreateFolder();
                $( this ).dialog( "close" );
            },
            Отмена: function() {
                $( this ).dialog( "close" );
            }
        },
        close: function() { $( this ).dialog( "destroy" ).text('').html(''); }
    }).html('<form><div class="controls"><span class="add-on" title="Имя"><span class="ui-icon ui-icon-folder-collapsed"></span></span><input type="text" maxlength="50" id="dir_name" value="Новая папка"/></div></form>');
    $('#dir_name').on("input", function(){
            var Name = $(this).val();
            var regEx = /(\/|\\|\^|\*|\>|\<|\:|\?|\|)|(^\.)/;
            var valid = regEx.test( Name );
            if((valid) || (Name.length > 30)){
                $('.add-on').css('background-color', '#ef4b4b'); 
            } else {
                $('.add-on').css('background-color', '#EEEEEE'); 
            }
        }).select();
    $( "#msg" ).find( "form" ).on( "submit", function( event ) {
      event.preventDefault();
      CreateFolder();
    });
}

function CreateFolder() {
    $.post("/actions.php", { action: "add", name: $('#dir_name').val(), rand: Math.random() })
    .done(function() {
        location.reload();  
    });    
}

function SelectUploadFile() {
    $( '#file_upload' ).change(function(e) {
        e.preventDefault();
        $( "#msg" ).dialog( "close" ); 
        UploadFile(this.files); 
    }).trigger('click');
}

function UploadFile(files) {
    
    if( typeof files == 'undefined' ) return;
    
    $( "#msg" ).dialog({
        autoOpen: true,
        resizable: false,
        title: "Загрузка...",
        modal: true,
        buttons: {
            Отмена: function() {
                $( this ).dialog( "close" );
            }
        },
        close: function() { StopUpload(); $( this ).dialog( "destroy" ).text('').html(''); }
    }).html('<div class="progress-label">...</div><div id="progressbar"></div>');
            
    $.each(files, function( key, value ) {
        if ((value.size > 0) || (value.type.length > 0)) {
            file_queue.push(value);
        }
    });
    fCnt = file_queue.length;
    StartUpload();
}

function StopUpload() {
    file_queue = [];
    for(var i=0;i<cFile.uploaders.length;i++) {
        cFile.uploaders[i].abort()
    }
    location.reload();   
}

function StartUpload() {
    if (file_queue.length > 0) {
        $( "#progressbar" ).progressbar({ value: false });
        var cf = file_queue[0];
        cFile = {
            id: 0,
            name: cf.name,
            file: cf,
            size: cf.size,
            offset: 0,
            uploaders: [],
            errors: 0,
            chunks_send: 0
        }
        $( ".progress-label" ).text("(" + (fCnt - file_queue.length + 1).toString() + "/" + fCnt + ") " + cFile.name);
        $.ajax({
           url: "/upload.php?action=start",
           method: "POST",
           cache: false,
           data: { name: cFile.name, size: cFile.size, rand: Math.random() } 
        }).fail(ajaxError).done(function ( data ) {
            var answer = JSON.parse(data);
            if (isNaN(answer.id)) {
                if (answer.err) {
                    ErrMsg(cFile.name+" - "+answer.msg);
                } else {
                    ErrMsg(cFile.name+" - Ошибка при загрузке файла");
                }
                file_queue.shift();
                StartUpload();
            } else {
                cFile.id = answer.id;
                upload(cFile.file.slice(cFile.offset, cFile.offset + BYTES_PER_CHUNK), cFile.offset);
                cFile.offset += BYTES_PER_CHUNK;
            }
        });    
    } else {
        location.reload();        
    }
}

function upload(blob, offset) {
   var Data = new FormData();
   Data.append('CHUNK', blob);
   var jqXHR = $.ajax({
       url: "/upload.php?action=upload&id="+cFile.id+ "&offset=" + offset,
       method: "POST",
       processData: false,
       contentType: false,
       cache: false,
       data: Data
   }).error(ajaxSuccess).success(ajaxSuccess);
   cFile.uploaders.push(jqXHR); 
}

function UploadSuccess() {
    $.ajax({
        url: "/upload.php?action=success",
        method: "POST",
        cache: false,
        data: { id: cFile.id, rand: Math.random() } 
    }).fail(ajaxError).done(function ( data ) {
        if (data === "OK") {
            file_queue.shift();
            StartUpload();     
        } else {
            ErrMsg(cFile.name+" - Ошибка при загрузке файла");
            location.reload();
        }           
    });    
}

function ajaxSuccess( errorThrown, textStatus, jqXHR ) {
    if (textStatus === "abort") return;
    if ((jqXHR.readyState === 4) && (jqXHR.status === 200)) {
        cFile.chunks_send++;
        var progress = cFile.chunks_send / (cFile.size / BYTES_PER_CHUNK) * 100;
        $( "#progressbar" ).progressbar({ value: progress });
        var pos = cFile.uploaders.indexOf(jqXHR);
        cFile.uploaders.splice(pos, 1);
        if (cFile.size > cFile.offset) {
            while ((cFile.size > cFile.offset) && (cFile.uploaders < 2)) {
                upload(cFile.file.slice(cFile.offset, cFile.offset + BYTES_PER_CHUNK), cFile.offset);
                cFile.offset += BYTES_PER_CHUNK;
            }                
        } else {
            if (!cFile.uploaders.length) {
                UploadSuccess();                
            }
        }
    } else {
        if (cFile.errors < 5) {
            cFile.errors++;
            var offset = this.url.match(/offset=([^&]+)/)[1];
            upload(cFile.file.slice(offset, offset + BYTES_PER_CHUNK), offset);
        } else {
            ErrMsg(cFile.name+" - Ошибка при загрузке файла");
            $( "#msg" ).dialog( "close" );
        } 
    }
}

function ajaxError( jqXHR, textStatus, errorThrown ) {
    if (textStatus === "abort") return;
    $( "#msg" ).dialog( "close" );
    ErrMsg(jqXHR.status + " " + errorThrown + " - Ошибка при загрузке файла");
}

$(document).ready(function(){
    $( '.selectedActions > .delete' ).on( "click", Delete );
    $( ".actions > .new" ).on( "click", Add );
    $("html").on("dragover", function(e) {
        e.preventDefault();
        e.stopPropagation();
    });
    $('html').on('drop', function (e) {
        e.stopPropagation();
        e.preventDefault();
        UploadFile(e.originalEvent.dataTransfer.files);
    });     
});