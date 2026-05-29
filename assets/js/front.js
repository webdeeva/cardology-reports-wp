/* Cardology Reports — front-end controller.
 * Pure DOM, no framework dependencies (works on any WP theme).
 */
(function () {
	'use strict';

	var cfg = window.CRWP_CFG || {};
	var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };

	function buildDate(m, d, y) {
		var mm = parseInt(m, 10), dd = parseInt(d, 10), yy = parseInt(y, 10);
		if (isNaN(mm) || mm < 1 || mm > 12) return null;
		if (isNaN(dd) || dd < 1 || dd > 31) return null;
		if (isNaN(yy) || yy < 1900) return null;
		return yy + '-' + ('0' + mm).slice(-2) + '-' + ('0' + dd).slice(-2);
	}

	function fmtCents(cents) { return '$' + (cents / 100).toFixed(2); }

	function postJSON(path, body) {
		return fetch(cfg.restRoot + path, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': cfg.nonce
			},
			body: JSON.stringify(body)
		}).then(function (res) {
			return res.text().then(function (text) {
				var parsed = null;
				try { parsed = text ? JSON.parse(text) : null; } catch (e) {}
				if (!res.ok) {
					var msg = (parsed && parsed.error) ? parsed.error : ('HTTP ' + res.status);
					throw new Error(msg);
				}
				return parsed || {};
			});
		});
	}

	function loadStripe() {
		return new Promise(function (resolve, reject) {
			if (typeof window.Stripe !== 'undefined') { resolve(window.Stripe(cfg.stripeKey)); return; }
			var poll = setInterval(function () {
				if (typeof window.Stripe !== 'undefined') {
					clearInterval(poll);
					resolve(window.Stripe(cfg.stripeKey));
				}
			}, 100);
			setTimeout(function () { clearInterval(poll); reject(new Error('Stripe.js failed to load')); }, 10000);
		});
	}

	/* ------------- Catalog: "Order this report" buttons ------------- */
	$$('[data-crwp-open-form]').forEach(function (btn) {
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			var slug = btn.getAttribute('data-slug');
			var wrappers = $$('[data-crwp-form-wrapper]');
			if (wrappers.length === 0) {
				// Fallback for setups that have a separate catalog/single page split.
				if (cfg.catalogUrl) {
					window.location.assign(cfg.catalogUrl + (cfg.catalogUrl.indexOf('?') === -1 ? '?' : '&') + 'report=' + encodeURIComponent(slug));
				}
				return;
			}
			wrappers.forEach(function (w) {
				if (w.getAttribute('data-crwp-form-wrapper') === slug) {
					w.hidden = false;
					w.scrollIntoView({ behavior: 'smooth', block: 'start' });
					// Focus the first input for instant typing.
					var input = w.querySelector('input[name="name"]');
					if (input) {
						setTimeout(function () { input.focus(); }, 350);
					}
				} else {
					w.hidden = true;
				}
			});
		});
	});

	/* ------------- Order form submission ------------- */
	$$('[data-crwp-form]').forEach(function (form) {
		var slug = form.getAttribute('data-slug');
		var error = form.querySelector('[data-crwp-error]');
		var submit = form.querySelector('[data-crwp-submit]');
		var originalSubmitText = submit ? submit.textContent : '';
		var promoHost = form.querySelector('[data-crwp-promo]');
		var promoState = null; // { valid, freeReport, promotionCodeId, code, discountedCents, originalCents }

		renderPromo();

		function showError(msg) {
			if (!error) return;
			error.textContent = msg;
			error.hidden = false;
		}
		function clearError() { if (error) { error.hidden = true; error.textContent = ''; } }

		function renderPromo() {
			if (!promoHost) return;
			promoHost.innerHTML = '';
			if (promoState && promoState.valid) {
				var box = document.createElement('div');
				box.className = 'crwp-promo__applied' + (promoState.freeReport ? ' crwp-promo__applied--free' : '');
				box.innerHTML = promoState.freeReport
					? '<strong>' + promoState.code + '</strong> — ' + cfg.i18n.applied + ' (' + cfg.i18n.free + ')'
					: '<strong>' + promoState.code + '</strong> — new total ' + fmtCents(promoState.discountedCents) +
						' <span style="opacity:.5;text-decoration:line-through">' + fmtCents(promoState.originalCents) + '</span>';
				var remove = document.createElement('button');
				remove.type = 'button';
				remove.className = 'crwp-promo__open';
				remove.style.marginLeft = '0.75rem';
				remove.textContent = 'Remove';
				remove.addEventListener('click', function () { promoState = null; updateCta(); renderPromo(); });
				box.appendChild(remove);
				promoHost.appendChild(box);
				return;
			}
			var open = document.createElement('button');
			open.type = 'button';
			open.className = 'crwp-promo__open';
			open.textContent = 'Have a promo code?';
			open.addEventListener('click', function () {
				promoHost.innerHTML = '';
				var wrap = document.createElement('div');
				wrap.className = 'crwp-promo';
				var input = document.createElement('input');
				input.type = 'text';
				input.placeholder = 'PROMO CODE';
				input.style.textTransform = 'uppercase';
				var apply = document.createElement('button');
				apply.type = 'button';
				apply.className = 'crwp-btn crwp-btn--gold';
				apply.textContent = cfg.i18n.apply;
				apply.addEventListener('click', function () {
					var code = input.value.trim().toUpperCase();
					if (!code) return;
					apply.disabled = true;
					postJSON('/validate-promo', { code: code, reportSlug: slug })
						.then(function (data) {
							if (!data.valid) { showError(data.error || cfg.i18n.invalidCode); apply.disabled = false; return; }
							promoState = data;
							clearError();
							updateCta();
							renderPromo();
						})
						.catch(function (err) { showError(err.message); apply.disabled = false; });
				});
				wrap.appendChild(input);
				wrap.appendChild(apply);
				promoHost.appendChild(wrap);
				input.focus();
			});
			promoHost.appendChild(open);
		}

		function updateCta() {
			if (!submit) return;
			if (promoState && promoState.freeReport) {
				submit.textContent = 'Claim your free report';
			} else if (promoState && promoState.discountedCents != null) {
				submit.textContent = 'Continue to Checkout — ' + fmtCents(promoState.discountedCents);
			} else {
				submit.textContent = originalSubmitText;
			}
		}

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			clearError();

			var fd = new FormData(form);
			var customer = {
				name: (fd.get('name') || '').toString().trim(),
				email: (fd.get('email') || '').toString().trim(),
				birthdate: buildDate(fd.get('month'), fd.get('day'), fd.get('year')),
				birthTime: (fd.get('birthTime') || '').toString() || null,
				birthPlace: (fd.get('birthPlace') || '').toString().trim() || null
			};
			if (!customer.name || customer.name.length < 2) { showError('Please enter your full name.'); return; }
			if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(customer.email)) { showError('Please enter a valid email address.'); return; }
			if (!customer.birthdate) { showError('Please enter a valid birth date.'); return; }

			var partner = null;
			if (fd.has('partnerName')) {
				partner = {
					name: (fd.get('partnerName') || '').toString().trim(),
					birthdate: buildDate(fd.get('partnerMonth'), fd.get('partnerDay'), fd.get('partnerYear'))
				};
				if (!partner.name || !partner.birthdate) { showError("Please enter your partner's name and birth date."); return; }
			}

			var age = null;
			if (fd.has('age') && fd.get('age')) {
				age = parseInt(fd.get('age'), 10);
				if (isNaN(age) || age < 1 || age > 120) { showError('Please enter a valid age (1–120).'); return; }
			}

			submit.disabled = true;
			var loadingText = (promoState && promoState.freeReport) ? cfg.i18n.claiming : cfg.i18n.redirecting;
			submit.textContent = loadingText;

			if (promoState && promoState.freeReport && promoState.code) {
				postJSON('/claim-free', {
					reportSlug: slug,
					customer: customer,
					partner: partner,
					age: age,
					promoCode: promoState.code
				}).then(function (data) {
					window.location.assign(window.location.origin + window.location.pathname + '?session_id=' + encodeURIComponent(data.sessionId));
				}).catch(function (err) {
					showError(err.message);
					submit.disabled = false;
					updateCta();
				});
				return;
			}

			postJSON('/checkout', {
				reportSlug: slug,
				customer: customer,
				partner: partner,
				age: age,
				promotionCodeId: promoState && promoState.valid ? promoState.promotionCodeId : null
			}).then(function (data) {
				if (data.url) { window.location.assign(data.url); return; }
				return loadStripe().then(function (stripe) {
					return stripe.redirectToCheckout({ sessionId: data.sessionId });
				}).then(function (result) {
					if (result && result.error) { throw new Error(result.error.message); }
				});
			}).catch(function (err) {
				showError(err.message);
				submit.disabled = false;
				updateCta();
			});
		});
	});

	/* ------------- Status / download page polling ------------- */
	(function () {
		var host = document.querySelector('[data-crwp-status]');
		if (!host) return;
		var sessionId = host.getAttribute('data-session-id') || '';
		if (!sessionId) return;
		var bar = host.querySelector('[data-crwp-progress-bar]');
		var loadingEl = host.querySelector('[data-crwp-status-loading]');
		var readyEl = host.querySelector('[data-crwp-status-ready]');
		var failedEl = host.querySelector('[data-crwp-status-failed]');
		var link = host.querySelector('[data-crwp-status-link]');
		var errEl = host.querySelector('[data-crwp-status-error]');

		var startedAt = Date.now();

		function poll() {
			fetch(cfg.restRoot + '/status?sessionId=' + encodeURIComponent(sessionId), {
				headers: { 'X-WP-Nonce': cfg.nonce }
			}).then(function (r) { return r.json(); })
			.then(function (data) {
				if (data.status === 'completed' && data.reportUrl) {
					if (loadingEl) loadingEl.hidden = true;
					if (readyEl) readyEl.hidden = false;
					if (link) link.setAttribute('href', data.reportUrl);
					return;
				}
				if (data.status === 'failed') {
					if (loadingEl) loadingEl.hidden = true;
					if (failedEl) failedEl.hidden = false;
					if (errEl) errEl.textContent = data.message || 'Generation failed.';
					return;
				}
				if (bar) {
					var elapsed = (Date.now() - startedAt) / 1000;
					var pct = Math.min(95, 8 + (elapsed / 600) * 87);
					bar.style.width = pct + '%';
				}
				setTimeout(poll, 5000);
			}).catch(function () { setTimeout(poll, 5000); });
		}
		poll();
	})();
})();
