function sendHTTP(method, url, data, actionOn200OK, actionOnError, isAsynchronous) {
  var xhr = new XMLHttpRequest();
  xhr.open(method, url, isAsynchronous);
  xhr.setRequestHeader("Content-type", "application/json");
  xhr.onreadystatechange = function () {
    if (xhr.readyState === 4) {
      
      if ((xhr.status === 200) || (xhr.status === 201)) {
        actionOn200OK(JSON.parse(xhr.response));
      }
      else {
        console.log((xhr.responseText));
        actionOnError(xhr.responseText);
      }
    }
  };
  if (data === null) {
    xhr.send();
  }
  else {
    console.log(JSON.stringify(data))
    xhr.send(JSON.stringify(data));
  }
}
function tableGenerator_buildHtmlTable(id, data, imei) {
  if ($.fn.DataTable.isDataTable('#' + id)) {
    var table = $('#' + id).DataTable();
    table.destroy();
    $('#' + id).empty();
  }
  var tabledata = data;
  var columns = this.tableGenerator_addAllColumnHeaders(id, tabledata);
  var appender = "<tbody>";
  var i = 0;
  var colIndex = 0;
  var colName;
  var cellValue;
  for (i = 0; i < tabledata.length; i++) {
    appender += '<tr>';
    for (colIndex = 0; colIndex < columns.length; colIndex++) {
      colName = columns[colIndex];
      cellValue = tabledata[i][colName] === undefined ? "" : tabledata[i][colName];
      if (cellValue === null || cellValue === undefined) {
        cellValue = "";
      }
      if (String(cellValue).match(/^\/Date/) || String(cellValue).match(/\d{4}-(?:0[1-9]|1[0-2])-(?:0[1-9]|[1-2]\d|3[0-1])T(?:[0-1]\d|2[0-3]):[0-5]\d:[0-5]\dZ?/i)) {
        if (colName === "_Date") {
          cellValue = this.convertJSONDateToLocaleString(cellValue, false);
        }
        else {

          cellValue = this.convertJSONDateToLocaleString(cellValue, true);
        }
      }
      else if (colName === "_Battery" || colName === "_Battery_Level") {
        var b00 = '<img class="imgLineHeight150" src="/images/battery_00.png">';
        var b25 = '<img class="imgLineHeight150" src="/images/battery_25.png">';
        var b50 = '<img class="imgLineHeight150" src="/images/battery_50.png">';
        var b75 = '<img class="imgLineHeight150" src="/images/battery_75.png">';
        var b100 = '<img class="imgLineHeight150" src="/images/battery_100.png">';
        cellValue = cellValue.replace(/\[00\]/, b00);
        cellValue = cellValue.replace(/\[25\]/, b25);
        cellValue = cellValue.replace(/\[50\]/, b50);
        cellValue = cellValue.replace(/\[75\]/, b75);
        cellValue = cellValue.replace(/\[100\]/, b100);
      }
      else if (colName === "_Time_Since_Last_Report" || colName === "Sleep_For" || colName === "Allowed_Idle_Time") {
        cellValue = this.momjsDurationToText(moment.duration(cellValue));
      }
      else if (colName === "Device_Name" || colName === "Data_Type") {
        cellValue = cellValue.replace(/_/g, "&#8203_");
      }
      else if (colName === "_Image" || colName === "_Reference_Image") {
        if ((cellValue.length === 0) || (cellValue.substring(0, 4) == "null")) { cellValue = "N/A"; }
        else if ((cellValue.substring(cellValue.length - 4, cellValue.length) == ".jpg")) {
          cellValue = "<div class=\"iBinEnlargeImg\">" +
          "<a download href=\"" + cellValue + "\"  target=\"_blank\">" +
            "<img alt=\"ImageName\" height=\"30\" src=\"" + cellValue + "\"></a>";
        }
      }
      else if ((colName === "_Article") || (colName === "_Article_Type") || (colName === "_Article_Image")) {
        if ((cellValue.length === 0) || (cellValue.substring(0, 4) == "null")) { cellValue = "N/A"; }
        else if (cellValue === "0.jpg") { cellValue = "no image"; }
        else {
          cellValue = "<div class=\"iBinEnlargeImg\">" +
          "<a download href=\"" + cellValue + "\" title=\"\"  target=\"_blank\">" +
            "<img alt=\"ImageName\" height=\"30\" src=\"" + cellValue + "\"></a>";
        }
      }
      else if (colName === "_Irrigation_Status") {
        cellValue = "<img ng-click=\"toggleValveAlert()\" height=\"30\" src=\"" + cellValue + "\">";
      }
      else if (colName === "_CTRL_HB_Status") {
        cellValue = convertCtrlStatusToText(cellValue);
      }
      else if (colName === "_Platform_Status") {
        cellValue = convertSplfStatusToText(cellValue);
      }
      else if (String(cellValue).match(/^SCREWID/)) {
        cellValue = "<img src='/images/ibin/SampleScrew_1s.jpg' height='30'>";
      }
      else if (Object.prototype.toString.call(cellValue) === '[object Array]') {
        console.log('[object Array]');
        console.log(cellValue);
        var tmp = "";
        for (var m = 0; m < cellValue.length; m++) {
          tmp += cellValue[m] + "<br />";
        }
        cellValue = tmp;
      }
      //if (Object.prototype.toString.call(cellValue) === '[object Array]') {
      //  var tmp = "";
      //  for (var m = 0; m < cellValue.length; m++) {
      //    tmp += cellValue[m] + "<br />";
      //  }
      //  cellValue = tmp;
      //}
      appender += '<td>' + cellValue + '</td>';
    }
    appender += '</tr>';
  }
  appender += "</tbody>";
  $("#" + id).append(appender);
}
function tableGenerator_addAllColumnHeaders(id, tabledata) {
  var columnSet = [];
  var headerTr = '<thead><tr>';
  for (var i = 0; i < 2; i++) {
    var rowHash = tabledata[i];
    for (var prop in rowHash) {
      if (!rowHash.hasOwnProperty(prop)) {
        //The current property is not a direct property of parent Element
        continue;
      }
      var key_mod = prop;
      if (($.inArray(key_mod, columnSet) === -1)) {
        columnSet.push(key_mod);
        headerTr = headerTr + '<th>' + key_mod.replace(/_/g, ' ') + '</th>';
      }
    }
  }
  headerTr = headerTr + '</tr></thead>';
  $("#" + id).append(headerTr);
  return columnSet;
}
function tebleOptions(tableId, searchboxId) {
  var otable = $('#' + tableId).DataTable({
    select: {
      style: 'single'
    },
    // dom: 'Bfrtip',
    //buttons: [
    //'colvis'
    //],
    //"columnDefs": [
    //  { "visible": false, "targets": 0 }
    //],
    "bDestroy": true,
    "paging": true,
    "pagingType": "simple_numbers",
    "lengthMenu": [10, 25, 50, 75, 100],
    "sDom": "<'row'<'col-sm-6'l><'col-sm-6'p>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-5'i>>",
    "bLengthChange": true,
    "bFilter": true,
    "bSort": true,
    // "sScrollX": true,
    "bAutoWidth": false,
    "order": [[0, "desc"]],
    "stateSave": true
  })
  .on('stateLoadParams.dt', function (e, settings, data) {
    data.search.search = "";
  })
  .on('select', function () {
    $('#edit_button').removeAttr('disabled');
    $('#delete_button').removeAttr('disabled');
  })
  .on('deselect', function () {
    $('#edit_button').attr('disabled', 'disabled');
    $('#delete_button').attr('disabled', 'disabled');
  });
  $('#' + searchboxId).keyup(function () {
    otable.search($(this).val()).draw();
  });
  otable.search($('#' + searchboxId).val()).draw();
  otable.column(0).visible(false);
  return otable;
}
function setActionOnSubmitFormButton(formId, buttonId, func) {
  $("#" + formId).on("submit", function (e) {
    if (!$("#" + buttonId).hasClass("disabled")) {
      e.preventDefault();
      func();
    }
  });
}
function getApiServerUrl() {
  return "http://vesseltracker";
}

