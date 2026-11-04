(() => {
  'use strict';

  const initialize = (scope = document) => {
    const headers = [...scope.querySelectorAll('[data-ta-index-header]')];
    if (scope.matches?.('[data-ta-index-header]')) headers.unshift(scope);

    headers.forEach((header) => {
      if (header.dataset.taInitialized && header.taehController) return;

      const toggle = header.querySelector('.ta-ih__toggle');
      const nav = header.querySelector('.ta-ih__nav');
      if (!toggle || !nav) return;

      header.dataset.taInitialized = 'true';
      const categoryList = header.querySelector('.ta-ih__categories');
      const previewName = header.querySelector('[data-ta-preview-name]');
      const previewNumber = header.querySelector('[data-ta-preview-number]');
      const mobile = window.matchMedia('(max-width: 900px)');

      // Elementor can duplicate server-rendered markup. Reassign the association.
      let navId;
      do {
        navId = 'taeh-nav-' + Math.random().toString(36).slice(2, 11);
      } while (document.getElementById(navId));
      nav.id = navId;
      toggle.setAttribute('aria-controls', navId);

      const closeDetails = () => {
        nav.querySelectorAll('details[open]').forEach((details) => {
          details.open = false;
        });
      };

      const setMenu = (open) => {
        header.classList.toggle('ta-ih--open', open);
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
        if (!open) closeDetails();
      };

      const updatePreview = (target) => {
        if (!(target instanceof Element) || !categoryList) return;
        const row = target.closest('.ta-ih__category-link');
        if (!row || !categoryList.contains(row)) return;
        if (previewName) previewName.textContent = row.querySelector('.ta-ih__category-name').textContent.trim();
        if (previewNumber) previewNumber.textContent = row.querySelector('.ta-ih__category-number').textContent.trim();
      };

      categoryList?.addEventListener('pointerover', (event) => updatePreview(event.target));
      categoryList?.addEventListener('focusin', (event) => updatePreview(event.target));
      toggle.addEventListener('click', () => setMenu(toggle.getAttribute('aria-expanded') !== 'true'));

      header.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        const details = event.target.closest('details[open]');
        if (details) {
          event.preventDefault();
          details.querySelector('summary').focus();
          details.open = false;
        } else if (mobile.matches && header.classList.contains('ta-ih--open')) {
          event.preventDefault();
          toggle.focus();
          setMenu(false);
        }
      });

      header.addEventListener('focusout', (event) => {
        if (!header.contains(event.relatedTarget)) setMenu(false);
      });

      nav.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');
        if (!link) return;
        // Preserve the menu when opening a destination in another tab.
        if (event.ctrlKey || event.metaKey || event.shiftKey || link.target === '_blank') return;
        if (mobile.matches) toggle.focus();
        else link.closest('details')?.querySelector('summary')?.focus();
        setMenu(false);
      });

      // At most one top-level dropdown is open at a time.
      nav.querySelectorAll('.ta-ih__nav-list > li > details').forEach((details) => {
        details.addEventListener('toggle', () => {
          if (!details.open) return;
          nav.querySelectorAll('.ta-ih__nav-list > li > details[open]').forEach((other) => {
            if (other !== details) other.open = false;
          });
        });
      });

      // Shared listeners below handle outside clicks and screen-size changes.
      header.taehController = { setMenu, mobile, toggle, nav };
      header.classList.add('ta-ih--enhanced');
      setMenu(false);
    });
  };

  document.addEventListener('click', (event) => {
    document.querySelectorAll('[data-ta-index-header]').forEach((header) => {
      const controller = header.taehController;
      if (!controller || header.contains(event.target)) return;
      const { nav, mobile, toggle, setMenu } = controller;
      if (nav.contains(document.activeElement)) {
        if (mobile.matches) toggle.focus();
        else document.activeElement.closest('details')?.querySelector('summary')?.focus();
      }
      setMenu(false);
    });
  });

  window.matchMedia('(max-width: 900px)').addEventListener('change', () => {
    document.querySelectorAll('[data-ta-index-header]').forEach((header) => {
      const controller = header.taehController;
      if (!controller) return;
      const { nav, mobile, toggle, setMenu } = controller;
      const focused = document.activeElement;
      if (mobile.matches && nav.contains(focused)) toggle.focus();
      else if (!mobile.matches && focused === toggle) nav.querySelector('a[href], summary')?.focus();
      else if (nav.contains(focused)) focused.closest('details')?.querySelector('summary')?.focus();
      setMenu(false);
    });
  });

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => initialize());
  else initialize();

  // Initialize new/replaced shortcode markup in Elementor's live preview.
  // No dependency on Elementor's JavaScript API or jQuery is required.
  new MutationObserver((records) => {
    records.forEach((record) => {
      record.addedNodes.forEach((node) => {
        if (node.nodeType === 1 && !node.closest('.ta-ih__spotlight')) initialize(node);
      });
    });
  }).observe(document.documentElement, { childList: true, subtree: true });
})();
