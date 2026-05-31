/**
 * Academic Portal — Premium Interaction Layer
 * portal.js v2.0 | No external dependencies
 */

/* ═══════════════════════════════════════════════════════════
   1. PARTICLE CANVAS BACKGROUND
   ═══════════════════════════════════════════════════════════ */
(function initParticles() {
  const canvas = document.createElement('canvas');
  canvas.id = 'particles-canvas';
  document.body.prepend(canvas);
  const ctx = canvas.getContext('2d');
  let W, H, particles = [];

  function resize() {
    W = canvas.width  = window.innerWidth;
    H = canvas.height = window.innerHeight;
  }
  resize();
  window.addEventListener('resize', resize, { passive: true });

  const COLORS = [
    'rgba(108,99,255,',
    'rgba(0,217,192,',
    'rgba(139,131,255,',
    'rgba(255,255,255,'
  ];

  function Particle() { this.reset(true); }
  Particle.prototype.reset = function (init) {
    this.x  = Math.random() * W;
    this.y  = init ? Math.random() * H : H + 8;
    this.r  = Math.random() * 1.8 + 0.4;
    this.vx = (Math.random() - 0.5) * 0.35;
    this.vy = -(Math.random() * 0.45 + 0.18);
    this.c  = COLORS[Math.floor(Math.random() * COLORS.length)];
    this.a  = Math.random() * 0.55 + 0.08;
    this.da = (Math.random() - 0.5) * 0.0018;
  };
  Particle.prototype.update = function () {
    this.x += this.vx;
    this.y += this.vy;
    this.a += this.da;
    if (this.a > 0.65) this.da = -Math.abs(this.da);
    if (this.a < 0.04) this.da =  Math.abs(this.da);
    if (this.y < -8)   this.reset(false);
  };
  Particle.prototype.draw = function () {
    ctx.beginPath();
    ctx.arc(this.x, this.y, this.r, 0, Math.PI * 2);
    ctx.fillStyle = this.c + this.a + ')';
    ctx.fill();
  };

  for (let i = 0; i < 90; i++) particles.push(new Particle());

  (function loop() {
    ctx.clearRect(0, 0, W, H);
    particles.forEach(function (p) { p.update(); p.draw(); });
    requestAnimationFrame(loop);
  })();
})();


/* ═══════════════════════════════════════════════════════════
   2. BUTTON RIPPLE EFFECT
   ═══════════════════════════════════════════════════════════ */
document.addEventListener('click', function (e) {
  var btn = e.target.closest(
    '.btn, .btn-submit, .btn-logout, [type="submit"]'
  );
  if (!btn) return;
  // Ensure relative positioning for the ripple
  var pos = getComputedStyle(btn).position;
  if (pos === 'static') btn.style.position = 'relative';
  btn.style.overflow = 'hidden';

  var rect   = btn.getBoundingClientRect();
  var size   = Math.max(rect.width, rect.height);
  var x      = e.clientX - rect.left  - size / 2;
  var y      = e.clientY - rect.top   - size / 2;
  var ripple = document.createElement('span');
  ripple.className = 'ripple';
  ripple.style.cssText =
    'position:absolute;border-radius:50%;transform:scale(0);' +
    'background:rgba(255,255,255,0.22);animation:rippleAnim 0.55s linear;' +
    'pointer-events:none;width:' + size + 'px;height:' + size + 'px;' +
    'left:' + x + 'px;top:' + y + 'px;';
  btn.appendChild(ripple);
  ripple.addEventListener('animationend', function () { ripple.remove(); });

  // Inject keyframe if missing
  if (!document.getElementById('ripple-style')) {
    var s = document.createElement('style');
    s.id  = 'ripple-style';
    s.textContent = '@keyframes rippleAnim{to{transform:scale(4);opacity:0;}}';
    document.head.appendChild(s);
  }
});


/* ═══════════════════════════════════════════════════════════
   3. PROFILE PHOTO LIVE PREVIEW
   ═══════════════════════════════════════════════════════════ */
