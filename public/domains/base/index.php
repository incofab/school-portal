<?php
$name = 'Edumanager';
$phone = '09035316014';
$email = 'support@edumanager.ng'; //'edumanager.ng@gmail.com';
$address = 'Lagos Nigeria';

$faqList = [
  [
    'question' => 'What is Edumanager?',
    'answer' =>
      'Edumanager is a comprehensive school management software that provides a wide range of features and functionalities to streamline various tasks within educational institutions.'
  ],
  [
    'question' => 'What are the key features of Edumanager?',
    'answer' =>
      'Edumanager includes features such as student enrollment/Admission Application, grade management, communication tools, school fees management, Online CBT exams, and more. It aims to cover all aspects of school administration.'
  ],
  [
    'question' => 'How can I get started with Edumanager?',
    'answer' =>
      'To get started with Edumanager, Click on <a href="' .
      route('registration-requests.create') .
      '">Sign up for an account</a>  and one of our staff will contact you and help setup your school'
  ],
  [
    'question' =>
      'Is Edumanager suitable for all types of educational institutions?',
    'answer' =>
      'Yes, Edumanager is designed to cater to the needs of various educational institutions, including nursery, primary, secondary and other institutions of learning.'
  ],
  [
    'question' => 'Can Edumanager handle multiple campuses or school branches?',
    'answer' =>
      'Yes, Edumanager is equipped to manage multiple campuses or branches. It allows you to efficiently organize and coordinate activities across different locations.'
  ],
  [
    'question' => 'How does Edumanager handle student information and records?',
    'answer' =>
      'Edumanager facilitates the storage and management of student information, including enrollment details, grades, and other relevant data. It ensures data security and easy retrieval when needed.'
  ],
  [
    'question' => 'What communication tools are available in Edumanager?',
    'answer' =>
      'Edumanager provides communication tools such as messaging systems and announcement features to facilitate efficient communication between administrators, teachers, and students.'
  ],
  [
    'question' => 'Can parents access information through Edumanager?',
    'answer' =>
      'Yes, Edumanager offers a parent portal that allows parents to access information about their child\'s academic progress, and other relevant updates.'
  ],
  [
    'question' => 'Is Edumanager customizable to suit our specific needs?',
    'answer' =>
      'Yes, Edumanager is designed with flexibility in mind. It offers customization options to adapt to the specific requirements and workflows of your educational institution.'
  ],
  [
    'question' => 'How can we receive support for any issues or queries?',
    'answer' =>
      'If you encounter any issues or have queries, you can contact our support team through the provided contact channels. We are committed to providing timely assistance.'
  ]
];
$testimonials = [
  [
    'name' => 'Ademoyega Kudirat',
    'position' => 'Principal',
    'school' => 'Greater Heights Secondary School',
    'testimony' =>
      'Edumanager has significantly improved our school administration. The intuitive interface and robust features have made tasks such as attendance tracking and grade management much more efficient. Our staff and students appreciate the streamlined communication tools as well.'
  ],
  [
    'name' => 'Alice Johnson',
    'position' => 'Head Teacher',
    'school' => 'Later Rain Primary School',
    'testimony' =>
      'We\'ve been using Edumanager for over a year, and it has simplified our daily operations. The ability to customize the system to fit our school\'s unique needs has been a game-changer. The parent portal has strengthened the school-home connection, and the support from the Edumanager team has been exceptional.'
  ],
  [
    'name' => 'Tobi Brown',
    'position' => 'Administrator',
    'school' => 'Global Scholars Internation School',
    'testimony' =>
      'Edumanager has exceeded our expectations in terms of functionality and ease of use. Managing multiple campuses is now a seamless process, and the centralized data management ensures accuracy and consistency. We highly recommend Edumanager to other educational institutions.'
  ],
  [
    'name' => 'Sarah Okechukwu',
    'position' => 'IT Coordinator',
    'school' => 'Bright Minds Academy',
    'testimony' =>
      'As the IT coordinator, I appreciate the flexibility and scalability of Edumanager. The system\'s adaptability to our evolving technological needs has been impressive. The regular updates and prompt customer support demonstrate Edumanager\'s commitment to delivering a top-notch solution.'
  ],
  [
    'name' => 'Abdusalam Mohammed',
    'position' => 'Director',
    'school' => 'Destiny Child Academy',
    'testimony' =>
      'Edumanager has revolutionized our university\'s administrative processes. From admissions to academic management, the software has proven to be a reliable companion. The comprehensive reporting tools have also empowered my teachers in identifying the areas our students are lagging behind'
  ]
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title><?= $name ?> - School Management Platform of choice</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
  <link href="/favicon.ico" rel="icon">
  <link href="/favicon.ico" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,600;1,700&family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&family=Raleway:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" integrity="sha512-jnSuA4Ss2PkkikSOLtYs8BlYIeeIK1h99ty4YfvRPAlzr377vr3CXDb7sb7eEEBYjDtcYj+AjBH3FLv5uSJuXg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" integrity="sha512-dPXYcDub/aeb08c63jRq/k6GaKccl256JQy/AnOq7CAnEZ9FzSL9wSbcZkMp4R26vBsMLFYH4kQ67/bbV8XaCQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/1.2.2/aos.css" integrity="sha512-wYGIZRH+bF0Frg6Bx1y1VWmRUU+kyuQT6J11WBk3YTYJbtNMW7cwPcXTOG0Ei9pquI//yabmtCvgS9YbPjoXig==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/glightbox/3.2.0/css/glightbox.min.css" integrity="sha512-T+KoG3fbDoSnlgEXFQqwcTC9AdkFIxhBlmoaFqYaIjq2ShhNwNao9AKaLUPMfwiBPL0ScxAtc+UYbHAgvd+sjQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Swiper/8.4.7/swiper-bundle.min.css" integrity="sha512-N2IsWuKsBYYiHNYdaEuK4eaRJ0onfUG+cdZilndYaMEhUGQq/McsFU75q3N+jbJUNXm6O+K52DRrK+bSpBGj0w==" crossorigin="anonymous" referrerpolicy="no-referrer" />

  <!-- Template Main CSS File -->
  <link href="<?= $basePath ?>assets/css/main.css" rel="stylesheet">

  <?php
// Template Url: https://bootstrapmade.com/impact-bootstrap-business-website-template/
?>
</head>

<body>

  <!-- ======= Header ======= -->
  <section id="topbar" class="topbar d-flex align-items-center">
    <div class="container d-flex justify-content-center justify-content-md-between">
      <div class="contact-info d-flex align-items-center">
        <i class="bi bi-envelope d-flex align-items-center"><a href="mailto:<?= $email ?>"><?= $email ?></a></i>
        <i class="bi bi-phone d-flex align-items-center ms-4"><span><?= $phone ?></span></i>
      </div>
      <div class="social-links d-none d-md-flex align-items-center">
        <a href="#" class="twitter"><i class="bi bi-twitter"></i></a>
        <a href="#" class="facebook"><i class="bi bi-facebook"></i></a>
        <a href="#" class="instagram"><i class="bi bi-instagram"></i></a>
        <a href="#" class="linkedin"><i class="bi bi-linkedin"></i></i></a>
      </div>
    </div>
  </section><!-- End Top Bar -->

  <header id="header" class="header d-flex align-items-center">

    <div class="container-fluid container-xl d-flex align-items-center justify-content-between">
      <a href="index.html" class="logo d-flex align-items-center">
        <!-- Uncomment the line below if you also wish to use an image logo -->
        <!-- <img src="<?= $basePath ?>assets/img/logo.png" alt=""> -->
        <h1><?= $name ?><span>.</span></h1>
      </a>
      <nav id="navbar" class="navbar">
        <ul>
          <li><a href="#hero">Home</a></li>
          <li><a href="#about">About</a></li>
          <li><a href="#services">Services</a></li>
          <!-- <li><a href="#team">Team</a></li> -->
          <!-- <li><a href="blog.html">Blog</a></li> -->
          <!-- 
          <li class="dropdown"><a href="#"><span>Drop Down</span> <i class="bi bi-chevron-down dropdown-indicator"></i></a>
            <ul>
              <li><a href="#">Drop Down 1</a></li>
              <li class="dropdown"><a href="#"><span>Deep Drop Down</span> <i class="bi bi-chevron-down dropdown-indicator"></i></a>
                <ul>
                  <li><a href="#">Deep Drop Down 1</a></li>
                  <li><a href="#">Deep Drop Down 2</a></li>
                  <li><a href="#">Deep Drop Down 3</a></li>
                  <li><a href="#">Deep Drop Down 4</a></li>
                  <li><a href="#">Deep Drop Down 5</a></li>
                </ul>
              </li>
              <li><a href="#">Drop Down 2</a></li>
              <li><a href="#">Drop Down 3</a></li>
              <li><a href="#">Drop Down 4</a></li>
            </ul>
          </li>-->
          <li><a href="#contact">Contact</a></li>
          <li><a href="https://edumanager.ng/partner-registration">Be a Partner</a></li>
          <li><a href="<?= route('login') ?>">Login/Register</a></li>
        </ul>
      </nav><!-- .navbar -->

      <i class="mobile-nav-toggle mobile-nav-show bi bi-list"></i>
      <i class="mobile-nav-toggle mobile-nav-hide d-none bi bi-x"></i>

    </div>
  </header><!-- End Header -->
  <!-- End Header -->

  <!-- ======= Hero Section ======= -->
  <section id="hero" class="hero">
    <div class="container position-relative">
      <div class="row gy-5" data-aos="fade-in">
        <div class="col-lg-6 order-2 order-lg-1 d-flex flex-column justify-content-center text-center text-lg-start">
          <h2>Welcome to <span><?= $name ?></span></h2>
          <p>Discover the power of streamlined education, the heartbeat of efficient school management - where every click unlocks endless possibilities.</p>
          <div class="d-flex justify-content-center justify-content-lg-start">
            <a href="#about" class="btn-get-started">Get Started</a>
            <a href="https://edumanager.ng/partner-registration" class="btn-watch-video d-flex align-items-center"><span>Become a Partner</span></a>
          </div>
        </div>
        <div class="col-lg-6 order-1 order-lg-2">
          <img src="<?= $basePath ?>assets/img/hero-img.svg" class="img-fluid" alt="" data-aos="zoom-out" data-aos-delay="100">
        </div>
      </div>
    </div>

    <div class="icon-boxes position-relative">
      <div class="container position-relative">
        <div class="row gy-4 mt-5">

          <div class="col-xl-3 col-md-6" data-aos="fade-up" data-aos-delay="100">
            <div class="icon-box">
              <div class="icon"><i class="bi bi-easel"></i></div>
              <h4 class="title"><a href="" class="stretched-link">Students Record</a></h4>
            </div>
          </div><!--End Icon Box -->

          <div class="col-xl-3 col-md-6" data-aos="fade-up" data-aos-delay="200">
            <div class="icon-box">
              <div class="icon"><i class="bi bi-gem"></i></div>
              <h4 class="title"><a href="" class="stretched-link">Record Results</a></h4>
            </div>
          </div><!--End Icon Box -->

          <div class="col-xl-3 col-md-6" data-aos="fade-up" data-aos-delay="300">
            <div class="icon-box">
              <div class="icon"><i class="bi bi-geo-alt"></i></div>
              <h4 class="title"><a href="" class="stretched-link">Manage Teachers</a></h4>
            </div>
          </div><!--End Icon Box -->

          <div class="col-xl-3 col-md-6" data-aos="fade-up" data-aos-delay="500">
            <div class="icon-box">
              <div class="icon"><i class="bi bi-command"></i></div>
              <h4 class="title"><a href="" class="stretched-link">Collect Fees</a></h4>
            </div>
          </div><!--End Icon Box -->

        </div>
      </div>
    </div>

    </div>
  </section>
  <!-- End Hero Section -->

  <main id="main">

    <!-- ======= About Us Section ======= -->
    <section id="about" class="about">
      <div class="container" data-aos="fade-up">

        <div class="section-header">
          <h2>About Us</h2>
          <p>At Edumanager, we are more than just a school management software; we are architects of educational efficiency, orchestrators of seamless administration, and partners in your institution's journey towards excellence. Our robust platform seamlessly integrates every essential feature, empowering educators, administrators, and students alike. From grade management to effortless communication and resource allocation, Edumanager is the catalyst for innovation in education. Our commitment extends beyond the digital realm; we envision a future where schools thrive, students excel, and administrators lead with confidence.</p>
        </div>

        <div class="row gy-4">
          <div class="col-lg-6">
            <h3>Every School needs Edumanager</h3>
            <img src="<?= $basePath ?>assets/img/about.jpg" class="img-fluid rounded-4 mb-4" alt="">
            <p>It doesn't matter the size of your school; every educational institution can benefit from Edumanager, the ultimate school management software solution. </p>
            <p>Edumanager simplifies administrative tasks, providing a user-friendly platform that seamlessly integrates crucial features like student enrollment, grade management, fees payment etc...</p>
          </div>
          <div class="col-lg-6">
            <div class="content ps-0 ps-lg-5">
              <p class="fst-italic">
                Digitize your daily activities and do away with traditional pen and paper methods
              </p>
              <ul>
                <li><i class="bi bi-check-circle-fill"></i> Intuitive User Interface</li>
                <li><i class="bi bi-check-circle-fill"></i> Easily navigable for even non-tech savy users</li>
                <li><i class="bi bi-check-circle-fill"></i> Fast Loading Speed: Optimize the website for quick loading times to provide a smooth and efficient experience for users. </li>
              </ul>
              <p>
                This platform is expertly developed with simplicity in mind. Navigation feels so natural that even non techy users
                can easily access and utilize its features.
              </p>

              <div class="position-relative mt-4">
                <img src="<?= $basePath ?>assets/img/about-2.jpg" class="img-fluid rounded-4" alt="">
                <a href="https://www.youtube.com/watch?v=LXb3EKWsInQ" class="glightbox play-btn"></a>
              </div>
            </div>
          </div>
        </div>

      </div>
    </section><!-- End About Us Section -->

    <!-- ======= Clients Section ======= -->
    <section id="clients" class="clients">
      <div class="container" data-aos="zoom-out">

        <div class="clients-slider swiper">
          <div class="swiper-wrapper align-items-center">
            <div class="swiper-slide"><img src="<?= $basePath ?>assets/img/clients/client-1.png" class="img-fluid" alt=""></div>
            <div class="swiper-slide"><img src="<?= $basePath ?>assets/img/clients/client-2.png" class="img-fluid" alt=""></div>
            <div class="swiper-slide"><img src="<?= $basePath ?>assets/img/clients/client-3.png" class="img-fluid" alt=""></div>
            <div class="swiper-slide"><img src="<?= $basePath ?>assets/img/clients/client-4.png" class="img-fluid" alt=""></div>
            <div class="swiper-slide"><img src="<?= $basePath ?>assets/img/clients/client-5.png" class="img-fluid" alt=""></div>
            <div class="swiper-slide"><img src="<?= $basePath ?>assets/img/clients/client-6.png" class="img-fluid" alt=""></div>
            <div class="swiper-slide"><img src="<?= $basePath ?>assets/img/clients/client-7.png" class="img-fluid" alt=""></div>
            <div class="swiper-slide"><img src="<?= $basePath ?>assets/img/clients/client-8.png" class="img-fluid" alt=""></div>
          </div>
        </div>

      </div>
    </section><!-- End Clients Section -->

    <!-- ======= Stats Counter Section ======= -->
    <section id="stats-counter" class="stats-counter">
      <div class="container" data-aos="fade-up">

        <div class="row gy-4 align-items-center">

          <div class="col-lg-6">
            <img src="<?= $basePath ?>assets/img/stats-img.svg" alt="" class="img-fluid">
          </div>

          <div class="col-lg-6">

            <div class="stats-item d-flex align-items-center">
              <span data-purecounter-start="0" data-purecounter-end="300" data-purecounter-duration="1" class="purecounter"></span>
              <p><strong>Happy Clients</strong> using this solution everyday for their activities</p>
            </div><!-- End Stats Item -->

            <div class="stats-item d-flex align-items-center">
              <span data-purecounter-start="0" data-purecounter-end="21" data-purecounter-duration="1" class="purecounter"></span>
              <p><strong>Feature</strong> Extremely helpful features</p>
            </div><!-- End Stats Item -->

            <div class="stats-item d-flex align-items-center">
              <span data-purecounter-start="0" data-purecounter-end="24" data-purecounter-duration="1" class="purecounter"></span>
              <p><strong>Hours Of Support</strong> Always available every day of the week</p>
            </div><!-- End Stats Item -->

          </div>

        </div>

      </div>
    </section><!-- End Stats Counter Section -->

    <!-- ======= Call To Action Section ======= -->
    <section id="call-to-action" class="call-to-action">
      <div class="container text-center" data-aos="zoom-out">
        <a href="https://www.youtube.com/watch?v=LXb3EKWsInQ" class="glightbox play-btn"></a>
        <h3>Join Us Today</h3>
        <p> It takes less than 24 hrs to fully onboard your school and digitize your operations. Get started with us and take your administrative processes to the next level and stay ahead of your peers</p>
        <a class="cta-btn" href="https://edumanager.ng/register">Register</a>
      </div>
    </section><!-- End Call To Action Section -->

    <!-- ======= Our Services Section ======= -->
    <section id="services" class="services sections-bg">
      <div class="container" data-aos="fade-up">

        <div class="section-header">
          <h2>Our Services</h2>
          <p>By automating mundane tasks, Edumanager allows educators to focus on teaching, while its robust reporting and analytics features empower schools to make data-driven decisions. Embrace the future of school management with Edumanager, ensuring your institution operates smoothly and stays at the forefront of educational excellence.</p>
        </div>

        <div class="row gy-4" data-aos="fade-up" data-aos-delay="100">

          <div class="col-lg-4 col-md-6">
            <div class="service-item  position-relative">
              <div class="icon">
                <i class="bi bi-pc-display-horizontal"></i>
              </div>
              <h3>Student Information System (SIS)</h3>
              <p>Manages student data, including personal details, academic records, attendance, and other relevant information.</p>
              <a href="#" class="readmore stretched-link">Read more <i class="bi bi-arrow-right"></i></a>
            </div>
          </div><!-- End Service Item -->

          <div class="col-lg-4 col-md-6">
            <div class="service-item position-relative">
              <div class="icon">
                <i class="bi bi-book-half"></i>
              </div>
              <h3>Exam and Result Management</h3>
              <p>Manages exam schedules, conducts assessments, and publishes results, aiding in the overall examination process.</p>
              <a href="#" class="readmore stretched-link">Read more <i class="bi bi-arrow-right"></i></a>
            </div>
          </div><!-- End Service Item -->

          <div class="col-lg-4 col-md-6">
            <div class="service-item position-relative">
              <div class="icon">
                <i class="bi bi-book-fill"></i>
              </div>
              <h3>Gradebook Management</h3>
              <p>Enables teachers to input, calculate, and manage students' grades, fostering efficient academic assessment.</p>
              <a href="#" class="readmore stretched-link">Read more <i class="bi bi-arrow-right"></i></a>
            </div>
          </div><!-- End Service Item -->

          <!--
          <div class="col-lg-4 col-md-6">
            <div class="service-item position-relative">
              <div class="icon">
                <i class="bi bi-easel"></i>
              </div>
              <h3>Timetable/Schedule Management</h3>
              <p>Generates and manages school schedules, class timings, and teacher allocations for effective time management.</p>
              <a href="#" class="readmore stretched-link">Read more <i class="bi bi-arrow-right"></i></a>
            </div>
          </div> -->

          <div class="col-lg-4 col-md-6">
            <div class="service-item position-relative">
              <div class="icon">
                <i class="bi bi-broadcast"></i>
              </div>
              <h3>Parent-Teacher Communication</h3>
              <p>Facilitates communication between educators and parents through messaging, announcements, and progress reports.</p>
              <a href="#" class="readmore stretched-link">Read more <i class="bi bi-arrow-right"></i></a>
            </div>
          </div><!-- End Service Item -->

          <div class="col-lg-4 col-md-6">
            <div class="service-item position-relative">
              <div class="icon">
                <i class="bi bi-calendar4-week"></i>
              </div>
              <h3>Fee Management</h3>
              <p>Automates the tracking and management of student fees, including invoicing, payments, and overdue reminders.</p>
              <a href="#" class="readmore stretched-link">Read more <i class="bi bi-arrow-right"></i></a>
            </div>
          </div><!-- End Service Item -->

          <!--
          <div class="col-lg-4 col-md-6">
            <div class="service-item position-relative">
              <div class="icon">
                <i class="bi bi-calendar4-week"></i>
              </div>
              <h3>HR and Payroll</h3>
              <p>Manages staff details, attendance, and payroll processing for efficient human resource management.</p>
              <a href="#" class="readmore stretched-link">Read more <i class="bi bi-arrow-right"></i></a>
            </div>
          </div>
          <div class="col-lg-4 col-md-6">
            <div class="service-item position-relative">
              <div class="icon">
                <i class="bi bi-calendar4-week"></i>
              </div>
              <h3>Communication and Notifications</h3>
              <p>Sends alerts, notifications, and updates to students, parents, and staff, enhancing overall communication within the school community.</p>
              <a href="#" class="readmore stretched-link">Read more <i class="bi bi-arrow-right"></i></a>
            </div>
          </div>

          -->

          <div class="col-lg-4 col-md-6">
            <div class="service-item position-relative">
              <div class="icon">
                <i class="bi bi-person-walking"></i>
              </div>
              <h3>Admission and Enrollment Management</h3>
              <p>Streamlines the admission process, managing student enrollment details and documentation.

              </p>
              <a href="#" class="readmore stretched-link">Read more <i class="bi bi-arrow-right"></i></a>
            </div>
          </div><!-- End Service Item -->

          <div class="col-lg-4 col-md-6">
            <div class="service-item position-relative">
              <div class="icon">
                <i class="bi bi-file-earmark-person"></i>
              </div>
              <h3>Custom Reporting</h3>
              <p>Provides customizable reports for various aspects of school management, aiding in data-driven decision-making.</p>
              <a href="#" class="readmore stretched-link">Read more <i class="bi bi-arrow-right"></i></a>
            </div>
          </div><!-- End Service Item -->

          <div class="col-lg-4 col-md-6">
            <div class="service-item position-relative">
              <div class="icon">
                <i class="bi bi-fingerprint"></i>
              </div>
              <h3>Security and Access Control</h3>
              <p>Implements security measures to protect sensitive data and controls access to the system based on user roles.</p>
              <a href="#" class="readmore stretched-link">Read more <i class="bi bi-arrow-right"></i></a>
            </div>
          </div><!-- End Service Item -->

          <div class="col-lg-4 col-md-6">
            <div class="service-item position-relative">
              <div class="icon">
                <i class="bi bi-laptop"></i>
              </div>
              <h3>Online Exam and E-Learning </h3>
              <p>Offers online examination capabilities and supports e-learning initiatives for remote or blended learning environments.</p>
              <a href="#" class="readmore stretched-link">Read more <i class="bi bi-arrow-right"></i></a>
            </div>
          </div><!-- End Service Item -->

        </div>

      </div>
    </section><!-- End Our Services Section -->

    <!-- ======= How it Works Section ======= -->
    <section id="how-it-works" class="how-it-works d-none">
      <div class="container" data-aos="fade-up">
        <div class="section-header">
          <h2>How it Works</h2>
          <p>Voluptatem quibusdam ut ullam perferendis repellat non ut consequuntur est eveniet deleniti fignissimos eos quam</p>
        </div>
      </div>
      </section>

    <!-- ======= Testimonials Section ======= -->
    <section id="testimonials" class="testimonials">
      <div class="container" data-aos="fade-up">

        <div class="section-header">
          <h2>Testimonials</h2>
          <p>Our solution is already serving a lot of schools across the country, take a look at they have to say about their experience with Edumanager.</p>
        </div>

        <div class="slides-3 swiper" data-aos="fade-up" data-aos-delay="100">
          <div class="swiper-wrapper">

            <?php foreach ($testimonials as $key => $testimonial): ?>
            <div class="swiper-slide">
              <div class="testimonial-wrap">
                <div class="testimonial-item">
                  <div class="d-flex align-items-center">
                    <img src="<?= asset(
                      'img/portrait.jpeg'
                    ) ?>" class="testimonial-img flex-shrink-0" alt="">
                    <div>
                      <h3><?= $testimonial['name'] ?></h3>
                      <h4><?= $testimonial['position'] ?> | <?= $testimonial[
   'school'
 ] ?></h4>
                      <div class="stars">
                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                      </div>
                    </div>
                  </div>
                  <p>
                    <i class="bi bi-quote quote-icon-left"></i>
                    <?= $testimonial['testimony'] ?>
                    <i class="bi bi-quote quote-icon-right"></i>
                  </p>
                </div>
              </div>
            </div><!-- End testimonial item -->
            <?php endforeach; ?>

            <!--
            <div class="swiper-slide">
              <div class="testimonial-wrap">
                <div class="testimonial-item">
                  <div class="d-flex align-items-center">
                    <img src="<?= $basePath ?>assets/img/testimonials/testimonials-2.jpg" class="testimonial-img flex-shrink-0" alt="">
                    <div>
                      <h3>Sara Wilsson</h3>
                      <h4>Designer</h4>
                      <div class="stars">
                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                      </div>
                    </div>
                  </div>
                  <p>
                    <i class="bi bi-quote quote-icon-left"></i>
                    Export tempor illum tamen malis malis eram quae irure esse labore quem cillum quid cillum eram malis quorum velit fore eram velit sunt aliqua noster fugiat irure amet legam anim culpa.
                    <i class="bi bi-quote quote-icon-right"></i>
                  </p>
                </div>
              </div>
            </div>< !-- End testimonial item -- >

            <div class="swiper-slide">
              <div class="testimonial-wrap">
                <div class="testimonial-item">
                  <div class="d-flex align-items-center">
                    <img src="<?= $basePath ?>assets/img/testimonials/testimonials-3.jpg" class="testimonial-img flex-shrink-0" alt="">
                    <div>
                      <h3>Jena Karlis</h3>
                      <h4>Store Owner</h4>
                      <div class="stars">
                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                      </div>
                    </div>
                  </div>
                  <p>
                    <i class="bi bi-quote quote-icon-left"></i>
                    Enim nisi quem export duis labore cillum quae magna enim sint quorum nulla quem veniam duis minim tempor labore quem eram duis noster aute amet eram fore quis sint minim.
                    <i class="bi bi-quote quote-icon-right"></i>
                  </p>
                </div>
              </div>
            </div>< !-- End testimonial item -- >

            <div class="swiper-slide">
              <div class="testimonial-wrap">
                <div class="testimonial-item">
                  <div class="d-flex align-items-center">
                    <img src="<?= $basePath ?>assets/img/testimonials/testimonials-4.jpg" class="testimonial-img flex-shrink-0" alt="">
                    <div>
                      <h3>Matt Brandon</h3>
                      <h4>Freelancer</h4>
                      <div class="stars">
                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                      </div>
                    </div>
                  </div>
                  <p>
                    <i class="bi bi-quote quote-icon-left"></i>
                    Fugiat enim eram quae cillum dolore dolor amet nulla culpa multos export minim fugiat minim velit minim dolor enim duis veniam ipsum anim magna sunt elit fore quem dolore.
                    <i class="bi bi-quote quote-icon-right"></i>
                  </p>
                </div>
              </div>
            </div>< !-- End testimonial item -- >

            <div class="swiper-slide">
              <div class="testimonial-wrap">
                <div class="testimonial-item">
                  <div class="d-flex align-items-center">
                    <img src="<?= $basePath ?>assets/img/testimonials/testimonials-5.jpg" class="testimonial-img flex-shrink-0" alt="">
                    <div>
                      <h3>John Larson</h3>
                      <h4>Entrepreneur</h4>
                      <div class="stars">
                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                      </div>
                    </div>
                  </div>
                  <p>
                    <i class="bi bi-quote quote-icon-left"></i>
                    Quis quorum aliqua sint quem legam fore sunt eram irure aliqua veniam tempor noster veniam enim culpa labore duis sunt culpa nulla illum cillum fugiat legam esse veniam culpa fore.
                    <i class="bi bi-quote quote-icon-right"></i>
                  </p>
                </div>
              </div>
            </div>< !-- End testimonial item -->

          </div>
          <div class="swiper-pagination"></div>
        </div>

      </div>
    </section><!-- End Testimonials Section -->

    <!-- ======= Pricing ======= -->
    <!-- Pricing Section -->
    <section id="pricing" class="py-5 bg-light">
      <div class="container">
        <div class="text-center mb-5">
          <h2 class="fw-bold">Affordable Pricing</h2>
          <p class="text-muted">
            Edumanager is designed to be simple and affordable. Schools only pay <strong>₦400 per student per term</strong>, this is highly negotiable depending on the size of your school. THERE ARE NOT HIDDEN FEES.
          </p>
        </div>

        <div class="row justify-content-center">
          <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm border-0 rounded-4">
              <div class="card-body text-center p-4">
                <h5 class="card-title fw-bold mb-3">Per Student</h5>
                <h2 class="fw-bold display-5 mb-3">₦400</h2>
                <p class="text-muted">per student, per term</p>
                <hr>
                <ul class="list-unstyled mb-4">
                  <li><i class="bi bi-check-circle text-success me-2"></i> Full access to all Edumanager features</li>
                  <li><i class="bi bi-check-circle text-success me-2"></i> Secure data storage</li>
                  <li><i class="bi bi-check-circle text-success me-2"></i> Online result printing</li>
                  <li><i class="bi bi-check-circle text-success me-2"></i> Email/SMS service for schools</li>
                  <li><i class="bi bi-check-circle text-success me-2"></i> Free support & updates</li>
                </ul>
                <a href="<?= route(
                  'login'
                ) ?>" class="btn  btn-lg rounded-pill px-4" style="background: var(--color-primary);
    border: 0;
    padding: 14px 45px;
    color: #fff;
    transition: 0.4s;
    border-radius: 50px;">
                  Get Started
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- <div class="text-center mt-5">
          <small class="text-muted">
            Example: A school with 200 students pays only <strong>₦80,000 per term</strong>.
          </small>
        </div> -->
      </div>
    </section>

    <!-- ======= Frequently Asked Questions Section ======= -->
    <section id="faq" class="faq">
      <div class="container" data-aos="fade-up">

        <div class="row gy-4">

          <div class="col-lg-4">
            <div class="content px-xl-5">
              <h3>Frequently Asked <strong>Questions</strong></h3>
              <p>
              Explore frequently asked questions about Edumanager to gain a better understanding of our school management software. Find answers to common queries and learn how Edumanager can enhance the efficiency of your institution.
              </p>
            </div>
          </div>

          <div class="col-lg-8">

            <div class="accordion accordion-flush" id="faqlist" data-aos="fade-up" data-aos-delay="100">

              <?php foreach ($faqList as $key => $faq): ?>
                <div class="accordion-item">
                  <h3 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-content-<?= $key ?>">
                      <span class="num"><?= $key + 1 ?>.</span>
                      <?= $faq['question'] ?>
                    </button>
                  </h3>
                  <div id="faq-content-<?= $key ?>" class="accordion-collapse collapse" data-bs-parent="#faqlist">
                    <div class="accordion-body">
                      <?= $faq['answer'] ?>
                    </div>
                  </div>
                </div><!-- # Faq item-->
              <?php endforeach; ?>
                <!-- 
              <div class="accordion-item">
                <h3 class="accordion-header">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-content-2">
                    <span class="num">2.</span>
                    Feugiat scelerisque varius morbi enim nunc faucibus a pellentesque?
                  </button>
                </h3>
                <div id="faq-content-2" class="accordion-collapse collapse" data-bs-parent="#faqlist">
                  <div class="accordion-body">
                    Dolor sit amet consectetur adipiscing elit pellentesque habitant morbi. Id interdum velit laoreet id donec ultrices. Fringilla phasellus faucibus scelerisque eleifend donec pretium. Est pellentesque elit ullamcorper dignissim. Mauris ultrices eros in cursus turpis massa tincidunt dui.
                  </div>
                </div>
              </div>< !-- # Faq item 

              <div class="accordion-item">
                <h3 class="accordion-header">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-content-3">
                    <span class="num">3.</span>
                    Dolor sit amet consectetur adipiscing elit pellentesque habitant morbi?
                  </button>
                </h3>
                <div id="faq-content-3" class="accordion-collapse collapse" data-bs-parent="#faqlist">
                  <div class="accordion-body">
                    Eleifend mi in nulla posuere sollicitudin aliquam ultrices sagittis orci. Faucibus pulvinar elementum integer enim. Sem nulla pharetra diam sit amet nisl suscipit. Rutrum tellus pellentesque eu tincidunt. Lectus urna duis convallis convallis tellus. Urna molestie at elementum eu facilisis sed odio morbi quis
                  </div>
                </div>
              </div>< !-- # Faq item-- >

              <div class="accordion-item">
                <h3 class="accordion-header">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-content-4">
                    <span class="num">4.</span>
                    Ac odio tempor orci dapibus. Aliquam eleifend mi in nulla?
                  </button>
                </h3>
                <div id="faq-content-4" class="accordion-collapse collapse" data-bs-parent="#faqlist">
                  <div class="accordion-body">
                    Dolor sit amet consectetur adipiscing elit pellentesque habitant morbi. Id interdum velit laoreet id donec ultrices. Fringilla phasellus faucibus scelerisque eleifend donec pretium. Est pellentesque elit ullamcorper dignissim. Mauris ultrices eros in cursus turpis massa tincidunt dui.
                  </div>
                </div>
              </div>< !-- # Faq item-- >

              <div class="accordion-item">
                <h3 class="accordion-header">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-content-5">
                    <span class="num">5.</span>
                    Tempus quam pellentesque nec nam aliquam sem et tortor consequat?
                  </button>
                </h3>
                <div id="faq-content-5" class="accordion-collapse collapse" data-bs-parent="#faqlist">
                  <div class="accordion-body">
                    Molestie a iaculis at erat pellentesque adipiscing commodo. Dignissim suspendisse in est ante in. Nunc vel risus commodo viverra maecenas accumsan. Sit amet nisl suscipit adipiscing bibendum est. Purus gravida quis blandit turpis cursus in
                  </div>
                </div>
              </div>< !-- # Faq item-->

            </div>

          </div>
        </div>

      </div>
    </section><!-- End Frequently Asked Questions Section -->

    <!-- ======= Contact Section ======= -->
    <section id="contact" class="contact">
      <div class="container" data-aos="fade-up">

        <div class="section-header">
          <h2>Contact</h2>
          <p>We are always available to listen to your needs. Talk to us</p>
        </div>

        <div class="row gx-lg-0 gy-4">

          <div class="col-lg-4">

            <div class="info-container d-flex flex-column align-items-center justify-content-center">
              <div class="info-item d-flex">
                <i class="bi bi-geo-alt flex-shrink-0"></i>
                <div>
                  <h4>Location:</h4>
                  <p><?= $name ?></p>
                </div>
              </div><!-- End Info Item -->

              <div class="info-item d-flex">
                <i class="bi bi-envelope flex-shrink-0"></i>
                <div>
                  <h4>Email:</h4>
                  <p><?= $email ?></p>
                </div>
              </div><!-- End Info Item -->

              <div class="info-item d-flex">
                <i class="bi bi-phone flex-shrink-0"></i>
                <div>
                  <h4>Call:</h4>
                  <p><?= $phone ?></p>
                </div>
              </div><!-- End Info Item -->

              <div class="info-item d-flex">
                <i class="bi bi-clock flex-shrink-0"></i>
                <div>
                  <h4>Open Hours:</h4>
                  <p>Mon-Sun: 8:00AM - 22PM</p>
                </div>
              </div><!-- End Info Item -->
            </div>

          </div>

          <div class="col-lg-8">
            <form action="forms/contact.php" method="post" role="form" class="php-email-form">
              <div class="row">
                <div class="col-md-6 form-group">
                  <input type="text" name="name" class="form-control" id="name" placeholder="Your Name" required>
                </div>
                <div class="col-md-6 form-group mt-3 mt-md-0">
                  <input type="email" class="form-control" name="email" id="email" placeholder="Your Email" required>
                </div>
              </div>
              <div class="form-group mt-3">
                <input type="text" class="form-control" name="subject" id="subject" placeholder="Subject" required>
              </div>
              <div class="form-group mt-3">
                <textarea class="form-control" name="message" rows="7" placeholder="Message" required></textarea>
              </div>
              <div class="my-3">
                <div class="loading">Loading</div>
                <div class="error-message"></div>
                <div class="sent-message">Your message has been sent. Thank you!</div>
              </div>
              <div class="text-center"><button type="submit">Send Message</button></div>
            </form>
          </div><!-- End Contact Form -->

        </div>

      </div>
    </section><!-- End Contact Section -->

  </main><!-- End #main -->

  <!-- ======= Footer ======= -->
  <footer id="footer" class="footer">

    <div class="container">
      <div class="row gy-4">
        <div class="col-lg-5 col-md-12 footer-info">
          <a href="index.html" class="logo d-flex align-items-center">
            <span>Edumanager</span>
          </a>
          <p>Take your school management to the next level. Improve your operational efficiency, reduce workload, and enhance the learning experience. Let's make education management smarter and more effective</p>
          <div class="social-links d-flex mt-4">
            <a href="#" class="twitter"><i class="bi bi-twitter"></i></a>
            <a href="#" class="facebook"><i class="bi bi-facebook"></i></a>
            <a href="#" class="instagram"><i class="bi bi-instagram"></i></a>
            <a href="#" class="linkedin"><i class="bi bi-linkedin"></i></a>
          </div>
        </div>

        <div class="col-lg-2 col-6 footer-links">
          <h4>Useful Links</h4>
          <ul>
            <li><a href="#">Home</a></li>
            <li><a href="#">About us</a></li>
            <li><a href="#">Services</a></li>
            <li><a href="#">Terms of service</a></li>
            <li><a href="#">Privacy policy</a></li>
          </ul>
        </div>

        <div class="col-lg-2 col-6 footer-links">
          <h4>Our Services</h4>
          <ul>
            <li><a href="#">School Management Solution</a></li>
            <li><a href="#">Online Exam</a></li>
            <li><a href="#">Result Analysis</a></li>
            <li><a href="#">Study Notes</a></li>
            <li><a href="#">Message Broadcast</a></li>
          </ul>
        </div>

        <div class="col-lg-3 col-md-12 footer-contact text-center text-md-start">
          <h4>Contact Us</h4>
          <p>
            <?= $address ?> <br><br>
            <strong>Phone:</strong> <?= $phone ?><br>
            <strong>Email:</strong> <?= $email ?><br>
          </p>

        </div>

      </div>
    </div>

    <div class="container mt-4">
      <div class="copyright">
        &copy; Copyright <strong><span><?= $name ?></span></strong>. All Rights Reserved
      </div>
      <!--
      <div class="credits">
        Designed by <a href="https://edumanager.ng/">Edumanager</a>
      </div>
      -->
    </div>

  </footer><!-- End Footer -->
  <!-- End Footer -->

  <a href="#" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <div id="preloader"></div>

  <!-- Vendor JS Files -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js" integrity="sha512-7Pi/otdlbbCR+LnW+F7PwFcSDJOuUJB3OxtEHbg4vSMvzvJjde4Po1v4BR9Gdc9aXNUNFVUY+SK51wWT8WF0Gg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/1.2.2/aos.js" integrity="sha512-oGZY9sm0R97S7njAI642O2Qn6YmWdc1UM9V1Ow4118BkOIt2i6oCJZ6YYIzmFpqOJNyyY6Z+09Oioj+HI2ZgEA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/glightbox/3.2.0/js/glightbox.min.js" integrity="sha512-S/H9RQ6govCzeA7F9D0m8NGfsGf0/HjJEiLEfWGaMCjFzavo+DkRbYtZLSO+X6cZsIKQ6JvV/7Y9YMaYnSGnAA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <script src="https://cdn.jsdelivr.net/npm/@srexi/purecounterjs/dist/purecounter_vanilla.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Swiper/8.4.7/swiper-bundle.min.js" integrity="sha512-h5Vv+n+z0eRnlJoUlWMZ4PLQv4JfaCVtgU9TtRjNYuNltS5QCqi4D4eZn4UkzZZuG2p4VBz3YIlsB7A2NVrbww==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <script src="https://unpkg.com/isotope-layout@3/dist/isotope.pkgd.min.js"></script>

  <!-- <script src="<?= $basePath ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script> -->
  <!-- <script src="<?= $basePath ?>assets/vendor/aos/aos.js"></script> -->
  <!-- <script src="<?= $basePath ?>assets/vendor/glightbox/js/glightbox.min.js"></script> -->
  <!-- <script src="<?= $basePath ?>assets/vendor/purecounter/purecounter_vanilla.js"></script> -->
  <!-- <script src="<?= $basePath ?>assets/vendor/swiper/swiper-bundle.min.js"></script> -->
  <!-- <script src="<?= $basePath ?>assets/vendor/isotope-layout/isotope.pkgd.min.js"></script> -->
  <!-- <script src="<?= $basePath ?>assets/vendor/php-email-form/validate.js"></script> -->

  <!-- Template Main JS File -->
  <script src="<?= $basePath ?>assets/js/main.js"></script>

</body>

</html>