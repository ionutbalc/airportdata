// http://trac.openlayers.org/wiki/GreatCircleAlgorithms

/**
 *    Geo Constants
 */
EARTH_RADIUS = 3958.75;    // in miles
EARTH_CIRCUMFERENCE = 24900; // in miles
MOON_DISTANCE = 238857;    // in miles
MARS_DISTANCE = 34649589;    // in miles
DEG2RAD =  0.01745329252;  // factor to convert degrees to radians (PI/180)
RAD2DEG = 57.29577951308;
GC_STEP = 500; // draw segment every GC_STEP mi
GC_MIN = 1000; // trigger GC paths once distance is greater than this

// Compute great circle distance between two points (spherical law of cosines)
// http://www.movable-type.co.uk/scripts/latlong.html
// � 2002-2008 Chris Veness
function gcDistance(lat1, lon1, lat2, lon2) {
  var rad = Math.PI / 180;
  lat1 = lat1 * rad;
  lon1 = lon1 * rad;
  lat2 = lat2 * rad;
  lon2 = lon2 * rad;
  var d = Math.acos(Math.sin(lat1)*Math.sin(lat2) + 
                  Math.cos(lat1)*Math.cos(lat2) *
                  Math.cos(lon2-lon1));
  if (d < 0) d += Math.PI;
  return Math.floor(d * EARTH_RADIUS);

}

// Compute great circle bearing from point "from" towards point "to"
function gcBearingTo(from, to) {
  var x = new Array(2);
  var y = new Array(2);
  var bearing;
  var adjust;
  
  if( isValid(from) && isValid(to)) {
    x[0] = from.x * DEG2RAD;    y[0] = from.y * DEG2RAD;
    x[1] = to.x * DEG2RAD;    y[1] = to.y * DEG2RAD;
    
    var a = Math.cos(y[1]) * Math.sin(x[1] - x[0]);
    var b = Math.cos(y[0]) * Math.sin(y[1]) - Math.sin(y[0]) 
      * Math.cos(y[1]) * Math.cos(x[1] - x[0]);
    
    if((a == 0) && (b == 0)) {
      bearing = 0;
      return bearing;
    }
    
    if( b == 0) {
      if( a < 0)  
	bearing = 270;
      else
	bearing = 90;
      return bearing;
    }
    
    if( b < 0) 
      adjust = Math.PI;
    else {
      if( a < 0) 
	adjust = 2 * Math.PI;
      else
	adjust = 0;
    }
    bearing = (Math.atan(a/b) + adjust) * RAD2DEG;
    return bearing;
  } else
    return null;
}


/**
 * Compute great circle waypoint "distance" miles away from "from" in direction "bearing"
 */
function gcWaypoint(from, distance, bearing) {
  var wp = new OpenLayers.Geometry.Point( 0, 0 );

  // Math.* trig functions require angles to be in radians
  var x = from.x * DEG2RAD;
  var y = from.y * DEG2RAD;
  var radBearing = bearing * DEG2RAD;
  
  // Convert arc distance to radians
  var d = distance / EARTH_RADIUS;
  
  // Modified based on http://williams.best.vwh.net/avform.htm
  var lat = Math.asin( Math.sin(y) * Math.cos(d) + Math.cos(y) * Math.sin(d) * Math.cos(radBearing));  
  var lon = Math.atan2( Math.sin(radBearing) * Math.sin(d) * Math.cos(y), Math.cos(d) - Math.sin(y) * Math.sin(lat));
  wp.x = (x + lon) * RAD2DEG;
  wp.y = lat * RAD2DEG;
  return wp;
}

/*
 * Return array of two points, flipping across dateline if needed
 */
function straightPath(startPoint, endPoint) {
  // Do we cross the dateline?  If yes, then flip endPoint across it
  if(Math.abs(startPoint.x-endPoint.x) > 180) {
    if(startPoint.x < endPoint.x) {
      endPoint.x -= 360;
    } else {
      endPoint.x += 360;
    }
  }
  return [startPoint, endPoint];
}

/*
 * Return array of GC waypoints between two points
 * Flips across dateline if needed, and removes any invisible points
 */  
