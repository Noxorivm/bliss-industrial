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
  const mobileNavToggleBtn = document.querySelector('.mobile-nav-toggle'); // Busca el contenedor de los iconos
  const navmenu = document.querySelector('#navmenu');
  const body = document.body;

  function mobileNavToggle() {
    body.classList.toggle('mobile-nav-active');
    const show = body.classList.contains('mobile-nav-active');

    const listIcon = document.querySelector('.mobile-nav-toggle.bi-list');
    const xIcon = document.querySelector('.mobile-nav-toggle.bi-x');
    if (listIcon) listIcon.style.display = show ? 'none' : 'block'; // Muestra/oculta basado en el estado
    if (xIcon) xIcon.style.display = show ? 'block' : 'none';   // Muestra/oculta basado en el estado


    if (navmenu) {
       navmenu.style.display = show ? 'block' : 'none';
       navmenu.style.opacity = show ? '1' : '0';
    }
  }

  if (mobileNavToggleBtn) {
      // Asumimos que .mobile-nav-toggle es un solo elemento que cambia su icono internamente
      // o que los iconos .bi-list y .bi-x están dentro de un contenedor .mobile-nav-toggle
      // El código HTML actual tiene dos <i> separados, así que necesitamos un listener en ambos o en un contenedor
      document.querySelectorAll('.mobile-nav-toggle').forEach(toggle => {
           toggle.addEventListener('click', (e) => {
               // Solo activa si se hace clic en el icono actualmente visible
               if (e.target.style.display !== 'none') {
                   mobileNavToggle();
               }
           });
       });


      if (navmenu) {
          navmenu.addEventListener('click', (e) => {
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
    navmenuLink.addEventListener('click', (e) => {
      if (navmenuLink.pathname === window.location.pathname && navmenuLink.hash) {
          if (body.classList.contains('mobile-nav-active')) {
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
      e.stopImmediatePropagation();
    });
  });


  /**
   * Preloader
   */
  const preloader = document.querySelector('#preloader');
  if (preloader) {
    window.addEventListener('load', () => {
      setTimeout(() => {
        preloader.style.opacity = '0';
        setTimeout(() => preloader.remove(), 500);
      }, 300);
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
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
  }
  window.addEventListener('load', toggleScrollTop);
  document.addEventListener('scroll', toggleScrollTop);

  /**
   * Animation on scroll function and init
   */
  function aosInit() {
    AOS.init({
      duration: 800,
      easing: 'ease-out-cubic',
      once: true,
      mirror: false
    });
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
   * Init Swipersliders (Generic)
   */
  function initSwipers() {
    document.querySelectorAll('.init-swiper').forEach(function (swiperElement) {
      let config = JSON.parse(swiperElement.querySelector('.swiper-config').innerHTML.trim());
      new Swiper(swiperElement, config);
    });
  }
  window.addEventListener('load', initSwipers);


  /**
   * Init Isotope Layout
   */
  document.querySelectorAll('.isotope-layout').forEach(function (isotopeItem) {
    let layout = isotopeItem.getAttribute('data-layout') ?? 'masonry';
    let filter = isotopeItem.getAttribute('data-default-filter') ?? '*';
    let sort = isotopeItem.getAttribute('data-sort') ?? 'original-order';
    let initIsotope;
    imagesLoaded(isotopeItem.querySelector('.isotope-container'), function () {
      initIsotope = new Isotope(isotopeItem.querySelector('.isotope-container'), {
        itemSelector: '.isotope-item', layoutMode: layout, filter: filter, sortBy: sort
      });
    });
    isotopeItem.querySelectorAll('.isotope-filters li').forEach(function (filters) {
      filters.addEventListener('click', function () {
        isotopeItem.querySelector('.isotope-filters .filter-active').classList.remove('filter-active');
        this.classList.add('filter-active');
        initIsotope.arrange({ filter: this.getAttribute('data-filter') });
        if (typeof aosInit === 'function') { aosInit(); }
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
          window.scrollTo({ top: section.offsetTop - parseInt(scrollMarginTop), behavior: 'smooth' });
        }, 100);
      }
    }
  });

  /**
   * Navmenu Scrollspy
   */
  let navmenulinks = document.querySelectorAll('.navmenu a');
  function navmenuScrollspy() {
      let position = window.scrollY + 200;
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