document.addEventListener("DOMContentLoaded", () => {

  /* =========================
     FVスライダー
  ========================= */

  const slides = document.querySelectorAll(".fv-slider img");

  let current = 0;

  slides.forEach((slide, index) => {
    slide.style.opacity = index === 0 ? "1" : "0";
    slide.style.transition = "opacity 1.2s ease";
  });

  function changeSlide() {

    slides[current].style.opacity = "0";

    current++;

    if (current >= slides.length) {
      current = 0;
    }

    slides[current].style.opacity = "1";
  }

  setInterval(changeSlide, 5000);



  /* =========================
     ハンバーガーメニュー
  ========================= */

  const hamburger = document.querySelector(".hamburger");
  const nav = document.querySelector(".global-nav");

  hamburger.addEventListener("click", () => {

    hamburger.classList.toggle("active");
    nav.classList.toggle("active");

  });


  const navLinks = document.querySelectorAll(".global-nav a");

  navLinks.forEach(link => {

    link.addEventListener("click", () => {

      hamburger.classList.remove("active");
      nav.classList.remove("active");

    });

  });


  /* =========================
     フェードイン
  ========================= */

  const fadeItems = document.querySelectorAll(".fadein");

  const observer = new IntersectionObserver((entries) => {

    entries.forEach(entry => {

      if (entry.isIntersecting) {

        entry.target.classList.add("show");

      }

    });

  }, {
    threshold: 0.05
  });

  fadeItems.forEach(item => {

    observer.observe(item);

  });


  document.querySelectorAll('.map-point').forEach(point => {

    point.addEventListener('click', function (e) {

      e.stopPropagation();

      document.querySelectorAll('.map-point').forEach(item => {

        if (item !== this) {
          item.classList.remove('active');
        }

      });

      this.classList.toggle('active');

    });

  });

  document.addEventListener('click', () => {

    document.querySelectorAll('.map-point').forEach(point => {
      point.classList.remove('active');
    });

  });
});