document.addEventListener('change', function (e) {
  var input = e.target;
  if (input.type !== 'file') return;
  var accept = input.getAttribute('accept') || '';
  if (!accept.includes('image')) return;
  var file = input.files[0];
  if (!file) return;

  var reader = new FileReader();
  reader.onload = function (ev) {
    var group   = input.closest('.form-group') || input.parentNode;
    var preview = group.querySelector('.photo-preview');
    if (!preview) {
      preview = document.createElement('img');
      preview.className = 'photo-preview';
      preview.alt = 'Photo preview';
      group.insertBefore(preview, input);
    }
    preview.src = ev.target.result;
    preview.style.display = 'block';
  };
  reader.readAsDataURL(file);
});


/* ═══════════════════════════════════════════════════════════
   4. SCROLL REVEAL (IntersectionObserver)
   ═══════════════════════════════════════════════════════════ */
(function initScrollReveal() {
  if (!('IntersectionObserver' in window)) {
    // Fallback: just show everything
    document.querySelectorAll('.section-card,.eval-card,.fade-in-up')
      .forEach(function (el) { el.style.opacity = '1'; el.style.transform = 'none'; });
    return;
  }

  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.07 });

  var selectors = '.section-card, .eval-card, .fade-in-up, .login-card, .register-card';
  document.querySelectorAll(selectors).forEach(function (el, i) {
    el.style.transitionDelay = Math.min(i * 0.06, 0.5) + 's';
    observer.observe(el);
  });
})();


/* ═══════════════════════════════════════════════════════════
   5. COUNT-UP ANIMATION (for score panels)
   ═══════════════════════════════════════════════════════════ */
(function initCountUp() {
  var scoreEls = document.querySelectorAll('.score-number[data-target]');
  scoreEls.forEach(function (el) {
    var target  = parseFloat(el.dataset.target) || 0;
    var current = 0;
    var step    = target / 60;
    var interval = setInterval(function () {
      current = Math.min(current + step, target);
      el.textContent = current.toFixed(2);
      if (current >= target) clearInterval(interval);
    }, 16);
  });
})();


/* ═══════════════════════════════════════════════════════════
   6. ANIMATED STAT BARS
   ═══════════════════════════════════════════════════════════ */
(function initStatBars() {
  var bars = document.querySelectorAll('.stat-bar-fill[data-pct]');
  bars.forEach(function (bar) {
    bar.style.width = '0%';
    setTimeout(function () {
      bar.style.width = bar.dataset.pct + '%';
    }, 350);
  });
})();


/* ═══════════════════════════════════════════════════════════
   7. TOAST NOTIFICATION SYSTEM
   ═══════════════════════════════════════════════════════════ */
window.showToast = function (message, type, duration) {
  type     = type     || 'info';
  duration = duration || 3500;

  var container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    document.body.appendChild(container);
  }

  var icons = { success: '✅', error: '❌', info: 'ℹ️' };
  var toast = document.createElement('div');
  toast.className = 'toast toast-' + type;
  toast.innerHTML =
    '<span style="font-size:1rem;">' + (icons[type] || 'ℹ️') + '</span>' +
    '<span>' + message + '</span>';
  container.appendChild(toast);

  setTimeout(function () {
    toast.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
    toast.style.opacity    = '0';
    toast.style.transform  = 'translateX(36px)';
    setTimeout(function () { toast.remove(); }, 420);
  }, duration);
};


/* ═══════════════════════════════════════════════════════════
   8. INPUT FOCUS GROUP HIGHLIGHT
   ═══════════════════════════════════════════════════════════ */
document.addEventListener('focusin', function (e) {
  var fg = e.target.closest && e.target.closest('.form-group');
  if (fg) fg.classList.add('focused');
});
document.addEventListener('focusout', function (e) {
  var fg = e.target.closest && e.target.closest('.form-group');
  if (fg) fg.classList.remove('focused');
});


/* ═══════════════════════════════════════════════════════════
   9. MOBILE NAV TOGGLE (if needed)
   ═══════════════════════════════════════════════════════════ */
var toggle = document.getElementById('nav-menu-toggle');
var menu   = document.getElementById('mobile-menu');
if (toggle && menu) {
  toggle.addEventListener('click', function () {
    menu.classList.toggle('open');
  });
}
