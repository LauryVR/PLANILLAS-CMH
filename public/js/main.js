

$(document).ready(function() {
 
    var sliderId = $('#features_slider'); // Slider ID
    sliderId.owlCarousel({
        thumbs: true,
        thumbsPrerendered: true,
        items: 1,
        loop: true,
        autoplay: true,
        dots: false,
        nav: false,
    });

    $("#slide-eventos").owlCarousel({
 
        //autoPlay: 3000, Set AutoPlay to 3 seconds
   
        items : 2,
        itemsDesktop : [1199,3],
        itemsDesktopSmall : [979,3]
   
    });

    $('#events').owlCarousel({
        loop: false,
        nav: false,
        margin: 30,
        autoplay: false,
        autoplayTimeout: 9000,
        responsive: {
            0: {
                items: 1,
                autoplay:true

            },
            600: {
                items: 2
            },
            1000: {
                items: 2
            }
        }
    });

    $('#salones').owlCarousel({
        loop: false,
        nav: false,
        margin: 30,
        autoplay: true,
        autoplayTimeout: 9000,
        dots: false,
        items:2
    });
    
    /*owlCarousel-rooms*/
    $('#rooms').owlCarousel({
        loop: true,
        nav: true,
        margin: 0,
        /* autoplay: true,
        autoplayTimeout: 12000,*/
        
    });
    /*end-owlCarousel-rooms*/
  
    $('#habitaciones').owlCarousel({
        autoplay: false,
        autoplayHoverPause: false,
        autoplayTimeout: 3000,
        autoplaySpeed: 800,
        center: true,
        stagePadding: 15,
        loop: true,
        margin: 15,
        animateOut: 'slide-up',
        animateIn: 'slide-down',
        nav:false,
        dots:false,
        responsive: {
            0: {
                items: 1.4,

            },
            600: {
                items: 1.4
            },
            1000: {
                items: 1.4
            }
        }
        
    });


    $("#owl-level").owlCarousel({
 
        nav : false, // Show next and prev buttons
   
        slideSpeed : 300,
        paginationSpeed : 400,
   
        items : 1, 
        itemsDesktop : false,
        itemsDesktopSmall : false,
        itemsTablet: false,
        itemsMobile : false,
        dots: true,
        autoplay:true

   
    });

    $("#owl-negocios").owlCarousel({
 
        nav : false, // Show next and prev buttons
   
        slideSpeed : 300,
        paginationSpeed : 400,
   
        items : 1, 
        itemsDesktop : false,
        itemsDesktopSmall : false,
        itemsTablet: false,
        itemsMobile : false,
        dots: true,
        autoplay:true

   
    });

    
  
   
});

$(document).ready(function(){
	var altura = $('.navbar').offset().top;
    
	
	$(window).on('scroll', function(){
		if ( $(window).scrollTop() > altura ){
			$('.navbar').addClass('fixed-top');
            $('navbar').removeClass('fixed')
           // $('.btnreservar1').addClass('d-none');
            //$('.btnreservar2').removeClass('d-none');
		} else {
			$('.navbar').removeClass('fixed-top');
            $('.navbar').addClass('fixed');
            //$('.btnreservar2').addClass('d-block');
            //$('.btnreservar1').addClass('d-none');
            //$('.btnreservar1').removeClass('d-none');

		}
	});

});

$(document).ready(function(){
    $("#btnmostrar").click(function(){
        $('#btn-reservar2').show();
        $('.btnreservar2').show();
    });

    $("#btnmostrar2").click(function(){
        $('#btn-reservar2').show();
        $('.btnreservar2').show();
    });

    /*$(".btn-ocultar").click(function(){
        $('#btn-reservar2').hide();
        $('.btnreservar2').hide();
    });*/

    
});

$(document).ready(function() {

    $('.dropdown').click(function(){
        $('#btn-reservar2').toggle();
    });
})


