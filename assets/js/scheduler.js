jQuery(document).ready(function($) {
	
	var timeFormat = 'g:i a', dateFormat = 'F j, Y';

	// Do Time Translation
	var phpTime = {
		a: 'tt', //	Lowercase Ante meridiem and Post meridiem	am or pm
		A: 'TT', //	Uppercase Ante meridiem and Post meridiem	AM or PM
		g: 'h', //	12-hour format of an hour without leading zeros	1 through 12
		G: 'H', //	24-hour format of an hour without leading zeros	0 through 23
		h: 'hh', //	12-hour format of an hour with leading zeros	01 through 12
		H: 'HH', //	24-hour format of an hour with leading zeros	00 through 23
		i: 'mm', //	Minutes with leading zeros	00 to 59
		s: 'ss', //	Seconds, with leading zeros	00 through 59
		u: 'c' //	 Microseconds (added in PHP 5.2.2). Note that date() will always generate 000000 since it takes an integer parameter, whereas DateTime::format() does support microseconds.	Example: 654321
	};

	$.each(phpTime, function( index, value ) {
		timeFormat = timeFormat.replace(index,value);
	});

	// Do Date Translation
	var phpDate = {
		d: 'dd', //	Day of the month, 2 digits with leading zeros	01 to 31
		D: 'D', //	A textual representation of a day, three letters	Mon through Sun
		j: 'd', //	Day of the month without leading zeros	1 to 31
		l: 'DD', // (lowercase 'L')	A full textual representation of the day of the week	Sunday through Saturday
		F: 'MM', //	A full textual representation of a month, such as January or March	January through December
		m: 'mm', //	Numeric representation of a month, with leading zeros	01 through 12
		M: 'M', //	A short textual representation of a month, three letters	Jan through Dec
		Y: 'yy', //	A full numeric representation of a year, 4 digits	Examples: 1999 or 2003
		y: 'y', //	A two digit representation of a year	Examples: 99 or 03
	};

	$.each(phpDate, function( index, value ) {
		dateFormat = dateFormat.replace(index,value);
	});
	
	// Implement datetimepicker
	$('.scheduler-date').datetimepicker({
		dateFormat: dateFormat,
		timeFormat: timeFormat
	});

});