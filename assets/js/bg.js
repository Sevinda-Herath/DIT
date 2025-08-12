'use strict';

// Neon particle network background animation for gamer vibe
// Lightweight, DPI-aware, reduced-motion friendly, and non-interactive
(() => {
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  if (prefersReducedMotion.matches) return; // Respect user preference

  // Create and style the canvas (behind content)
  const canvas = document.createElement('canvas');
  canvas.setAttribute('aria-hidden', 'true');
  canvas.dataset.bgCanvas = 'true';
  Object.assign(canvas.style, {
    position: 'fixed',
    inset: '0',
    width: '100vw',
    height: '100vh',
  zIndex: '0',
    pointerEvents: 'none',
    // Slight blending for neon feel on dark theme
    mixBlendMode: 'screen'
  });
  document.body.prepend(canvas);

  const ctx = canvas.getContext('2d', { alpha: true });

  let dpr = Math.min(window.devicePixelRatio || 1, 2);
  let width = 0, height = 0, area = 0;
  let particles = [];
  let paused = false;

  const mouse = { x: null, y: null, active: false };

  function rand(min, max) { return Math.random() * (max - min) + min; }

  function resize() {
    dpr = Math.min(window.devicePixelRatio || 1, 2);
    // Ensure CSS pixels map to device pixels
    const cssWidth = canvas.clientWidth;
    const cssHeight = canvas.clientHeight;
    width = Math.floor(cssWidth * dpr);
    height = Math.floor(cssHeight * dpr);
    canvas.width = width;
    canvas.height = height;
    area = (width * height) / (dpr * dpr); // area in CSS px^2
    tunePopulation();
  }

  function tunePopulation() {
    // Density tuned for desktop vs mobile; clamp to keep perf in check
    const isSmall = Math.min(width / dpr, height / dpr) < 700;
    const baseDensity = isSmall ? 0.00004 : 0.00007; // particles per css px^2
    const target = Math.max(30, Math.min(180, Math.floor(area * baseDensity)));
    if (particles.length > target) particles.length = target;
    while (particles.length < target) particles.push(makeParticle());
  }

  function makeParticle() {
    const theta = rand(0, Math.PI * 2);
    const speed = rand(0.08, 0.35) * dpr; // gentle motion
    return {
      x: rand(0, width),
      y: rand(0, height),
      vx: Math.cos(theta) * speed,
      vy: Math.sin(theta) * speed,
      r: rand(0.6, 1.8) * dpr,
      hue: rand(255, 285), // purple-blue band to match theme
      life: rand(4, 10),
      age: 0
    };
  }

  function step() {
    // trail fade
    ctx.globalCompositeOperation = 'source-over';
    ctx.fillStyle = 'rgba(8, 5, 20, 0.18)'; // subtle trail on dark bg
    ctx.fillRect(0, 0, width, height);

    // particles
    ctx.globalCompositeOperation = 'lighter';

    for (let i = 0; i < particles.length; i++) {
      const p = particles[i];
      p.x += p.vx;
      p.y += p.vy;
      p.age += 0.016; // approx per frame

      // wrap around edges
      if (p.x < 0) p.x += width; else if (p.x > width) p.x -= width;
      if (p.y < 0) p.y += height; else if (p.y > height) p.y -= height;

      // twinkle via slight hue drift
      p.hue += 0.05;
      if (p.hue > 300) p.hue = 255;

      // draw dot
      ctx.beginPath();
      ctx.fillStyle = `hsla(${p.hue}, 100%, 65%, 0.9)`;
      ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
      ctx.fill();
    }

    // connect nearby particles with neon lines
    const maxDist = 120 * dpr;
    for (let i = 0; i < particles.length; i++) {
      const a = particles[i];
      for (let j = i + 1; j < particles.length; j++) {
        const b = particles[j];
        const dx = a.x - b.x;
        const dy = a.y - b.y;
        const dist = Math.hypot(dx, dy);
        if (dist < maxDist) {
          const t = 1 - dist / maxDist;
          ctx.strokeStyle = `hsla(270, 100%, 68%, ${t * 0.55})`;
          ctx.lineWidth = Math.max(0.5, 1.2 * dpr * t);
          ctx.beginPath();
          ctx.moveTo(a.x, a.y);
          ctx.lineTo(b.x, b.y);
          ctx.stroke();
        }
      }
    }

    // connect to mouse for interactivity
    if (mouse.active && mouse.x != null) {
      const mouseRange = 160 * dpr;
      for (let i = 0; i < particles.length; i++) {
        const p = particles[i];
        const dx = p.x - mouse.x;
        const dy = p.y - mouse.y;
        const dist = Math.hypot(dx, dy);
        if (dist < mouseRange) {
          const t = 1 - dist / mouseRange;
          ctx.strokeStyle = `hsla(200, 100%, 60%, ${t * 0.9})`;
          ctx.lineWidth = Math.max(0.6, 1.6 * dpr * t);
          ctx.beginPath();
          ctx.moveTo(p.x, p.y);
          ctx.lineTo(mouse.x, mouse.y);
          ctx.stroke();

          // gentle repel to create dynamic motion near cursor
          const f = (mouseRange - dist) / mouseRange * 0.02 * dpr;
          if (dist > 0.0001) {
            p.vx += (dx / dist) * f;
            p.vy += (dy / dist) * f;
          }
        }
      }
    }

    if (!paused) requestAnimationFrame(step);
  }

  function start() {
    if (!paused) return;
    paused = false;
    requestAnimationFrame(step);
  }

  function stop() {
    paused = true;
  }

  // Event wiring
  window.addEventListener('resize', resize);

  window.addEventListener('mousemove', (e) => {
    mouse.active = true;
    const rect = canvas.getBoundingClientRect();
    mouse.x = (e.clientX - rect.left) * dpr;
    mouse.y = (e.clientY - rect.top) * dpr;
  });

  window.addEventListener('mouseout', () => { mouse.active = false; });

  document.addEventListener('visibilitychange', () => {
    if (document.hidden) stop(); else start();
  });

  // React to reduced-motion changes live
  prefersReducedMotion.addEventListener?.('change', (e) => {
    if (e.matches) {
      stop();
      ctx.clearRect(0, 0, width, height);
    } else {
      start();
    }
  });

  // Init and kick off
  resize();
  // Prime trails with a clear
  ctx.clearRect(0, 0, width, height);
  requestAnimationFrame(step);
})();
