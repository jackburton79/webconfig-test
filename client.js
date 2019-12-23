if ($('body').hasClass("MainPage")) {
    $(document).ready(function() {
        $("#addhost").off("submit").submit(ServerAddHost);
    
        $("#writeconfbutton").off("click").click(ServerWriteConfiguration);
        $("#saveconfig").off("click").click(ServerWriteConfiguration);
        
        $("#downloadconfbutton").off("click").click(ServerDownloadConfiguration);
        $("#downloadconfig").off("click").click(ServerDownloadConfiguration);
    
        $("#importconfig").off("click").click(ServerImportConfiguration);
        $("#editdefaultconfig").off("click").click(ServerEditCustomConfig);
        ServerGetHosts();
        ServerGetGroups();
    });
}


function ServerGetHosts()
{
    $.ajax({
        url: 'server/request.php',
        type: "POST",
        data: {action: "gethosts"},
        dataType: 'json',               
        success: function(data) {
            if (data) {
                $('table#hosttable').empty();
                
                var html = "<thead><tr><th>" + "Hostname" + "</th>" +
                    "<th>MAC Address" + "</th><th>Groups</th><th></th><th></th>"+
                    "</tr></thead>";
                
                html += "<tbody>";
                
                for (var i in data) {
                    var obj = data[i];
                    var mac = obj["mac_address"];
                    var name = obj["name"];
                    html += '<tr><td id="hostname">' + name +
                        '</td><td id="mac_address">' + FormatMACAddress(mac) + '</td>' +
                        '<td><form class="fiddlegroupsonf">' +
                        '<input id="groupstags" class="form-control" name="groups" type="text" data-role="tagsinput"/>' +
                        '</form></td>' +
                        '<td><form class="edithostconf">' +
                        '<input id="edithostconfbutton" type="submit" name="Edit" value="Edit Configuration"/>' + 
                        '</form></td>' + 
                        '<td><span class="table-remove glyphicon glyphicon-remove">' +
                        '</td></tr>';
                }
                html += "<tr class=\"hide\"> " +
                    "<td></td>" + 
                    "<td></td>" +
                    "<td>" +
                    "</td></tr>" + "</tbody>";
                
                $("table#hosttable").html(html);
                
            }        
        },
        error:function() {
            ShowAlert('Error while getting the host list: (' + data.responseJSON.message + ")", 'alert-danger');
        }   
    }); 
};


function ServerGetGroups() {
    $.ajax({
        url: 'server/request.php',
        type: "POST",
        data: {action: "getgroups"},
        dataType: 'json',               
        success: function(data) {
            $('table#groupstable').empty();
            
            var html = "<thead><tr><th>" + "Group name" + "</th><th></th></tr></thead>";
            
            html += "<tbody>";
            for (var i in data) {
                var obj = data[i];
                var name = obj["name"];
                html += "<tr><td id=\"groupname\">" + name + "</td></tr>";
            }
            html += "</tbody>";
            
            $("table#groupstable").html(html);
        },
        error:function() {
            ShowAlert('There was an error getting the groups list', 'alert-danger');
        }   
    });
};


function ServerAddHost(event)
{
    event.preventDefault();
     
    var hostname = $(":input#host_name").val();
    var macaddr = $(":input#host_mac").val();
    $(":input#host_mac").parent().removeClass("has-error has-feedback");
    $(":input#host_name").parent().removeClass("has-error has-feedback");
    if (!IsValidMACAddress(macaddr)) {
        $(":input#host_mac").parent().addClass("has-error has-feedback");
        ShowAlert( "Please insert a valid MAC address...", 'alert-danger' );
        return;
    } else if (!IsValidHostName(hostname)) {
        $(":input#host_name").parent().addClass("has-error has-feedback");
        ShowAlert( "Please insert a valid hostname...", 'alert-danger' );
        return;
    }
    
    $.ajax({
        url: 'server/request.php',
        type: "POST",
        data: {action: "addhost", name: hostname, mac: macaddr},
        success: function(data) {
            ServerGetHosts();
        },
        error:function(data) {
            ShowAlert('Error while adding the host (' + data.responseJSON.message + ")", 'alert-danger');
        }   
    }); 
};