class trackPageManager {
  init() {
    var self = this;
    self.getAllTracks();
    self.addListenerToBTnAndSel();
    $('#trackRegistation').validator('update');
  }
  addListenerToBTnAndSel() {
    var self = this;
    setActionOnSubmitFormButton('trackRegistation', 'submitNewTrackButton', function () { self.submitTrack() });
    setActionOnSubmitFormButton('editTrackForm', 'submiteditTrackButton', function () { self.submitEditTrack() });
    document.getElementById("create_button").addEventListener("click", self.openPopUpWindowCreate);
    document.getElementById("edit_button").addEventListener("click", function () { self.openPopUpWindowEdit() });
    document.getElementById("delete_button").addEventListener("click", function () { self.openPopUpWindowDelete() });
    document.getElementById("closeNewTrackButton").addEventListener("click", self.closePopUpWindowCreate);
    document.getElementById("closeEditTrackButton").addEventListener("click", self.closePopUpWindowEdit);
    document.getElementById("edit_button").setAttribute('disabled', 'disabled');
    document.getElementById("delete_button").setAttribute('disabled', 'disabled');
    document.getElementById("refreshButton").addEventListener("click", function () { self.getAllTracks() });
  }
  getAllTracks() {
    var url = getApiServerUrl() + "/api/v1/tracks/search";
    var querystring = "";
   
    if (document.getElementById("mmsifilter").value != "") {
      var mmsistring = document.getElementById("mmsifilter").value;
      console.log(mmsistring);
      console.log(mmsistring.includes(","));
      if (mmsistring.includes(",")) {
        var mmsilist = mmsistring.split(',');
        for (var i = 0; i < mmsilist.length; i++) {
          querystring += "&mmsi[]=" + mmsilist[i];
        }
      }
      else {
        querystring += "&mmsi=" + document.getElementById("mmsifilter").value;
      }      
    };
    if (document.getElementById("lonstartfilter").value != "") {
      querystring += "&lonstart=" + document.getElementById("lonstartfilter").value;
    };
    if (document.getElementById("lonendfilter").value != "") {
      querystring += "&lonend=" + document.getElementById("lonendfilter").value;
    };
    if (document.getElementById("latstartfilter").value != "") {
      querystring += "&latstart=" + document.getElementById("latstartfilter").value;
    };
    if (document.getElementById("latendfilter").value != "") {
      querystring += "&latend=" + document.getElementById("latendfilter").value;
    };
    if (document.getElementById("timestartfilter").value != "") {
      querystring += "&timestart=" + document.getElementById("timestartfilter").value;
    };
    if (document.getElementById("timeendfilter").value != "") {
      querystring += "&timeend=" + document.getElementById("timeendfilter").value;
    };
    if (querystring != "") {
      url += "?" + querystring.substr(1);
    };
    console.log(url);
    var self = this;
    document.getElementById("tracktable-responsive").innerHTML = "";
    $("#spinner").show();
    sendHTTP( // sendHTTP(method, url, data, actionOn200OK, actionOnError, isAsynchronous)
      "GET",
      url,
      null,
      function (jsonResponse) { self.onGetAllTrackResp(jsonResponse) },
      function (responseText) { $.alert({ title: "Error", content: responseText, confirmButton: "OK", confirmButtonClass: 'btn btn-primary' }) },
      true
     );
  }
  onGetAllTrackResp(jsonResponse) {
    var self = this;
    self.tracks = {};
    var tracks = jsonResponse;
    document.getElementById("errormessage").innerHTML = "";
    document.getElementById("tracktable-responsive").innerHTML = '<table class="table table-striped table-bordered table-hover" id="listTrackTable"></table>';
    var guiData = [];
    if (Array.isArray(jsonResponse)) {
      tableGenerator_buildHtmlTable("listTrackTable", tracks);
      self.oTable = tebleOptions("listTrackTable", "datatablesSearchBox");
    }
    else {
      document.getElementById("tracktable-responsive").innerHTML = '';
      document.getElementById("errormessage").innerHTML = "No Track found";
    }
    $('#spinner').hide();
  }
  submitTrack() {
    var self = this;
    var data = {
        "mmsi": document.getElementById("trackmmsi").value,
        "status": document.getElementById("trackstatus").value,
        "stationId": document.getElementById("trackstationId").value,
        "speed": document.getElementById("trackspeed").value,
        "lon": document.getElementById("tracklon").value,
        "lat": document.getElementById("tracklat").value,
        "course": document.getElementById("trackcourse").value,
        "heading": document.getElementById("trackheading").value,
        "rot": document.getElementById("trackrot").value,
        "timestamp": document.getElementById("tracktimestamp").value
    }
    sendHTTP( // sendHTTP(method, url, data, actionOn200OK, actionOnError, isAsynchronous)
      "POST",
       getApiServerUrl() + "/api/v1/track",
       data,
       function (jsonResponse) { self.onSendRequestResp() },
       function (responsetext) { $.alert({ title: "Error", content: responsetext, confirmButton: "OK", confirmButtonClass: 'btn btn-primary' }) },
       true
    );
  }
  submitEditTrack() {
    var self = this;
    var data = {
      "mmsi": document.getElementById("edittrackmmsi").value,
      "status": (document.getElementById("edittrackstatus").value),
      "stationId": (document.getElementById("edittrackstationId").value),
      "speed": (document.getElementById("edittrackspeed").value),
      "lon": parseFloat(document.getElementById("edittracklon").value),
      "lat": parseFloat(document.getElementById("edittracklat").value),
      "course": (document.getElementById("edittrackcourse").value),
      "heading": (document.getElementById("edittrackheading").value),
      "rot": document.getElementById("edittrackrot").value,
      "timestamp": (document.getElementById("edittracktimestamp").value)
    }
    sendHTTP( // sendHTTP(method, url, data, actionOn200OK, actionOnError, isAsynchronous)
      "PUT",
      getApiServerUrl() + "/api/v1/track/" + self.currentTrackIdSelected,
      data,
      function (jsonResponse) { self.onSendRequestResp() },
      function (response) { $.alert({ title: "Error", content: response, confirmButton: "OK", confirmButtonClass: 'btn btn-primary' }) },
      true
    );
  }
  submitDeleteTrack() {
    var self = this;
    $.confirm({
      title: 'Delete Track',
      content: 'Are you sure?',
      confirmButton: "Delete",
      cancelButton: "Cancel",
      confirmButtonClass: 'btn btn-primary',
      cancelButtonClass: 'btn btn-primary',
      confirm: function () {
        $("#spinner").show();
        sendHTTP( // sendHTTP(method, url, data, actionOn200OK, actionOnError, isAsynchronous)
          "DELETE",
          getApiServerUrl() + "/api/v1/track/" + self.currentTrackIdSelected,
          null,
          function (jsonResponse) { self.onSendRequestResp() },
          function (responseText) { $.alert({ title: "Error", content: responseText, confirmButton: "OK", confirmButtonClass: 'btn btn-primary' }) },
          true
        );
      },
      cancel: function () {
        $("#spinner").hide();
      }
    });
  }
  onSendRequestResp() {
    var self = this;
    self.getAllTracks();
    self.closePopUpWindowCreate();
    self.closePopUpWindowEdit();
    $('#edit_button').attr('disabled', 'disabled');
    $('#delete_button').attr('disabled', 'disabled');
  }
  openPopUpWindowCreate() {
    var createModal = document.getElementById("create_Track");
    createModal.style.display = "block";
  }
  openPopUpWindowEdit() {
    var self = this;
    //get reference to datatable to get id of selected Track and refresh after update
    var TrackDatatable = $('#listTrackTable').DataTable();
    //ger reference to modal: create Track
    var updateModal = document.getElementById("update_Track");
    //get selected track id
    self.currentTrackIdSelected = TrackDatatable.row('.selected').data()[0];
    document.getElementById('editTrackForm').style.display = 'block';
    console.log(TrackDatatable.row('.selected').data());
    document.getElementById("edittrackmmsi").value = TrackDatatable.row('.selected').data()[1];
    document.getElementById("edittrackstatus").value = TrackDatatable.row('.selected').data()[2];
    document.getElementById("edittrackstationId").value = TrackDatatable.row('.selected').data()[3];
    document.getElementById("edittrackspeed").value = TrackDatatable.row('.selected').data()[4];
    document.getElementById("edittracklon").value = TrackDatatable.row('.selected').data()[5];
    document.getElementById("edittracklat").value = TrackDatatable.row('.selected').data()[6];
    document.getElementById("edittrackcourse").value = TrackDatatable.row('.selected').data()[7];
    document.getElementById("edittrackheading").value = TrackDatatable.row('.selected').data()[8];
    document.getElementById("edittrackrot").value = TrackDatatable.row('.selected').data()[9];
    document.getElementById("edittracktimestamp").value = TrackDatatable.row('.selected').data()[10];

    $('#editTrackForm').validator('update');
    $('#submiteditTrackButton').addClass('disabled');
    document.getElementById("update_Track").style.display = "block";
  }
  openPopUpWindowDelete() {
    var self = this;
    self.currentTrackIdSelected = $('#listTrackTable').DataTable().row('.selected').data()[0];
    self.submitDeleteTrack();
  }
  closePopUpWindowCreate() {
    console.log("asgasg");
    var modal = document.getElementById('create_Track');
    modal.style.display = "none";
  }
  closePopUpWindowEdit() {
    var modal = document.getElementById('update_Track');
    modal.style.display = "none";
  }
}

$(document).ready(function () {
  var pageManager = new trackPageManager();
  pageManager.init();
});