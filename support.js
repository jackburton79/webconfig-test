
function IsValidMACAddress(macaddr)
{
    var regex = /^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/;
    var regex2 = /^([0-9A-Fa-f]{12})$/;
    return regex.test(macaddr) || regex2.test(macaddr);
}


function IsValidHostName(name)
{
    var regex = /^([0-9A-Za-z\-\_]{1,128})$/;
    return regex.test(name);
}


function FormatMACAddress(mac)
{
    return mac.replace(/(\w{2})(\w{2})(\w{2})(\w{2})(\w{2})(\w{2})/,
        '$1:$2:$3:$4:$5:$6');
}


function DestroyClickedElement(event)
{
    document.body.removeChild(event.target);
}


function TriggerDownload(url, name)
{
    var downloadLink = document.createElement("a");
 
    downloadLink.download = name;
    downloadLink.innerHTML = "Download File";
    downloadLink.href = url;
    downloadLink.onclick = DestroyClickedElement;
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);

    downloadLink.click();
};
    
    
function ShowAlert(string, type = 'alert-success', fadeOutDuration = 10000)
{
    
    $('<div class="alert alert-dismissable ' + type + '">'+
        '<button type="button" class="close" ' + 
        'data-dismiss="alert" aria-hidden="true">' + 
        '&times;' + '</button>' + string + 
        '</div>').fadeOut(fadeOutDuration).appendTo($('#alerts'));
}