(function () {
  "use strict";

  /**
   * Apply .scrolled class to the body as the page is scrolled down
   */
  function toggleScrolled() {
    const selectBody = document.querySelector('body');
    const selectHeader = document.querySelector('#header');
    if (!selectHeader || (!selectHeader.classList.contains('scroll-up-sticky') && !selectHeader.classList.contains('sticky-top') && !selectHeader.classList.contains('fixed-top'))) {
        return;
    }
    window.scrollY > 100 ? selectBody.classList.add('scrolled') : selectBody.classList.remove('scrolled');
  }
  document.addEventListener('scroll', toggleScrolled);
  window.addEventListener('load', toggleScrolled);

  /**
   * Mobile nav toggle
   */
  const mobileNavShow = document.querySelector('.mobile-nav-show');
  const mobileNavHide = document.querySelector('.mobile-nav-hide');
  const navmenu = document.querySelector('#navmenu');
  const body = document.body;

  function mobileNavToggle(event) {
    if (event) event.preventDefault();
    body.classList.toggle('mobile-nav-active');
    mobileNavShow.classList.toggle('d-none');
    mobileNavHide.classList.toggle('d-none');

    if (navmenu) {
        if (body.classList.contains('mobile-nav-active')) {
            navmenu.style.display = 'block';
            void navmenu.offsetWidth; // Force reflow
            navmenu.style.opacity = '1';
        } else {
            navmenu.style.opacity = '0';
            setTimeout(() => {
                if (!body.classList.contains('mobile-nav-active')) {
                    navmenu.style.display = 'none';
                }
            }, 300);
        }
    }
  }

  if (mobileNavShow && mobileNavHide) {
    mobileNavShow.addEventListener('click', mobileNavToggle);
    mobileNavHide.addEventListener('click', mobileNavToggle);

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
   * Fixed Buttons Manager (Scroll top, Sticky CTA, Sticky WhatsApp)
   */
  const scrollTop = document.querySelector('.scroll-top'); // ID: #scroll-top en HTML
  const stickyCtaButton = document.querySelector('#stickyCtaButton'); // ID: #stickyCtaButton en HTML
  const stickyWhatsAppButton = document.querySelector('#stickyWhatsAppButton'); // ID: #stickyWhatsAppButton en HTML

  function toggleFixedButtons() {
    let scrollYPosition = window.scrollY;

    if (scrollTop) {
      scrollYPosition > 100 ? scrollTop.classList.add('active') : scrollTop.classList.remove('active');
    }
    if (stickyCtaButton) {
      scrollYPosition > 200 ? stickyCtaButton.classList.add('visible') : stickyCtaButton.classList.remove('visible');
    }
    if (stickyWhatsAppButton) {
      scrollYPosition > 200 ? stickyWhatsAppButton.classList.add('visible') : stickyWhatsAppButton.classList.remove('visible'); // Mismo umbral que CTA, o diferente si prefieres
    }
  }

  if (scrollTop) {
      scrollTop.addEventListener('click', (e) => {
        e.preventDefault();
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
  }
  // Llama a la función combinada en load y scroll
  window.addEventListener('load', toggleFixedButtons);
  document.addEventListener('scroll', toggleFixedButtons);
  // Fin Fixed Buttons Manager

  /**
   * Animation on scroll function and init
   */
  function aosInit() {
    if (typeof AOS !== 'undefined') {
        AOS.init({
          duration: 800,
          easing: 'ease-out-cubic',
          once: true,
          mirror: false
        });
    } else {
        console.warn("AOS library not loaded.");
    }
  }
  window.addEventListener('load', aosInit);

  /**
   * Init GLightbox
   */
  if (typeof GLightbox !== 'undefined') {
      const lightbox = GLightbox({
        selector: '.glightbox'
      });
  } else {
      console.warn("GLightbox library not loaded.");
  }

  /**
   * Init PureCounter
   */
  if (typeof PureCounter !== 'undefined') {
    new PureCounter();
  } else {
    console.warn("PureCounter library not loaded.");
  }

  /**
   * Init Swipers (Generic)
   */
  function initSwipers() {
    if (typeof Swiper === 'undefined') {
        console.warn("Swiper library not loaded. Cannot initialize swipers.");
        return;
    }
    document.querySelectorAll('.init-swiper').forEach(function (swiperElement) {
      let configEl = swiperElement.querySelector('.swiper-config');
      if (configEl) {
        try {
            let config = JSON.parse(configEl.innerHTML.trim());
            new Swiper(swiperElement, config);
        } catch (e) {
            console.error("Error parsing Swiper config:", e, configEl.innerHTML);
        }
      } else {
          console.warn("Swiper config element not found for a .init-swiper element.");
      }
    });
  }
  window.addEventListener('load', initSwipers);

  /**
   * Init Isotope Layout
   */
  if (typeof Isotope !== 'undefined' && typeof imagesLoaded !== 'undefined') {
      document.querySelectorAll('.isotope-layout').forEach(function (isotopeItem) {
        let layout = isotopeItem.getAttribute('data-layout') ?? 'masonry';
        let filter = isotopeItem.getAttribute('data-default-filter') ?? '*';
        let sort = isotopeItem.getAttribute('data-sort') ?? 'original-order';
        let initIsotope;
        let isotopeContainer = isotopeItem.querySelector('.isotope-container');
        if (!isotopeContainer) { console.warn("Isotope container not found."); return; }
        imagesLoaded(isotopeContainer, function () {
          try {
            initIsotope = new Isotope(isotopeContainer, {
              itemSelector: '.isotope-item', layoutMode: layout, filter: filter, sortBy: sort
            });
          } catch (e) { console.error("Error initializing Isotope:", e); }
        });
        isotopeItem.querySelectorAll('.isotope-filters li').forEach(function (filters) {
          filters.addEventListener('click', function () {
            let activeFilter = isotopeItem.querySelector('.isotope-filters .filter-active');
            if (activeFilter) activeFilter.classList.remove('filter-active');
            this.classList.add('filter-active');
            if (initIsotope) {
                initIsotope.arrange({ filter: this.getAttribute('data-filter') });
                if (typeof aosInit === 'function') { aosInit(); }
            }
          }, false);
        });
      });
  } else {
      console.warn("Isotope or imagesLoaded library not loaded.");
  }

  /**
   * Correct scrolling position upon page load for URLs containing hash links.
   */
  window.addEventListener('load', function (e) {
    if (window.location.hash) {
      let section = document.querySelector(window.location.hash);
      if (section) {
        setTimeout(() => {
          let header = document.querySelector('#header.fixed-top'); // Solo si es fixed-top
          let headerHeight = header ? header.offsetHeight : 0;
          // scrollMarginTop debería estar definido en el CSS de las secciones.
          // Si no, el cálculo con headerHeight es un fallback.
          let scrollMarginTop = parseInt(getComputedStyle(section).scrollMarginTop) || 0;
          let finalOffset = Math.max(headerHeight, scrollMarginTop);


          window.scrollTo({
            top: section.offsetTop - finalOffset,
            behavior: 'smooth'
          });
        }, 100);
      }
    }
  });

  /**
   * Navmenu Scrollspy
   */
  let navmenulinks = document.querySelectorAll('.navmenu a');
  function navmenuScrollspy() {
      let header = document.querySelector('#header.fixed-top');
      let headerHeight = header ? header.offsetHeight : 0;
      // El offset ayuda a que el enlace se active un poco antes de que la sección esté exactamente en el borde superior.
      // Considera la altura del header fijo + un pequeño margen adicional.
      let position = window.scrollY + headerHeight + 50;

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