const STORAGE_KEY = 'theme';

export const Theme = {
  system() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  },

  get() {
    return localStorage.getItem(STORAGE_KEY) || 'system';
  },

  resolve() {
    const stored = this.get();
    return stored === 'system' ? this.system() : stored;
  },

  apply(theme) {
    const resolved = theme === 'system' ? this.system() : theme;
    document.documentElement.classList.toggle('dark', resolved === 'dark');
  },

  set(theme) {
    localStorage.setItem(STORAGE_KEY, theme);
    this.apply(theme);
  },

  cycle(current) {
    const modes = ['light', 'dark', 'system'];
    const idx = modes.indexOf(current);
    return modes[(idx + 1) % modes.length];
  },

  init() {
    this.apply(this.get());
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
      if (this.get() === 'system') this.apply('system');
    });
  },
};
