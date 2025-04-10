(function () {
  "use strict";

  // Añade clase "scrolled" al hacer scroll
  function toggleScrolled() {
    const body = document.querySelector('body');
    const header = document.querySelector('#header');
    if (!header.classList.contains('scroll-up-sticky') && !header.classList.contains('sticky-top') && !header.classList.contains('fixed-top')) return;
    window.scrollY > 100 ? body.classList.add('scrolled') : body.classList.remove('scrolled');
  }

  document.addEventListener('scroll', toggleScrolled);
  window.addEventListener('load', toggleScrolled);

  // Menú móvil
  const mobileNavToggleBtn = document.querySelector('.mobile-nav-toggle');
  function mobileNavToggle() {
    document.body.classList.toggle('mobile-nav-active');
    mobileNavToggleBtn?.classList.toggle('bi-list');
    mobileNavToggleBtn?.classList.toggle('bi-x');
  }
  mobileNavToggleBtn?.addEventListener('click', mobileNavToggle);

  // Ocultar nav móvil al hacer clic en links del mismo hash
  document.querySelectorAll('#navmenu a').forEach(link => {
    link.addEventListener('click', () => {
      if (document.body.classList.contains('mobile-nav-active')) {
        mobileNavToggle();
      }
    });
  });

  // Dropdowns móviles
  document.querySelectorAll('.navmenu .toggle-dropdown').forEach(dropdown => {
    dropdown.addEventListener('click', function (e) {
      e.preventDefault();
      this.parentNode.classList.toggle('active');
      this.parentNode.nextElementSibling.classList.toggle('dropdown-active');
      e.stopImmediatePropagation();
    });
  });

  // Preloader
  const preloader = document.querySelector('#preloader');
  window.addEventListener('load', () => {
    preloader?.remove();
  });

  // Scroll top button
  const scrollTop = document.querySelector('.scroll-top');
  function toggleScrollTop() {
    if (scrollTop) {
      window.scrollY > 100 ? scrollTop.classList.add('active') : scrollTop.classList.remove('active');
    }
  }
  scrollTop?.addEventListener('click', (e) => {
    e.preventDefault();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
  window.addEventListener('load', toggleScrollTop);
  document.addEventListener('scroll', toggleScrollTop);

  // AOS (animaciones suaves)
  function aosInit() {
    AOS.init({
      duration: 800,
      easing: 'ease-in-out',
      once: true,
      mirror: false
    });
  }
  window.addEventListener('load', aosInit);

  // GLightbox
  GLightbox({ selector: '.glightbox' });

  // Contador
  new PureCounter();

  // Swipers
  function initSwiper() {
    document.querySelectorAll(".init-swiper").forEach(swiperEl => {
      const config = JSON.parse(swiperEl.querySelector(".swiper-config").innerHTML.trim());
      new Swiper(swiperEl, config);
    });
  }
  window.addEventListener("load", initSwiper);

  // Isotope
  document.querySelectorAll('.isotope-layout').forEach(layout => {
    const layoutMode = layout.dataset.layout || 'masonry';
    const defaultFilter = layout.dataset.defaultFilter || '*';
    const sort = layout.dataset.sort || 'original-order';

    let iso;
    imagesLoaded(layout.querySelector('.isotope-container'), () => {
      iso = new Isotope(layout.querySelector('.isotope-container'), {
        itemSelector: '.isotope-item',
        layoutMode: layoutMode,
        filter: defaultFilter,
        sortBy: sort
      });
    });

    layout.querySelectorAll('.isotope-filters li').forEach(filter => {
      filter.addEventListener('click', function () {
        layout.querySelector('.filter-active').classList.remove('filter-active');
        this.classList.add('filter-active');
        iso.arrange({ filter: this.dataset.filter });
        if (typeof aosInit === 'function') aosInit();
      });
    });
  });

  // Scrollspy
  function navmenuScrollspy() {
    const links = document.querySelectorAll('.navmenu a');
    const position = window.scrollY + 200;

    links.forEach(link => {
      if (!link.hash) return;
      const section = document.querySelector(link.hash);
      if (!section) return;

      if (position >= section.offsetTop && position <= (section.offsetTop + section.offsetHeight)) {
        document.querySelectorAll('.navmenu a.active').forEach(i => i.classList.remove('active'));
        link.classList.add('active');
      }
    });
  }
  window.addEventListener('load', navmenuScrollspy);
  document.addEventListener('scroll', navmenuScrollspy);

  // Scroll suave al cargar con hash
  window.addEventListener('load', () => {
    if (window.location.hash && document.querySelector(window.location.hash)) {
      setTimeout(() => {
        const el = document.querySelector(window.location.hash);
        const margin = parseInt(getComputedStyle(el).scrollMarginTop);
        window.scrollTo({ top: el.offsetTop - margin, behavior: 'smooth' });
      }, 100);
    }
  });

  // Animación personalizada para botón de evento (BLISS Executive)
  const btnEvento = document.querySelector('#evento-btn');
  const emailBox = document.querySelector('#evento-email-box');

  if (btnEvento && emailBox) {
    btnEvento.addEventListener('click', () => {
      emailBox.classList.toggle('show');
      emailBox.classList.contains('show')
        ? emailBox.classList.add('fade-in')
        : emailBox.classList.remove('fade-in');
    });
  }

})();
