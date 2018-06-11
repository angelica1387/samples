/* 
 *@author=Angelica Espinosa <angelica1387@gmail.com>
 *@date=May 11, 2018 
 * 
 */
/*
 * Require Google Maps API Key
 *
 */
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
	
