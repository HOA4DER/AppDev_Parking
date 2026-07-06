<?php
session_start();

$isSignedIn = !empty($_SESSION['is_signed_in']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Siksik Parking System</title>
    <script src="script.js" defer></script>
</head>
<body>
    <section class="main-page-section">
        <section class="header-promotion-container" id="home" tabindex="-1">
            <div class="main-page-header-container">
                <div class="logo-system-name-container">
                    <img src="images/logo.svg" class="logo-icon" alt="Siksik logo">
                    <span class="system-name">Siksik</span>
                </div>

                <div class="nav-bar-container">
                    <button class="nav-bar-btn" type="button" data-scroll-target="home">Home</button>
                    <button class="nav-bar-btn" type="button" data-scroll-target="how-it-works">Book Parking</button>
                    <button class="nav-bar-btn" type="button" data-scroll-target="pricing">Pricing</button>
                    <button class="nav-bar-btn" type="button" data-scroll-target="about-us">About Us</button>
                    <button class="nav-bar-btn" type="button" data-scroll-target="contact">Contact</button>
                </div>

                <div class="sign-in-container" style="gap: 12px;">
                    <a class="SignIn-btn" href="dashboard.php" style="background: transparent; border: 1px solid rgba(255,255,255,0.16); color: #8d8b8b;">
                        Dashboard
                    </a>
                    <?php if ($isSignedIn): ?>
                        <a class="SignIn-btn" href="book.php">
                            Book Now
                            <img src="images/arrow-right.svg" class="side-icon" alt="">
                        </a>
                        <a class="SignIn-btn" href="logout.php" style="background: transparent; border: 1px solid rgba(255,255,255,0.16); color: #8d8b8b;">
                            Sign Out
                        </a>
                    <?php else: ?>
                        <a class="SignIn-btn" href="main.php">
                            Sign In
                            <img src="images/arrow-right.svg" class="side-icon" alt="">
                        </a>
                    <?php endif; ?>
                </div>

            </div>

            <div class="promotion-body-container">
                <div class="main-page-body">
                    <div class="real-time-availability-container">
                        <div>
                            <div class="pulse-green"></div>
                        </div>
                        <div>
                            <span class="real-time-text">Real Time availability</span>
                            <span class="locations-active">5</span>
                            <span class="locations-text">Spots</span>
                        </div>
                    </div>

                    <div class="main-page-header-body-container">
                        <span class="main-page-header-text">Smart Parking,</span>
                        <span class="main-page-header-text2">Simplified.</span>
                    </div>

                    <div class="main-page-subheader-body-container">
                        <span class="main-page-subheader-text">Reserve your spot online, just like choosing a seat in the</span>
                        <span class="main-page-subheader-text">cinema. Find, compare, and book before you leave home.</span>
                    </div>
                </div>

                <div class="main-page-btn-container">
                    <?php if ($isSignedIn): ?>
                    <a class="book-parking-btn" href="book.php">
                        <img src="images/location.svg" class="location-icon" alt="">
                        Book a Parking Spot
                    </a>
                    <?php else: ?>
                    <a class="book-parking-btn" href="main.php">
                        <img src="images/location.svg" class="location-icon" alt="">
                        Book a Parking Spot
                    </a>
                    <?php endif; ?>
                    <button class="how-it-works-btn" type="button" data-scroll-target="how-it-works">See How it Works</button>
                </div>

                <div class="statistics-wrapper">
                    <div class="statistics-container">
                        <span class="statistics-header">
                            5000+
                        </span>
                        <span class="statistics-subheader">
                            Spots Reserved
                        </span>
                    </div>

                    <div class="statistics-container">
                        <span class="statistics-header">
                            98%
                        </span>
                        <span class="statistics-subheader">
                            On Time availability
                        </span>
                    </div>

                    <div class="statistics-container">
                        <span class="statistics-header">
                            4.8 ★
                        </span>
                        <span class="statistics-subheader">
                            User Rating
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <section class="how-it-works-section" id="how-it-works" tabindex="-1">
            <div class="how-it-works-header-container">
                <span class="offer-header-h3">HOW IT WORKS</span>
                <span class="offer-header-h1">Reserve your space in three steps</span>
            </div>

            <div class="how-it-works-wrapper">
                <div class="how-it-works-card">
                    <span class="how-step-number">01</span>
                    <span class="how-step-header">Choose a location</span>
                    <span class="how-step-text">Browse nearby verified parking areas and check available spots before you leave.</span>
                </div>

                <div class="how-it-works-card">
                    <span class="how-step-number">02</span>
                    <span class="how-step-header">Pick your spot</span>
                    <span class="how-step-text">Select the slot that fits your arrival time, vehicle, and budget.</span>
                </div>

                <div class="how-it-works-card">
                    <span class="how-step-number">03</span>
                    <span class="how-step-header">Park with confidence</span>
                    <span class="how-step-text">Arrive with your reservation ready and go straight to your assigned space.</span>
                </div>
            </div>
        </section>

        <section class="offer-section">
            <div class="offer-header-wrapper">
                <div class="offer-header-container">
                    <span class="offer-header-h3">WHAT WE OFFER</span>
                    <span class="offer-header-h1">Everything you need to park smarter</span>
                </div>
            </div>

            <div class="offers-wrapper">
                <div class="offers-container">
                    <img src="images/offer-reserve.svg" class="offers-icon" alt="">
                    <span class="offers-header-text">Reserve Parking Online</span>
                    <span class="offers-subheader-text">Choose your slot before you arrive. No more circling the block.</span>
                </div>

                <div class="offers-container">
                    <img src="images/offer-availability.svg" class="offers-icon" alt="">
                    <span class="offers-header-text">Real-Time Availability</span>
                    <span class="offers-subheader-text">See which spaces are free instantly. Always up-to-date.</span>
                </div>

                <div class="offers-container">
                    <img src="images/offer-security.svg" class="offers-icon" alt="">
                    <span class="offers-header-text">Verified Locations</span>
                    <span class="offers-subheader-text">Safe and trusted parking areas with round-the-clock security.</span>
                </div>

                <div class="offers-container">
                    <img src="images/offer-pricing.svg" class="offers-icon" alt="">
                    <span class="offers-header-text">Competitive Pricing</span>
                    <span class="offers-subheader-text">Affordable rates with no hidden fees. Pay exactly what you see.</span>
                </div>
            </div>


        </section>

        <section class="pricing-section" id="pricing" tabindex="-1">
            <div class="pricing-header-container">
                <span class="offer-header-h3">PRICING</span>
                <span class="offer-header-h1">Simple rates for every stop</span>
            </div>

            <div class="pricing-wrapper">
                <div class="pricing-card">
                    <span class="pricing-plan-name">Quick Stop</span>
                    <span class="pricing-plan-price">₱60/hr</span>
                    <span class="pricing-plan-text">Best for errands, short meetings, and quick city stops.</span>
                </div>

                <div class="pricing-card pricing-card-featured">
                    <span class="pricing-plan-name">Daily Saver</span>
                    <span class="pricing-plan-price">₱280/day</span>
                    <span class="pricing-plan-text">Reserved all-day parking with real-time slot confirmation.</span>
                </div>

                <div class="pricing-card">
                    <span class="pricing-plan-name">Overnight</span>
                    <span class="pricing-plan-price">₱420/night</span>
                    <span class="pricing-plan-text">Secure overnight spaces with monitored partner locations.</span>
                </div>
            </div>
        </section>

        <footer class="main-footer">
            <div class="footer-content-wrapper">
                <div class="footer-brand-container" id="about-us" tabindex="-1">
                    <div class="footer-logo-container">
                        <img src="images/logo.svg" class="footer-logo-mark" alt="Siksik logo">
                        <span class="footer-system-name">Siksik</span>
                    </div>
                    <span class="footer-brand-text">
                        Smart parking made simple for drivers who want a faster, safer way to reserve a spot before arriving.
                    </span>
                    <div class="footer-status-container">
                        <div class="pulse-green"></div>
                        <span>Live availability across 5 active locations</span>
                    </div>
                </div>

                <div class="footer-links-wrapper">
                    <div class="footer-link-group">
                        <span class="footer-link-header">Explore</span>
                        <a href="#home" class="footer-link" data-scroll-target="home">Home</a>
                        <a href="#how-it-works" class="footer-link" data-scroll-target="how-it-works">Book Parking</a>
                        <a href="#pricing" class="footer-link" data-scroll-target="pricing">Pricing</a>
                        <a href="#about-us" class="footer-link" data-scroll-target="about-us">About Us</a>
                    </div>

                    <div class="footer-link-group">
                        <span class="footer-link-header">Support</span>
                        <a href="#contact" class="footer-link" data-scroll-target="contact">Contact</a>
                        <a href="#" class="footer-link">Help Center</a>
                        <a href="#" class="footer-link">Safety</a>
                        <a href="#" class="footer-link">Terms</a>
                    </div>

                    <div class="footer-link-group footer-contact-group" id="contact" tabindex="-1">
                        <span class="footer-link-header">Get in touch</span>
                        <span class="footer-contact-text">support@siksikparking.com</span>
                        <span class="footer-contact-text">+63 912 345 6789</span>
                        <span class="footer-contact-text">Open daily, 6:00 AM - 11:00 PM</span>
                    </div>
                </div>
            </div>

            <div class="footer-bottom-container">
                <span class="footer-bottom-text">© 2026 Siksik Parking System. All rights reserved.</span>
                <div class="footer-social-container">
                    <a href="#" class="footer-social-link" aria-label="Facebook">f</a>
                    <a href="#" class="footer-social-link" aria-label="Instagram">ig</a>
                    <a href="#" class="footer-social-link" aria-label="X">x</a>
                </div>
            </div>
        </footer>
    </section>
</body>
</html>
