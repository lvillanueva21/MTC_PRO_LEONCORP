<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="utf-8">
        <title>Cental - Car Rent Website Template</title>
        <meta content="width=device-width, initial-scale=1.0" name="viewport">
        <meta content="" name="keywords">
        <meta content="" name="description">

        <!-- Google Web Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,400;0,700;0,900;1,400;1,700;1,900&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet"> 

        <!-- Icon Font Stylesheet -->
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css"/>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

        <!-- Libraries Stylesheet -->
        <link href="/web/lib/animate/animate.min.css" rel="stylesheet">
        <link href="/web/lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">


        <!-- Customized Bootstrap Stylesheet -->
        <link href="/web/css/bootstrap.min.css" rel="stylesheet">

        <!-- Template Stylesheet -->
        <link href="/web/css/style.css" rel="stylesheet">
    </head>

    <body>

        <!-- Spinner Start -->
        <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
        <!-- Spinner End -->

        <!-- Topbar Start -->
        <?php include __DIR__ . '/web/partials/topbar.php'; ?>
        <!-- Topbar End -->

        <!-- Navbar & Hero Start -->
        <?php include __DIR__ . '/web/partials/navbar.php'; ?>
        <!-- Navbar & Hero End -->

        <!-- Carousel Start -->
        <?php include __DIR__ . '/web/partials/formulario_carrusel.php'; ?>
        <!-- Carousel End -->

        <!-- Features Start -->
        <?php include __DIR__ . '/web/partials/features.php'; ?>
        <!-- Features End -->

        <!-- About Start -->
        <?php include __DIR__ . '/web/partials/about.php'; ?>
        <!-- About End -->

        <!-- Fact Counter -->
        <?php include __DIR__ . '/web/partials/counter.php'; ?>
        <!-- Fact Counter -->

        <!-- Services Start -->
        <?php include __DIR__ . '/web/partials/services.php'; ?>
        <!-- Services End -->

        <!-- Car categories Start -->
        <?php include __DIR__ . '/web/partials/carrusel_servicios.php'; ?>
        <!-- Car categories End -->

        <!-- Car Steps Start -->
        <?php include __DIR__ . '/web/partials/process.php'; ?>
        <!-- Car Steps End -->

        <!-- Novedades Start -->
        <?php include __DIR__ . '/web/partials/novedades.php'; ?>
        <!-- Novedades End -->

        <!-- Banner Start -->
        <?php include __DIR__ . '/web/partials/banner.php'; ?>
        <!-- Banner End -->

        <!-- Team Start -->
        <?php include __DIR__ . '/web/partials/carrusel_empresas.php'; ?>
        <!-- Team End -->

        <!-- Testimonial Start -->
        <?php include __DIR__ . '/web/partials/testimonios.php'; ?>
        <!-- Testimonial End -->

        <!-- Footer Start -->
        <div id="contacto" class="container-fluid footer py-5 wow fadeIn" data-wow-delay="0.2s">
            <div class="container py-5">
                <div class="row g-5">
                    <div class="col-md-6 col-lg-6 col-xl-3">
                        <div class="footer-item d-flex flex-column">
                            <div class="footer-item">
                                <h4 class="text-white mb-4">Sobre Nosotros</h4>
                                <p class="mb-3">Dolor amet sit justo amet elitr clita ipsum elitr est.Lorem ipsum dolor sit amet, consectetur adipiscing elit consectetur adipiscing elit.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-6 col-xl-3">
                        <div class="footer-item d-flex flex-column">
                            <h4 class="text-white mb-4">Links rápidos</h4>
                            <a href="#"><i class="fas fa-angle-right me-2"></i> Nosotros</a>
                            <a href="#"><i class="fas fa-angle-right me-2"></i> Servicios</a>
                            <a href="#"><i class="fas fa-angle-right me-2"></i> Empresas</a>
                            <a href="#"><i class="fas fa-angle-right me-2"></i> Beneficios</a>
                            <a href="#"><i class="fas fa-angle-right me-2"></i> Contáctanos</a>
                            <a href="#"><i class="fas fa-angle-right me-2"></i> Términos y condiciones</a>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-6 col-xl-3">
                        <div class="footer-item d-flex flex-column">
                            <h4 class="text-white mb-4">Horario de atención</h4>
                            <div class="mb-3">
                                <h6 class="text-muted mb-0">Lunes - Viernes:</h6>
                                <p class="text-white mb-0">07.00 am to 04.00 pm</p>
                            </div>
                            <div class="mb-3">
                                <h6 class="text-muted mb-0">Sábado:</h6>
                                <p class="text-white mb-0">7.00 am to 01.00 pm</p>
                            </div>
                            <div class="mb-3">
                                <h6 class="text-muted mb-0">Domingo:</h6>
                                <p class="text-white mb-0">7.00 am to 01.00 pm (Previa coordinación)</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-6 col-xl-3">
                        <div class="footer-item d-flex flex-column">
                            <h4 class="text-white mb-4">Contacto</h4>
                            <a href="#"><i class="fa fa-map-marker-alt me-2"></i> Calle 8 de setiembre 1345</a>
                            <a href="mailto:info@example.com"><i class="fas fa-envelope me-2"></i> q@gmail.com</a>
                            <a href="tel:+012 345 67890"><i class="fas fa-phone me-2"></i> 964881841</a>
                            <div class="d-flex">
                                <a class="btn btn-secondary btn-md-square rounded-circle me-3" href=""><i class="fab fa-facebook-f text-white"></i></a>
                                <a class="btn btn-secondary btn-md-square rounded-circle me-3" href=""><i class="fab fa-twitter text-white"></i></a>
                                <a class="btn btn-secondary btn-md-square rounded-circle me-3" href=""><i class="fab fa-instagram text-white"></i></a>
                                <a class="btn btn-secondary btn-md-square rounded-circle me-0" href=""><i class="fab fa-linkedin-in text-white"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Footer End -->
        
        <!-- Copyright Start -->
        <div id="copyright" class="container-fluid copyright py-4">
            <div class="container">
                <div class="row g-4 align-items-center">
                    <div class="col-md-6 text-center text-md-start mb-md-0">
                        <span class="text-body"><a href="#" class="border-bottom text-white"><i class="fas fa-copyright text-light me-2"></i>LuigiSistemas</a>, Todos los derechos reservados.</span>
                    </div>
                    <div class="col-md-6 text-center text-md-end text-body">
                        Designed By <a class="border-bottom text-white" href="https://htmlcodex.com">LuigiSistemas</a> Para <a class="border-bottom text-white" href="#">Grupo Génesis</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- Copyright End -->


        <!-- Back to Top -->
        <a href="#" class="btn btn-secondary btn-lg-square rounded-circle back-to-top"><i class="fa fa-arrow-up"></i></a>   

        
    <!-- JavaScript Libraries -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/web/lib/wow/wow.min.js"></script>
    <script src="/web/lib/easing/easing.min.js"></script>
    <script src="/web/lib/waypoints/waypoints.min.js"></script>
    <script src="/web/lib/counterup/counterup.min.js"></script>
    <script src="/web/lib/owlcarousel/owl.carousel.min.js"></script>
    

    <!-- Template Javascript -->
    <script src="/web/js/main.js"></script>
    </body>

</html>

