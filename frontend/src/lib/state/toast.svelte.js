/**
 * Svelte 5 Pure Runes Toast State Manager
 * Universal, reactive notification bus for Valora Vault.
 * Supports auto-dismiss, priority stacking, and semantic notifications.
 */
class ToastManager {
  toasts = $state([]);

  /**
   * Add a toast notification
   * @param {Object|string} options - Toast options or simple message string
   * @param {string} [options.message] - Notification text
   * @param {'success'|'error'|'warning'|'info'} [options.type='info'] - Semantic variant
   * @param {string} [options.title] - Optional heading
   * @param {number} [options.duration=4500] - Duration in ms (0 = permanent until clicked)
   * @returns {string} - Unique toast ID
   */
  show(options) {
    const id = 'toast_' + Math.random().toString(36).substring(2, 9) + '_' + Date.now();
    const config = typeof options === 'string' ? { message: options } : (options || {});

    const type = config.type || 'info';
    const message = config.message || '';
    const defaultTitle = type === 'success' 
      ? 'Berhasil' 
      : type === 'error' 
        ? 'Terjadi Kesalahan' 
        : type === 'warning' 
          ? 'Perhatian' 
          : 'Pemberitahuan';
    const title = config.title || defaultTitle;
    const duration = typeof config.duration === 'number' ? config.duration : 4500;

    const toast = {
      id,
      message,
      type,
      title,
      duration,
      createdAt: Date.now()
    };

    // Stacking: add to array
    this.toasts.push(toast);

    if (duration > 0 && typeof window !== 'undefined') {
      setTimeout(() => {
        this.remove(id);
      }, duration);
    }

    return id;
  }

  /**
   * Show success toast
   * @param {string} message
   * @param {string} [title='Berhasil Disimpan']
   * @param {number} [duration=4500]
   */
  success(message, title = 'Berhasil Disimpan', duration = 4500) {
    return this.show({ message, type: 'success', title, duration });
  }

  /**
   * Show error toast
   * @param {string} message
   * @param {string} [title='Terjadi Kesalahan']
   * @param {number} [duration=6500]
   */
  error(message, title = 'Terjadi Kesalahan', duration = 6500) {
    return this.show({ message, type: 'error', title, duration });
  }

  /**
   * Show warning toast
   * @param {string} message
   * @param {string} [title='Perhatian']
   * @param {number} [duration=5000]
   */
  warning(message, title = 'Perhatian', duration = 5000) {
    return this.show({ message, type: 'warning', title, duration });
  }

  /**
   * Show info toast
   * @param {string} message
   * @param {string} [title='Informasi']
   * @param {number} [duration=4000]
   */
  info(message, title = 'Informasi', duration = 4000) {
    return this.show({ message, type: 'info', title, duration });
  }

  /**
   * Remove a specific toast by ID
   * @param {string} id
   */
  remove(id) {
    this.toasts = this.toasts.filter(t => t.id !== id);
  }

  /**
   * Clear all toasts
   */
  clear() {
    this.toasts = [];
  }
}

export const toastState = new ToastManager();
