/*
 * Fiber Map and Datatable JavaScript File
 * 
 * 
*/
$(window).on('load',function() {
	$(".loader").fadeOut("slow");
});

var map;
//var src = 'https://www.dropbox.com/s/fm6y67t7mqh04xb/Fiber-Nov-2017-DLe.kml?dl=1&dummy='+ (new Date()).getTime();
var src = 'https://www.dropbox.com/s/v3a9movdk6sj8vg/Fiber.kmz?dl=1&dummy='+ (new Date()).getTime();

function initMap() {
	map = new google.maps.Map(document.getElementById('map'), {
		center : new google.maps.LatLng(37.546965, -120.920140),
		zoom : 6

	});

	var kmlLayer = new google.maps.KmlLayer(src, {
		suppressInfoWindows : true,
		preserveViewport : false,
		map : map
	});
	kmlLayer.addListener('click', function(event) {
		var content = event.featureData.infoWindowHtml;
		var testimonial = document.getElementById('capture');
		testimonial.innerHTML = content;
	});
}

// Data Table
var table2;
$(document).ready(function() {
	table2 = $('#data_table2').DataTable({
		deferRender : true,
		scrollY : 400,
		scrollX : 600,
		scrollCollapse : true,
		scroller : true,
		info : false,
		"columnDefs" : [ {
			"targets" : [ 13 ],
			"visible" : false,
			"searchable" : false
		}, {
			"targets" : [ 14 ],
			"visible" : false,
			"searchable" : false
		} ],
		"oLanguage" : {
			"sSearch" : "Keyword Search:"
		}
	});
});
// Click and Direct Google Map Placemark Method

$('#data_table2 tbody').on('click', 'tr', function() {
	var lat = table2.row(this).data()[13]; // Lat
	//console.log(lat);
	var log = table2.row(this).data()[14]; // Long
	//console.log(log)
	if(lat != 0 && log != 0){
		map.setCenter(new google.maps.LatLng(lat, log));
		map.setZoom(13);
	}
});