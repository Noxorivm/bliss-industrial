/**
 * main.js – Bliss Industrial
 * Última revisión: 05-06-2025
 */
(function () {
  "use strict";

  /*--------------------------------------------------------------
  # 1. Cambia el header al hacer scroll
  --------------------------------------------------------------*/
  function toggleScrolled() {
    const body   = document.body;
    const header = document.querySelector('#header');
    if (!header || (!header.classList.contains('scroll-up-sticky') &&
                    !header.classList.contains('sticky-top') &&
                    !header.classList.contains('fixed-top'))) return;

    window.scrollY > 100 ? body.classList.add('scrolled')
                         : body.classList.remove('scrolled');
  }
  document.addEventListener('scroll', toggleScrolled);
  window.addEventListener('load',    toggleScrolled);

  /*--------------------------------------------------------------
  # 2. Mobile Nav – abrir / cerrar
  --------------------------------------------------------------*/
  const body        = document.body;
  const navMenu     = document.querySelector('#navmenu');
  const toggleBtns  = document.querySelectorAll('.mobile-nav-toggle');     // ☰ y ✕
  const btnShow     = document.querySelector('.mobile-nav-show');         // ☰
  const btnHide     = document.querySelector('.mobile-nav-hide');         // ✕

  function mobileNavToggle (e) {
    if (e) e.preventDefault();

    body.classList.toggle('mobile-nav-active');
    btnShow.classList.toggle('d-none');
    btnHide.classList.toggle('d-none');

    /* Mostrar / ocultar UL con transición de opacidad */
    if (navMenu) {
      if (body.classList.contains('mobile-nav-active')) {
        navMenu.style.display = 'block';
        void navMenu.offsetWidth;            // reflow
        navMenu.style.opacity = '1';
      } else {
        navMenu.style.opacity = '0';
        setTimeout(() => {
          if (!body.classList.contains('mobile-nav-active')) {
            navMenu.style.display = 'none';
          }
        }, 300);                             // igual que transición CSS
      }
    }
  }

  toggleBtns.forEach(btn => btn.addEventListener('click', mobileNavToggle));

  /* Cerrar menú al hacer clic en cualquier enlace (ancla o normal) */
  document.querySelectorAll('#navmenu a').forEach(link => {
    link.addEventListener('click', () => {
      if (body.classList.contains('mobile-nav-active')) mobileNavToggle();
    });
  });

  /*--------------------------------------------------------------
  # 3. Dropdowns en la versión móvil
  --------------------------------------------------------------*/
  document.querySelectorAll('.navmenu .toggle-dropdown').forEach(drop => {
    drop.addEventListener('click', e => {
      e.preventDefault();
      drop.parentNode.classList.toggle('active');
      drop.parentNode.nextElementSibling.classList.toggle('dropdown-active');
      e.stopImmediatePropagation();
    });
  });

  /*--------------------------------------------------------------
  # 4. Botones fijos (scroll-top, CTA, WhatsApp)
  --------------------------------------------------------------*/
  const scrollTopBtn      = document.querySelector('.scroll-top');
  const stickyCtaBtn      = document.querySelector('#stickyCtaButton');
  const stickyWhatsBtn    = document.querySelector('#stickyWhatsAppButton');

  function toggleFixedButtons() {
    const y = window.scrollY;
    if (scrollTopBtn)   (y > 100) ? scrollTopBtn.classList.add('active')
                                  : scrollTopBtn.classList.remove('active');
    if (stickyCtaBtn)   (y > 200) ? stickyCtaBtn.classList.add('visible')
                                  : stickyCtaBtn.classList.remove('visible');
    if (stickyWhatsBtn) (y > 200) ? stickyWhatsBtn.classList.add('visible')
                                  : stickyWhatsBtn.classList.remove('visible');
  }
  window.addEventListener('load',  toggleFixedButtons);
  document.addEventListener('scroll', toggleFixedButtons);

  if (scrollTopBtn) {
    scrollTopBtn.addEventListener('click', e => {
      e.preventDefault();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  /*--------------------------------------------------------------
  # 5. AOS init
  --------------------------------------------------------------*/
  function aosInit() {
    if (typeof AOS !== 'undefined') {
      AOS.init({
        duration: 800,
        easing: 'ease-out-cubic',
        once: true,
        mirror: false
      });
    }
  }
  window.addEventListener('load', aosInit);

  /*--------------------------------------------------------------
  # 6. GLightbox
  --------------------------------------------------------------*/
  if (typeof GLightbox !== 'undefined') GLightbox({ selector: '.glightbox' });

  /*--------------------------------------------------------------
  # 7. PureCounter
  --------------------------------------------------------------*/
  if (typeof PureCounter !== 'undefined') new PureCounter();

  /*--------------------------------------------------------------
  # 8. Swiper (genérico, lee config en .swiper-config)
  --------------------------------------------------------------*/
  function initSwipers() {
    if (typeof Swiper === 'undefined') return;
    document.querySelectorAll('.init-swiper').forEach(swiperEl => {
      const cfgEl = swiperEl.querySelector('.swiper-config');
      if (!cfgEl) return;
      try { new Swiper(swiperEl, JSON.parse(cfgEl.textContent.trim())); }
      catch (e) { console.error('Swiper config error', e); }
    });
  }
  window.addEventListener('load', initSwipers);

  /*--------------------------------------------------------------
  # 9. Isotope + imagesLoaded
  --------------------------------------------------------------*/
  if (typeof Isotope !== 'undefined' && typeof imagesLoaded !== 'undefined') {
    document.querySelectorAll('.isotope-layout').forEach(wrap => {
      const container = wrap.querySelector('.isotope-container');
      if (!container) return;
      imagesLoaded(container, () => {
        const iso = new Isotope(container, {
          itemSelector: '.isotope-item',
          layoutMode:   wrap.dataset.layout || 'masonry',
          filter:       wrap.dataset.defaultFilter || '*',
          sortBy:       wrap.dataset.sort || 'original-order'
        });
        wrap.querySelectorAll('.isotope-filters li').forEach(btn => {
          btn.addEventListener('click', () => {
            wrap.querySelector('.filter-active')?.classList.remove('filter-active');
            btn.classList.add('filter-active');
            iso.arrange({ filter: btn.dataset.filter });
            aosInit();                       // re-animar
          });
        });
      });
    });
  }

  /*--------------------------------------------------------------
  # 10. Scroll a secciones con offset (carga con hash)
  --------------------------------------------------------------*/
  window.addEventListener('load', () => {
    if (!window.location.hash) return;
    const target = document.querySelector(window.location.hash);
    if (!target) return;
    setTimeout(() => {
      const header  = document.querySelector('#header.fixed-top');
      const offset  = header ? header.offsetHeight : 0;
      const extra   = parseInt(getComputedStyle(target).scrollMarginTop) || 0;
      window.scrollTo({ top: target.offsetTop - Math.max(offset, extra), behavior: 'smooth' });
    }, 100);
  });

  /*--------------------------------------------------------------
  # 11. Scroll-Spy para menú
  --------------------------------------------------------------*/
  const navLinks = document.querySelectorAll('.navmenu a');
  function navScrollSpy() {
    const header    = document.querySelector('#header.fixed-top');
    const threshold = (header ? header.offsetHeight : 0) + 50;
    const pos       = window.scrollY + threshold;

    navLinks.forEach(link => {
      if (!link.hash) return;
      const section = document.querySelector(link.hash);
      if (!section) return;
      (pos >= section.offsetTop && pos <= section.offsetTop + section.offsetHeight)
        ? link.classList.add('active')
        : link.classList.remove('active');
    });
  }
  window.addEventListener('load', navScrollSpy);
  document.addEventListener('scroll', navScrollSpy);

  /*--------------------------------------------------------------
  # 12. Preloader
  --------------------------------------------------------------*/
  const preloader = document.querySelector('#preloader');
  if (preloader) {
    window.addEventListener('load', () => {
      setTimeout(() => {
        preloader.style.opacity = '0';
        setTimeout(() => preloader.remove(), 500);
      }, 300);
    });
  }
})();
