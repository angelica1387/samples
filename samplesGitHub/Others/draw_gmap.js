Array.prototype.sum = function() {
    return this.reduce(function(a,b){return a+b;});
};
function drawMap(){
	var elem = document.getElementById('map');
	targetingData = target;
	var maxZIndex = 1000000;
	var bounds_map = new google.maps.LatLngBounds() ;

				var icon = "../img/red_marker.png";
				//var icon = customIcon({fillColor: '#cc2028',strokeColor: '#fff'});
			
				var styleNormal = {
                    strokeColor:  "#29a29d",
					strokeOpacity: 0.8,
					strokeWeight: 2,
                    fillColor:  "#29a29d",
                    fillOpacity: 0.20,
                    icon:icon, 
				};
	// Add center calculation method
	google.maps.Polygon.prototype.getApproximateCenter = function(polygonBounds) {
		
		var boundsHeight = 0,
			  boundsWidth = 0,
			  centerPoint,
			  heightIncr = 0,
			  maxSearchLoops,
			  maxSearchSteps = 10,
			  n = 1,
			  northWest,
			  testPos,
			  widthIncr = 0;


		  // Get polygon Centroid
		  centerPoint = polygonBounds.getCenter();
		  
		  


		  if (google.maps.geometry.poly.containsLocation(centerPoint, this)) {
			  // Nothing to do Centroid is in polygon use it as is
			  return centerPoint;
		  } else {
			  maxSearchLoops = maxSearchSteps / 2;


			  // Calculate NorthWest point so we can work out height of polygon NW->SE
			  northWest = new google.maps.LatLng(
				polygonBounds.getNorthEast().lat(), 
				polygonBounds.getSouthWest().lng()
			  );


			  // Work out how tall and wide the bounds are and what our search
			  // increment will be
			  boundsHeight = google.maps.geometry.spherical.computeDistanceBetween(
				northWest, 
				polygonBounds.getSouthWest()
			  );
			  heightIncr = boundsHeight / maxSearchSteps;


			  boundsWidth = google.maps.geometry.spherical.computeDistanceBetween(
				 northWest, polygonBounds.getNorthEast()
			  );
			  widthIncr = boundsWidth / maxSearchSteps;


			  // Expand out from Centroid and find a point within polygon at 
			  // 0, 90, 180, 270 degrees
			  for (; n <= maxSearchSteps; n++) {
				  // Test point North of Centroid
				  testPos = google.maps.geometry.spherical.computeOffset(
					centerPoint, 
					(heightIncr * n), 
					0
				  );
				  if (google.maps.geometry.poly.containsLocation(testPos, this)) {
					  break;
				  }


				  // Test point East of Centroid
				  testPos = google.maps.geometry.spherical.computeOffset(
					centerPoint, 
					(widthIncr * n), 
					90
				  );
				  if (google.maps.geometry.poly.containsLocation(testPos, this)) {
					  break;
				  }


				  // Test point South of Centroid
				  testPos = google.maps.geometry.spherical.computeOffset(
					centerPoint, 
					(heightIncr * n), 
					180
				  );
				  if (google.maps.geometry.poly.containsLocation(testPos, this)) {
					  break;
				  }


				  // Test point West of Centroid
				  testPos = google.maps.geometry.spherical.computeOffset(
					centerPoint, 
					(widthIncr * n), 
					270
				  );
				  if (google.maps.geometry.poly.containsLocation(testPos, this)) {
					  break;
				  }
			  }


			  return(testPos);
			}
	};
	
	google.maps.Data.Feature.prototype.getApproximateCenter = function(){
				
		var boundsHeight = 0,
			boundsWidth = 0,
			centerPoint,
			heightIncr = 0,
			maxSearchLoops,
			maxSearchSteps = 10,
			n = 1,
			northWest,
			testPos,
			widthIncr = 0;
			  
		var featureBounds = new google.maps.LatLngBounds();			
		processPoints(this.getGeometry(), featureBounds.extend, featureBounds);
		
		centerPoint = featureBounds.getCenter();
		
		if(featureContainsLatLng(centerPoint, this.getGeometry())){
			return centerPoint;
		}
		
		maxSearchLoops = maxSearchSteps / 2;

		// Calculate NorthWest point so we can work out height of polygon NW->SE
		northWest = new google.maps.LatLng(
			featureBounds.getNorthEast().lat(), 
			featureBounds.getSouthWest().lng()
		);
		//console.log("northWest" + centerPoint.lat() + ", "+centerPoint.lng() );


		// Work out how tall and wide the bounds are and what our search
		// increment will be
		
		boundsHeight = google.maps.geometry.spherical.computeDistanceBetween(
				northWest, 
				featureBounds.getSouthWest()
		);
		heightIncr = boundsHeight / maxSearchSteps;

		boundsWidth = google.maps.geometry.spherical.computeDistanceBetween(
			northWest, featureBounds.getNorthEast()
		);
		widthIncr = boundsWidth / maxSearchSteps;

		// Expand out from Centroid and find a point within polygon at 
		// 0, 90, 180, 270 degrees
		for (; n <= maxSearchSteps; n++) {
			// Test point North of Centroid
			testPos = google.maps.geometry.spherical.computeOffset(
										centerPoint, 
										(heightIncr * n), 
										0
						);
			if(featureContainsLatLng(testPos, this.getGeometry())){
				//console.log("Test point North of Centroid : inside polygon" );
					break;
			}		
					
			// Test point East of Centroid
			testPos = google.maps.geometry.spherical.computeOffset(
												centerPoint, 
												(widthIncr * n), 
												90
			);
			if(featureContainsLatLng(testPos, this.getGeometry())){
				//console.log("Test point East of Centroid : inside polygon" );
					break;
			}
			 
			// Test point South of Centroid
			testPos = google.maps.geometry.spherical.computeOffset(
						centerPoint, 
						(heightIncr * n), 
						180
			);
			if(featureContainsLatLng(testPos, this.getGeometry())){
				//console.log("Test point South of Centroid : inside polygon" );
					break;
			}
			
			// Test point West of Centroid
			testPos = google.maps.geometry.spherical.computeOffset(
										centerPoint, 
										(widthIncr * n), 
										270
			);
			if(featureContainsLatLng(testPos, this.getGeometry())){
				//console.log("Test point West of Centroid : inside polygon" );
					break;
			}
		}		
		return(testPos);
		
	}
	
	google.maps.Data.Feature.prototype.getApproximateArea = function(){		
		
		var areaFeature = 0;
		
		if(this.getGeometry().getType() == "Polygon"){
			this.getGeometry().getArray().forEach(function(g) {
				areaFeature += google.maps.geometry.spherical.computeArea(g.getArray());
			});
			
			
		}else if(this.getGeometry().getType() !== "Point"){			
			this.getGeometry().getArray().forEach(function(g) {

					g.getArray().forEach(function(g_1) {

						areaFeature += google.maps.geometry.spherical.computeArea(g_1.getArray());
					});
			});
		}
		return Math.round(areaFeature);
}
	 
	
	if(targetingData.length > 0){
			try{
               target = JSON.parse(targetingData);
            }catch(err){
              target = targetingData;          
            }		
			var features = [];
			var markers = [];
			var lats = [];
			var lngs = [];
			$.each( target, function( index, value ){
				if( (value.lat !== null) && (value.lon !== null)){
					lats.push(parseFloat(value.lat));
					lngs.push(parseFloat(value.lon));
					var coordinates = null;
						if(value.polygon !== null){
							try {
							  coordinates = JSON.parse(value.polygon);
							} catch (err) {
							   coordinates = value.polygon;
							}
						}
						
						if(value.radius != 0 ){
							coordinates = generateGeoJSONCircle(new google.maps.LatLng(parseFloat(value.lat),parseFloat(value.lon)),
																value.radius*1609.34, 32);
						
																
						}
						if(coordinates === null){	
							/*Point coordinates are in x, y order (easting, northing for projected coordinates, longitude, latitude for geographic coordinates)*/
							 coordinates = {"type":"Point",
											"coordinates":[parseFloat(value.lon),parseFloat(value.lat)]};
						}
						
						var poly = (typeof coordinates.geometry  != 'undefined')
									?coordinates.geometry
									:coordinates;	
						var feature = {
                                    "type": "Feature",
                                    "id": value.canonicalName ,
                                    "geometry": poly,
                                    "properties":{"lat": parseFloat(value.lat),
													"lon": parseFloat(value.lon),
													"reach":value.reach,									
													"clicks" : parseInt(value.clicks),
													"clicks_2" : parseInt(value.clicks_2),									
													"change" : (parseInt(value.clicks) - parseInt(value.clicks_2))	,
													"imps" : parseInt(value.imps),	
													"ctr" : parseFloat((value.clicks/value.imps)*100).toFixed(2),
									}
                                  }; 
						//console.log(JSON.stringify(feature));
					 
                        features.push(feature) ;
				}				
			});
			if((lats.length === 0) || (lngs.length === 0) ){
				initializeMap();
				return;
			}			
			
			var state = new google.maps.Data();
			
			state.addListener('addfeature', function(evt) {					
				
				if(evt.feature.getGeometry().getType() != "Point"){					
					var  markerPos = new google.maps.LatLng(parseFloat(evt.feature.getProperty("lat")), parseFloat(evt.feature.getProperty("lon")));
					/*console.log("FeatureId : "+ evt.feature.getId());	
					console.log(JSON.stringify(markerPos));	
					console.log("Geometry Type : "+ evt.feature.getGeometry().getType());	*/

					if(!(featureContainsLatLng(markerPos, evt.feature.getGeometry()))){	
						markerPos = evt.feature.getApproximateCenter();
						evt.feature.setProperty("lat",markerPos.lat());
						evt.feature.setProperty("lon",markerPos.lng());
					}	
					//console.log("New marker : " + JSON.stringify(markerPos));		
					 markers.push(new google.maps.Marker({"icon":icon, 
																"position": markerPos,
																"title":evt.feature.getId(),
																}));
							
					bounds_map.extend(markerPos);
				}
						
				
			});
		  
			var infoWindow = new google.maps.InfoWindow();
			var center = new google.maps.LatLng(lats.sum()/lats.length, lngs.sum()/lngs.length); 

			var mapOptions = {
                zoom: 10,
                streetViewControl: false,
                center: center,
                mapTypeId: google.maps.MapTypeId.ROADMAP,
            };	
			
			map = new google.maps.Map(elem,mapOptions ); 
			$("#map").addClass("loader");
			
			$.each(features, function(k,v){	
				var styleNormal = {
                    strokeColor:  "#29a29d",
					strokeOpacity: 0.8,
					strokeWeight: 2,
                    fillColor:  "#29a29d",
                    fillOpacity: 0.20,
                    icon:icon, 
					title: v.properties.id,
					zIndex:v.properties.zIndex
                };
				try{
					var shape = state.addGeoJson(v);
					shape[0].setProperty("area",shape[0].getApproximateArea());
					state.setStyle(function(shape){
						return ({
							
								strokeColor:  "#29a29d",
								strokeWeight: 1,
								fillColor:  "#29a29d",
								fillOpacity: 0.20,
								icon:icon, 
								zIndex: (maxZIndex - shape.getProperty("area"))
						});
						
					});
				}catch(err){
					console.log(err);
					v.geometry = {"type":"Point",
								"coordinates":[parseFloat(v.lon),parseFloat(v.lat)]};
					state.addGeoJson(v);	
				}
			});	
			
	     	//state.setStyle(styleNormal);
			state.setMap(map);
			map.fitBounds(bounds_map);
			var markerCluster = new MarkerClusterer(map, markers,
													{imagePath: 'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/m'});
													
			state.addListener('click', function(evt) {
					
				   var reach = (evt.feature.getProperty("reach") == 0) ? ''
				   :'Estimated Reach : ' + evt.feature.getProperty("reach") + '<br>';
					var contentString ='<b>' + evt.feature.getId() + '</b><br>'+
					reach +
					'Clicks : ' + evt.feature.getProperty("clicks") + '<br>' +
					'Impressions : ' + evt.feature.getProperty("imps")+ '<br>' +
					'CTR(%) : ' + evt.feature.getProperty("ctr");
					
					infoWindow.setPosition(new google.maps.LatLng(evt.feature.getProperty("lat"),evt.feature.getProperty("lon")));
					infoWindow.setContent(contentString);
					infoWindow.open(map);
				});	
			state.addListener('mouseover', function(evt) {
					
					state.revertStyle();				
					var styleHover = {
						strokeColor:  "#29a29d",
						strokeWeight: 3,
						fillColor:  "#29a29d",
						fillOpacity: 0.35,
						icon:icon,
						title: evt.feature.getId()
					};
					
					state.overrideStyle(evt.feature, styleHover);
			});
			state.addListener('mouseout', function(evt) {
					state.revertStyle();
			});
			$("#map").removeClass("loader");

			
			
	}
	else{initializeMap();}
}
/*function getApproximateArea(geometry){		
		
		if(geometry.getType() == "Polygon"){
			return Math.round(google.maps.geometry.spherical.computeArea(geometry.getAt(0).getArray()));
			
		}else if(geometry.getType() == "MultiPolygon"){	
			var areaFeature = 0;		
			geometry.getArray().forEach(function(g) {
				areaFeature += getApproximateArea(g);
				//google.maps.geometry.spherical.computeArea(g.getAt(0).getArray());
			});
			return Math.round(areaFeature);

		}

}*/
 function processPoints(geometry, callback, thisArg) {
	 
        if (geometry instanceof google.maps.LatLng) {
			callback.call(thisArg, geometry);
        } else if (geometry instanceof google.maps.Data.Point) {
			callback.call(thisArg, geometry.get());
        } else {
			geometry.getArray().forEach(function(g) {
				processPoints(g, callback, thisArg);
          });
        }
}

 function loadGeoJsonString(geoString) {
        var geojson = JSON.parse(geoString);
        map.data.addGeoJson(geojson);
        zoom(map);
}

