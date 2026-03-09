// ===== ELECSTORE CORE JS =====
'use strict';

// ===== TOAST NOTIFICATIONS =====
const Toast = {
  container: null,

  init() {
    this.container = document.getElementById('toast-container');
    if (!this.container) {
      this.container = document.createElement('div');
      this.container.id = 'toast-container';
      document.body.appendChild(this.container);
    }
  },

  show(message, type = 'info', duration = 3500) {
    if (!this.container) this.init();
    const icons = { success: 'bi-check-circle-fill', error: 'bi-x-circle-fill', warning: 'bi-exclamation-triangle-fill', info: 'bi-info-circle-fill' };
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
      <i class="bi ${icons[type] || icons.info} toast-icon"></i>
      <span class="toast-msg">${message}</span>
      <button class="toast-close" onclick="Toast.remove(this.parentElement)"><i class="bi bi-x"></i></button>`;
    this.container.appendChild(toast);
    setTimeout(() => this.remove(toast), duration);
    return toast;
  },

  remove(toast) {
    if (!toast || !toast.parentElement) return;
    toast.classList.add('removing');
    setTimeout(() => toast.remove(), 300);
  },

  success: (msg, d) => Toast.show(msg, 'success', d),
  error: (msg, d) => Toast.show(msg, 'error', d),
  warning: (msg, d) => Toast.show(msg, 'warning', d),
  info: (msg, d) => Toast.show(msg, 'info', d),
};

// ===== AJAX HELPER =====
const Ajax = {
  async post(url, data = {}) {
    try {
      const formData = new FormData();
      Object.entries(data).forEach(([k, v]) => formData.append(k, v));
      const res = await fetch(url, { method: 'POST', body: formData });
      return await res.json();
    } catch (e) {
      console.error(e);
      return { error: 'Network error. Please try again.' };
    }
  },

  async get(url, params = {}) {
    try {
      const qs = new URLSearchParams(params).toString();
      const res = await fetch(qs ? `${url}?${qs}` : url);
      return await res.json();
    } catch (e) {
      console.error(e);
      return { error: 'Network error. Please try again.' };
    }
  }
};

// ===== NAVBAR =====
const Navbar = {
  init() {
    // Scroll effect
    window.addEventListener('scroll', () => {
      const nav = document.querySelector('.navbar');
      if (nav) nav.classList.toggle('scrolled', window.scrollY > 20);
    });

    // User dropdown
    const userBtn = document.getElementById('nav-user-btn');
    const userDropdown = document.getElementById('nav-user-dropdown');
    if (userBtn && userDropdown) {
      userBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        userDropdown.classList.toggle('active');
      });
    }

    // Close dropdowns on outside click
    document.addEventListener('click', () => {
      document.querySelectorAll('.nav-dropdown').forEach(d => d.classList.remove('active'));
    });

    // Search
    this.initSearch();
  },

  initSearch() {
    const input = document.getElementById('nav-search-input');
    const dropdown = document.getElementById('search-dropdown');
    if (!input || !dropdown) return;

    let timeout;
    input.addEventListener('input', () => {
      clearTimeout(timeout);
      const q = input.value.trim();
      if (q.length < 2) { dropdown.classList.remove('active'); return; }
      timeout = setTimeout(() => Navbar.doSearch(q), 350);
    });

    input.addEventListener('focus', () => {
      if (input.value.trim().length >= 2) dropdown.classList.add('active');
    });

    document.addEventListener('click', (e) => {
      if (!input.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.classList.remove('active');
      }
    });

    input.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        window.location.href = `user/shop.php?search=${encodeURIComponent(input.value.trim())}`;
      }
    });
  },

  async doSearch(q) {
    const dropdown = document.getElementById('search-dropdown');
    dropdown.innerHTML = '<div style="padding:16px;text-align:center;color:var(--text-muted)"><i class="bi bi-arrow-repeat spin"></i> Searching...</div>';
    dropdown.classList.add('active');
    const res = await Ajax.get('api/search.php', { q });
    if (res.products && res.products.length) {
      dropdown.innerHTML = res.products.map(p => `
        <div class="search-result-item" onclick="window.location='user/product.php?id=${p.id}'">
          <img src="assets/images/products/${p.image}" alt="${p.name}" class="search-result-img" onerror="this.src='assets/images/placeholder.jpg'">
          <div class="search-result-info">
            <div class="name">${p.name}</div>
            <div class="price">${p.sale_price ? '$'+parseFloat(p.sale_price).toFixed(2) : '$'+parseFloat(p.price).toFixed(2)}</div>
          </div>
        </div>`).join('') + `<div style="padding:12px 16px;text-align:center;border-top:1px solid var(--border)"><a href="user/shop.php?search=${encodeURIComponent(q)}" style="color:var(--primary-light);font-size:0.85rem">See all results →</a></div>`;
    } else {
      dropdown.innerHTML = `<div style="padding:24px;text-align:center;color:var(--text-muted)"><i class="bi bi-search" style="font-size:1.5rem;display:block;margin-bottom:8px"></i>No products found for "<strong>${q}</strong>"</div>`;
    }
  }
};

// ===== CART FUNCTIONS =====
const Cart = {
  async add(productId, quantity = 1, btn = null) {
    if (btn) { btn.classList.add('adding'); btn.innerHTML = '<i class="bi bi-check2"></i>'; }
    const res = await Ajax.post('api/cart.php', { action: 'add', product_id: productId, quantity });
    if (res.success) {
      Toast.success(res.message || 'Added to cart!');
      Cart.updateCount(res.count);
    } else {
      Toast.error(res.error || 'Failed to add to cart');
    }
    if (btn) {
      setTimeout(() => {
        btn.classList.remove('adding');
        btn.innerHTML = '<i class="bi bi-cart-plus"></i>';
      }, 1200);
    }
  },

  async remove(productId) {
    const res = await Ajax.post('api/cart.php', { action: 'remove', product_id: productId });
    if (res.success) {
      Toast.info('Removed from cart');
      Cart.updateCount(res.count);
    } else {
      Toast.error(res.error || 'Failed to remove');
    }
    return res;
  },

  async update(productId, quantity) {
    return await Ajax.post('api/cart.php', { action: 'update', product_id: productId, quantity });
  },

  updateCount(count) {
    const badge = document.getElementById('cart-count');
    if (badge) {
      badge.textContent = count;
      badge.style.display = count > 0 ? 'flex' : 'none';
    }
  }
};

// ===== WISHLIST / LIKES =====
const Wishlist = {
  async toggle(productId, btn = null) {
    const res = await Ajax.post('api/likes.php', { action: 'toggle', product_id: productId });
    if (res.success) {
      Toast[res.liked ? 'success' : 'info'](res.message);
      if (btn) {
        btn.classList.toggle('liked', res.liked);
        btn.title = res.liked ? 'Remove from wishlist' : 'Add to wishlist';
      }
      // Update wishlist count badge
      const badge = document.getElementById('wishlist-count');
      if (badge && res.total !== undefined) {
        badge.textContent = res.total;
        badge.style.display = res.total > 0 ? 'flex' : 'none';
      }
    } else {
      if (res.redirect) window.location.href = res.redirect;
      else Toast.error(res.error || 'Failed to update wishlist');
    }
  }
};

// ===== QUICK VIEW MODAL =====
const QuickView = {
  async open(productId) {
    const overlay = document.getElementById('quick-view-overlay');
    if (!overlay) return;
    overlay.classList.add('active');
    const content = document.getElementById('quick-view-content');
    content.innerHTML = `<div style="padding:60px;text-align:center"><i class="bi bi-arrow-repeat spin" style="font-size:2rem;color:var(--primary-light)"></i></div>`;

    const res = await Ajax.get('api/product.php', { id: productId });
    if (res.product) {
      const p = res.product;
      const price = p.sale_price ? `<span class="product-price">$${parseFloat(p.sale_price).toFixed(2)}</span> <span class="product-old-price">$${parseFloat(p.price).toFixed(2)}</span>` : `<span class="product-price">$${parseFloat(p.price).toFixed(2)}</span>`;
      content.innerHTML = `
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:32px">
          <div style="background:var(--bg-input);border-radius:var(--radius-md);overflow:hidden;height:300px">
            <img src="assets/images/products/${p.image}" style="width:100%;height:100%;object-fit:cover" alt="${p.name}" onerror="this.src='assets/images/placeholder.jpg'">
          </div>
          <div>
            <div class="product-brand">${p.brand || 'Unknown'}</div>
            <h3 style="margin-bottom:12px">${p.name}</h3>
            <div style="display:flex;gap:12px;align-items:center;margin-bottom:16px">${price}</div>
            <p style="color:var(--text-secondary);font-size:0.9rem;margin-bottom:20px;line-height:1.7">${p.description || ''}</p>
            <div style="display:flex;gap:10px;flex-wrap:wrap">
              <button class="btn btn-primary" onclick="Cart.add(${p.id},1,this)"><i class="bi bi-cart-plus"></i> Add to Cart</button>
              <a href="user/product.php?id=${p.id}" class="btn btn-outline">View Details</a>
            </div>
          </div>
        </div>`;
    } else {
      content.innerHTML = `<div class="empty-state"><div class="empty-state-icon"><i class="bi bi-exclamation-circle"></i></div><h3>Product not found</h3></div>`;
    }
  },

  close() {
    const overlay = document.getElementById('quick-view-overlay');
    if (overlay) overlay.classList.remove('active');
  }
};

// ===== INIT =====
document.addEventListener('DOMContentLoaded', () => {
  Toast.init();
  Navbar.init();

  // Close quick view on overlay click
  const qvOverlay = document.getElementById('quick-view-overlay');
  if (qvOverlay) {
    qvOverlay.addEventListener('click', (e) => {
      if (e.target === qvOverlay) QuickView.close();
    });
  }
});
