(function () {
  "use strict";

  /**
   * Apply .scrolled class to the body as the page is scrolled down
   */
  function toggleScrolled() {
    const selectBody = document.querySelector('body');
    const selectHeader = document.querySelector('#header');
    if (!selectHeader.classList.contains('scroll-up-sticky') && !selectHeader.classList.contains('sticky-top') && !selectHeader.classList.contains('fixed-top')) return;
    window.scrollY > 100 ? selectBody.classList.add('scrolled') : selectBody.classList.remove('scrolled');
  }
  document.addEventListener('scroll', toggleScrolled);
  window.addEventListener('load', toggleScrolled);

  /**
   * Mobile nav toggle
   */
  const mobileNavToggleBtn = document.querySelector('.mobile-nav-toggle');
  const navmenu = document.querySelector('#navmenu'); // Get the navmenu itself
  const body = document.body; // Cache body element

  function mobileNavToggle() {
    body.classList.toggle('mobile-nav-active');
    const show = body.classList.contains('mobile-nav-active');

    // Toggle icons visibility based on state
    const listIcon = document.querySelector('.mobile-nav-toggle.bi-list');
    const xIcon = document.querySelector('.mobile-nav-toggle.bi-x');
    if (listIcon) listIcon.style.display = show ? 'none' : 'block';
    if (xIcon) xIcon.style.display = show ? 'block' : 'none';

    // Toggle navmenu visibility for smoother transitions
    if (navmenu) {
       navmenu.style.display = show ? 'block' : 'none'; // Use display instead of visibility
       // Force reflow for transition
       // void navmenu.offsetWidth;
       navmenu.style.opacity = show ? '1' : '0';
    }
  }

  if (mobileNavToggleBtn) {
      // Add listener to the primary toggle button (which includes both icons)
      const mainToggleContainer = document.querySelector('.mobile-nav-toggle'); // Assuming the container holds both
       if (mainToggleContainer) {
           mainToggleContainer.addEventListener('click', (e) => {
               // Prevent clicks on hidden icon from triggering toggle
               if ((e.target.classList.contains('bi-list') && !body.classList.contains('mobile-nav-active')) ||
                   (e.target.classList.contains('bi-x') && body.classList.contains('mobile-nav-active'))) {
                   mobileNavToggle();
               }
           });
       } else {
           // Fallback if icons are separate (less ideal)
           document.querySelectorAll('.mobile-nav-toggle').forEach(toggle => {
               toggle.addEventListener('click', mobileNavToggle);
           });
       }

      // Add click listener to the overlay (navmenu when active) to close
      if (navmenu) {
          navmenu.addEventListener('click', (e) => {
              // Close only if clicking the background overlay itself, not a link inside ul
              if (e.target === navmenu && body.classList.contains('mobile-nav-active')) {
                  mobileNavToggle();
              }
          });
      }
  }

  /**
   * Hide mobile nav on same-page/hash links
   */
  document.querySelectorAll('#navmenu a').forEach(navmenuLink => {
    navmenuLink.addEventListener('click', (e) => { // Add event arg
      // Check if it's a hash link on the same page
      if (navmenuLink.pathname === window.location.pathname && navmenuLink.hash) {
          if (body.classList.contains('mobile-nav-active')) {
            // Prevent default jump, allow smooth scroll to handle it
            // e.preventDefault(); // Optional: Remove if smooth scroll works fine
            mobileNavToggle();
          }
      }
    });
  });

  /**
   * Toggle mobile nav dropdowns
   */
  document.querySelectorAll('.navmenu .toggle-dropdown').forEach(dropdownToggle => {
    dropdownToggle.addEventListener('click', function (e) {
      e.preventDefault();
      this.parentNode.classList.toggle('active');
      this.parentNode.nextElementSibling.classList.toggle('dropdown-active');
      e.stopImmediatePropagation(); // Prevent closing nav when clicking dropdown
    });
  });


  /**
   * Preloader
   */
  const preloader = document.querySelector('#preloader');
  if (preloader) {
    window.addEventListener('load', () => {
      // Optional: add a short delay before removing for smoother fade
      setTimeout(() => {
        preloader.style.opacity = '0'; // Fade out
        setTimeout(() => preloader.remove(), 500); // Remove after fade
      }, 300); // 300ms delay before starting fade
    });
  }

  /**
   * Scroll top button
   */
  const scrollTop = document.querySelector('.scroll-top');
  function toggleScrollTop() {
    if (scrollTop) {
      window.scrollY > 100 ? scrollTop.classList.add('active') : scrollTop.classList.remove('active');
    }
  }
  if (scrollTop) {
      scrollTop.addEventListener('click', (e) => {
        e.preventDefault();
        window.scrollTo({
          top: 0,
          behavior: 'smooth'
        });
      });
  }
  window.addEventListener('load', toggleScrollTop);
  document.addEventListener('scroll', toggleScrollTop);

  /**
   * Animation on scroll function and init
   */
  function aosInit() {
    AOS.init({
      duration: 800, // Slightly longer duration
      easing: 'ease-out-cubic', // Smoother easing
      once: true, // Animation happens only once
      mirror: false // Animation doesn't reverse on scroll up
    });
    // Optional: Refresh AOS after dynamic content loads if needed
    // setTimeout(() => { AOS.refresh(); }, 500);
  }
  window.addEventListener('load', aosInit);

  /**
   * Init GLightbox
   */
  const glightbox = GLightbox({
    selector: '.glightbox'
  });

  /**
   * Init PureCounter
   */
  new PureCounter();

  /**
   * Init Swipersliders (Generic - if you add more later)
   * Specific Swiper for clients is now in HTML inline script
   */
  function initSwipers() {
    document.querySelectorAll('.init-swiper').forEach(function (swiperElement) {
      let config = JSON.parse(swiperElement.querySelector('.swiper-config').innerHTML.trim());
      new Swiper(swiperElement, config);
    });
  }
  window.addEventListener('load', initSwipers);


  /**
   * Init Isotope Layout (Keep if you plan to use filtering)
   */
  document.querySelectorAll('.isotope-layout').forEach(function (isotopeItem) {
    let layout = isotopeItem.getAttribute('data-layout') ?? 'masonry';
    let filter = isotopeItem.getAttribute('data-default-filter') ?? '*';
    let sort = isotopeItem.getAttribute('data-sort') ?? 'original-order';

    let initIsotope;
    imagesLoaded(isotopeItem.querySelector('.isotope-container'), function () {
      initIsotope = new Isotope(isotopeItem.querySelector('.isotope-container'), {
        itemSelector: '.isotope-item',
        layoutMode: layout,
        filter: filter,
        sortBy: sort
      });
    });

    isotopeItem.querySelectorAll('.isotope-filters li').forEach(function (filters) {
      filters.addEventListener('click', function () {
        isotopeItem.querySelector('.isotope-filters .filter-active').classList.remove('filter-active');
        this.classList.add('filter-active');
        initIsotope.arrange({
          filter: this.getAttribute('data-filter')
        });
        if (typeof aosInit === 'function') {
          aosInit(); // Re-trigger AOS animations if needed after filtering
        }
      }, false);
    });

  });

  /**
   * Correct scrolling position upon page load for URLs containing hash links.
   */
  window.addEventListener('load', function (e) {
    if (window.location.hash) {
      let section = document.querySelector(window.location.hash);
      if (section) {
        setTimeout(() => {
          let scrollMarginTop = getComputedStyle(section).scrollMarginTop;
          window.scrollTo({
            top: section.offsetTop - parseInt(scrollMarginTop),
            behavior: 'smooth'
          });
        }, 100); // Delay slightly to ensure layout stability
      }
    }
  });

  /**
   * Navmenu Scrollspy
   */
  let navmenulinks = document.querySelectorAll('.navmenu a');
  function navmenuScrollspy() {
      let position = window.scrollY + 200; // Offset for activation point
      navmenulinks.forEach(navmenulink => {
        if (!navmenulink.hash) return;
        let section = document.querySelector(navmenulink.hash);
        if (!section) return;
        if (position >= section.offsetTop && position <= (section.offsetTop + section.offsetHeight)) {
          document.querySelectorAll('.navmenu a.active').forEach(link => link.classList.remove('active'));
          navmenulink.classList.add('active');
        } else {
          navmenulink.classList.remove('active');
        }
      })
  }
  window.addEventListener('load', navmenuScrollspy);
  document.addEventListener('scroll', navmenuScrollspy);

})();