function generateGeoJSONCircle(center, radius, numSides ){

		  var points = [],
			  degreeStep = 360 / numSides;

		  for(var i = 0; i < numSides; i++){
			var gpos = google.maps.geometry.spherical.computeOffset(center, radius, degreeStep * i);
			points.push([gpos.lng(), gpos.lat()]);
		  };

		  // Duplicate the last point to close the geojson ring
		  points.push(points[0]);

		  return {
			type: 'Polygon',
			coordinates: [ points ]
		  };
}

function featureContainsLatLng(latLng, geometry){
	
	if (geometry instanceof google.maps.Data.Polygon) {
			var containsPoint = false;
			geometry.getArray().forEach(function(g_1) {
				var poly = new google.maps.Polygon({paths:g_1.getArray()});			
				if(google.maps.geometry.poly.containsLocation(latLng,poly)){
					containsPoint = true;
					return;
				}
				if(containsPoint){
					return;
				}
			});		
			return containsPoint;					
	}else if(!(geometry instanceof google.maps.Data.Point) &&
	 !(geometry instanceof google.maps.Data.Polygon)){
			var containsPoint = false;
			geometry.getArray().forEach(function(g) {				
				if(featureContainsLatLng(latLng,g)){
					containsPoint = true;
					return;
				}
		});		
		return containsPoint;
	 }
	
}
function customIcon (opts) {
  return Object.assign({
    path: 'M 0,0 C -2,-20 -10,-22 -10,-30 A 10,10 0 1,1 10,-30 C 10,-22 2,-20 0,0 z M -2,-30 a 2,2 0 1,1 4,0 2,2 0 1,1 -4,0',
    fillColor: '#29a29d',
    fillOpacity: 1,
    strokeColor: '#3aa',
    strokeWeight: 1,
    scale: 1,
  }, opts);
}
function initializeMap() {
        // Create a simple map.
        //features = data;
       var  map = new google.maps.Map(document.getElementById('map'), {
          zoom: 4,
          center: {
             lat:  37.09024,
            lng: -95.712891
          }
        });
}
function validatePolygon(polygon){
	
	var equals = true;		   
	if(polygon.type == "Polygon"){
		 polygon.coordinates.forEach(function(g ) {			
				var firstPoint = new google.maps.LatLng(parseFloat(g[0][0]),parseFloat(g[0][1]));
				var length = g.length;
				var lastPoint = new google.maps.LatLng(parseFloat(g[length-1][0]),parseFloat(g[length-1][1]));
				if(!firstPoint.equals(lastPoint)){
					equals = false;
					return;
				}
			});
	}else if(polygon.type == "MultiPolygon"){		
		 polygon.coordinates.forEach(function(g ) {	
		 		//console.log(g);
				g.forEach(function(g_1 ){
					//console.log(g_1);
					var firstPoint = new google.maps.LatLng(parseFloat(g_1[0][0]),parseFloat(g_1[0][1]));
					var length = g_1 .length;
					var lastPoint = new google.maps.LatLng(parseFloat(g_1[length-1][0]),parseFloat(g_1[length-1][1]));
					if(!firstPoint.equals(lastPoint)){
						equals = false;
					return;
				}
				if(!equals){
					return;
				}
			});	
		});
			
	}
	
	return equals;	
	
}