function gcPath(startPoint, endPoint, distance) {
  // Do we cross the dateline?  If yes, then flip endPoint across it
  if(Math.abs(startPoint.x-endPoint.x) > 180) {
    if(startPoint.x < endPoint.x) {
      endPoint.x -= 360;
    } else {
      endPoint.x += 360;
    }
  }

  // And... action!
  var pointList = new Array();
  var wayPoint = startPoint;
  var d = GC_STEP;
  var step = GC_STEP;
  if(startPoint.x > -360 && startPoint.x < 360) {
    pointList.push(startPoint);
  }
  while(d < distance) {
    var bearing = gcBearingTo(wayPoint, endPoint); // degrees, clockwise from 0 deg at north
    var wayPoint = gcWaypoint(wayPoint, step, bearing);
    if(wayPoint.x > -360 && wayPoint.x < 360) {
      pointList.push(wayPoint);
    } else {
      if((wayPoint.x < -360 && bearing > 180) ||
	 (wayPoint.x > 360 && bearing < 180)) {
	break; // line's gone off the map, so stop rendering
      }
    }

    // Increase step resolution near the poles
    if(Math.abs(wayPoint.y) > 60) {
      step = GC_STEP / 2;
    } else {
      step = GC_STEP;
    }
    d += step;
  }
  if(endPoint.x > -360 && endPoint.x < 360) {
    pointList.push(endPoint);
  }
  return pointList;
}

// Check if point is a point
function isValid(point) {
    if((point.x != null) && (point.y != null) && (point.x != NaN) && (point.y != NaN))
        return( true );
    else
        return( false );
}

// And some totally unrelated helper functions
//
// Check if DST is active
function checkDST(type, date, year) {
  switch(type) {
  case "E":
    alert(getLastDay(year, 3, 0) + " < " + date + " < " + getLastDay(year, 10, 0));
    // Europe: Last Sunday in Mar to last Sunday in Oct
    if (date > getLastDay(year, 3, 0) &&
	date < getLastDay(year, 10, 0)) {
      return true;
    }
    break;

  case "A":
    // US/Canada: 2nd Sunday in Mar to 1st Sunday in Nov
    if (date > getNthDay(year, 3, 2, 0) &&
	date < getNthDay(year, 11, 1, 0)) {
      return true;
    }
    break;

  case "A":
    // South America: Until 3rd Sunday in Mar or after 3nd Sunday in Oct
    if (date < getNthDay(year, 3, 3, 0) ||
	date > getNthDay(year, 10, 3, 0)) {
      return true;
    }
    break;

  case "O":
    // Australia: Until 1st Sunday in April or after 1st Sunday in Oct
    if (date < getNthDay(year, 4, 1, 0) ||
	date > getNthDay(year, 10, 1, 0)) {
      return true;
    }
    break;

  case "Z":
    // New Zealand: Until 1st Sunday in April or after last Sunday in Sep
    if (date < getNthDay(year, 4, 1, 0) ||
	date > getLastDay(year, 9, 0)) {
      return true;
    }
    break;

  default:
    // cases U, N -- do nothing
  }
  return false;
}

// Get Nth day of type X in a given month (eg. third Sunday in March 2009)
// 'type' is 0 for Sun, 1 for Mon, etc
function getNthDay(year, month, nth, type) {
  date = new Date();
  date.setFullYear(year, month-1, 1); // Date object months start from 0
  day = date.getDay();
  if(type >= day) nth -= 1;
  date.setDate(date.getDate() + (7 - (day - type)) + ((nth-1) * 7));
  return date;
}

// Get last day of type X in a given month (eg. last Sunday in March 2009)
function getLastDay(year, month, type) {
  date = new Date();
  date.setFullYear(year, month, 1); // Date object months start from 0, so this is +1
  date.setDate(date.getDate()-1); // last day of previous month
  date.setDate(date.getDate() - (date.getDay() - type));
  return date;
} 

// Splice and dice apdata chunks
// code:apid:x:y:tz:dst
function getX(element) {
  var ap = $(element + 'id').value.split(":");
  return ap[2];
}
function getY(element) {
  var ap = $(element + 'id').value.split(":");
  return ap[3];
}
function getTZ(element) {
  var ap = $(element + 'id').value.split(":");
  return parseInt(ap[4]);
}
function getDST(element) {
  var ap = $(element + 'id').value.split(":");
  return ap[5];
}

// Return HTML string representing user's elite status icon
// If validity is not null, also return text description and validity period

var eliteicons = [ [ 'S', 'Silver Elite', '/img/silver-star.png' ],
		   [ 'G', 'Gold Elite', '/img/gold-star.png' ],
		   [ 'P', 'Platinum Elite', '/img/platinum-star.png' ],
		   [ 'X', 'Thank you for using OpenFlights &mdash; please donate!', '/img/icon-warning.png' ] ];

function getEliteIcon(e, validity) {
  if(e && e != "") {
    for(i = 0; i < eliteicons.length; i++) {
      if(eliteicons[i][0] == e) {
	if(validity) {
	  return "<center><img src='" + eliteicons[i][2] + "' title='" + eliteicons[i][1] + "' height=34 width=34></img><br><b>" + eliteicons[i][1] + "</b><br><small>Valid until<br>" + validity + "</small></center>";
	} else {
	  return "<span style='float: right'><a href='/donate.html' target='_blank'><img src='" + eliteicons[i][2] + "' title='" + eliteicons[i][1] + "' height=34 width=34></a></span>";
	}
      }
    }
  }
  return "";
}