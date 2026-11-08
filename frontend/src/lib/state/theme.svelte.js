/**
 * Svelte 5 Pure Runes Theme State Manager
 * Manages Daylight Light Mode (Default) and High-Contrast Dark Mode.
 */
class ThemeManager {
  current = $state('light');

  constructor() {
    if (typeof window !== 'undefined') {
      const stored = localStorage.getItem('hyperion_theme');
      if (stored === 'dark' || stored === 'light') {
        this.current = stored;
      } else {
        this.current = 'light';
      }
      this.syncDOM();
    }
  }

  toggle() {
    this.current = this.current === 'light' ? 'dark' : 'light';
    if (typeof window !== 'undefined') {
      localStorage.setItem('hyperion_theme', this.current);
      this.syncDOM();
    }
  }

  setTheme(theme) {
    if (theme !== 'light' && theme !== 'dark') return;
    this.current = theme;
    if (typeof window !== 'undefined') {
      localStorage.setItem('hyperion_theme', theme);
      this.syncDOM();
    }
  }

  syncDOM() {
    if (typeof document !== 'undefined') {
      const root = document.documentElement;
      if (this.current === 'dark') {
        root.classList.add('dark');
        root.style.colorScheme = 'dark';
      } else {
        root.classList.remove('dark');
        root.style.colorScheme = 'light';
      }
    }
  }

  get isDark() {
    return this.current === 'dark';
  }
}

export const themeState = new ThemeManager();
