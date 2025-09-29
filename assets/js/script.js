'use strict';



/**
 * add event on multiple elements
 */

const addEventOnElements = function (elements, eventType, callback) {
  for (let i = 0, len = elements.length; i < len; i++) {
    elements[i].addEventListener(eventType, callback);
  }
}



/**
 * MOBILE NAVBAR
 * navbar will show after clicking menu button
 */

const navbar = document.querySelector("[data-navbar]");
const navToggler = document.querySelector("[data-nav-toggler]");
const navLinks = document.querySelectorAll("[data-nav-link]");

const toggleNav = function () {
  navbar.classList.toggle("active");
  this.classList.toggle("active");
}

navToggler.addEventListener("click", toggleNav);

const navClose = () => {
  navbar.classList.remove("active");
  navToggler.classList.remove("active");
}

addEventOnElements(navLinks, "click", navClose);



/**
 * HEADER and BACK TOP BTN
 * header and back top btn will be active after scrolled down to 100px of screen
 */

const header = document.querySelector("[data-header]");
const backTopBtn = document.querySelector("[data-back-top-btn]");

const activeEl = function () {
  if (window.scrollY > 100) {
    header.classList.add("active");
    backTopBtn.classList.add("active");
  } else {
    header.classList.remove("active");
    backTopBtn.classList.remove("active");
  }
}

window.addEventListener("scroll", activeEl);



/**
 * Button hover ripple effect
 */

const buttons = document.querySelectorAll("[data-btn]");

const buttonHoverRipple = function (event) {
  this.style.setProperty("--top", `${event.offsetY}px`);
  this.style.setProperty("--left", `${event.offsetX}px`);
}

addEventOnElements(buttons, "mousemove", buttonHoverRipple);



/**
 * Scroll reveal
 */

const revealElements = document.querySelectorAll("[data-reveal]");

const revealElementOnScroll = function () {
  for (let i = 0, len = revealElements.length; i < len; i++) {
    const isElementInsideWindow = revealElements[i].getBoundingClientRect().top < window.innerHeight / 1.1;

    if (isElementInsideWindow) {
      revealElements[i].classList.add("revealed");
    }
  }
}

window.addEventListener("scroll", revealElementOnScroll);

window.addEventListener("load", revealElementOnScroll);



/**
 * Custom cursor
 */

const cursor = document.querySelector("[data-cursor]");
const hoverElements = [...document.querySelectorAll("a"), ...document.querySelectorAll("button")];

const cursorMove = function (event) {
  cursor.style.top = `${event.clientY}px`;
  cursor.style.left = `${event.clientX}px`;
}

window.addEventListener("mousemove", cursorMove);

addEventOnElements(hoverElements, "mouseover", function () {
  cursor.classList.add("hovered");
});

addEventOnElements(hoverElements, "mouseout", function () {
  cursor.classList.remove("hovered");
});

/**
 * Signup Page
 */

// Simple auth tab toggler (scoped to this page)
    (function(){
      const tabs = document.querySelectorAll('.auth-tab');
      const panels = {
        login: document.getElementById('panel-login'),
        signup: document.getElementById('panel-signup'),
      };
      tabs.forEach(btn => {
        btn.addEventListener('click', () => {
          const target = btn.getAttribute('data-auth-tab');
          // toggle active state on tabs
          tabs.forEach(t => t.classList.toggle('active', t === btn));
          tabs.forEach(t => t.setAttribute('aria-selected', t === btn ? 'true' : 'false'));
          // toggle panels
          Object.keys(panels).forEach(key => panels[key].classList.toggle('active', key === target));
        });
      });
    })();

    // Multi-select game button logic
    const gameBtns = document.querySelectorAll('.game-btn');
    const hiddenInput = document.getElementById('selected-game-titles');
    gameBtns.forEach(btn => {
      btn.addEventListener('click', function () {
        btn.classList.toggle('selected');
        const selected = Array.from(gameBtns)
          .filter(b => b.classList.contains('selected'))
          .map(b => b.getAttribute('data-game'));
        hiddenInput.value = selected.join(',');
        // Required validation: at least one game selected
        if (selected.length === 0) {
          hiddenInput.setCustomValidity('Please select at least one game.');
        } else {
          hiddenInput.setCustomValidity('');
        }
      });
    });