(function () {
	var header = document.querySelector('.site-header');
	var toggle = document.querySelector('.menu-toggle');
	var nav = document.getElementById('site-nav');

	function onScroll() {
		header.classList.toggle('is-scrolled', window.scrollY > 20);
	}
	window.addEventListener('scroll', onScroll, { passive: true });
	onScroll();

	if (toggle) {
		toggle.addEventListener('click', function () {
			var open = nav.classList.toggle('is-open');
			document.body.classList.toggle('menu-open', open);
			toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		});
		nav.addEventListener('click', function (e) {
			if (e.target.tagName === 'A') {
				nav.classList.remove('is-open');
				document.body.classList.remove('menu-open');
				toggle.setAttribute('aria-expanded', 'false');
			}
		});
	}

	// Work category filter (delegated, so it also works inside the Elementor editor).
	document.addEventListener('click', function (e) {
		var btn = e.target.closest ? e.target.closest('.work-filter button') : null;
		if (!btn) return;
		var box = btn.closest('.work-showcase');
		var f = btn.getAttribute('data-filter');
		box.querySelectorAll('.work-filter button').forEach(function (b) {
			var on = b === btn;
			b.classList.toggle('is-active', on);
			b.setAttribute('aria-selected', on ? 'true' : 'false');
		});
		box.querySelectorAll('.sc-item').forEach(function (it) {
			var show = f === '*' || (' ' + it.getAttribute('data-cats') + ' ').indexOf(' ' + f + ' ') > -1;
			it.classList.toggle('is-hidden', !show);
			if (show) {
				it.classList.remove('is-entering');
				void it.offsetWidth;
				it.classList.add('is-entering');
			}
		});
	});

	// Laptop slideshow (work pages): arrows, dots, auto-advance (pauses on hover).
	document.querySelectorAll('[data-lap-slides]').forEach(function (lap, n) {
		var slides = lap.querySelectorAll('.lap-slide');
		var dots = lap.parentNode.querySelectorAll('.lap-dot');
		var i = 0, timer = null;
		var still = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		function load(s) {
			if (!s) return;
			var im = s.querySelector('img[data-src]');
			if (im) {
				if (im.dataset.srcset) im.srcset = im.dataset.srcset;
				im.src = im.dataset.src;
				im.removeAttribute('data-src');
				im.removeAttribute('data-srcset');
			}
			var bg = s.querySelector('[data-bg]');
			if (bg) bg.removeAttribute('data-bg');
		}
		function films(active) {
			slides.forEach(function (s) {
				var v = s.querySelector('video[data-film-src]');
				if (!v) return;
				if (s === active && !still) {
					if (!v.src) v.src = v.dataset.filmSrc;
					var p = v.play();
					if (p && p.catch) p.catch(function () {});
				} else if (!v.paused) {
					v.pause();
				}
			});
		}
		function show(k) {
			i = (k + slides.length) % slides.length;
			load(slides[i]);
			load(slides[(i + 1) % slides.length]);
			slides.forEach(function (s, j) { s.classList.toggle('is-on', j === i); });
			dots.forEach(function (d, j) { d.classList.toggle('is-on', j === i); });
			films(slides[i]);
		}
		// Films get longer on screen than still slides.
		function delay() { return slides[i] && slides[i].classList.contains('is-film') ? 9000 : 3500; }
		function play() { stop(); if (!still) timer = setTimeout(function tick() { show(i + 1); timer = setTimeout(tick, delay()); }, delay()); }
		function stop() { if (timer) clearTimeout(timer); }
		// Controls sit inside card links: change slide without following the link.
		function bind(el, fn) {
			if (!el) return;
			el.addEventListener('click', function (e) { e.preventDefault(); e.stopPropagation(); fn(); play(); });
			el.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); e.stopPropagation(); fn(); play(); } });
		}
		bind(lap.querySelector('.lap-prev'), function () { show(i - 1); });
		bind(lap.querySelector('.lap-next'), function () { show(i + 1); });
		dots.forEach(function (d, k) { bind(d, function () { show(k); }); });
		lap.addEventListener('mouseenter', stop);
		lap.addEventListener('mouseleave', play);
		setTimeout(play, n * 700); // stagger multiple sliders
	});

	// Links inside card links (e.g. "Watch the films").
	document.addEventListener('click', function (e) {
		var go = e.target.closest ? e.target.closest('[data-go]') : null;
		if (!go) return;
		e.preventDefault(); e.stopPropagation();
		window.location.href = go.getAttribute('data-go');
	}, true);
	document.addEventListener('keydown', function (e) {
		if ((e.key === 'Enter' || e.key === ' ') && e.target.matches && e.target.matches('[data-go]')) {
			e.preventDefault(); window.location.href = e.target.getAttribute('data-go');
		}
	});

	// Showcase board films: open in a full-screen player.
	document.addEventListener('click', function (e) {
		var t = e.target.closest ? e.target.closest('.cb-reel[data-video]') : null;
		if (!t) return;
		e.preventDefault();
		var ov = document.createElement('div');
		ov.className = 'cb-modal';
		ov.innerHTML = '<button type="button" class="cb-close" aria-label="Close">×</button><video controls autoplay playsinline></video>';
		ov.querySelector('video').src = t.getAttribute('data-video');
		function close() { ov.querySelector('video').pause(); ov.remove(); document.removeEventListener('keydown', esc); }
		function esc(k) { if (k.key === 'Escape') close(); }
		ov.addEventListener('click', function (k) { if (k.target === ov || k.target.classList.contains('cb-close')) close(); });
		document.addEventListener('keydown', esc);
		document.body.appendChild(ov);
	});

	// Campaign page reels: big play button, one film at a time.
	document.querySelectorAll('.cmp-reel-frame').forEach(function (fr) {
		var v = fr.querySelector('video'), b = fr.querySelector('.cmp-reel-play');
		b.addEventListener('click', function () {
			document.querySelectorAll('.cmp-reel-frame video').forEach(function (o) { if (o !== v) o.pause(); });
			v.controls = true;
			fr.classList.add('is-playing');
			v.play();
		});
		v.addEventListener('pause', function () { if (v.currentTime === 0 || v.ended) fr.classList.remove('is-playing'); });
		v.addEventListener('ended', function () { fr.classList.remove('is-playing'); v.controls = false; });
	});

	var els = document.querySelectorAll('.reveal');
	if (!('IntersectionObserver' in window)) {
		els.forEach(function (el) { el.classList.add('is-in'); });
		return;
	}
	var io = new IntersectionObserver(function (entries) {
		entries.forEach(function (en) {
			if (en.isIntersecting) {
				en.target.classList.add('is-in');
				io.unobserve(en.target);
			}
		});
	}, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
	els.forEach(function (el, i) {
		el.style.transitionDelay = (i % 4) * 60 + 'ms';
		io.observe(el);
	});

	// Highlight nav link for the section in view.
	var links = Array.prototype.slice.call(document.querySelectorAll('.nav a[href*="#"]'));
	var map = {};
	links.forEach(function (a) { map[a.hash.slice(1)] = a; });
	var spy = new IntersectionObserver(function (entries) {
		entries.forEach(function (en) {
			if (en.isIntersecting && map[en.target.id]) {
				links.forEach(function (a) { a.classList.remove('is-active'); });
				map[en.target.id].classList.add('is-active');
			}
		});
	}, { rootMargin: '-45% 0px -50% 0px' });
	Object.keys(map).forEach(function (id) {
		var s = document.getElementById(id);
		if (s) spy.observe(s);
	});
})();