function ServerEditCustomConfig(event)
{
    event.preventDefault();
    
    var hostName = "Default";
    var node = $(event.currentTarget.closest("tr")).find("td#hostname");
    
    if (node.text() != "")
        hostName = node.text();
    
    var configText = "";
    $.ajax({
        url: 'server/request.php',
        type: "POST",
        data: {action: "getcustomconfig", name: hostName},
        success: function(data) {
            //console.log(data);
            configText = data[0]["configuration"];
        },
        error:function(data) {
           configText = "";
        }   
    });

    var newWindow = window.open("textconfig.html");
    $(newWindow.document).ready(function () {
        var configElement = newWindow.document.getElementById('configuration');
        var labelElement = newWindow.document.getElementById('label');
        var form = newWindow.document.getElementById('saveconfig');
        configElement.value = configText;
        labelElement.innerHTML = hostName + " configuration";
        
        $(form).submit(ServerSaveCustomConfig);
    });
}


function ServerSaveCustomConfig(event)
{
    event.preventDefault();
    
    var label = $(this).find('label#label');
    var confTextArea = $(this).find("textarea#configuration");
    var hostName = label.text();
    var configurationText = confTextArea.val();
    
    if (hostName == "") {
        hostName = "Default";
    } else
        hostName = hostName.substr(0, hostName.indexOf(' '));
        
    $.ajax({
        url: 'server/request.php',
        type: "POST",
        data: {action: "savecustomconfig", name: hostName, configuration: configurationText},
        success: function(data) {
            ShowAlert(hostName + ' custom configuration written successfully!', 'alert-success');
            
            // Close the window
            label.get(0).ownerDocument.defaultView.close();
        },
        error:function(data) {
            ShowAlert('Error while saving ' + hostName + ' custom configuration (' + data.responseJSON.message + ")", 'alert-danger');
        }   
    });
}


function ServerEditHostConfig(event)
{
    event.preventDefault();
    var typedArray = ["foo", "bar"];
    var blob = new Blob([typedArray], {type: 'text/plain'});
    var url = URL.createObjectURL(blob);
    var node = $(event.currentTarget.closest("tr")).find("td#hostname");

    var configName = node.text() + "_config.txt";
    
    TriggerDownload(url, configName);
}


function ServerDeleteHost(hostname, macaddr)
{
    $.ajax({
        url: 'server/request.php',
        type: "POST",
        data: {action: "deletehost", name: hostname, mac: macaddr},
        success: function(data) {
            // nothing to do, the row has already been removed
        },
        error:function(data) {
            //$("#hosterrorspan").text('Error while adding the host:' + data).show().fadeOut(5000);
            ShowAlert('Error while deleting the host (' + data.responseJSON.message + ")", 'alert-danger');
            ServerGetHosts();
        }   
    }); 
}


function ServerImportConfiguration(event)
{
    if (confirm("This operation will delete all the entries in the database, do you want to proceed ?")) {
        $.ajax({
            url: 'server/request.php',
            type: "POST",
            data: {action: "importconfig"},
            success: function(data) {
                location.reload();
                ShowAlert("Configuration imported succesfully!");
            },
            error: function(data) {
                ShowAlert("Cannot import configuration: (" + data.responseJSON.message + ").", 'alert-danger', 30000);
            }
        });
    }
}


function ServerWriteConfiguration(event)
{
    $.ajax({
        url: 'server/request.php',
        type: "POST",
        data: {action: "writeconfig"},
        success: function(data) {
            ShowAlert("Configuration written succesfully!");
        },
        error: function(data) {
            ShowAlert("Cannot write configuration. Check the destination directory permissions (" + data.responseJSON.message + ").", 'alert-danger', 30000);
        }
    });
}


function ServerDownloadConfiguration(event)
{
    $.ajax({
        url: 'server/request.php',
        type: "POST",
        data: {action: "downloadfullconfiguration"},
        dataType: 'binary',
        //processData: false,
        success: function(data, status, request) {
            var blob = new Blob([data], {type: 'application/zip'});
            var url = URL.createObjectURL(blob);
            
            TriggerDownload(url, "thinstation_conf.zip");
        },
        error: function(data) {
             ShowAlert("Cannot get configuration. (" + data.responseJSON.message + ").", 'alert-danger', 30000);
        }
    });
}


function InputChanged(event) {
    console.log("changed"); 
};