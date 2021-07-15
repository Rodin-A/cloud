function SelectAll() {
    $( '.select_file' )
        .off()
        .prop( "checked", $( '#select_all_files' ).prop( "checked" ) )
        .on( "click", SelectFile );
    ShowSelected();
}

function FileSizeConvert(bytes) {
    if (!bytes) return '';
    var arBytes = [
        ["TB", Math.pow(1024, 4)],
        ["GB", Math.pow(1024, 3)],
        ["MB", Math.pow(1024, 2)],
        ["KB", 1024],
        ["B", 1]
    ];

    var result = '';
    arBytes.forEach( function(arItem) {
        if(bytes >= arItem[1]) {
            result = (bytes / arItem[1]).toFixed(2).toString();
            result = result.replace(".", ",")+" "+arItem[0];
            bytes = 0;
        }
    });
    return result;
}

function GetSelected() {
    var dirs = [];
    var files = [];
    $( 'tr.dir td:first-child > .select_file:checked' )
        .parents( ".dir" )
        .find( "a" )
        .each(function() {
            dirs.push( $( this ).prop("name") );
        });
    $( 'tr.file td:first-child > .select_file:checked' )
        .parents( ".file" )
        .find( "a" )
        .each(function() {
            files.push( $( this ).prop("name") );
        });
    return JSON.stringify({"dirs": dirs, "files": files});
}

function Download() {
    var link = $(this);
    if (link.prop("disabled")) return;
    link.prop("disabled", true);
    setTimeout(function(){link.prop("disabled", false);},500);

    $( "#get-zip input" ).val( GetSelected() );
    $( "#get-zip" ).submit();
}

function Plural(n, Vars) {
    var plural;
    plural = (n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<12 || n%100>14) ? 1 : n%10==0 || (n%10>=5 && n%10<=9) || (n%100>=11 && n%100<=14)? 2 : 3);
    plural = (plural === true ? 1 : plural ? plural : 0);
    return Vars[plural].replace(/%n/g, n);
}

function GetPluralStr(dc, fc) {
    var str;
    var DirPlurail = ["%n каталог", "%n каталога", "%n каталогов", "%n каталогов"];
    var FilesPlurail = ["%n файл", "%n файла", "%n файлов", "%n файлов"];
    var DS = Plural( dc, DirPlurail );
    var FS = Plural( fc, FilesPlurail );
    if ((dc > 0) && (fc > 0)) {
        str = DS + ' и ' + FS;            
    } else {
        if (dc > 0) {
            str = DS;            
        } else {
            str = FS;
        }
    }
    return str;   
}

function ShowSummary() {
    var DC = $( 'tr.dir' ).length;
    var FC = $( 'tr.file' ).length;
    $( '#summary-name' ).text( GetPluralStr(DC,FC) );
}

function SelectFile() {
    $( '#select_all_files' ).prop( "checked", false );
    ShowSelected();   
}

function ShowSelected() {
    var DC = $( 'tr.dir td:first-child > .select_file:checked' ).length;
    var FC = $( 'tr.file td:first-child > .select_file:checked' ).length;
    if ((DC>0)||(FC>0)) {
        $( '#filestable' ).addClass('multiselect');
        var size = 0;
        $( '.select_file:checked' ).parents( 'tr' ).find( '.bsize' )
            .each(function() {
                size += +$( this ).text();
            });
        $( '#col-name' ).text(GetPluralStr(DC,FC)+' ('+FileSizeConvert(size)+')');
    } else {
        $( '#filestable' ).removeClass('multiselect');
        $( '#col-name' ).text('Имя');
    }         
}

$(document).ready(function(){
    ShowSummary();
    $( '#logout' ).button({
        icons: { primary: "ui-icon-power" }
    });
    $( '#select_all_files' ).on( "click", SelectAll );   
    $( '.select_file' ).on( "click", SelectFile );
    $( '.selectedActions > .download' ).on( "click", Download );
});