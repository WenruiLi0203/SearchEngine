//Detect Scroller function
$(window).scroll(function() {
	if ($(this).scrollTop() >= 50) { // If page is scrolled more than 50px
		$('#Top').fadeIn(200); // Fade in the arrow
	} else {
		$('#Top').fadeOut(200); // Else fade out the arrow
	}
});
// Top Button Click Function
$('#Top').click(function() { // When arrow is clicked
	$('body,html').animate({
		scrollTop : 0
	// Scroll to top of body
	}, 500);
});
