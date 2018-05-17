/*
 * Search Engine php JavaScript file
 */

// Page Loader
$(window).on('load',function() {
	$(".loader").fadeOut("slow");
	$('div.dataTables_scrollBody').height(400);
});

// Google Map API
var map;
function initMap() {
	map = new google.maps.Map(document.getElementById('google_map'), {
		center : new google.maps.LatLng(37.776372, -120.805515),
		zoom : 7
	});
	var infoWindow = new google.maps.InfoWindow;

	// Change this depending on the name of your PHP or XML file
	downloadUrl('getxml.php', function(data) {
		var xml = data.responseXML;
		var markers = xml.documentElement.getElementsByTagName('element');
		Array.prototype.forEach.call(markers, function(markerElem) {
			//var id = markerElem.getAttribute('id');
			var name = markerElem.getAttribute('name');
			var address = markerElem.getAttribute('address');
			var type = markerElem.getAttribute('type');
			var point = new google.maps.LatLng(parseFloat(markerElem
					.getAttribute('lat')), parseFloat(markerElem
					.getAttribute('lng')));

			var infowincontent = document.createElement('div');
			var strong = document.createElement('strong');
			strong.textContent = name;
			infowincontent.appendChild(strong);
			infowincontent.appendChild(document.createElement('br'));

			var text = document.createElement('text');
			text.textContent = address;
			infowincontent.appendChild(text);
			var icon = customLabel[type] || {};
			var marker = new google.maps.Marker({
				map : map,
				position : point,
				label : icon.label
			});
			marker.addListener('click', function() {
				infoWindow.setContent(infowincontent);
				infoWindow.open(map, marker);
			});
		});
	});
}

// Get data from XML file to google map
function downloadUrl(url, callback) {
	var request = window.ActiveXObject ? new ActiveXObject('Microsoft.XMLHTTP')
			: new XMLHttpRequest;

	request.onreadystatechange = function() {
		if (request.readyState == 4) {
			request.onreadystatechange = doNothing;
			callback(request, request.status);
		}
	};

	request.open('GET', url, true);
	request.send(null);
}

function doNothing() {
}

// Data Table
var table;
$(document).ready(function() {
	// $('#data_table').DataTable();
	table = $('#data_table').DataTable({
		deferRender : true,
		scrollY : 600,
		scrollX : 600,
		scrollX : true,
		scrollCollapse:true,
		scroller : true,
		info : false,
		"columnDefs" : [ {
			"targets" : [ 14 ],
			"visible" : false,
			"searchable" : false
		}, {
			"targets" : [ 15 ],
			"visible" : false,
			"searchable" : false
		} ],
		"oLanguage" : {
			"sSearch" : "Keyword Search:"
		},
		responsive: {
            details: {
                display: $.fn.dataTable.Responsive.display.modal( {
                    header: function ( row ) {
                        var data = row.data();
                        return 'Details for '+data[6];
                    }
                } ),
                renderer: $.fn.dataTable.Responsive.renderer.tableAll( {
                    tableClass: 'table'
                } )
            }
        }
	});
});

// Click and Direct Google Map Placemark Method
$('#data_table tbody').on('click', 'tr', function() {
	var lat = table.row(this).data()[14]; // Lat
	console.log(lat);
	var log = table.row(this).data()[15]; // Long
	console.log(log);
	map.setCenter(new google.maps.LatLng(lat, log));
	map.setZoom(17);
});

var customLabel = {};

function adjustScrollY(){
	if($('div.dataTables_scrollBody').height() == 400){
		$('div.dataTables_scrollBody').height(600);
	}else{
		$('div.dataTables_scrollBody').height(400);
	}
}

// Map Toggle Animation
$("#map_switch")
		.click(
				function() {
					$("#google_map").fadeToggle("slow");
					var src = ($(this).attr('src') === 'images/map-icon-on.png') ? 'images/map-icon-off.png'
							: 'images/map-icon-on.png';
					$(this).attr('src', src);
					adjustScrollY();
				});

// Collapse Map Toggle
$("#map_collapse_switch").click(function() {
	$("#google_map").fadeToggle("slow");
	adjustScrollY();
});


