
var $TABLE = $('#table');

var ID=$(this).attr('id');
var first=$("#first_input_"+ID).val();
var last=$("#last_input_"+ID).val();
var dataString = 'id='+ ID +'&firstname='+first+'&lastname='+last;

// On every Ajax request, re-register the callbacks for table functions,
// so that added table rows get the correct callbacks
$( document ).ajaxComplete(function( event, request, settings ) {
      RegisterTableCallbacks();
});
      
function RegisterTableCallbacks() {
  
  /*$('.table-add').off('click').on("click", function () {
    var $clone = $TABLE.find('tr.hide').clone(true).removeClass('hide table-line');
    $TABLE.find('table').append($clone);
  });*/
  
  $(".edithostconf").off("submit").submit(ServerEditCustomConfig);
  
  $('.table-remove').off('click').on("click", function (event) {
    var tr = $(this).parents('tr');
    var hostname = tr.find('td#hostname').text();
    var mac_address = tr.find('td#mac_address').text();
    event.preventDefault();
    if (confirm('Are you sure you want to delete host ' + hostname + '?')) {
      tr.detach();
      ServerDeleteHost(hostname, mac_address);
    }
  });
  
  $('.table-up').off('click').on("click", function () {
    var $row = $(this).parents('tr');
    if ($row.index() === 1) return; // Don't go above the header
    $row.prev().before($row.get(0));
  });
  
  $('.table-down').off('click').on("click", function () {
    var $row = $(this).parents('tr');
    $row.next().after($row.get(0));
  });
  
  /*$('#groupstags').tagsinput({
        allowDuplicates: false
  });*/
};
